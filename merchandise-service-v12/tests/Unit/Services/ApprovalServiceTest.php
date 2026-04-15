<?php

declare(strict_types=1);

use App\DTOs\ApprovalDTO;
use App\DTOs\ApprovalItemDTO;
use App\Enums\OrderStatus;
use App\Events\MerchandiseOrderApproved;
use App\Events\MerchandiseOrderPartiallyApproved;
use App\Events\MerchandiseOrderRejected;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\MerchandiseOrder;
use App\Models\MerchandiseOrderItem;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Services\Approval\ApprovalService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->orderRepo = Mockery::mock(OrderRepositoryInterface::class);
    $this->service = new ApprovalService($this->orderRepo);
    Event::fake();
});

afterEach(fn () => Mockery::close());

test('approve updates the order to approved when all quantities are accepted', function () {
    $item = new MerchandiseOrderItem(['requested_quantity' => 5, 'unit_price_cents' => 10000]);
    $item->forceFill(['id' => 1]);
    $order = new MerchandiseOrder(['status' => OrderStatus::PendingApproval]);
    $order->forceFill(['id' => 1]);
    $order->setRelation('items', collect([$item]));

    $dto = new ApprovalDTO(
        staffId: 5,
        items: [new ApprovalItemDTO(itemId: 1, approvedQuantity: 5)],
    );

    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);
    $this->orderRepo->shouldReceive('applyApproval')
        ->once()
        ->with(
            $order,
            Mockery::on(function (array $resolvedItems): bool {
                return $resolvedItems === [
                    [
                        'item_id' => 1,
                        'approved_quantity' => 5,
                        'line_total_cents' => 50000,
                    ],
                ];
            }),
            OrderStatus::Approved,
            5
        )->andReturnUsing(function (MerchandiseOrder $order, array $resolvedItems, OrderStatus $status, int $staffId): MerchandiseOrder {
            $order->forceFill([
                'status' => $status,
                'subtotal_cents' => 50000,
                'total_cents' => 50000,
                'approved_by' => $staffId,
            ]);

            return $order;
        });

    $result = $this->service->approve(1, $dto);

    expect($result->status)->toBe(OrderStatus::Approved);
    Event::assertDispatched(MerchandiseOrderApproved::class);
    Event::assertNotDispatched(MerchandiseOrderPartiallyApproved::class);
});

test('approve updates the order to partially approved when any item is reduced', function () {
    $item = new MerchandiseOrderItem(['requested_quantity' => 10, 'unit_price_cents' => 5000]);
    $item->forceFill(['id' => 1]);
    $order = new MerchandiseOrder(['status' => OrderStatus::PendingApproval]);
    $order->forceFill(['id' => 1]);
    $order->setRelation('items', collect([$item]));

    $dto = new ApprovalDTO(
        staffId: 5,
        items: [new ApprovalItemDTO(itemId: 1, approvedQuantity: 6)],
    );

    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);
    $this->orderRepo->shouldReceive('applyApproval')
        ->once()
        ->with(
            $order,
            Mockery::on(function (array $resolvedItems): bool {
                return $resolvedItems === [
                    [
                        'item_id' => 1,
                        'approved_quantity' => 6,
                        'line_total_cents' => 30000,
                    ],
                ];
            }),
            OrderStatus::PartiallyApproved,
            5
        )->andReturnUsing(function (MerchandiseOrder $order, array $resolvedItems, OrderStatus $status, int $staffId): MerchandiseOrder {
            $order->forceFill([
                'status' => $status,
                'subtotal_cents' => 30000,
                'total_cents' => 30000,
                'approved_by' => $staffId,
            ]);

            return $order;
        });

    $result = $this->service->approve(1, $dto);

    expect($result->status)->toBe(OrderStatus::PartiallyApproved);
    Event::assertDispatched(MerchandiseOrderPartiallyApproved::class);
    Event::assertNotDispatched(MerchandiseOrderApproved::class);
});

test('approve throws InvalidOrderTransitionException when the order is not pending approval', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::Approved]);
    $order->forceFill(['id' => 1]);
    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);

    expect(fn () => $this->service->approve(1, new ApprovalDTO(staffId: 5, items: [])))
        ->toThrow(InvalidOrderTransitionException::class);
});

test('reject updates the order to rejected with a reason', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::PendingApproval]);
    $order->forceFill(['id' => 1]);
    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);
    $this->orderRepo->shouldReceive('reject')
        ->once()
        ->with($order, 5, 'Budget not approved')->andReturnUsing(function (MerchandiseOrder $order, int $staffId, string $reason): MerchandiseOrder {
            $order->forceFill([
                'status' => OrderStatus::Rejected,
                'rejected_reason' => $reason,
            ]);

            return $order;
        });

    $result = $this->service->reject(1, 5, 'Budget not approved');

    expect($result->status)->toBe(OrderStatus::Rejected);
    Event::assertDispatched(MerchandiseOrderRejected::class);
});

test('reject throws InvalidOrderTransitionException when the order is not pending approval', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::Dispatched]);
    $order->forceFill(['id' => 1]);
    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);

    expect(fn () => $this->service->reject(1, 5, 'reason'))
        ->toThrow(InvalidOrderTransitionException::class);
});

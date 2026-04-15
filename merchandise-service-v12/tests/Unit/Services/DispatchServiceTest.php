<?php

declare(strict_types=1);

use App\DTOs\DispatchDTO;
use App\Enums\OrderStatus;
use App\Events\MerchandiseOrderDispatched;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\MerchandiseDispatch;
use App\Models\MerchandiseOrder;
use App\Repositories\Interfaces\DispatchRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Services\Dispatch\DispatchService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->dispatchRepo = Mockery::mock(DispatchRepositoryInterface::class);
    $this->orderRepo = Mockery::mock(OrderRepositoryInterface::class);
    $this->service = new DispatchService($this->dispatchRepo, $this->orderRepo);
    Event::fake();
});

afterEach(fn () => Mockery::close());

test('dispatch creates a dispatch record for approved orders', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::Approved, 'company_id' => 1]);
    $order->forceFill(['id' => 1]);
    $dto = new DispatchDTO(staffId: 5, courier: 'BlueDart', trackingNumber: 'BD123456', estimatedDeliveryAt: null);
    $dispatch = new MerchandiseDispatch(['id' => 1]);

    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);
    $this->dispatchRepo->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $data) use ($order, $dto): bool {
            return $data['company_id'] === $order->company_id
                && $data['order_id'] === $order->id
                && $data['dispatched_by'] === $dto->staffId
                && $data['courier'] === $dto->courier
                && $data['tracking_number'] === $dto->trackingNumber;
        }))->andReturn($dispatch);
    $this->orderRepo->shouldReceive('updateStatus')->once()->with($order, OrderStatus::Dispatched)->andReturnUsing(function (MerchandiseOrder $order, OrderStatus $status): void {
        $order->status = $status;
    });

    $result = $this->service->dispatch(1, $dto);

    expect($result)->toBeInstanceOf(MerchandiseDispatch::class);
    Event::assertDispatched(MerchandiseOrderDispatched::class);
});

test('dispatch rejects non-dispatchable orders', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::PendingApproval]);
    $order->forceFill(['id' => 1]);
    $dto = new DispatchDTO(staffId: 5, courier: null, trackingNumber: null, estimatedDeliveryAt: null);

    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);

    expect(fn () => $this->service->dispatch(1, $dto))
        ->toThrow(InvalidOrderTransitionException::class);
});

test('listDispatches returns a paginator from the repository', function () {
    $paginator = new LengthAwarePaginator([], 0, 15);
    $this->dispatchRepo->shouldReceive('list')->once()->with([])->andReturn($paginator);

    expect($this->service->listDispatches([]))->toBeInstanceOf(LengthAwarePaginator::class);
});

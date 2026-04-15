<?php

declare(strict_types=1);

use App\Enums\AcknowledgementStatus;
use App\Enums\OrderStatus;
use App\Events\MerchandiseAcknowledgementApproved;
use App\Events\MerchandiseAcknowledgementRejected;
use App\Events\MerchandiseDeliveryAcknowledged;
use App\Exceptions\InvalidAcknowledgementStateException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\MerchandiseAcknowledgement;
use App\Models\MerchandiseOrder;
use App\Repositories\Interfaces\AcknowledgementRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Services\Acknowledgement\AcknowledgementService;
use App\Services\Invoice\InvoiceServiceInterface;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->ackRepo = Mockery::mock(AcknowledgementRepositoryInterface::class);
    $this->orderRepo = Mockery::mock(OrderRepositoryInterface::class);
    $this->invoiceService = Mockery::mock(InvoiceServiceInterface::class);
    $this->service = new AcknowledgementService($this->ackRepo, $this->orderRepo, $this->invoiceService);
    Event::fake();
});

afterEach(fn () => Mockery::close());

test('acknowledge creates a pending acknowledgement for dispatched orders', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::Dispatched, 'customer_id' => 10, 'company_id' => 1]);
    $order->forceFill(['id' => 1]);
    $ack = new MerchandiseAcknowledgement(['status' => AcknowledgementStatus::Pending]);
    $ack->forceFill(['id' => 1]);

    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);
    $this->ackRepo->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function (array $data) use ($order): bool {
            return $data['company_id'] === $order->company_id
                && $data['order_id'] === $order->id
                && $data['acknowledged_by'] === 10
                && $data['status'] === AcknowledgementStatus::Pending;
        }))->andReturn($ack);
    $this->orderRepo->shouldReceive('updateStatus')->once()->with($order, OrderStatus::Acknowledged)->andReturnUsing(function (MerchandiseOrder $order, OrderStatus $status): void {
        $order->status = $status;
    });

    $result = $this->service->acknowledge(1, 10, 'All items received in good condition');

    expect($result)->toBeInstanceOf(MerchandiseAcknowledgement::class);
    Event::assertDispatched(MerchandiseDeliveryAcknowledged::class);
});

test('acknowledge throws InvalidOrderTransitionException when the order is not dispatched', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::Approved, 'customer_id' => 10]);
    $order->forceFill(['id' => 1]);
    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);

    expect(fn () => $this->service->acknowledge(1, 10, null))
        ->toThrow(InvalidOrderTransitionException::class);
});

test('approveAcknowledgement marks the acknowledgement approved and creates an invoice', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::Acknowledged]);
    $order->forceFill(['id' => 1]);
    $ack = new MerchandiseAcknowledgement(['order_id' => 1, 'status' => AcknowledgementStatus::Pending]);
    $ack->forceFill(['id' => 1]);
    $ack->setRelation('order', $order);

    $this->ackRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($ack);
    $this->ackRepo->shouldReceive('approve')
        ->once()
        ->with($ack, 5)->andReturnUsing(function (MerchandiseAcknowledgement $ack, int $staffId): MerchandiseAcknowledgement {
            $ack->forceFill([
                'status' => AcknowledgementStatus::Approved,
                'reviewed_by' => $staffId,
            ]);

            return $ack;
        });
    $this->invoiceService->shouldReceive('createInvoice')->once()->with(1);
    $this->orderRepo->shouldReceive('updateStatus')->twice()->with($order, OrderStatus::InvoiceGenerated)->andReturnUsing(function (MerchandiseOrder $order, OrderStatus $status): void {
        $order->status = $status;
    });

    $result = $this->service->approveAcknowledgement(1, 5);

    expect($result->status)->toBe(AcknowledgementStatus::Approved);
    Event::assertDispatched(MerchandiseAcknowledgementApproved::class);
});

test('approveAcknowledgement throws InvalidAcknowledgementStateException when already reviewed', function () {
    $ack = new MerchandiseAcknowledgement(['status' => AcknowledgementStatus::Approved]);
    $ack->forceFill(['id' => 1]);
    $this->ackRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($ack);

    expect(fn () => $this->service->approveAcknowledgement(1, 5))
        ->toThrow(InvalidAcknowledgementStateException::class);
});

test('rejectAcknowledgement marks the acknowledgement rejected and reopens the order', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::Acknowledged]);
    $order->forceFill(['id' => 1]);
    $ack = new MerchandiseAcknowledgement(['order_id' => 1, 'status' => AcknowledgementStatus::Pending]);
    $ack->forceFill(['id' => 1]);
    $ack->setRelation('order', $order);

    $this->ackRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($ack);
    $this->ackRepo->shouldReceive('reject')
        ->once()
        ->with($ack, 5, 'Wrong items delivered')->andReturnUsing(function (MerchandiseAcknowledgement $ack, int $staffId, string $reason): MerchandiseAcknowledgement {
            $ack->forceFill([
                'status' => AcknowledgementStatus::Rejected,
                'reviewed_by' => $staffId,
                'rejection_reason' => $reason,
            ]);

            return $ack;
        });
    $this->orderRepo->shouldReceive('updateStatus')->once()->with($order, OrderStatus::Dispatched)->andReturnUsing(function (MerchandiseOrder $order, OrderStatus $status): void {
        $order->status = $status;
    });

    $result = $this->service->rejectAcknowledgement(1, 5, 'Wrong items delivered');

    expect($result->status)->toBe(AcknowledgementStatus::Rejected);
    Event::assertDispatched(MerchandiseAcknowledgementRejected::class);
});

test('rejectAcknowledgement throws InvalidAcknowledgementStateException when already approved', function () {
    $ack = new MerchandiseAcknowledgement(['status' => AcknowledgementStatus::Approved]);
    $ack->forceFill(['id' => 1]);
    $this->ackRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($ack);

    expect(fn () => $this->service->rejectAcknowledgement(1, 5, 'reason'))
        ->toThrow(InvalidAcknowledgementStateException::class);
});

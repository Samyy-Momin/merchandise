<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Events\MerchandiseInvoiceGenerated;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\InvoiceNotFoundException;
use App\Models\MerchandiseInvoice;
use App\Models\MerchandiseOrder;
use App\Models\MerchandisePayment;
use App\Repositories\Interfaces\InvoiceRepositoryInterface;
use App\Repositories\Interfaces\OrderRepositoryInterface;
use App\Services\Invoice\InvoiceService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->invoiceRepo = Mockery::mock(InvoiceRepositoryInterface::class);
    $this->orderRepo = Mockery::mock(OrderRepositoryInterface::class);
    $this->service = new InvoiceService($this->invoiceRepo, $this->orderRepo);
    Event::fake();
});

afterEach(fn () => Mockery::close());

test('createInvoice creates an invoice with a due date 15 days from now', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::InvoiceGenerated, 'company_id' => 1, 'customer_id' => 10, 'subtotal_cents' => 50000, 'total_cents' => 50000]);
    $order->forceFill(['id' => 1]);
    $invoice = new MerchandiseInvoice(['invoice_number' => 'MINV-202604-0001', 'status' => InvoiceStatus::PaymentPending, 'due_date' => Carbon::now()->addDays(15)->toDateString()]);
    $invoice->forceFill(['id' => 1]);

    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);
    $this->invoiceRepo->shouldReceive('create')
        ->once()
        ->with($order, Mockery::on(function (Carbon $dueDate): bool {
            return $dueDate->toDateString() === Carbon::now()->addDays(15)->toDateString();
        }))->andReturn($invoice);
    $this->orderRepo->shouldReceive('updateStatus')->once()->with($order, OrderStatus::PaymentPending)->andReturnUsing(function (MerchandiseOrder $order, OrderStatus $status): void {
        $order->status = $status;
    });

    $result = $this->service->createInvoice(1);

    expect($result)->toBeInstanceOf(MerchandiseInvoice::class);
    expect($result->due_date->toDateString())->toBe(Carbon::now()->addDays(15)->toDateString());
    Event::assertDispatched(MerchandiseInvoiceGenerated::class);
});

test('createInvoice throws InvalidOrderTransitionException when the order cannot move to invoice generated', function () {
    $order = new MerchandiseOrder(['status' => OrderStatus::Dispatched]);
    $order->forceFill(['id' => 1]);
    $this->orderRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($order);

    expect(fn () => $this->service->createInvoice(1))
        ->toThrow(InvalidOrderTransitionException::class);
});

test('recordPayment marks the invoice as paid when the balance is settled', function () {
    $invoice = new MerchandiseInvoice([
        'order_id' => 99,
        'company_id' => 1,
        'total_cents' => 50000,
        'amount_paid_cents' => 30000,
        'status' => InvoiceStatus::PaymentPending,
        'due_date' => Carbon::now()->addDays(10)->toDateString(),
    ]);
    $invoice->forceFill(['id' => 1]);
    $payment = new MerchandisePayment(['id' => 1, 'amount_cents' => 20000]);
    $order = new MerchandiseOrder(['status' => OrderStatus::PaymentPending]);
    $order->forceFill(['id' => 99]);
    $invoice->setRelation('order', $order);

    $this->invoiceRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($invoice);
    $this->invoiceRepo->shouldReceive('recordPayment')->once()->with($invoice, 20000, 'cash', null)->andReturn($payment);
    $this->invoiceRepo->shouldReceive('updateStatus')->once()->with($invoice, InvoiceStatus::Paid)->andReturnUsing(function (MerchandiseInvoice $invoice, InvoiceStatus $status): void {
        $invoice->status = $status;
    });
    $this->orderRepo->shouldReceive('updateStatus')->once()->with($order, OrderStatus::Completed)->andReturnUsing(function (MerchandiseOrder $order, OrderStatus $status): void {
        $order->status = $status;
    });

    $result = $this->service->recordPayment(1, 20000, 'cash', null);

    expect($result)->toBe($payment);
});

test('recordPayment keeps the invoice pending after a partial payment', function () {
    $invoice = new MerchandiseInvoice([
        'order_id' => 99,
        'company_id' => 1,
        'total_cents' => 50000,
        'amount_paid_cents' => 10000,
        'status' => InvoiceStatus::PaymentPending,
        'due_date' => Carbon::now()->addDays(10)->toDateString(),
    ]);
    $invoice->forceFill(['id' => 1]);
    $payment = new MerchandisePayment(['id' => 1, 'amount_cents' => 15000]);

    $this->invoiceRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($invoice);
    $this->invoiceRepo->shouldReceive('recordPayment')->once()->with($invoice, 15000, 'cheque', 'CHQ-001')->andReturn($payment);
    $this->invoiceRepo->shouldNotReceive('updateStatus');
    $this->orderRepo->shouldNotReceive('updateStatus');

    $result = $this->service->recordPayment(1, 15000, 'cheque', 'CHQ-001');

    expect($result)->toBe($payment);
});

test('recordPayment marks the invoice overdue when a late partial payment arrives', function () {
    $invoice = new MerchandiseInvoice([
        'order_id' => 77,
        'company_id' => 1,
        'total_cents' => 50000,
        'amount_paid_cents' => 0,
        'status' => InvoiceStatus::PaymentPending,
        'due_date' => Carbon::now()->subDay()->toDateString(),
    ]);
    $invoice->forceFill(['id' => 1]);
    $payment = new MerchandisePayment(['id' => 1, 'amount_cents' => 20000]);
    $order = new MerchandiseOrder(['status' => OrderStatus::Overdue]);
    $order->forceFill(['id' => 77]);
    $invoice->setRelation('order', $order);

    $this->invoiceRepo->shouldReceive('findOrFail')->once()->with(1)->andReturn($invoice);
    $this->invoiceRepo->shouldReceive('recordPayment')->once()->with($invoice, 20000, 'bank_transfer', null)->andReturn($payment);
    $this->invoiceRepo->shouldReceive('updateStatus')->once()->with($invoice, InvoiceStatus::Overdue)->andReturnUsing(function (MerchandiseInvoice $invoice, InvoiceStatus $status): void {
        $invoice->status = $status;
    });
    $this->orderRepo->shouldReceive('updateStatus')->once()->with($order, OrderStatus::Overdue)->andReturnUsing(function (MerchandiseOrder $order, OrderStatus $status): void {
        $order->status = $status;
    });

    $this->service->recordPayment(1, 20000, 'bank_transfer', null);
});

test('getInvoice throws InvoiceNotFoundException when not found', function () {
    $this->invoiceRepo->shouldReceive('findOrFail')->once()->with(999)->andThrow(new InvoiceNotFoundException(999));

    expect(fn () => $this->service->getInvoice(999))->toThrow(InvoiceNotFoundException::class);
});

test('listInvoices returns a paginator from the repository', function () {
    $paginator = new LengthAwarePaginator([], 0, 15);
    $this->invoiceRepo->shouldReceive('list')->once()->with([])->andReturn($paginator);

    expect($this->service->listInvoices([]))->toBeInstanceOf(LengthAwarePaginator::class);
});

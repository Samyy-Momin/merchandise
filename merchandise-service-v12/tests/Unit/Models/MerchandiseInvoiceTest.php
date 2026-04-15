<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\MerchandiseInvoice;
use App\Models\MerchandiseOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;

uses(DatabaseMigrations::class);

test('can be created with required fields', function () {
    $order = MerchandiseOrder::factory()->create();

    $invoice = MerchandiseInvoice::create([
        'company_id' => 1,
        'invoice_number' => 'MINV-202604-0001',
        'order_id' => $order->id,
        'customer_id' => 42,
        'status' => InvoiceStatus::Draft,
        'subtotal_cents' => 10000,
        'total_cents' => 10000,
        'due_date' => now()->addDays(15)->toDateString(),
    ]);

    expect($invoice->id)->toBeGreaterThan(0);
    expect($invoice->invoice_number)->toBe('MINV-202604-0001');
    expect($invoice->status)->toBe(InvoiceStatus::Draft);
});

test('status is cast to InvoiceStatus enum', function () {
    $order = MerchandiseOrder::factory()->create();
    $invoice = MerchandiseInvoice::factory()->create([
        'order_id' => $order->id,
        'status' => InvoiceStatus::PaymentPending,
    ]);

    expect($invoice->fresh()->status)->toBeInstanceOf(InvoiceStatus::class);
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::PaymentPending);
});

test('invoice_number matches pattern MINV-YYYYMM-NNNN', function () {
    $order = MerchandiseOrder::factory()->create();
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id]);

    expect($invoice->invoice_number)->toMatch('/^MINV-\d{6}-\d{4}$/');
});

test('amount_paid_cents defaults to zero', function () {
    $order = MerchandiseOrder::factory()->create();
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id]);

    expect($invoice->amount_paid_cents)->toBe(0);
});

test('amount_due_cents accessor returns total minus paid', function () {
    $order = MerchandiseOrder::factory()->create();
    $invoice = MerchandiseInvoice::factory()->create([
        'order_id' => $order->id,
        'total_cents' => 50000,
        'amount_paid_cents' => 20000,
    ]);

    expect($invoice->amount_due_cents)->toBe(30000);
});

test('due_date is set to 15 days from created_date per BRD rule', function () {
    $order = MerchandiseOrder::factory()->create();
    $invoice = MerchandiseInvoice::factory()->create([
        'order_id' => $order->id,
    ]);

    $expected = Carbon::parse($invoice->created_date)->addDays(15)->toDateString();
    expect($invoice->due_date->toDateString())->toBe($expected);
});

test('is_overdue returns true when due_date has passed and not fully paid', function () {
    $order = MerchandiseOrder::factory()->create();
    $invoice = MerchandiseInvoice::factory()->create([
        'order_id' => $order->id,
        'status' => InvoiceStatus::PaymentPending,
        'total_cents' => 50000,
        'amount_paid_cents' => 0,
        'due_date' => now()->subDay()->toDateString(),
    ]);

    expect($invoice->isOverdue())->toBeTrue();
});

test('is_overdue returns false when fully paid even past due date', function () {
    $order = MerchandiseOrder::factory()->create();
    $invoice = MerchandiseInvoice::factory()->create([
        'order_id' => $order->id,
        'status' => InvoiceStatus::Paid,
        'total_cents' => 50000,
        'amount_paid_cents' => 50000,
        'due_date' => now()->subDay()->toDateString(),
    ]);

    expect($invoice->isOverdue())->toBeFalse();
});

test('has order relationship', function () {
    $order = MerchandiseOrder::factory()->create();
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id]);

    expect($invoice->order())->toBeInstanceOf(BelongsTo::class);
    expect($invoice->order->id)->toBe($order->id);
});

test('has payments relationship', function () {
    $order = MerchandiseOrder::factory()->create();
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id]);

    expect($invoice->payments())->toBeInstanceOf(HasMany::class);
});

test('one order can have only one invoice', function () {
    $order = MerchandiseOrder::factory()->create();
    MerchandiseInvoice::factory()->create(['order_id' => $order->id]);

    expect(fn () => MerchandiseInvoice::factory()->create(['order_id' => $order->id]))
        ->toThrow(QueryException::class);
});

test('tax_cents and discount_cents default to zero', function () {
    $order = MerchandiseOrder::factory()->create();
    $invoice = MerchandiseInvoice::factory()->create(['order_id' => $order->id]);

    expect($invoice->tax_cents)->toBe(0);
    expect($invoice->discount_cents)->toBe(0);
});

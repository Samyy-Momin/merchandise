<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\MerchandiseOrder;
use App\Models\MerchandiseOrderItem;
use App\Models\MerchandiseSku;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

test('can be created with required fields', function () {
    $order = MerchandiseOrder::factory()->create([
        'company_id' => 1,
        'customer_id' => 42,
        'status' => OrderStatus::Submitted,
        'subtotal_cents' => 10000,
        'total_cents' => 10000,
    ]);

    expect($order->id)->toBeGreaterThan(0);
    expect($order->customer_id)->toBe(42);
    expect($order->status)->toBe(OrderStatus::Submitted);
});

test('status is cast to OrderStatus enum', function () {
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval]);
    expect($order->fresh()->status)->toBe(OrderStatus::PendingApproval);
    expect($order->fresh()->status)->toBeInstanceOf(OrderStatus::class);
});

test('order_ref is generated on creation', function () {
    $order = MerchandiseOrder::factory()->create();
    expect($order->order_ref)->toMatch('/^MORD-\d{8}-\d{4}$/');
});

test('has custom timestamps created_date and updated_date', function () {
    $order = MerchandiseOrder::factory()->create();
    expect($order->created_date)->not->toBeNull();
    expect($order->updated_date)->not->toBeNull();
});

test('has items relationship', function () {
    $order = MerchandiseOrder::factory()->create();
    expect($order->items())->toBeInstanceOf(HasMany::class);
});

test('has dispatch relationship', function () {
    $order = MerchandiseOrder::factory()->create();
    expect($order->dispatch())->toBeInstanceOf(HasOne::class);
});

test('has acknowledgement relationship', function () {
    $order = MerchandiseOrder::factory()->create();
    expect($order->acknowledgement())->toBeInstanceOf(HasOne::class);
});

test('has invoice relationship', function () {
    $order = MerchandiseOrder::factory()->create();
    expect($order->invoice())->toBeInstanceOf(HasOne::class);
});

test('approved_by and approved_at are nullable', function () {
    $order = MerchandiseOrder::factory()->create();
    expect($order->approved_by)->toBeNull();
    expect($order->approved_at)->toBeNull();
});

test('rejected_reason is nullable', function () {
    $order = MerchandiseOrder::factory()->create();
    expect($order->rejected_reason)->toBeNull();
});

test('order items can be attached', function () {
    $order = MerchandiseOrder::factory()->create();
    $sku = MerchandiseSku::factory()->create();

    MerchandiseOrderItem::factory()->create([
        'order_id' => $order->id,
        'sku_id' => $sku->id,
        'requested_quantity' => 5,
        'unit_price_cents' => $sku->unit_price_cents,
        'line_total_cents' => 5 * $sku->unit_price_cents,
        'company_id' => $order->company_id,
    ]);

    expect($order->items()->count())->toBe(1);
});

test('order item requires an existing sku', function () {
    $order = MerchandiseOrder::factory()->create();

    expect(fn () => MerchandiseOrderItem::create([
        'company_id' => 1,
        'order_id' => $order->id,
        'sku_id' => 999999,
        'sku_code' => 'SKU-MISSING',
        'sku_name' => 'Missing SKU',
        'requested_quantity' => 1,
        'unit_price_cents' => 500,
        'line_total_cents' => 0,
    ]))->toThrow(QueryException::class);
});

test('is_approvable returns true only for pending_approval status', function () {
    $pending = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval]);
    $submitted = MerchandiseOrder::factory()->create(['status' => OrderStatus::Submitted]);

    expect($pending->isApprovable())->toBeTrue();
    expect($submitted->isApprovable())->toBeFalse();
});

test('is_cancellable returns true for submitted and pending_approval', function () {
    $submitted = MerchandiseOrder::factory()->create(['status' => OrderStatus::Submitted]);
    $pending = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval]);
    $approved = MerchandiseOrder::factory()->create(['status' => OrderStatus::Approved]);

    expect($submitted->isCancellable())->toBeTrue();
    expect($pending->isCancellable())->toBeTrue();
    expect($approved->isCancellable())->toBeFalse();
});

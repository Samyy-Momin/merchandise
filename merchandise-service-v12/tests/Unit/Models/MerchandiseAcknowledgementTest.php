<?php

declare(strict_types=1);

use App\Enums\AcknowledgementStatus;
use App\Models\MerchandiseAcknowledgement;
use App\Models\MerchandiseOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

test('can be created with required fields', function () {
    $order = MerchandiseOrder::factory()->create();

    $ack = MerchandiseAcknowledgement::create([
        'company_id' => 1,
        'order_id' => $order->id,
        'acknowledged_by' => 99,
        'acknowledged_at' => now(),
        'status' => AcknowledgementStatus::Pending,
    ]);

    expect($ack->id)->toBeGreaterThan(0);
    expect($ack->order_id)->toBe($order->id);
    expect($ack->status)->toBe(AcknowledgementStatus::Pending);
});

test('status is cast to AcknowledgementStatus enum', function () {
    $order = MerchandiseOrder::factory()->create();
    $ack = MerchandiseAcknowledgement::factory()->create([
        'order_id' => $order->id,
        'status' => AcknowledgementStatus::Pending,
    ]);

    expect($ack->fresh()->status)->toBeInstanceOf(AcknowledgementStatus::class);
    expect($ack->fresh()->status)->toBe(AcknowledgementStatus::Pending);
});

test('reviewed_by and reviewed_at are nullable', function () {
    $order = MerchandiseOrder::factory()->create();
    $ack = MerchandiseAcknowledgement::factory()->create(['order_id' => $order->id]);

    expect($ack->reviewed_by)->toBeNull();
    expect($ack->reviewed_at)->toBeNull();
});

test('rejection_reason is nullable', function () {
    $order = MerchandiseOrder::factory()->create();
    $ack = MerchandiseAcknowledgement::factory()->create(['order_id' => $order->id]);

    expect($ack->rejection_reason)->toBeNull();
});

test('has order relationship', function () {
    $order = MerchandiseOrder::factory()->create();
    $ack = MerchandiseAcknowledgement::factory()->create(['order_id' => $order->id]);

    expect($ack->order())->toBeInstanceOf(BelongsTo::class);
    expect($ack->order->id)->toBe($order->id);
});

test('one order can have only one acknowledgement', function () {
    $order = MerchandiseOrder::factory()->create();
    MerchandiseAcknowledgement::factory()->create(['order_id' => $order->id]);

    expect(fn () => MerchandiseAcknowledgement::factory()->create(['order_id' => $order->id]))
        ->toThrow(QueryException::class);
});

test('has custom timestamps created_date and updated_date', function () {
    $order = MerchandiseOrder::factory()->create();
    $ack = MerchandiseAcknowledgement::factory()->create(['order_id' => $order->id]);

    expect($ack->created_date)->not->toBeNull();
});

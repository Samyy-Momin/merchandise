<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\MerchandiseOrder;
use App\Models\MerchandiseOrderItem;
use App\Models\MerchandiseSku;

// ---------- POST /api/v2/merchandise/orders/{id}/approve ----------

test('admin can fully approve an order', function () {
    $this->actingAsRole(5, 'admin');
    $sku = MerchandiseSku::factory()->create(['unit_price_cents' => 10000]);
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);
    $item = MerchandiseOrderItem::factory()->create([
        'order_id' => $order->id,
        'sku_id' => $sku->id,
        'requested_quantity' => 5,
        'unit_price_cents' => 10000,
        'line_total_cents' => 50000,
        'company_id' => 1,
    ]);

    $response = $this->postJson("/api/v2/merchandise/orders/{$order->id}/approve", [
        'items' => [
            ['item_id' => $item->id, 'approved_quantity' => 5],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['status' => 'approved']);
    $this->assertDatabaseHas('merchandise_orders', ['id' => $order->id, 'status' => 'approved']);
    $this->assertDatabaseHas('merchandise_order_items', ['id' => $item->id, 'approved_quantity' => 5]);
});

test('admin can partially approve an order with reduced quantities', function () {
    $this->actingAsRole(5, 'admin');
    $sku = MerchandiseSku::factory()->create(['unit_price_cents' => 10000]);
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);
    $item = MerchandiseOrderItem::factory()->create([
        'order_id' => $order->id,
        'sku_id' => $sku->id,
        'requested_quantity' => 10,
        'unit_price_cents' => 10000,
        'line_total_cents' => 100000,
        'company_id' => 1,
    ]);

    $response = $this->postJson("/api/v2/merchandise/orders/{$order->id}/approve", [
        'items' => [
            ['item_id' => $item->id, 'approved_quantity' => 6],  // reduced
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['status' => 'partially_approved']);
    $this->assertDatabaseHas('merchandise_order_items', [
        'id' => $item->id,
        'approved_quantity' => 6,
        'line_total_cents' => 60000,  // recalculated: 6 × 10000
    ]);
});

test('senior_manager can approve an order', function () {
    $this->actingAsRole(5, 'senior_manager');
    $sku = MerchandiseSku::factory()->create(['unit_price_cents' => 5000]);
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);
    $item = MerchandiseOrderItem::factory()->create([
        'order_id' => $order->id, 'sku_id' => $sku->id,
        'requested_quantity' => 2, 'unit_price_cents' => 5000, 'line_total_cents' => 10000, 'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/approve", [
        'items' => [['item_id' => $item->id, 'approved_quantity' => 2]],
    ])->assertStatus(200);
});

test('customer cannot approve an order', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/approve", [
        'items' => [],
    ])->assertStatus(403);
});

test('vendor cannot approve an order', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/approve", [
        'items' => [],
    ])->assertStatus(403);
});

test('returns 422 when approving already approved order', function () {
    $this->actingAsRole(5, 'admin');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Approved, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/approve", [
        'items' => [],
    ])->assertStatus(422);
});

test('approval stores approved_by and approved_at', function () {
    $this->actingAsRole(5, 'admin');
    $sku = MerchandiseSku::factory()->create(['unit_price_cents' => 1000]);
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);
    $item = MerchandiseOrderItem::factory()->create([
        'order_id' => $order->id, 'sku_id' => $sku->id,
        'requested_quantity' => 1, 'unit_price_cents' => 1000, 'line_total_cents' => 1000, 'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/approve", [
        'items' => [['item_id' => $item->id, 'approved_quantity' => 1]],
    ]);

    $this->assertDatabaseHas('merchandise_orders', [
        'id' => $order->id,
        'approved_by' => 5,
    ]);
    expect(MerchandiseOrder::find($order->id)->approved_at)->not->toBeNull();
});

// ---------- POST /api/v2/merchandise/orders/{id}/reject ----------

test('admin can reject an order with reason', function () {
    $this->actingAsRole(5, 'admin');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);

    $response = $this->postJson("/api/v2/merchandise/orders/{$order->id}/reject", [
        'reason' => 'Budget exceeded for this month',
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['status' => 'rejected']);
    $this->assertDatabaseHas('merchandise_orders', [
        'id' => $order->id,
        'status' => 'rejected',
        'rejected_reason' => 'Budget exceeded for this month',
    ]);
});

test('reject validates reason is required', function () {
    $this->actingAsRole(5, 'admin');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/reject", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['reason']);
});

test('customer cannot reject an order', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/reject", [
        'reason' => 'test',
    ])->assertStatus(403);
});

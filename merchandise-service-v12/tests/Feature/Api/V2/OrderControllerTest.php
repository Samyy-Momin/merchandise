<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\MerchandiseOrder;
use App\Models\MerchandiseSku;

// ---------- POST /api/v2/merchandise/orders ----------

test('customer can place an order', function () {
    $this->actingAsRole(10, 'customer');
    $sku = MerchandiseSku::factory()->create(['unit_price_cents' => 50000, 'stock_quantity' => 20]);

    $response = $this->postJson('/api/v2/merchandise/orders', [
        'items' => [
            ['sku_id' => $sku->id, 'quantity' => 3],
        ],
        'notes' => 'Please pack carefully',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['data' => ['id', 'order_ref', 'status', 'total_cents', 'items']]);
    $response->assertJsonFragment(['status' => 'pending_approval']);
    $this->assertDatabaseHas('merchandise_orders', ['customer_id' => 10, 'status' => 'pending_approval']);
});

test('order_ref is generated in format MORD-YYYYMMDD-NNNN', function () {
    $this->actingAsRole(10, 'customer');
    $sku = MerchandiseSku::factory()->create(['stock_quantity' => 10]);

    $response = $this->postJson('/api/v2/merchandise/orders', [
        'items' => [['sku_id' => $sku->id, 'quantity' => 1]],
    ]);

    $response->assertStatus(201);
    expect($response->json('data.order_ref'))->toMatch('/^MORD-\d{8}-\d{4}$/');
});

test('total_cents is calculated as sum of line totals', function () {
    $this->actingAsRole(10, 'customer');
    $sku1 = MerchandiseSku::factory()->create(['unit_price_cents' => 10000, 'stock_quantity' => 20]);
    $sku2 = MerchandiseSku::factory()->create(['unit_price_cents' => 5000, 'stock_quantity' => 20]);

    $response = $this->postJson('/api/v2/merchandise/orders', [
        'items' => [
            ['sku_id' => $sku1->id, 'quantity' => 2],   // 20000
            ['sku_id' => $sku2->id, 'quantity' => 4],   // 20000
        ],
    ]);

    $response->assertStatus(201);
    expect($response->json('data.total_cents'))->toBe(40000);
});

test('unit_price_cents is snapshotted from SKU at order time', function () {
    $this->actingAsRole(10, 'customer');
    $sku = MerchandiseSku::factory()->create(['unit_price_cents' => 75000, 'stock_quantity' => 10]);

    $response = $this->postJson('/api/v2/merchandise/orders', [
        'items' => [['sku_id' => $sku->id, 'quantity' => 1]],
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('merchandise_order_items', [
        'sku_id' => $sku->id,
        'unit_price_cents' => 75000,
    ]);

    // Change SKU price AFTER order — should not affect existing order items
    $sku->update(['unit_price_cents' => 99999]);
    $this->assertDatabaseHas('merchandise_order_items', ['unit_price_cents' => 75000]);
});

test('returns 422 when stock is insufficient', function () {
    $this->actingAsRole(10, 'customer');
    $sku = MerchandiseSku::factory()->create(['stock_quantity' => 2]);

    $response = $this->postJson('/api/v2/merchandise/orders', [
        'items' => [['sku_id' => $sku->id, 'quantity' => 10]],
    ]);

    $response->assertStatus(422);
    $response->assertJsonFragment(['message' => 'Insufficient stock']);
});

test('returns 422 for inactive sku', function () {
    $this->actingAsRole(10, 'customer');
    $sku = MerchandiseSku::factory()->create(['is_active' => false, 'stock_quantity' => 10]);

    $this->postJson('/api/v2/merchandise/orders', [
        'items' => [['sku_id' => $sku->id, 'quantity' => 1]],
    ])->assertStatus(422);
});

test('returns 403 when vendor tries to place order', function () {
    $this->actingAsRole(1, 'vendor');
    $sku = MerchandiseSku::factory()->create(['stock_quantity' => 10]);

    $this->postJson('/api/v2/merchandise/orders', [
        'items' => [['sku_id' => $sku->id, 'quantity' => 1]],
    ])->assertStatus(403);
});

test('validates items array is required', function () {
    $this->actingAsRole(10, 'customer');

    $this->postJson('/api/v2/merchandise/orders', [])->assertStatus(422);
});

test('customer can place a b2b procurement order with buyer and vendor store ids', function () {
    $this->actingAsRole(10, 'customer');
    $sku = MerchandiseSku::factory()->create(['unit_price_cents' => 15000, 'stock_quantity' => 20]);

    $response = $this->postJson('/api/v2/merchandise/orders', [
        'order_kind' => 'procurement',
        'buyer_store_id' => 101,
        'vendor_store_id' => 202,
        'fulfillment_store_id' => 303,
        'items' => [
            ['sku_id' => $sku->id, 'quantity' => 2],
        ],
        'notes' => 'B2B store procurement',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.order_kind', 'procurement')
        ->assertJsonPath('data.buyer_store_id', 101)
        ->assertJsonPath('data.vendor_store_id', 202)
        ->assertJsonPath('data.fulfillment_store_id', 303);

    $orderId = (int) $response->json('data.id');
    $approvalRequestId = (int) $response->json('data.approval_request_id');
    expect($approvalRequestId)->toBeGreaterThan(0);

    $this->assertDatabaseHas('merchandise_orders', [
        'id' => $orderId,
        'order_kind' => 'procurement',
        'buyer_store_id' => 101,
        'vendor_store_id' => 202,
        'fulfillment_store_id' => 303,
        'approval_request_id' => $approvalRequestId,
    ]);

    $this->assertDatabaseHas('approval_requests', [
        'id' => $approvalRequestId,
        'entity_type' => 'merchandise_order',
        'entity_id' => $orderId,
        'status' => 'pending_approval',
        'buyer_store_id' => 101,
        'vendor_store_id' => 202,
    ]);
});

// ---------- GET /api/v2/merchandise/orders ----------

test('customer sees only their own orders', function () {
    $this->actingAsRole(10, 'customer');
    MerchandiseOrder::factory()->create(['customer_id' => 10, 'company_id' => 1]);
    MerchandiseOrder::factory()->create(['customer_id' => 99, 'company_id' => 1]);

    $response = $this->getJson('/api/v2/merchandise/orders');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['customer_id' => 10]);
});

test('admin sees all orders', function () {
    $this->actingAsRole(1, 'admin');
    MerchandiseOrder::factory()->count(5)->create(['company_id' => 1]);

    $response = $this->getJson('/api/v2/merchandise/orders');

    $response->assertStatus(200);
    $response->assertJsonCount(5, 'data');
});

test('orders can be filtered by status', function () {
    $this->actingAsRole(1, 'admin');
    MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);
    MerchandiseOrder::factory()->create(['status' => OrderStatus::Approved, 'company_id' => 1]);

    $response = $this->getJson('/api/v2/merchandise/orders?status=pending_approval');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
});

// ---------- GET /api/v2/merchandise/orders/{id} ----------

test('show returns order details with items', function () {
    $this->actingAsRole(1, 'admin');
    $order = MerchandiseOrder::factory()->create(['company_id' => 1]);

    $response = $this->getJson("/api/v2/merchandise/orders/{$order->id}");

    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => ['id', 'order_ref', 'status', 'items']]);
});

test('show returns 404 for non-existent order', function () {
    $this->actingAsRole(1, 'admin');

    $this->getJson('/api/v2/merchandise/orders/9999')->assertStatus(404);
});

// ---------- DELETE /api/v2/merchandise/orders/{id} ----------

test('customer can cancel their own pending order', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create([
        'customer_id' => 10,
        'status' => OrderStatus::Submitted,
        'company_id' => 1,
    ]);

    $response = $this->deleteJson("/api/v2/merchandise/orders/{$order->id}");

    $response->assertStatus(200);
    $this->assertDatabaseHas('merchandise_orders', ['id' => $order->id, 'status' => 'cancelled']);
});

test('customer cannot cancel an approved order', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create([
        'customer_id' => 10,
        'status' => OrderStatus::Approved,
        'company_id' => 1,
    ]);

    $this->deleteJson("/api/v2/merchandise/orders/{$order->id}")->assertStatus(422);
});

test('customer cannot cancel another customers order', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create([
        'customer_id' => 99,
        'status' => OrderStatus::Submitted,
        'company_id' => 1,
    ]);

    $this->deleteJson("/api/v2/merchandise/orders/{$order->id}")->assertStatus(403);
});

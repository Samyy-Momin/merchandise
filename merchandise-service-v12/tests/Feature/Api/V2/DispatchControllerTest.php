<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\MerchandiseDispatch;
use App\Models\MerchandiseOrder;

// ---------- POST /api/v2/merchandise/orders/{id}/dispatch ----------

test('vendor can dispatch an approved order', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Approved, 'company_id' => 1]);

    $response = $this->postJson("/api/v2/merchandise/orders/{$order->id}/dispatch", [
        'courier' => 'BlueDart',
        'tracking_number' => 'BD123456',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['data' => ['id', 'order_id', 'courier', 'tracking_number', 'dispatched_at']]);
    $this->assertDatabaseHas('merchandise_orders', ['id' => $order->id, 'status' => 'dispatched']);
    $this->assertDatabaseHas('merchandise_dispatches', ['order_id' => $order->id, 'courier' => 'BlueDart']);
});

test('vendor can dispatch a partially_approved order', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PartiallyApproved, 'company_id' => 1]);

    $response = $this->postJson("/api/v2/merchandise/orders/{$order->id}/dispatch", [
        'courier' => 'DTDC',
        'tracking_number' => 'DTDC789',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('merchandise_orders', ['id' => $order->id, 'status' => 'dispatched']);
});

test('vendor can dispatch without courier info', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Approved, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/dispatch", [])->assertStatus(201);
});

test('customer cannot dispatch an order', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Approved, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/dispatch", [])->assertStatus(403);
});

test('admin cannot dispatch an order', function () {
    $this->actingAsRole(5, 'admin');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Approved, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/dispatch", [])->assertStatus(403);
});

test('returns 422 when dispatching a pending_approval order', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::PendingApproval, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/dispatch", [])->assertStatus(422);
});

test('returns 422 when dispatching a rejected order', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Rejected, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/dispatch", [])->assertStatus(422);
});

test('dispatch records dispatched_by staff id', function () {
    $this->actingAsRole(7, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Approved, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/dispatch", [
        'courier' => 'FedEx',
    ]);

    $this->assertDatabaseHas('merchandise_dispatches', [
        'order_id' => $order->id,
        'dispatched_by' => 7,
    ]);
});

// ---------- GET /api/v2/merchandise/dispatches ----------

test('vendor can list all dispatches', function () {
    $this->actingAsRole(1, 'vendor');
    $orders = MerchandiseOrder::factory()->count(3)->create(['status' => OrderStatus::Dispatched, 'company_id' => 1]);
    foreach ($orders as $order) {
        MerchandiseDispatch::factory()->create(['order_id' => $order->id, 'company_id' => 1]);
    }

    $response = $this->getJson('/api/v2/merchandise/dispatches');

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

test('customer cannot list dispatches', function () {
    $this->actingAsRole(10, 'customer');

    $this->getJson('/api/v2/merchandise/dispatches')->assertStatus(403);
});

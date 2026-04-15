<?php

declare(strict_types=1);

use App\Enums\AcknowledgementStatus;
use App\Enums\OrderStatus;
use App\Models\MerchandiseAcknowledgement;
use App\Models\MerchandiseInvoice;
use App\Models\MerchandiseOrder;

// ---------- POST /api/v2/merchandise/orders/{id}/acknowledge ----------

test('customer can acknowledge delivery of a dispatched order', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create([
        'customer_id' => 10,
        'status' => OrderStatus::Dispatched,
        'company_id' => 1,
    ]);

    $response = $this->postJson("/api/v2/merchandise/orders/{$order->id}/acknowledge", [
        'notes' => 'All items received in good condition',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['data' => ['id', 'order_id', 'status', 'acknowledged_at']]);
    $response->assertJsonFragment(['status' => 'pending']);
    $this->assertDatabaseHas('merchandise_orders', ['id' => $order->id, 'status' => 'acknowledged']);
    $this->assertDatabaseHas('merchandise_acknowledgements', [
        'order_id' => $order->id,
        'acknowledged_by' => 10,
        'status' => 'pending',
    ]);
});

test('acknowledgement notes are optional', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create([
        'customer_id' => 10,
        'status' => OrderStatus::Dispatched,
        'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/acknowledge", [])->assertStatus(201);
});

test('returns 422 when order is not dispatched', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create([
        'customer_id' => 10,
        'status' => OrderStatus::Approved,
        'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/acknowledge", [])->assertStatus(422);
});

test('vendor cannot acknowledge delivery', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Dispatched, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/acknowledge", [])->assertStatus(403);
});

test('admin cannot acknowledge delivery', function () {
    $this->actingAsRole(5, 'admin');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Dispatched, 'company_id' => 1]);

    $this->postJson("/api/v2/merchandise/orders/{$order->id}/acknowledge", [])->assertStatus(403);
});

// ---------- POST /api/v2/merchandise/acknowledgements/{id}/approve ----------

test('vendor can approve an acknowledgement and it triggers invoice creation', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Acknowledged, 'company_id' => 1]);
    $ack = MerchandiseAcknowledgement::factory()->create([
        'order_id' => $order->id,
        'acknowledged_by' => 10,
        'status' => AcknowledgementStatus::Pending,
        'company_id' => 1,
    ]);

    $response = $this->postJson("/api/v2/merchandise/acknowledgements/{$ack->id}/approve");

    $response->assertStatus(200);
    $response->assertJsonFragment(['status' => 'approved']);
    $this->assertDatabaseHas('merchandise_acknowledgements', [
        'id' => $ack->id,
        'status' => 'approved',
        'reviewed_by' => 1,
    ]);
    // Order should advance into payment pending once the invoice is created
    $this->assertDatabaseHas('merchandise_orders', ['id' => $order->id, 'status' => 'payment_pending']);
    $this->assertDatabaseCount('merchandise_invoices', 1);
    $this->assertDatabaseHas('merchandise_invoices', ['order_id' => $order->id]);
});

test('invoice due_date is set to 15 days from approval date', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Acknowledged, 'company_id' => 1]);
    $ack = MerchandiseAcknowledgement::factory()->create([
        'order_id' => $order->id, 'status' => AcknowledgementStatus::Pending, 'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/acknowledgements/{$ack->id}/approve");

    $invoice = MerchandiseInvoice::where('order_id', $order->id)->first();
    expect($invoice)->not->toBeNull();
    $expectedDue = now()->addDays(15)->toDateString();
    expect($invoice->due_date->toDateString())->toBe($expectedDue);
});

test('super_admin can approve an acknowledgement', function () {
    $this->actingAsRole(1, 'super_admin');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Acknowledged, 'company_id' => 1]);
    $ack = MerchandiseAcknowledgement::factory()->create([
        'order_id' => $order->id, 'status' => AcknowledgementStatus::Pending, 'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/acknowledgements/{$ack->id}/approve")->assertStatus(200);
});

test('customer cannot approve an acknowledgement', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Acknowledged, 'company_id' => 1]);
    $ack = MerchandiseAcknowledgement::factory()->create([
        'order_id' => $order->id, 'status' => AcknowledgementStatus::Pending, 'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/acknowledgements/{$ack->id}/approve")->assertStatus(403);
});

test('returns 422 when approving an already approved acknowledgement', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::InvoiceGenerated, 'company_id' => 1]);
    $ack = MerchandiseAcknowledgement::factory()->create([
        'order_id' => $order->id, 'status' => AcknowledgementStatus::Approved, 'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/acknowledgements/{$ack->id}/approve")->assertStatus(422);
});

// ---------- POST /api/v2/merchandise/acknowledgements/{id}/reject ----------

test('vendor can reject an acknowledgement and order reverts to dispatched', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Acknowledged, 'company_id' => 1]);
    $ack = MerchandiseAcknowledgement::factory()->create([
        'order_id' => $order->id,
        'acknowledged_by' => 10,
        'status' => AcknowledgementStatus::Pending,
        'company_id' => 1,
    ]);

    $response = $this->postJson("/api/v2/merchandise/acknowledgements/{$ack->id}/reject", [
        'reason' => 'Wrong items were received',
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['status' => 'rejected']);
    $this->assertDatabaseHas('merchandise_acknowledgements', [
        'id' => $ack->id,
        'status' => 'rejected',
        'rejection_reason' => 'Wrong items were received',
    ]);
    // Critical BRD rule: order reverts to dispatched for re-acknowledgement
    $this->assertDatabaseHas('merchandise_orders', ['id' => $order->id, 'status' => 'dispatched']);
});

test('reject validates reason is required', function () {
    $this->actingAsRole(1, 'vendor');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Acknowledged, 'company_id' => 1]);
    $ack = MerchandiseAcknowledgement::factory()->create([
        'order_id' => $order->id, 'status' => AcknowledgementStatus::Pending, 'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/acknowledgements/{$ack->id}/reject", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['reason']);
});

test('customer cannot reject an acknowledgement', function () {
    $this->actingAsRole(10, 'customer');
    $order = MerchandiseOrder::factory()->create(['status' => OrderStatus::Acknowledged, 'company_id' => 1]);
    $ack = MerchandiseAcknowledgement::factory()->create([
        'order_id' => $order->id, 'status' => AcknowledgementStatus::Pending, 'company_id' => 1,
    ]);

    $this->postJson("/api/v2/merchandise/acknowledgements/{$ack->id}/reject", [
        'reason' => 'test',
    ])->assertStatus(403);
});

<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\MerchandiseOrder;

test('customer can create and submit an approval request for an order', function () {
    $this->actingAsRole(10, 'customer');

    $order = MerchandiseOrder::factory()->create([
        'company_id' => 1,
        'customer_id' => 10,
        'status' => OrderStatus::PendingApproval,
    ]);

    $createResponse = $this->postJson('/api/v2/merchandise/approvals', [
        'entity_type' => 'merchandise_order',
        'entity_id' => $order->id,
        'buyer_store_id' => 1001,
        'vendor_store_id' => 2002,
        'approver_role' => 'admin',
    ]);

    $createResponse->assertStatus(201)
        ->assertJsonPath('data.entity_type', 'merchandise_order')
        ->assertJsonPath('data.entity_id', $order->id)
        ->assertJsonPath('data.status', 'draft');

    $approvalId = (int) $createResponse->json('data.id');
    expect($approvalId)->toBeGreaterThan(0);

    $submitResponse = $this->postJson("/api/v2/merchandise/approvals/{$approvalId}/submit");

    $submitResponse->assertStatus(200)
        ->assertJsonPath('data.status', 'pending_approval');
});

test('admin can approve an approval request', function () {
    $this->actingAsRole(5, 'admin');

    $order = MerchandiseOrder::factory()->create([
        'company_id' => 1,
        'status' => OrderStatus::PendingApproval,
    ]);

    $createResponse = $this->postJson('/api/v2/merchandise/approvals', [
        'entity_type' => 'merchandise_order',
        'entity_id' => $order->id,
        'buyer_store_id' => 111,
        'vendor_store_id' => 222,
        'approver_role' => 'admin',
    ]);

    $approvalId = (int) $createResponse->json('data.id');

    $this->postJson("/api/v2/merchandise/approvals/{$approvalId}/submit")->assertStatus(200);

    $approveResponse = $this->postJson("/api/v2/merchandise/approvals/{$approvalId}/approve", [
        'reason' => 'Approved by procurement manager',
    ]);

    $approveResponse->assertStatus(200)
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonPath('data.approved_by', 5)
        ->assertJsonPath('data.decision_reason', 'Approved by procurement manager');

    $this->assertDatabaseHas('approval_requests', [
        'id' => $approvalId,
        'status' => 'approved',
        'approved_by' => 5,
    ]);
});

test('reject endpoint requires reason', function () {
    $this->actingAsRole(5, 'admin');

    $order = MerchandiseOrder::factory()->create([
        'company_id' => 1,
        'status' => OrderStatus::PendingApproval,
    ]);

    $createResponse = $this->postJson('/api/v2/merchandise/approvals', [
        'entity_type' => 'merchandise_order',
        'entity_id' => $order->id,
        'buyer_store_id' => 111,
        'vendor_store_id' => 222,
        'approver_role' => 'admin',
    ]);

    $approvalId = (int) $createResponse->json('data.id');
    $this->postJson("/api/v2/merchandise/approvals/{$approvalId}/submit")->assertStatus(200);

    $this->postJson("/api/v2/merchandise/approvals/{$approvalId}/reject", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['reason']);
});

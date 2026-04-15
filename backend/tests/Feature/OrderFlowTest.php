<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\FakesKeycloakToken;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase, FakesKeycloakToken;

    private string $buyerId = 'buyer-uuid-1';
    private string $approverId = 'approver-uuid-1';

    private function seedCatalogueAndAddress(): array
    {
        $cat = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $cat->id, 'price' => 100]);
        $address = Address::create([
            'user_id' => $this->buyerId,
            'name' => 'Test',
            'phone' => '9999999999',
            'address_line' => '123 Test St',
            'city' => 'Mumbai',
            'state' => 'MH',
            'pincode' => '400001',
        ]);
        return [$product, $address];
    }

    private function createOrder(): Order
    {
        [$product, $address] = $this->seedCatalogueAndAddress();
        $order = Order::create([
            'user_id' => $this->buyerId,
            'status' => 'pending_approval',
            'total_amount' => 200,
            'address_id' => $address->id,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty_requested' => 2,
            'price' => 100,
        ]);
        return $order->fresh(['items']);
    }

    // ──────────────────────────────────────────────────
    // Order Placement
    // ──────────────────────────────────────────────────

    public function test_buyer_can_place_order(): void
    {
        [$product, $address] = $this->seedCatalogueAndAddress();

        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $product->id, 'qty' => 3],
                ],
                'address_id' => $address->id,
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['status' => 'pending_approval']);
        $this->assertDatabaseHas('orders', ['user_id' => $this->buyerId]);
    }

    public function test_place_order_validates_items(): void
    {
        [$product, $address] = $this->seedCatalogueAndAddress();

        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->postJson('/api/orders', [
                'items' => [],
                'address_id' => $address->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_place_order_validates_address_ownership(): void
    {
        [$product, $address] = $this->seedCatalogueAndAddress();

        // Different user tries to use this address
        $response = $this->withKeycloakToken(['buyer'], ['sub' => 'other-user-uuid'])
            ->postJson('/api/orders', [
                'items' => [['product_id' => $product->id, 'qty' => 1]],
                'address_id' => $address->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Invalid address']);
    }

    // ──────────────────────────────────────────────────
    // Order Listing
    // ──────────────────────────────────────────────────

    public function test_buyer_sees_own_orders(): void
    {
        $this->createOrder();

        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->getJson('/api/orders');

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_approver_sees_all_orders(): void
    {
        $this->createOrder();

        $response = $this->withKeycloakToken(['approver'], ['sub' => $this->approverId])
            ->getJson('/api/orders');

        $response->assertOk();
    }

    public function test_order_show_returns_detail(): void
    {
        $order = $this->createOrder();

        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->getJson("/api/orders/{$order->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $order->id]);
    }

    // ──────────────────────────────────────────────────
    // Order Approval
    // ──────────────────────────────────────────────────

    public function test_approver_can_fully_approve(): void
    {
        $order = $this->createOrder();
        $item = $order->items->first();

        $response = $this->withKeycloakToken(['approver'], ['sub' => $this->approverId])
            ->postJson("/api/orders/{$order->id}/approve", [
                'items' => [
                    ['item_id' => $item->id, 'qty_approved' => 2],
                ],
                'comments' => 'Looks good',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'approved']);
    }

    public function test_approver_partial_approval(): void
    {
        $order = $this->createOrder();
        $item = $order->items->first();

        $response = $this->withKeycloakToken(['approver'], ['sub' => $this->approverId])
            ->postJson("/api/orders/{$order->id}/approve", [
                'items' => [
                    ['item_id' => $item->id, 'qty_approved' => 1],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'partially_approved']);
    }

    public function test_approver_rejection_when_zero_approved(): void
    {
        $order = $this->createOrder();
        $item = $order->items->first();

        $response = $this->withKeycloakToken(['approver'], ['sub' => $this->approverId])
            ->postJson("/api/orders/{$order->id}/approve", [
                'items' => [
                    ['item_id' => $item->id, 'qty_approved' => 0],
                ],
                'comments' => 'Not needed',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'rejected']);
    }

    public function test_approve_rejects_over_requested_qty(): void
    {
        $order = $this->createOrder();
        $item = $order->items->first();

        $response = $this->withKeycloakToken(['approver'], ['sub' => $this->approverId])
            ->postJson("/api/orders/{$order->id}/approve", [
                'items' => [
                    ['item_id' => $item->id, 'qty_approved' => 999],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'qty_approved exceeds remaining for one or more items']);
    }

    public function test_buyer_cannot_approve_orders(): void
    {
        $order = $this->createOrder();
        $item = $order->items->first();

        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->postJson("/api/orders/{$order->id}/approve", [
                'items' => [['item_id' => $item->id, 'qty_approved' => 1]],
            ]);

        $response->assertStatus(403);
    }

    // ──────────────────────────────────────────────────
    // Full Details
    // ──────────────────────────────────────────────────

    public function test_full_details_returns_structured_response(): void
    {
        $order = $this->createOrder();

        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->getJson("/api/orders/{$order->id}/full-details");

        $response->assertOk();
        $response->assertJsonStructure([
            'order' => ['id', 'user_id', 'status', 'total_amount'],
            'items',
            'acknowledgement',
            'issues',
        ]);
    }
}

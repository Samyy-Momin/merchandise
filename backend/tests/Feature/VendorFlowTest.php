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
use App\Models\Shipment;
use App\Models\TrackingLog;
use App\Models\ShipmentItem;

class VendorFlowTest extends TestCase
{
    use RefreshDatabase, FakesKeycloakToken;

    private string $buyerId = 'buyer-uuid-1';
    private string $vendorId = 'vendor-uuid-1';

    private function createApprovedOrder(): Order
    {
        $cat = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $cat->id, 'price' => 100]);
        $address = Address::create([
            'user_id' => $this->buyerId,
            'name' => 'Test', 'phone' => '9999999999',
            'address_line' => '123 St', 'city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001',
        ]);
        $order = Order::create([
            'user_id' => $this->buyerId, 'status' => 'approved',
            'total_amount' => 200, 'address_id' => $address->id,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'qty_requested' => 2, 'qty_approved' => 2, 'price' => 100,
        ]);
        return $order->fresh(['items']);
    }

    // ──────────────────────────────────────────────────
    // Vendor Order List
    // ──────────────────────────────────────────────────

    public function test_vendor_can_list_orders(): void
    {
        $this->createApprovedOrder();

        $response = $this->withKeycloakToken(['vendor'], ['sub' => $this->vendorId])
            ->getJson('/api/vendor/orders');

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_vendor_list_filter_by_status(): void
    {
        $this->createApprovedOrder();

        $response = $this->withKeycloakToken(['vendor'], ['sub' => $this->vendorId])
            ->getJson('/api/vendor/orders?filter=ready');

        $response->assertOk();
    }

    // ──────────────────────────────────────────────────
    // Processing
    // ──────────────────────────────────────────────────

    public function test_vendor_can_mark_processing(): void
    {
        $order = $this->createApprovedOrder();

        $response = $this->withKeycloakToken(['vendor'], ['sub' => $this->vendorId])
            ->postJson("/api/vendor/orders/{$order->id}/process");

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'processing']);
        $this->assertDatabaseHas('shipments', ['order_id' => $order->id]);
    }

    public function test_process_fails_for_non_approved_order(): void
    {
        $order = $this->createApprovedOrder();
        $order->update(['status' => 'pending_approval']);

        $response = $this->withKeycloakToken(['vendor'], ['sub' => $this->vendorId])
            ->postJson("/api/vendor/orders/{$order->id}/process");

        $response->assertStatus(422);
    }

    // ──────────────────────────────────────────────────
    // Dispatch
    // ──────────────────────────────────────────────────

    public function test_vendor_can_dispatch(): void
    {
        $order = $this->createApprovedOrder();
        $order->update(['status' => 'processing']);
        Shipment::create(['order_id' => $order->id, 'vendor_id' => $this->vendorId, 'status' => 'processing']);

        $response = $this->withKeycloakToken(['vendor'], ['sub' => $this->vendorId])
            ->postJson("/api/vendor/orders/{$order->id}/dispatch", [
                'tracking_number' => 'TRK123',
                'courier_name' => 'BlueDart',
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'dispatched']);
    }

    public function test_dispatch_requires_tracking_info(): void
    {
        $order = $this->createApprovedOrder();
        $order->update(['status' => 'processing']);
        Shipment::create(['order_id' => $order->id, 'vendor_id' => $this->vendorId, 'status' => 'processing']);

        $response = $this->withKeycloakToken(['vendor'], ['sub' => $this->vendorId])
            ->postJson("/api/vendor/orders/{$order->id}/dispatch", []);

        $response->assertStatus(422);
    }

    // ──────────────────────────────────────────────────
    // Transit + Deliver
    // ──────────────────────────────────────────────────

    public function test_vendor_can_mark_transit(): void
    {
        $order = $this->createApprovedOrder();
        $order->update(['status' => 'dispatched']);
        Shipment::create(['order_id' => $order->id, 'vendor_id' => $this->vendorId, 'status' => 'dispatched']);

        $response = $this->withKeycloakToken(['vendor'], ['sub' => $this->vendorId])
            ->postJson("/api/vendor/orders/{$order->id}/transit");

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'in_transit']);
    }

    public function test_vendor_can_deliver_with_items(): void
    {
        $order = $this->createApprovedOrder();
        $order->update(['status' => 'in_transit']);
        $shipment = Shipment::create(['order_id' => $order->id, 'vendor_id' => $this->vendorId, 'status' => 'in_transit']);
        $item = $order->items->first();

        $response = $this->withKeycloakToken(['vendor'], ['sub' => $this->vendorId])
            ->postJson("/api/vendor/orders/{$order->id}/deliver", [
                'items' => [
                    ['order_item_id' => $item->id, 'delivered_qty' => 2],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['status' => 'delivered']);
        // Invoice should be auto-created
        $this->assertDatabaseHas('invoices', ['order_id' => $order->id]);
    }
}

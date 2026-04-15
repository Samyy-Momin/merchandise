<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\FakesKeycloakToken;
use App\Models\Approval;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;

class ApprovalTest extends TestCase
{
    use RefreshDatabase, FakesKeycloakToken;

    private string $approverId = 'approver-uuid-1';

    private function seedApproval(): Approval
    {
        $cat = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $cat->id, 'price' => 50]);
        $address = Address::create([
            'user_id' => 'buyer-1', 'name' => 'Test', 'phone' => '999',
            'address_line' => '1 St', 'city' => 'C', 'state' => 'S', 'pincode' => '000',
        ]);
        $order = Order::create([
            'user_id' => 'buyer-1', 'status' => 'approved',
            'total_amount' => 100, 'address_id' => $address->id,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'qty_requested' => 2, 'qty_approved' => 2, 'price' => 50,
        ]);
        return Approval::create([
            'order_id' => $order->id,
            'approver_id' => $this->approverId,
            'status' => 'approved',
            'comments' => 'OK',
        ]);
    }

    public function test_approvals_index(): void
    {
        $this->seedApproval();

        $response = $this->withKeycloakToken(['approver'], ['sub' => $this->approverId])
            ->getJson('/api/approvals');

        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_approvals_filter_by_status(): void
    {
        $this->seedApproval();

        $response = $this->withKeycloakToken(['approver'], ['sub' => $this->approverId])
            ->getJson('/api/approvals?status=approved');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_approvals_stats(): void
    {
        $this->seedApproval();

        $response = $this->withKeycloakToken(['approver'], ['sub' => $this->approverId])
            ->getJson('/api/approvals/stats');

        $response->assertOk();
        $response->assertJsonStructure(['approved', 'rejected', 'partial']);
    }

    public function test_approval_show(): void
    {
        $approval = $this->seedApproval();

        $response = $this->withKeycloakToken(['approver'], ['sub' => $this->approverId])
            ->getJson("/api/approvals/{$approval->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $approval->id]);
    }

    public function test_approval_show_returns_404_for_other_approver(): void
    {
        $approval = $this->seedApproval();

        $response = $this->withKeycloakToken(['approver'], ['sub' => 'different-approver'])
            ->getJson("/api/approvals/{$approval->id}");

        $response->assertStatus(404);
    }
}

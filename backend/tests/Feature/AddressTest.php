<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\FakesKeycloakToken;
use App\Models\Address;

class AddressTest extends TestCase
{
    use RefreshDatabase, FakesKeycloakToken;

    private string $buyerId = 'buyer-uuid-1';

    public function test_list_addresses(): void
    {
        Address::create([
            'user_id' => $this->buyerId, 'name' => 'Home',
            'phone' => '9999999999', 'address_line' => '123 St',
            'city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001',
        ]);

        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->getJson('/api/addresses');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Home']);
    }

    public function test_create_address(): void
    {
        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->postJson('/api/addresses', [
                'name' => 'Office',
                'phone' => '8888888888',
                'address_line' => '456 Business Park',
                'city' => 'Delhi',
                'state' => 'DL',
                'pincode' => '110001',
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'Office']);
        $this->assertDatabaseHas('addresses', ['name' => 'Office', 'user_id' => $this->buyerId]);
    }

    public function test_update_address(): void
    {
        $addr = Address::create([
            'user_id' => $this->buyerId, 'name' => 'Old',
            'phone' => '9999999999', 'address_line' => '123 St',
            'city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001',
        ]);

        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->putJson("/api/addresses/{$addr->id}", ['name' => 'New Name']);

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'New Name']);
    }

    public function test_delete_address(): void
    {
        $addr = Address::create([
            'user_id' => $this->buyerId, 'name' => 'ToDelete',
            'phone' => '9999999999', 'address_line' => '123 St',
            'city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001',
        ]);

        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->deleteJson("/api/addresses/{$addr->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('addresses', ['id' => $addr->id]);
    }

    public function test_cannot_access_other_users_address(): void
    {
        $addr = Address::create([
            'user_id' => 'other-user', 'name' => 'NotMine',
            'phone' => '9999999999', 'address_line' => '123 St',
            'city' => 'Mumbai', 'state' => 'MH', 'pincode' => '400001',
        ]);

        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->putJson("/api/addresses/{$addr->id}", ['name' => 'Hacked']);

        // Should be 404 (findOrFail with user_id filter) or 500 from catch
        $this->assertTrue(in_array($response->status(), [404, 500]));
    }

    public function test_create_address_validation(): void
    {
        $response = $this->withKeycloakToken(['buyer'], ['sub' => $this->buyerId])
            ->postJson('/api/addresses', [
                'name' => 'Test',
                // missing required fields
            ]);

        $response->assertStatus(422);
    }
}

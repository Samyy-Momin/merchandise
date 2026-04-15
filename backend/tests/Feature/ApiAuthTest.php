<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\FakesKeycloakToken;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase, FakesKeycloakToken;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'Unauthorized: missing bearer token']);
    }

    public function test_invalid_token_returns_401(): void
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer invalid.token'])->getJson('/api/me');
        $response->assertStatus(401);
    }

    public function test_me_returns_user_and_roles(): void
    {
        $response = $this->withKeycloakToken(['buyer'], [
            'sub' => 'user-uuid-1',
            'preferred_username' => 'testbuyer',
            'email' => 'buyer@test.com',
            'name' => 'Test Buyer',
        ])->getJson('/api/me');

        $response->assertOk();
        $response->assertJsonStructure([
            'user' => ['sub', 'preferred_username', 'email', 'name'],
            'roles',
        ]);
        $response->assertJsonFragment(['sub' => 'user-uuid-1']);
    }

    public function test_buyer_role_endpoint_allows_buyer(): void
    {
        $response = $this->withKeycloakToken(['buyer'])->getJson('/api/buyer');
        $response->assertOk();
        $response->assertJsonFragment(['message' => 'buyer ok']);
    }

    public function test_buyer_role_endpoint_denies_vendor(): void
    {
        $response = $this->withKeycloakToken(['vendor'])->getJson('/api/buyer');
        $response->assertStatus(403);
    }

    public function test_approver_role_endpoint_allows_approver(): void
    {
        $response = $this->withKeycloakToken(['approver'])->getJson('/api/approver');
        $response->assertOk();
    }

    public function test_admin_bypass_works_on_any_role_endpoint(): void
    {
        $response = $this->withKeycloakToken(['admin'])->getJson('/api/buyer');
        $response->assertOk();

        $response = $this->withKeycloakToken(['admin'])->getJson('/api/vendor');
        $response->assertOk();
    }

    public function test_super_admin_bypass_works(): void
    {
        $response = $this->withKeycloakToken(['super_admin'])->getJson('/api/approver');
        $response->assertOk();
    }
}

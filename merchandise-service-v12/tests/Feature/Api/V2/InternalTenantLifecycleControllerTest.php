<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

test('approver readiness is pending until bootstrap is requested', function () {
    Cache::flush();

    $response = $this->getJson('/api/internal/tenants/41/readiness?unit_id=2');

    $response->assertStatus(200)
        ->assertJsonPath('data.service', 'approver-service-v12')
        ->assertJsonPath('data.company_id', 41)
        ->assertJsonPath('data.unit_id', 2)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.bootstrapped', false)
        ->assertJsonPath('data.healthy', true);
});

test('approver bootstrap primes readiness for the tenant', function () {
    Cache::flush();

    $bootstrapResponse = $this->postJson('/api/internal/tenants/41/bootstrap', [
        'unit_id' => 2,
        'business_type' => 'retail_merchandise',
    ]);

    $bootstrapResponse->assertStatus(202)
        ->assertJsonPath('data.service', 'approver-service-v12')
        ->assertJsonPath('data.company_id', 41)
        ->assertJsonPath('data.unit_id', 2);

    $readinessResponse = $this->getJson('/api/internal/tenants/41/readiness?unit_id=2');

    $readinessResponse->assertStatus(200)
        ->assertJsonPath('data.service', 'approver-service-v12')
        ->assertJsonPath('data.status', 'ready')
        ->assertJsonPath('data.bootstrapped', true)
        ->assertJsonPath('data.healthy', true);
});

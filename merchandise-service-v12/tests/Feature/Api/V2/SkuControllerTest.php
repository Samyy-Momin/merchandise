<?php

declare(strict_types=1);

use App\Models\MerchandiseSku;

// ---------- GET /api/v2/merchandise/skus ----------

test('index returns paginated skus for any authenticated user', function () {
    $this->actingAsRole(1, 'customer');
    MerchandiseSku::factory()->count(3)->create();

    $response = $this->getJson('/api/v2/merchandise/skus');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [['id', 'sku_code', 'name', 'unit_price_cents', 'stock_quantity', 'is_active']],
        'meta' => ['total', 'per_page', 'current_page'],
    ]);
});

test('index returns only active skus by default', function () {
    $this->actingAsRole(1, 'customer');
    MerchandiseSku::factory()->create(['is_active' => true]);
    MerchandiseSku::factory()->create(['is_active' => false]);

    $response = $this->getJson('/api/v2/merchandise/skus');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
});

test('index can filter by category', function () {
    $this->actingAsRole(1, 'customer');
    MerchandiseSku::factory()->create(['category' => 'Stationery']);
    MerchandiseSku::factory()->create(['category' => 'Housekeeping']);

    $response = $this->getJson('/api/v2/merchandise/skus?category=Stationery');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['category' => 'Stationery']);
});

test('index is scoped to the active tenant', function () {
    $this->actingAsRole(1, 'customer', 1);

    MerchandiseSku::withoutGlobalScopes()->create([
        'company_id' => 1,
        'sku_code' => 'SKU-T1',
        'name' => 'Tenant 1 SKU',
        'unit_price_cents' => 1000,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    MerchandiseSku::withoutGlobalScopes()->create([
        'company_id' => 2,
        'sku_code' => 'SKU-T2',
        'name' => 'Tenant 2 SKU',
        'unit_price_cents' => 1000,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v2/merchandise/skus');

    $response->assertSuccessful();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonFragment(['sku_code' => 'SKU-T1']);
    $response->assertJsonMissing(['sku_code' => 'SKU-T2']);
});

// ---------- GET /api/v2/merchandise/skus/{id} ----------

test('show returns sku details', function () {
    $this->actingAsRole(1, 'customer');
    $sku = MerchandiseSku::factory()->create();

    $response = $this->getJson("/api/v2/merchandise/skus/{$sku->id}");

    $response->assertStatus(200);
    $response->assertJsonFragment(['id' => $sku->id, 'name' => $sku->name]);
});

test('show returns 404 for non-existent sku', function () {
    $this->actingAsRole(1, 'customer');

    $this->getJson('/api/v2/merchandise/skus/9999')->assertStatus(404);
});

// ---------- POST /api/v2/merchandise/skus (vendor/super_admin only) ----------

test('store creates a sku when called by vendor', function () {
    $this->actingAsRole(1, 'vendor');

    $response = $this->postJson('/api/v2/merchandise/skus', [
        'sku_code' => 'SKU-001',
        'name' => 'A4 Printing Paper',
        'unit_price_cents' => 50000,
        'stock_quantity' => 100,
        'category' => 'Stationery',
    ]);

    $response->assertStatus(201);
    $response->assertJsonFragment(['sku_code' => 'SKU-001', 'name' => 'A4 Printing Paper']);
    $this->assertDatabaseHas('merchandise_skus', ['sku_code' => 'SKU-001']);
});

test('store creates a sku when called by super_admin', function () {
    $this->actingAsRole(1, 'super_admin');

    $response = $this->postJson('/api/v2/merchandise/skus', [
        'sku_code' => 'SKU-002',
        'name' => 'Whiteboard Marker',
        'unit_price_cents' => 15000,
        'stock_quantity' => 50,
    ]);

    $response->assertStatus(201);
});

test('store returns 403 when customer tries to create a sku', function () {
    $this->actingAsRole(1, 'customer');

    $this->postJson('/api/v2/merchandise/skus', [
        'sku_code' => 'SKU-003',
        'name' => 'Pen',
        'unit_price_cents' => 5000,
        'stock_quantity' => 200,
    ])->assertStatus(403);
});

test('store returns 403 when admin tries to create a sku', function () {
    $this->actingAsRole(1, 'admin');

    $this->postJson('/api/v2/merchandise/skus', [
        'sku_code' => 'SKU-004',
        'name' => 'Pen',
        'unit_price_cents' => 5000,
        'stock_quantity' => 200,
    ])->assertStatus(403);
});

test('store validates required fields', function () {
    $this->actingAsRole(1, 'vendor');

    $response = $this->postJson('/api/v2/merchandise/skus', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sku_code', 'name', 'unit_price_cents', 'stock_quantity']);
});

test('store validates unit_price_cents must be positive integer', function () {
    $this->actingAsRole(1, 'vendor');

    $response = $this->postJson('/api/v2/merchandise/skus', [
        'sku_code' => 'SKU-005',
        'name' => 'Stapler',
        'unit_price_cents' => -100,
        'stock_quantity' => 10,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['unit_price_cents']);
});

test('store uses tenant company id from request context', function () {
    $this->actingAsRole(5, 'vendor', 2);

    $response = $this->postJson('/api/v2/merchandise/skus', [
        'sku_code' => 'SKU-TENANT-2',
        'name' => 'Branch Cleaner',
        'unit_price_cents' => 2500,
        'stock_quantity' => 25,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('merchandise_skus', [
        'sku_code' => 'SKU-TENANT-2',
        'company_id' => 2,
    ]);
});

test('store rejects duplicate sku code within the same tenant', function () {
    $this->actingAsRole(1, 'vendor', 1);

    MerchandiseSku::factory()->create([
        'company_id' => 1,
        'sku_code' => 'SKU-DUPLICATE',
    ]);

    $response = $this->postJson('/api/v2/merchandise/skus', [
        'sku_code' => 'SKU-DUPLICATE',
        'name' => 'Duplicate SKU',
        'unit_price_cents' => 1500,
        'stock_quantity' => 10,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sku_code']);
});

test('store allows duplicate sku code in a different tenant', function () {
    MerchandiseSku::withoutGlobalScopes()->create([
        'company_id' => 1,
        'sku_code' => 'SKU-CROSS-TENANT',
        'name' => 'Tenant 1 SKU',
        'unit_price_cents' => 1000,
        'stock_quantity' => 5,
        'is_active' => true,
    ]);

    $this->actingAsRole(1, 'vendor', 2);

    $response = $this->postJson('/api/v2/merchandise/skus', [
        'sku_code' => 'SKU-CROSS-TENANT',
        'name' => 'Tenant 2 SKU',
        'unit_price_cents' => 2000,
        'stock_quantity' => 10,
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('merchandise_skus', [
        'company_id' => 2,
        'sku_code' => 'SKU-CROSS-TENANT',
    ]);
});

// ---------- PUT /api/v2/merchandise/skus/{id} ----------

test('update modifies sku when called by vendor', function () {
    $this->actingAsRole(1, 'vendor');
    $sku = MerchandiseSku::factory()->create(['name' => 'Old Name']);

    $response = $this->putJson("/api/v2/merchandise/skus/{$sku->id}", [
        'name' => 'New Name',
        'unit_price_cents' => 60000,
        'stock_quantity' => 80,
    ]);

    $response->assertStatus(200);
    $response->assertJsonFragment(['name' => 'New Name']);
    $this->assertDatabaseHas('merchandise_skus', ['id' => $sku->id, 'name' => 'New Name']);
});

test('update returns 403 for customer', function () {
    $this->actingAsRole(1, 'customer');
    $sku = MerchandiseSku::factory()->create();

    $this->putJson("/api/v2/merchandise/skus/{$sku->id}", ['name' => 'Hacked'])->assertStatus(403);
});

// ---------- DELETE /api/v2/merchandise/skus/{id} ----------

test('destroy deactivates sku when called by vendor', function () {
    $this->actingAsRole(1, 'vendor');
    $sku = MerchandiseSku::factory()->create(['is_active' => true]);

    $response = $this->deleteJson("/api/v2/merchandise/skus/{$sku->id}");

    $response->assertStatus(200);
    $this->assertDatabaseHas('merchandise_skus', ['id' => $sku->id, 'is_active' => false]);
});

test('destroy returns 403 for customer', function () {
    $this->actingAsRole(1, 'customer');
    $sku = MerchandiseSku::factory()->create();

    $this->deleteJson("/api/v2/merchandise/skus/{$sku->id}")->assertStatus(403);
});

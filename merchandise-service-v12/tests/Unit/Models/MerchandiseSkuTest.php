<?php

declare(strict_types=1);

use App\Models\MerchandiseSku;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;

uses(DatabaseMigrations::class);

test('can be created with required fields', function () {
    $sku = MerchandiseSku::create([
        'company_id' => 1,
        'sku_code' => 'SKU-001',
        'name' => 'A4 Printing Paper',
        'unit_price_cents' => 50000,
        'stock_quantity' => 100,
    ]);

    expect($sku->id)->toBeGreaterThan(0);
    expect($sku->sku_code)->toBe('SKU-001');
    expect($sku->name)->toBe('A4 Printing Paper');
    expect($sku->unit_price_cents)->toBe(50000);
    expect($sku->stock_quantity)->toBe(100);
});

test('is_active defaults to true', function () {
    $sku = MerchandiseSku::factory()->create();
    expect($sku->is_active)->toBeTrue();
});

test('can be deactivated', function () {
    $sku = MerchandiseSku::factory()->create(['is_active' => true]);
    $sku->update(['is_active' => false]);
    expect($sku->fresh()->is_active)->toBeFalse();
});

test('has custom timestamps created_date and updated_date', function () {
    $sku = MerchandiseSku::factory()->create();
    expect($sku->created_date)->not->toBeNull();
    expect($sku->updated_date)->not->toBeNull();
});

test('stores images as json array', function () {
    $sku = MerchandiseSku::factory()->create([
        'images' => ['https://example.com/img1.jpg', 'https://example.com/img2.jpg'],
    ]);

    expect($sku->fresh()->images)->toBeArray();
    expect($sku->fresh()->images)->toHaveCount(2);
});

test('sku_code is unique per company', function () {
    MerchandiseSku::factory()->create(['company_id' => 1, 'sku_code' => 'SKU-001']);

    expect(fn () => MerchandiseSku::factory()->create(['company_id' => 1, 'sku_code' => 'SKU-001']))
        ->toThrow(QueryException::class);
});

test('same sku_code can exist in different companies', function () {
    MerchandiseSku::factory()->create(['company_id' => 1, 'sku_code' => 'SKU-001']);
    $sku2 = MerchandiseSku::factory()->create(['company_id' => 2, 'sku_code' => 'SKU-001']);

    expect($sku2->id)->toBeGreaterThan(0);
});

test('has fillable fields', function () {
    $sku = new MerchandiseSku;
    $fillable = $sku->getFillable();

    expect($fillable)->toContain('sku_code');
    expect($fillable)->toContain('name');
    expect($fillable)->toContain('description');
    expect($fillable)->toContain('category');
    expect($fillable)->toContain('unit_price_cents');
    expect($fillable)->toContain('stock_quantity');
    expect($fillable)->toContain('images');
    expect($fillable)->toContain('is_active');
    expect($fillable)->toContain('company_id');
});

test('has orders relationship', function () {
    $sku = MerchandiseSku::factory()->create();
    expect($sku->orderItems())->toBeInstanceOf(HasMany::class);
});

test('active scope filters only active skus', function () {
    MerchandiseSku::factory()->create(['is_active' => true]);
    MerchandiseSku::factory()->create(['is_active' => true]);
    MerchandiseSku::factory()->create(['is_active' => false]);

    expect(MerchandiseSku::active()->count())->toBe(2);
});

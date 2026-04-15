<?php

declare(strict_types=1);

use App\DTOs\SkuDTO;
use App\Exceptions\SkuHasActiveOrdersException;
use App\Exceptions\SkuNotFoundException;
use App\Models\MerchandiseSku;
use App\Repositories\Interfaces\SkuRepositoryInterface;
use App\Services\Sku\SkuService;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $this->repo = Mockery::mock(SkuRepositoryInterface::class);
    $this->service = new SkuService($this->repo);
});

afterEach(fn () => Mockery::close());

// ---------- createSku ----------

test('createSku calls repository and returns MerchandiseSku', function () {
    $dto = new SkuDTO(
        companyId: 1,
        name: 'A4 Paper',
        skuCode: 'SKU-001',
        unitPriceCents: 50000,
        stockQuantity: 100,
        description: null,
        category: 'Stationery',
        images: [],
        isActive: true,
    );

    $sku = new MerchandiseSku(['name' => 'A4 Paper', 'sku_code' => 'SKU-001', 'unit_price_cents' => 50000]);

    $this->repo->shouldReceive('create')->once()->with($dto)->andReturn($sku);

    $result = $this->service->createSku($dto);

    expect($result)->toBeInstanceOf(MerchandiseSku::class);
    expect($result->name)->toBe('A4 Paper');
});

// ---------- updateSku ----------

test('updateSku finds sku then calls repository update', function () {
    $sku = new MerchandiseSku(['id' => 1, 'name' => 'Old Name']);
    $dto = new SkuDTO(companyId: 1, name: 'New Name', skuCode: 'SKU-001', unitPriceCents: 60000, stockQuantity: 50);
    $updated = new MerchandiseSku(['id' => 1, 'name' => 'New Name']);

    $this->repo->shouldReceive('findOrFail')->once()->with(1)->andReturn($sku);
    $this->repo->shouldReceive('update')->once()->with($sku, $dto)->andReturn($updated);

    $result = $this->service->updateSku(1, $dto);

    expect($result->name)->toBe('New Name');
});

test('updateSku throws SkuNotFoundException when sku does not exist', function () {
    $dto = new SkuDTO(companyId: 1, name: 'X', skuCode: 'SKU-999', unitPriceCents: 100, stockQuantity: 0);
    $this->repo->shouldReceive('findOrFail')->once()->with(999)->andThrow(new SkuNotFoundException(999));

    expect(fn () => $this->service->updateSku(999, $dto))->toThrow(SkuNotFoundException::class);
});

// ---------- deleteSku ----------

test('deleteSku deactivates the sku when no active orders exist', function () {
    $sku = new MerchandiseSku(['id' => 1, 'is_active' => true]);

    $this->repo->shouldReceive('findOrFail')->once()->with(1)->andReturn($sku);
    $this->repo->shouldReceive('hasActiveOrders')->once()->with($sku)->andReturn(false);
    $this->repo->shouldReceive('deactivate')->once()->with($sku);

    $this->service->deleteSku(1);
});

test('deleteSku throws exception when sku has active orders', function () {
    $sku = new MerchandiseSku(['id' => 1]);

    $this->repo->shouldReceive('findOrFail')->once()->with(1)->andReturn($sku);
    $this->repo->shouldReceive('hasActiveOrders')->once()->with($sku)->andReturn(true);

    expect(fn () => $this->service->deleteSku(1))
        ->toThrow(SkuHasActiveOrdersException::class);
});

// ---------- listSkus ----------

test('listSkus returns paginator from repository', function () {
    $paginator = new LengthAwarePaginator([], 0, 15);
    $filters = ['category' => 'Stationery', 'is_active' => true];

    $this->repo->shouldReceive('list')->once()->with($filters)->andReturn($paginator);

    $result = $this->service->listSkus($filters);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

// ---------- findOrFail ----------

test('findOrFail returns sku when found', function () {
    $sku = new MerchandiseSku(['id' => 1, 'name' => 'Pen']);
    $this->repo->shouldReceive('findOrFail')->once()->with(1)->andReturn($sku);

    expect($this->service->findOrFail(1))->toBe($sku);
});

test('findOrFail throws SkuNotFoundException when not found', function () {
    $this->repo->shouldReceive('findOrFail')->once()->with(99)->andThrow(new SkuNotFoundException(99));

    expect(fn () => $this->service->findOrFail(99))->toThrow(SkuNotFoundException::class);
});

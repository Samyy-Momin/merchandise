<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\SkuDTO;
use App\Exceptions\SkuNotFoundException;
use App\Models\MerchandiseSku;
use App\Repositories\Interfaces\SkuRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SkuRepository implements SkuRepositoryInterface
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = MerchandiseSku::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $filters['is_active']);
        } else {
            $query->where('is_active', true);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('id', 'desc')->paginate(20);
    }

    public function findOrFail(int $id): MerchandiseSku
    {
        $sku = MerchandiseSku::find((int) $id);

        if ($sku === null) {
            throw new SkuNotFoundException($id);
        }

        return $sku;
    }

    public function create(SkuDTO $dto): MerchandiseSku
    {
        return MerchandiseSku::create([
            'company_id' => $dto->companyId,
            'sku_code' => $dto->skuCode,
            'name' => $dto->name,
            'description' => $dto->description,
            'category' => $dto->category,
            'unit_price_cents' => $dto->unitPriceCents,
            'stock_quantity' => $dto->stockQuantity,
            'images' => $dto->images,
            'is_active' => $dto->isActive,
        ]);
    }

    public function update(MerchandiseSku $sku, SkuDTO $dto): MerchandiseSku
    {
        $sku->update([
            'sku_code' => $dto->skuCode,
            'name' => $dto->name,
            'description' => $dto->description,
            'category' => $dto->category,
            'unit_price_cents' => $dto->unitPriceCents,
            'stock_quantity' => $dto->stockQuantity,
            'images' => $dto->images,
        ]);

        return $sku->fresh();
    }

    public function deactivate(MerchandiseSku $sku): void
    {
        $sku->update(['is_active' => false]);
    }

    public function hasActiveOrders(MerchandiseSku $sku): bool
    {
        return $sku->orderItems()
            ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['completed', 'rejected', 'cancelled']))
            ->exists();
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\DTOs\SkuDTO;
use App\Models\MerchandiseSku;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SkuRepositoryInterface
{
    public function list(array $filters): LengthAwarePaginator;

    public function findOrFail(int $id): MerchandiseSku;

    public function create(SkuDTO $dto): MerchandiseSku;

    public function update(MerchandiseSku $sku, SkuDTO $dto): MerchandiseSku;

    public function deactivate(MerchandiseSku $sku): void;

    public function hasActiveOrders(MerchandiseSku $sku): bool;
}

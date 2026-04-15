<?php

declare(strict_types=1);

namespace App\Services\Sku;

use App\DTOs\SkuDTO;
use App\Exceptions\SkuHasActiveOrdersException;
use App\Models\MerchandiseSku;
use App\Repositories\Interfaces\SkuRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SkuService implements SkuServiceInterface
{
    public function __construct(
        private readonly SkuRepositoryInterface $skuRepo,
    ) {}

    public function listSkus(array $filters): LengthAwarePaginator
    {
        return $this->skuRepo->list($filters);
    }

    public function findOrFail(int $id): MerchandiseSku
    {
        return $this->skuRepo->findOrFail($id);
    }

    public function createSku(SkuDTO $dto): MerchandiseSku
    {
        return $this->skuRepo->create($dto);
    }

    public function updateSku(int $id, SkuDTO $dto): MerchandiseSku
    {
        $sku = $this->skuRepo->findOrFail($id);

        return $this->skuRepo->update($sku, $dto);
    }

    public function deleteSku(int $id): void
    {
        $sku = $this->skuRepo->findOrFail($id);

        if ($this->skuRepo->hasActiveOrders($sku)) {
            throw new SkuHasActiveOrdersException($id);
        }

        $this->skuRepo->deactivate($sku);
    }
}

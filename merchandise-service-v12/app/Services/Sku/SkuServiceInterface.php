<?php

declare(strict_types=1);

namespace App\Services\Sku;

use App\DTOs\SkuDTO;
use App\Models\MerchandiseSku;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SkuServiceInterface
{
    public function listSkus(array $filters): LengthAwarePaginator;

    public function findOrFail(int $id): MerchandiseSku;

    public function createSku(SkuDTO $dto): MerchandiseSku;

    public function updateSku(int $id, SkuDTO $dto): MerchandiseSku;

    public function deleteSku(int $id): void;
}

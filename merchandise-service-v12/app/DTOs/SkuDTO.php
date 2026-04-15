<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class SkuDTO
{
    public function __construct(
        public int $companyId,
        public string $name,
        public string $skuCode,
        public int $unitPriceCents,
        public int $stockQuantity,
        public ?string $description = null,
        public ?string $category = null,
        public array $images = [],
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data, int $companyId): self
    {
        return new self(
            companyId: $companyId,
            name: $data['name'],
            skuCode: $data['sku_code'],
            unitPriceCents: $data['unit_price_cents'],
            stockQuantity: $data['stock_quantity'] ?? 0,
            description: $data['description'] ?? null,
            category: $data['category'] ?? null,
            images: $data['images'] ?? [],
            isActive: $data['is_active'] ?? true,
        );
    }
}

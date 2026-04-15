<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class OrderDTO
{
    /**
     * @param  OrderItemDTO[]  $items
     */
    public function __construct(
        public int $customerId,
        public int $companyId,
        public array $items,
        public string $orderKind = 'standard',
        public ?int $buyerStoreId = null,
        public ?int $vendorStoreId = null,
        public ?int $fulfillmentStoreId = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data, int $customerId, int $companyId): self
    {
        return new self(
            customerId: $customerId,
            companyId: $companyId,
            items: array_map(
                fn ($i) => new OrderItemDTO(skuId: $i['sku_id'], requestedQuantity: $i['quantity']),
                $data['items']
            ),
            orderKind: $data['order_kind'] ?? 'standard',
            buyerStoreId: $data['buyer_store_id'] ?? null,
            vendorStoreId: $data['vendor_store_id'] ?? null,
            fulfillmentStoreId: $data['fulfillment_store_id'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ApprovalDTO
{
    /**
     * @param  ApprovalItemDTO[]  $items
     */
    public function __construct(
        public int $staffId,
        public array $items,
    ) {}

    public static function fromArray(array $data, int $staffId): self
    {
        return new self(
            staffId: $staffId,
            items: array_map(
                fn ($i) => new ApprovalItemDTO(itemId: $i['item_id'], approvedQuantity: $i['approved_quantity']),
                $data['items'] ?? []
            ),
        );
    }
}

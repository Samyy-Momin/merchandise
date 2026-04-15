<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ApprovalItemDTO
{
    public function __construct(
        public int $itemId,
        public int $approvedQuantity,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class AcknowledgementDTO
{
    public function __construct(
        public int $customerId,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data, int $customerId): self
    {
        return new self(
            customerId: $customerId,
            notes: $data['notes'] ?? null,
        );
    }
}

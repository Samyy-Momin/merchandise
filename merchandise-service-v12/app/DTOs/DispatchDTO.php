<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class DispatchDTO
{
    public function __construct(
        public int $staffId,
        public ?string $courier = null,
        public ?string $trackingNumber = null,
        public ?string $estimatedDeliveryAt = null,
    ) {}

    public static function fromArray(array $data, int $staffId): self
    {
        return new self(
            staffId: $staffId,
            courier: $data['courier'] ?? null,
            trackingNumber: $data['tracking_number'] ?? null,
            estimatedDeliveryAt: $data['estimated_delivery_at'] ?? null,
        );
    }
}

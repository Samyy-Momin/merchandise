<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class InvoiceDTO
{
    public function __construct(
        public int $amountCents,
        public string $paymentMethod,
        public ?string $reference = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            amountCents: (int) $data['amount_cents'],
            paymentMethod: $data['payment_method'],
            reference: $data['reference'] ?? null,
        );
    }
}

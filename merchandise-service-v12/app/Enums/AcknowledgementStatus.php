<?php

declare(strict_types=1);

namespace App\Enums;

enum AcknowledgementStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    private const TRANSITIONS = [
        'pending' => ['approved', 'rejected'],
        'approved' => [],
        'rejected' => [],
    ];

    public function canTransitionTo(self $next): bool
    {
        return in_array($next->value, self::TRANSITIONS[$this->value] ?? [], true);
    }

    public function triggersInvoice(): bool
    {
        return $this === self::Approved;
    }
}

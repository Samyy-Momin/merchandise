<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case PaymentPending = 'payment_pending';
    case Paid = 'paid';
    case Overdue = 'overdue';

    private const TRANSITIONS = [
        'draft' => ['sent'],
        'sent' => ['payment_pending'],
        'payment_pending' => ['paid', 'overdue'],
        'paid' => [],
        'overdue' => ['paid'],
    ];

    public function canTransitionTo(self $next): bool
    {
        return in_array($next->value, self::TRANSITIONS[$this->value] ?? [], true);
    }

    public function isPending(): bool
    {
        return in_array($this, [self::PaymentPending, self::Overdue], true);
    }
}

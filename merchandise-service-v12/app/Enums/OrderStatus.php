<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Submitted = 'submitted';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case PartiallyApproved = 'partially_approved';
    case Rejected = 'rejected';
    case Processing = 'processing';
    case Dispatched = 'dispatched';
    case Acknowledged = 'acknowledged';
    case InvoiceGenerated = 'invoice_generated';
    case PaymentPending = 'payment_pending';
    case Completed = 'completed';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    private const TRANSITIONS = [
        'submitted' => ['pending_approval', 'cancelled'],
        'pending_approval' => ['approved', 'partially_approved', 'rejected'],
        'approved' => ['processing'],
        'partially_approved' => ['processing'],
        'processing' => ['dispatched'],
        'dispatched' => ['acknowledged'],
        'acknowledged' => ['invoice_generated', 'dispatched'],  // dispatched = vendor rejection revert
        'invoice_generated' => ['payment_pending'],
        'payment_pending' => ['completed', 'overdue'],
        'overdue' => ['completed'],
        'rejected' => [],
        'completed' => [],
        'cancelled' => [],
    ];

    public function canTransitionTo(self $next): bool
    {
        return in_array($next->value, self::TRANSITIONS[$this->value] ?? [], true);
    }
}

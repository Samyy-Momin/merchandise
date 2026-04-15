<?php

declare(strict_types=1);

use App\Enums\OrderStatus;

test('has all BRD-required status cases', function () {
    expect(OrderStatus::Submitted->value)->toBe('submitted');
    expect(OrderStatus::PendingApproval->value)->toBe('pending_approval');
    expect(OrderStatus::Approved->value)->toBe('approved');
    expect(OrderStatus::PartiallyApproved->value)->toBe('partially_approved');
    expect(OrderStatus::Rejected->value)->toBe('rejected');
    expect(OrderStatus::Processing->value)->toBe('processing');
    expect(OrderStatus::Dispatched->value)->toBe('dispatched');
    expect(OrderStatus::Acknowledged->value)->toBe('acknowledged');
    expect(OrderStatus::InvoiceGenerated->value)->toBe('invoice_generated');
    expect(OrderStatus::PaymentPending->value)->toBe('payment_pending');
    expect(OrderStatus::Completed->value)->toBe('completed');
    expect(OrderStatus::Overdue->value)->toBe('overdue');
    expect(OrderStatus::Cancelled->value)->toBe('cancelled');
});

test('has exactly 13 cases', function () {
    expect(OrderStatus::cases())->toHaveCount(13);
});

// ---------- Valid forward transitions ----------

test('submitted can transition to pending_approval', function () {
    expect(OrderStatus::Submitted->canTransitionTo(OrderStatus::PendingApproval))->toBeTrue();
});

test('submitted can transition to cancelled', function () {
    expect(OrderStatus::Submitted->canTransitionTo(OrderStatus::Cancelled))->toBeTrue();
});

test('pending_approval can transition to approved', function () {
    expect(OrderStatus::PendingApproval->canTransitionTo(OrderStatus::Approved))->toBeTrue();
});

test('pending_approval can transition to partially_approved', function () {
    expect(OrderStatus::PendingApproval->canTransitionTo(OrderStatus::PartiallyApproved))->toBeTrue();
});

test('pending_approval can transition to rejected', function () {
    expect(OrderStatus::PendingApproval->canTransitionTo(OrderStatus::Rejected))->toBeTrue();
});

test('approved can transition to processing', function () {
    expect(OrderStatus::Approved->canTransitionTo(OrderStatus::Processing))->toBeTrue();
});

test('partially_approved can transition to processing', function () {
    expect(OrderStatus::PartiallyApproved->canTransitionTo(OrderStatus::Processing))->toBeTrue();
});

test('processing can transition to dispatched', function () {
    expect(OrderStatus::Processing->canTransitionTo(OrderStatus::Dispatched))->toBeTrue();
});

test('dispatched can transition to acknowledged', function () {
    expect(OrderStatus::Dispatched->canTransitionTo(OrderStatus::Acknowledged))->toBeTrue();
});

test('acknowledged can transition to invoice_generated', function () {
    expect(OrderStatus::Acknowledged->canTransitionTo(OrderStatus::InvoiceGenerated))->toBeTrue();
});

test('acknowledged can revert to dispatched when vendor rejects acknowledgement', function () {
    expect(OrderStatus::Acknowledged->canTransitionTo(OrderStatus::Dispatched))->toBeTrue();
});

test('invoice_generated can transition to payment_pending', function () {
    expect(OrderStatus::InvoiceGenerated->canTransitionTo(OrderStatus::PaymentPending))->toBeTrue();
});

test('payment_pending can transition to completed', function () {
    expect(OrderStatus::PaymentPending->canTransitionTo(OrderStatus::Completed))->toBeTrue();
});

test('payment_pending can transition to overdue', function () {
    expect(OrderStatus::PaymentPending->canTransitionTo(OrderStatus::Overdue))->toBeTrue();
});

// ---------- Invalid transitions ----------

test('completed cannot transition to any state', function () {
    foreach (OrderStatus::cases() as $target) {
        expect(OrderStatus::Completed->canTransitionTo($target))->toBeFalse(
            "Completed should not be able to transition to {$target->value}"
        );
    }
});

test('rejected cannot transition to any state', function () {
    foreach (OrderStatus::cases() as $target) {
        expect(OrderStatus::Rejected->canTransitionTo($target))->toBeFalse(
            "Rejected should not be able to transition to {$target->value}"
        );
    }
});

test('cancelled cannot transition to any state', function () {
    foreach (OrderStatus::cases() as $target) {
        expect(OrderStatus::Cancelled->canTransitionTo($target))->toBeFalse(
            "Cancelled should not be able to transition to {$target->value}"
        );
    }
});

test('dispatched cannot skip acknowledgement and go directly to invoice_generated', function () {
    expect(OrderStatus::Dispatched->canTransitionTo(OrderStatus::InvoiceGenerated))->toBeFalse();
});

test('approved cannot skip processing and go directly to dispatched', function () {
    expect(OrderStatus::Approved->canTransitionTo(OrderStatus::Dispatched))->toBeFalse();
});

test('can be created from string value', function () {
    expect(OrderStatus::from('submitted'))->toBe(OrderStatus::Submitted);
    expect(OrderStatus::from('pending_approval'))->toBe(OrderStatus::PendingApproval);
    expect(OrderStatus::from('completed'))->toBe(OrderStatus::Completed);
});

test('tryFrom returns null for unknown value', function () {
    expect(OrderStatus::tryFrom('unknown_status'))->toBeNull();
});

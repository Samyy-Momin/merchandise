<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;

test('has all required cases', function () {
    expect(InvoiceStatus::Draft->value)->toBe('draft');
    expect(InvoiceStatus::Sent->value)->toBe('sent');
    expect(InvoiceStatus::PaymentPending->value)->toBe('payment_pending');
    expect(InvoiceStatus::Paid->value)->toBe('paid');
    expect(InvoiceStatus::Overdue->value)->toBe('overdue');
});

test('has exactly 5 cases', function () {
    expect(InvoiceStatus::cases())->toHaveCount(5);
});

test('draft can transition to sent', function () {
    expect(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Sent))->toBeTrue();
});

test('sent can transition to payment_pending', function () {
    expect(InvoiceStatus::Sent->canTransitionTo(InvoiceStatus::PaymentPending))->toBeTrue();
});

test('payment_pending can transition to paid', function () {
    expect(InvoiceStatus::PaymentPending->canTransitionTo(InvoiceStatus::Paid))->toBeTrue();
});

test('payment_pending can transition to overdue', function () {
    expect(InvoiceStatus::PaymentPending->canTransitionTo(InvoiceStatus::Overdue))->toBeTrue();
});

test('overdue can transition to paid when payment is eventually received', function () {
    expect(InvoiceStatus::Overdue->canTransitionTo(InvoiceStatus::Paid))->toBeTrue();
});

test('paid cannot transition further', function () {
    foreach (InvoiceStatus::cases() as $target) {
        expect(InvoiceStatus::Paid->canTransitionTo($target))->toBeFalse(
            "Paid invoice should not transition to {$target->value}"
        );
    }
});

test('isPending returns true only for payment_pending and overdue', function () {
    expect(InvoiceStatus::PaymentPending->isPending())->toBeTrue();
    expect(InvoiceStatus::Overdue->isPending())->toBeTrue();
    expect(InvoiceStatus::Draft->isPending())->toBeFalse();
    expect(InvoiceStatus::Sent->isPending())->toBeFalse();
    expect(InvoiceStatus::Paid->isPending())->toBeFalse();
});

test('can be created from string value', function () {
    expect(InvoiceStatus::from('draft'))->toBe(InvoiceStatus::Draft);
    expect(InvoiceStatus::from('paid'))->toBe(InvoiceStatus::Paid);
    expect(InvoiceStatus::from('overdue'))->toBe(InvoiceStatus::Overdue);
});

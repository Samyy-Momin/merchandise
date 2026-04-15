<?php

declare(strict_types=1);

use App\Enums\AcknowledgementStatus;

test('has all required cases', function () {
    expect(AcknowledgementStatus::Pending->value)->toBe('pending');
    expect(AcknowledgementStatus::Approved->value)->toBe('approved');
    expect(AcknowledgementStatus::Rejected->value)->toBe('rejected');
});

test('has exactly 3 cases', function () {
    expect(AcknowledgementStatus::cases())->toHaveCount(3);
});

test('pending can transition to approved', function () {
    expect(AcknowledgementStatus::Pending->canTransitionTo(AcknowledgementStatus::Approved))->toBeTrue();
});

test('pending can transition to rejected', function () {
    expect(AcknowledgementStatus::Pending->canTransitionTo(AcknowledgementStatus::Rejected))->toBeTrue();
});

test('approved cannot transition further', function () {
    foreach (AcknowledgementStatus::cases() as $target) {
        expect(AcknowledgementStatus::Approved->canTransitionTo($target))->toBeFalse(
            "Approved acknowledgement should not transition to {$target->value}"
        );
    }
});

test('rejected cannot transition further', function () {
    foreach (AcknowledgementStatus::cases() as $target) {
        expect(AcknowledgementStatus::Rejected->canTransitionTo($target))->toBeFalse(
            "Rejected acknowledgement should not transition to {$target->value}"
        );
    }
});

test('approved is the only state that triggers invoice generation', function () {
    expect(AcknowledgementStatus::Approved->triggersInvoice())->toBeTrue();
    expect(AcknowledgementStatus::Pending->triggersInvoice())->toBeFalse();
    expect(AcknowledgementStatus::Rejected->triggersInvoice())->toBeFalse();
});

test('can be created from string value', function () {
    expect(AcknowledgementStatus::from('pending'))->toBe(AcknowledgementStatus::Pending);
    expect(AcknowledgementStatus::from('approved'))->toBe(AcknowledgementStatus::Approved);
    expect(AcknowledgementStatus::from('rejected'))->toBe(AcknowledgementStatus::Rejected);
});

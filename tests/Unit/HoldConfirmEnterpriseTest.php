<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Admin\HoldConfirmEnterprise;

beforeEach(function (): void {
    HoldConfirmEnterprise::requiresAuditReason(false);
    HoldConfirmEnterprise::bulkHoldMs(null);
});

it('exposes bulk hold default and override helpers', function () {
    expect(HoldConfirmEnterprise::bulkHoldMs())
        ->toBe(HoldConfirmEnterprise::DEFAULT_BULK_HOLD_MS)
        ->and(HoldConfirmEnterprise::bulkHoldMs(4200))
        ->toBe(4200)
        ->and(HoldConfirmEnterprise::bulkHoldMs())
        ->toBe(4200);
});

it('exposes audit reason requirement toggle', function () {
    expect(HoldConfirmEnterprise::requiresAuditReason())->toBeFalse();

    HoldConfirmEnterprise::requiresAuditReason(true);

    expect(HoldConfirmEnterprise::requiresAuditReason())->toBeTrue();
});

it('documents keyboard contract constants for hold confirm enterprise', function () {
    expect(HoldConfirmEnterprise::keyboardContract())->toMatchArray([
        'hold' => HoldConfirmEnterprise::KEYBOARD_HOLD,
        'activate' => HoldConfirmEnterprise::KEYBOARD_ACTIVATE,
        'cancel' => HoldConfirmEnterprise::KEYBOARD_CANCEL,
    ])
        ->and(HoldConfirmEnterprise::KEYBOARD_HOLD)->toBe(' ')
        ->and(HoldConfirmEnterprise::KEYBOARD_CANCEL)->toBe('Escape')
        ->and(HoldConfirmEnterprise::EVENT_HOLD_COMPLETE)->toBe('fff-hold-complete');
});

it('documents host data attribute names for audit and bulk holds', function () {
    expect(HoldConfirmEnterprise::DATA_AUDIT_REASON_REQUIRED)->toBe('data-fff-audit-reason-required')
        ->and(HoldConfirmEnterprise::DATA_BULK_HOLD_MS)->toBe('data-fff-bulk-hold-ms');
});

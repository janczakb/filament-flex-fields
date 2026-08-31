<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Admin;

final class HoldConfirmEnterprise
{
    /** Default hold duration for bulk destructive table actions (ms). */
    public const int DEFAULT_BULK_HOLD_MS = 3500;

    /** Keyboard: start / continue hold while focused. */
    public const string KEYBOARD_HOLD = ' ';

    /** Keyboard: alternate activate key (same contract as hold). */
    public const string KEYBOARD_ACTIVATE = 'Enter';

    /** Keyboard: cancel in-progress hold without firing the action. */
    public const string KEYBOARD_CANCEL = 'Escape';

    /** Host attribute when audit reason textarea must be filled before complete. */
    public const string DATA_AUDIT_REASON_REQUIRED = 'data-fff-audit-reason-required';

    /** Host attribute override for bulk hold duration (ms). */
    public const string DATA_BULK_HOLD_MS = 'data-fff-bulk-hold-ms';

    /** Alpine / DOM event fired when hold progress reaches 100%. */
    public const string EVENT_HOLD_COMPLETE = 'fff-hold-complete';

    private static bool $requiresAuditReason = false;

    private static ?int $bulkHoldMs = null;

    /**
     * Get or set the global default for requiring an audit reason on destructive holds.
     */
    public static function requiresAuditReason(?bool $required = null): bool
    {
        if ($required !== null) {
            self::$requiresAuditReason = $required;
        }

        return self::$requiresAuditReason;
    }

    /**
     * Resolved bulk hold duration in milliseconds (per-action overrides come later).
     */
    public static function bulkHoldMs(?int $milliseconds = null): int
    {
        if (func_num_args() === 1) {
            self::$bulkHoldMs = $milliseconds;
        }

        return self::$bulkHoldMs ?? self::DEFAULT_BULK_HOLD_MS;
    }

    /**
     * @return array{
     *     hold: string,
     *     activate: string,
     *     cancel: string,
     * }
     */
    public static function keyboardContract(): array
    {
        return [
            'hold' => self::KEYBOARD_HOLD,
            'activate' => self::KEYBOARD_ACTIVATE,
            'cancel' => self::KEYBOARD_CANCEL,
        ];
    }
}

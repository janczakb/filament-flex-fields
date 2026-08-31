<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\DateTime;

/**
 * Canonical ISO-style storage formats for Flex Fields date/time values.
 *
 * Stored values are locale-neutral strings suitable for JSON, databases, and APIs.
 * Display formatting is handled separately via {@see DateTimeFieldValue::resolveDisplayFormat()}.
 */
interface DateTimeStorageContract
{
    /** ISO 8601 calendar date: `YYYY-MM-DD` (e.g. `2026-06-15`). */
    public const DATE = 'Y-m-d';

    /** ISO 8601 year-month: `YYYY-MM` (e.g. `2026-06`). */
    public const MONTH = 'Y-m';

    /** ISO 8601 year: `YYYY` (e.g. `2026`). */
    public const YEAR = 'Y';

    /** 24-hour time without seconds: `HH:MM` (e.g. `09:30`). */
    public const TIME = 'H:i';

    /** 24-hour time with seconds: `HH:MM:SS` (e.g. `09:30:00`). */
    public const TIME_WITH_SECONDS = 'H:i:s';

    /** ISO 8601 local date-time (minute precision): `YYYY-MM-DDTHH:MM:00`. */
    public const DATE_TIME_MINUTE = 'Y-m-d\TH:i:00';

    /** ISO 8601 local date-time (second precision): `YYYY-MM-DDTHH:MM:SS`. */
    public const DATE_TIME_SECOND = 'Y-m-d\TH:i:s';

    /** ISO 8601 local date-time (hour precision): `YYYY-MM-DDTHH:00:00`. */
    public const DATE_TIME_HOUR = 'Y-m-d\TH:00:00';

    /** Schedule slot time (always `HH:MM`, 24-hour, no timezone offset). */
    public const SCHEDULE_TIME = 'H:i';
}

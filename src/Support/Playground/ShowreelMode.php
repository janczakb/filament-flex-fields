<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support\Playground;

/**
 * Curated playground hub order for post-upgrade spot-checks (`V3AutoUpgrade::playgroundHubChecklist()`).
 */
final class ShowreelMode
{
    public const TOUR_DURATION_SECONDS = 60;

    /**
     * Playground slugs to spot-check after a v3 upgrade.
     *
     * @return list<string>
     */
    public static function hubOrder(): array
    {
        return [
            'schema-conditions',
            'field-intelligence',
            'composition-recipes',
            'select-field',
            'phone-field',
            'country-field',
            'date-time-fields',
            'schedule-field',
            'barcode-scanner-field',
            'choice-cards',
            'segment-tabs',
            'form-layouts',
            'admin-columns',
            'hold-confirm',
            'user-column',
        ];
    }

    public static function tourDurationSeconds(): int
    {
        return self::TOUR_DURATION_SECONDS;
    }

    /**
     * Suggested dwell time per hub when auto-advancing the showreel.
     */
    public static function secondsPerHub(): int
    {
        $count = count(self::hubOrder());

        if ($count === 0) {
            return self::TOUR_DURATION_SECONDS;
        }

        return (int) floor(self::TOUR_DURATION_SECONDS / $count);
    }
}

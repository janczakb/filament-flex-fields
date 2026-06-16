<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schedule;

final class ScheduleDays
{
    /** @var list<string> */
    public const ALL = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /** @var list<string> */
    public const WEEKDAYS = ['mon', 'tue', 'wed', 'thu', 'fri'];

    /**
     * @param  list<string>  $days
     * @return list<string>
     */
    public static function normalize(array $days): array
    {
        $normalized = [];

        foreach ($days as $day) {
            $day = strtolower(trim((string) $day));

            if ($day === '' || ! in_array($day, self::ALL, true)) {
                continue;
            }

            if (! in_array($day, $normalized, true)) {
                $normalized[] = $day;
            }
        }

        return $normalized === [] ? self::ALL : $normalized;
    }

    /**
     * @param  list<string>  $days
     * @return list<string>
     */
    public static function onlyValidDays(array $days): array
    {
        $normalized = [];

        foreach ($days as $day) {
            $day = strtolower(trim((string) $day));

            if ($day === '' || ! in_array($day, self::ALL, true)) {
                continue;
            }

            if (! in_array($day, $normalized, true)) {
                $normalized[] = $day;
            }
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $days
     * @return list<string>
     */
    public static function normalizeWorkdays(array $days): array
    {
        $normalized = [];

        foreach ($days as $day) {
            $day = strtolower(trim((string) $day));

            if ($day === '' || ! in_array($day, self::ALL, true)) {
                continue;
            }

            if (! in_array($day, $normalized, true)) {
                $normalized[] = $day;
            }
        }

        return $normalized !== [] ? $normalized : self::WEEKDAYS;
    }

    public static function isWeekday(string $day): bool
    {
        return in_array(strtolower($day), self::WEEKDAYS, true);
    }
}

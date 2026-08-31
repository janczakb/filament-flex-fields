<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\DateTime;

use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleDays;

final class ScheduleV2
{
    private const MINUTES_PER_DAY = 1440;

    /**
     * @param  array<string, mixed>  $slot
     */
    public static function slotIsOvernight(array $slot): bool
    {
        return (bool) ($slot['overnight'] ?? false);
    }

    /**
     * @param  list<array{from: string, to: string, overnight?: bool}>  $intervals
     */
    public static function validateNoOverlap(array $intervals): bool
    {
        $segments = self::expandIntervals($intervals);

        if (count($segments) < 2) {
            return true;
        }

        usort($segments, fn (array $left, array $right): int => $left['start'] <=> $right['start']);

        for ($index = 1; $index < count($segments); $index++) {
            if ($segments[$index]['start'] < $segments[$index - 1]['end']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{timezone?: string, days: array<string, array{enabled: bool, slots: list<array<string, mixed>>}>}  $schedule
     * @return array{timezone?: string, days: array<string, array{enabled: bool, slots: list<array<string, mixed>>}>}
     */
    public static function copyDay(array $schedule, string $fromDay, string $toDay): array
    {
        $fromDay = strtolower(trim($fromDay));
        $toDay = strtolower(trim($toDay));

        if (! in_array($fromDay, ScheduleDays::ALL, true) || ! in_array($toDay, ScheduleDays::ALL, true)) {
            return $schedule;
        }

        $days = is_array($schedule['days'] ?? null) ? $schedule['days'] : [];

        if (! is_array($days[$fromDay] ?? null)) {
            return $schedule;
        }

        /** @var array{enabled: bool, slots: list<array<string, mixed>>} $source */
        $source = $days[$fromDay];

        $days[$toDay] = [
            'enabled' => (bool) ($source['enabled'] ?? false),
            'slots' => array_map(
                static fn (array $slot): array => $slot,
                is_array($source['slots'] ?? null) ? $source['slots'] : [],
            ),
        ];

        return [
            ...$schedule,
            'days' => $days,
        ];
    }

    /**
     * @param  list<array{from: string, to: string, overnight?: bool}>  $intervals
     * @return list<array{start: int, end: int}>
     */
    private static function expandIntervals(array $intervals): array
    {
        $segments = [];

        foreach ($intervals as $interval) {
            $from = self::timeToMinutes($interval['from'] ?? '');
            $to = self::timeToMinutes($interval['to'] ?? '');

            if ($from === null || $to === null) {
                continue;
            }

            $overnight = (bool) ($interval['overnight'] ?? false) || $from >= $to;

            if ($overnight) {
                if ($from < self::MINUTES_PER_DAY) {
                    $segments[] = ['start' => $from, 'end' => self::MINUTES_PER_DAY];
                }

                if ($to > 0) {
                    $segments[] = ['start' => 0, 'end' => $to];
                }

                continue;
            }

            if ($from < $to) {
                $segments[] = ['start' => $from, 'end' => $to];
            }
        }

        return $segments;
    }

    private static function timeToMinutes(string $time): ?int
    {
        $normalized = DateTimeOs::normalizeTime($time);

        if ($normalized === null) {
            return null;
        }

        [$hours, $minutes] = array_map(intval(...), explode(':', $normalized));

        return ($hours * 60) + $minutes;
    }
}

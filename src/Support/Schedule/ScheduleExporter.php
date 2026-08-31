<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schedule;

use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeOs;

final class ScheduleExporter
{
    /**
     * @param  array{timezone?: string, days: array<string, array{enabled: bool, slots: list<array<string, mixed>>}>}  $schedule
     * @param  list<string>  $days
     * @return array{
     *     timezone: string,
     *     days: array<string, array{enabled: bool, slots: list<array<string, mixed>>}>,
     *     opening_hours: list<array{day: string, enabled: bool, slots: list<array<string, mixed>>}>
     * }
     */
    public function toApi(array $schedule, array $days = ScheduleDays::ALL): array
    {
        $normalized = (new ScheduleNormalizer)->normalize($schedule, $days, $schedule['timezone'] ?? null);
        $timezone = is_string($normalized['timezone'] ?? null) ? $normalized['timezone'] : 'UTC';

        $openingHours = [];

        foreach (ScheduleDays::normalize($days) as $day) {
            $dayState = $normalized['days'][$day] ?? ['enabled' => false, 'slots' => []];

            $openingHours[] = [
                'day' => $day,
                'enabled' => (bool) ($dayState['enabled'] ?? false),
                'slots' => is_array($dayState['slots'] ?? null) ? $dayState['slots'] : [],
            ];
        }

        return [
            'timezone' => $timezone,
            'days' => $normalized['days'],
            'opening_hours' => $openingHours,
        ];
    }

    /**
     * @param  array{timezone?: string, days: array<string, array{enabled: bool, slots: list<array<string, mixed>>}>}  $schedule
     * @param  list<string>  $days
     * @return list<array{day: string, label: string, periods: list<array{from: string, to: string, overnight?: bool, type?: string}>}>
     */
    public function toOpeningHoursSummary(array $schedule, array $days = ScheduleDays::ALL): array
    {
        $api = $this->toApi($schedule, $days);
        $summary = [];

        foreach ($api['opening_hours'] as $entry) {
            $day = $entry['day'];

            if (! ($entry['enabled'] ?? false)) {
                $summary[] = [
                    'day' => $day,
                    'label' => __("filament-flex-fields::default.schedule.days.{$day}"),
                    'periods' => [],
                ];

                continue;
            }

            $periods = [];

            foreach ($entry['slots'] as $slot) {
                if (! is_array($slot)) {
                    continue;
                }

                $from = DateTimeOs::normalizeTime($slot['from'] ?? null);
                $to = DateTimeOs::normalizeTime($slot['to'] ?? null);

                if ($from === null || $to === null) {
                    continue;
                }

                $period = [
                    'from' => $from,
                    'to' => $to,
                    'type' => ($slot['type'] ?? 'slot') === 'break' ? 'break' : 'slot',
                ];

                if ((bool) ($slot['overnight'] ?? false)) {
                    $period['overnight'] = true;
                }

                $periods[] = $period;
            }

            $summary[] = [
                'day' => $day,
                'label' => __("filament-flex-fields::default.schedule.days.{$day}"),
                'periods' => $periods,
            ];
        }

        return $summary;
    }
}

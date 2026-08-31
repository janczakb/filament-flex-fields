<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schedule;

final class ScheduleChangeAuditor
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $days
     * @return list<array{
     *     type: string,
     *     day?: string,
     *     before?: mixed,
     *     after?: mixed,
     *     message: string
     * }>
     */
    public function diff(array $before, array $after, array $days = ScheduleDays::ALL): array
    {
        $normalizer = new ScheduleNormalizer;
        $days = ScheduleDays::normalize($days);
        $beforeNormalized = $normalizer->normalize($before, $days, $before['timezone'] ?? null);
        $afterNormalized = $normalizer->normalize($after, $days, $after['timezone'] ?? null);
        $changes = [];

        $beforeTimezone = $beforeNormalized['timezone'] ?? null;
        $afterTimezone = $afterNormalized['timezone'] ?? null;

        if ($beforeTimezone !== $afterTimezone) {
            $changes[] = [
                'type' => 'timezone',
                'before' => $beforeTimezone,
                'after' => $afterTimezone,
                'message' => __('filament-flex-fields::default.schedule.audit.timezone_changed', [
                    'from' => $beforeTimezone ?? '—',
                    'to' => $afterTimezone ?? '—',
                ]),
            ];
        }

        foreach ($days as $day) {
            $beforeDay = $beforeNormalized['days'][$day] ?? ['enabled' => false, 'slots' => []];
            $afterDay = $afterNormalized['days'][$day] ?? ['enabled' => false, 'slots' => []];

            if ($this->dayStatesEqual($beforeDay, $afterDay)) {
                continue;
            }

            $dayLabel = __("filament-flex-fields::default.schedule.days.{$day}");
            $type = $this->resolveDayChangeType($beforeDay, $afterDay);

            $changes[] = [
                'type' => $type,
                'day' => $day,
                'before' => $beforeDay,
                'after' => $afterDay,
                'message' => match ($type) {
                    'day_enabled' => __('filament-flex-fields::default.schedule.audit.day_enabled', ['day' => $dayLabel]),
                    'day_disabled' => __('filament-flex-fields::default.schedule.audit.day_disabled', ['day' => $dayLabel]),
                    default => __('filament-flex-fields::default.schedule.audit.day_slots_changed', ['day' => $dayLabel]),
                },
            ];
        }

        return $changes;
    }

    /**
     * @param  list<array{type: string, day?: string, before?: mixed, after?: mixed, message: string}>  $changes
     * @return list<string>
     */
    public function describe(array $changes): array
    {
        return array_values(array_map(
            static fn (array $change): string => (string) ($change['message'] ?? ''),
            $changes,
        ));
    }

    /**
     * @param  array{enabled: bool, slots: list<array<string, mixed>>}  $before
     * @param  array{enabled: bool, slots: list<array<string, mixed>>}  $after
     */
    private function resolveDayChangeType(array $before, array $after): string
    {
        $beforeEnabled = (bool) ($before['enabled'] ?? false);
        $afterEnabled = (bool) ($after['enabled'] ?? false);

        if (! $beforeEnabled && $afterEnabled) {
            return 'day_enabled';
        }

        if ($beforeEnabled && ! $afterEnabled) {
            return 'day_disabled';
        }

        return 'day_slots_changed';
    }

    /**
     * @param  array{enabled: bool, slots: list<array<string, mixed>>}  $left
     * @param  array{enabled: bool, slots: list<array<string, mixed>>}  $right
     */
    private function dayStatesEqual(array $left, array $right): bool
    {
        return json_encode($left) === json_encode($right);
    }
}

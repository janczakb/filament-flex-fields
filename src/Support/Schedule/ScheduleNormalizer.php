<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schedule;

final class ScheduleNormalizer
{
    /**
     * @param  list<string>  $days
     * @return array{timezone?: string, days: array<string, array{enabled: bool, slots: list<array{from: string, to: string}>}>}
     */
    public function normalize(mixed $state, array $days, ?string $defaultTimezone = null): array
    {
        $days = ScheduleDays::normalize($days);
        $normalized = [
            'days' => [],
        ];

        if ($defaultTimezone !== null) {
            $normalized['timezone'] = $defaultTimezone;
        }

        if (! is_array($state)) {
            foreach ($days as $day) {
                $normalized['days'][$day] = $this->emptyDay();
            }

            return $normalized;
        }

        if (isset($state['timezone']) && is_string($state['timezone']) && trim($state['timezone']) !== '') {
            $normalized['timezone'] = trim($state['timezone']);
        }

        $dayStates = is_array($state['days'] ?? null) ? $state['days'] : [];

        foreach ($days as $day) {
            $dayState = is_array($dayStates[$day] ?? null) ? $dayStates[$day] : [];
            $normalized['days'][$day] = $this->normalizeDay($dayState);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $dayState
     * @return array{enabled: bool, slots: list<array{from: string, to: string, type: string}>}
     */
    public function normalizeDay(array $dayState): array
    {
        $enabled = (bool) ($dayState['enabled'] ?? false);
        $slots = [];

        if (is_array($dayState['slots'] ?? null)) {
            foreach ($dayState['slots'] as $slot) {
                if (! is_array($slot)) {
                    continue;
                }

                $from = $this->normalizeTime($slot['from'] ?? null);
                $to = $this->normalizeTime($slot['to'] ?? null);

                if ($from === null || $to === null) {
                    continue;
                }

                $slots[] = [
                    'from' => $from,
                    'to' => $to,
                    'type' => ($slot['type'] ?? 'slot') === 'break' ? 'break' : 'slot',
                ];
            }
        }

        return [
            'enabled' => $enabled,
            'slots' => $slots,
        ];
    }

    /**
     * @return array{enabled: bool, slots: list<array{from: string, to: string, type: string}>}
     */
    public function emptyDay(): array
    {
        return [
            'enabled' => false,
            'slots' => [],
        ];
    }

    public function normalizeTime(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $time = trim((string) $value);

        if ($time === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches) !== 1) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}

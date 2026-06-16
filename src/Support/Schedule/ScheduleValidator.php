<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schedule;

use Closure;

final class ScheduleValidator
{
    public function __construct(
        private readonly int $minSlots = 1,
        private readonly int $maxSlots = 10,
        private readonly bool $requireSlotsForEnabledDays = true,
    ) {}

    /**
     * @param  list<string>  $days
     * @param  list<string>|null  $allowedTimezones
     */
    public function validate(mixed $state, array $days, Closure $fail, ?string $timezone = null, ?array $allowedTimezones = null): void
    {
        if (! is_array($state)) {
            $fail(__('filament-flex-fields::default.schedule.validation.invalid'));

            return;
        }

        $days = ScheduleDays::normalize($days);
        $dayStates = is_array($state['days'] ?? null) ? $state['days'] : [];

        if ($timezone !== null) {
            $submittedTimezone = $state['timezone'] ?? null;

            if (! is_string($submittedTimezone) || trim($submittedTimezone) === '') {
                $fail(__('filament-flex-fields::default.schedule.validation.timezone_required'));

                return;
            }

            if (is_array($allowedTimezones) && ! in_array(trim($submittedTimezone), $allowedTimezones, true)) {
                $fail(__('validation.in', ['attribute' => __('filament-flex-fields::default.schedule.timezone')]));

                return;
            }
        }

        foreach ($days as $day) {
            $dayState = is_array($dayStates[$day] ?? null) ? $dayStates[$day] : null;

            if ($dayState === null) {
                $fail(__('filament-flex-fields::default.schedule.validation.missing_day', [
                    'day' => __("filament-flex-fields::default.schedule.days.{$day}"),
                ]));

                return;
            }

            $this->validateDay($day, $dayState, $fail);
        }
    }

    /**
     * @param  array<string, mixed>  $dayState
     */
    public function validateDay(string $day, array $dayState, Closure $fail): void
    {
        $enabled = (bool) ($dayState['enabled'] ?? false);
        $slots = is_array($dayState['slots'] ?? null) ? $dayState['slots'] : [];
        $dayLabel = __("filament-flex-fields::default.schedule.days.{$day}");
        $normalizer = new ScheduleNormalizer;

        if (! $enabled) {
            return;
        }

        $normalizedSlots = [];

        foreach ($slots as $index => $slot) {
            if (! is_array($slot)) {
                $fail(__('filament-flex-fields::default.schedule.validation.invalid_slot', [
                    'day' => $dayLabel,
                    'slot' => $index + 1,
                ]));

                return;
            }

            $from = $normalizer->normalizeTime($slot['from'] ?? null);
            $to = $normalizer->normalizeTime($slot['to'] ?? null);

            if ($from === null || $to === null) {
                $fail(__('filament-flex-fields::default.schedule.validation.invalid_time', [
                    'day' => $dayLabel,
                    'slot' => $index + 1,
                ]));

                return;
            }

            if ($this->timeToMinutes($from) >= $this->timeToMinutes($to)) {
                $fail(__('filament-flex-fields::default.schedule.validation.from_before_to', [
                    'day' => $dayLabel,
                    'slot' => $index + 1,
                ]));

                return;
            }

            $normalizedSlots[] = [
                'from' => $from,
                'to' => $to,
            ];
        }

        if ($this->requireSlotsForEnabledDays && count($normalizedSlots) < $this->minSlots) {
            $fail(__('filament-flex-fields::default.schedule.validation.min_slots', [
                'day' => $dayLabel,
                'count' => $this->minSlots,
            ]));

            return;
        }

        if (count($normalizedSlots) > $this->maxSlots) {
            $fail(__('filament-flex-fields::default.schedule.validation.max_slots', [
                'day' => $dayLabel,
                'count' => $this->maxSlots,
            ]));

            return;
        }

        if ($this->slotsOverlap($normalizedSlots)) {
            $fail(__('filament-flex-fields::default.schedule.validation.overlap', [
                'day' => $dayLabel,
            ]));
        }
    }

    /**
     * @param  list<array{from: string, to: string}>  $slots
     */
    public function slotsOverlap(array $slots): bool
    {
        if (count($slots) < 2) {
            return false;
        }

        usort($slots, fn (array $left, array $right): int => $this->timeToMinutes($left['from']) <=> $this->timeToMinutes($right['from']));

        for ($index = 1; $index < count($slots); $index++) {
            if ($this->timeToMinutes($slots[$index]['from']) < $this->timeToMinutes($slots[$index - 1]['to'])) {
                return true;
            }
        }

        return false;
    }

    public function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map(intval(...), explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}

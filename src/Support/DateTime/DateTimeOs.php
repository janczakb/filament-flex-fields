<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\DateTime;

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;
use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleDays;
use Carbon\CarbonInterface;

final class DateTimeOs implements DateTimeStorageContract
{
    public static function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format(self::DATE);
        }

        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $normalizer = new DateTimeFieldValue(
            DateTimeFieldMode::Date,
            DateTimeGranularity::Day,
            false,
            self::DATE,
        );

        return $normalizer->normalizeSingle((string) $value);
    }

    public static function normalizeDateTime(
        mixed $value,
        DateTimeGranularity $granularity = DateTimeGranularity::Minute,
        bool $showSeconds = false,
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format(self::resolveDateTimeStorageFormat($granularity, $showSeconds));
        }

        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $storageFormat = self::resolveDateTimeStorageFormat($granularity, $showSeconds);

        $normalizer = new DateTimeFieldValue(
            DateTimeFieldMode::DateTime,
            $granularity,
            $showSeconds,
            $storageFormat,
        );

        return $normalizer->normalizeSingle((string) $value);
    }

    public static function normalizeTime(mixed $value, bool $showSeconds = false): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $storageFormat = $showSeconds ? self::TIME_WITH_SECONDS : self::TIME;

        $normalizer = new DateTimeFieldValue(
            DateTimeFieldMode::Time,
            DateTimeGranularity::Minute,
            $showSeconds,
            $storageFormat,
        );

        return $normalizer->normalizeSingle($value);
    }

    public static function firstDayOfWeekForLocale(?string $locale = null): int
    {
        $locale = $locale ?? (function_exists('app') ? app()->getLocale() : 'en');

        if (extension_loaded('intl')) {
            $calendar = \IntlCalendar::createInstance(null, $locale);

            if ($calendar !== false) {
                $icuDay = $calendar->getFirstDayOfWeek();

                return max(0, min(6, $icuDay - 1));
            }
        }

        return 0;
    }

    /**
     * Default weekday business-hours schedule (Mon–Fri 09:00–17:00).
     *
     * @param  list<string>|null  $days
     * @return array{timezone?: string, days: array<string, array{enabled: bool, slots: list<array{from: string, to: string}>}>}
     */
    public static function businessHoursDefaults(?string $timezone = 'UTC', ?array $days = null): array
    {
        $days = ScheduleDays::normalize($days ?? ScheduleDays::ALL);
        $schedule = [
            'days' => [],
        ];

        if ($timezone !== null) {
            $schedule['timezone'] = $timezone;
        }

        foreach ($days as $day) {
            $isWeekday = ScheduleDays::isWeekday($day);

            $schedule['days'][$day] = [
                'enabled' => $isWeekday,
                'slots' => $isWeekday
                    ? [['from' => '09:00', 'to' => '17:00']]
                    : [],
            ];
        }

        return $schedule;
    }

    public static function resolveDateTimeStorageFormat(
        DateTimeGranularity $granularity,
        bool $showSeconds,
    ): string {
        return match ($granularity) {
            DateTimeGranularity::Day => self::DATE,
            DateTimeGranularity::Hour => self::DATE_TIME_HOUR,
            DateTimeGranularity::Minute => self::DATE_TIME_MINUTE,
            DateTimeGranularity::Second => $showSeconds ? self::DATE_TIME_SECOND : self::DATE_TIME_MINUTE,
        };
    }
}

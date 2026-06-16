<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\DateTime;

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;
use Bjanczak\FilamentFlexFields\Enums\MonthDisplay;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimeField;

class DateTimeSegmentHydrator
{
    /**
     * @return array{
     *     parts: list<string>,
     *     single: array<string, string>,
     *     range: array{start: array<string, string>, end: array<string, string>}|null,
     * }
     */
    public static function forField(FlexDateTimeField $field): array
    {
        $monthDisplay = $field->getMonthDisplay();
        $locale = $field->getLocale();
        $parts = self::segmentParts(
            $field->getMode(),
            $field->getGranularity(),
            $field->getHourCycle(),
            $field->shouldShowSeconds(),
            $locale,
            $field->shouldShowYearSegment(),
        );

        $normalizer = new DateTimeFieldValue(
            $field->getMode(),
            $field->getGranularity(),
            $field->shouldShowSeconds(),
            $field->getStorageFormat(),
        );

        $initialState = $field->resolveInitialStateForAlpine();

        if (($field->getMode() === DateTimeFieldMode::DateRange || $field->getMode() === DateTimeFieldMode::TimeRange) && is_array($initialState)) {
            return [
                'parts' => $parts,
                'single' => self::emptySegments($parts),
                'range' => [
                    'start' => self::segmentsFromStoredValue(
                        $initialState['start'] ?? null,
                        $parts,
                        $field->getMode(),
                        $field->getGranularity(),
                        $field->getHourCycle(),
                        $field->shouldForceLeadingZeros(),
                        $normalizer,
                        $locale,
                        $monthDisplay,
                    ),
                    'end' => self::segmentsFromStoredValue(
                        $initialState['end'] ?? null,
                        $parts,
                        $field->getMode(),
                        $field->getGranularity(),
                        $field->getHourCycle(),
                        $field->shouldForceLeadingZeros(),
                        $normalizer,
                        $locale,
                        $monthDisplay,
                    ),
                ],
            ];
        }

        return [
            'parts' => $parts,
            'single' => self::segmentsFromStoredValue(
                is_string($initialState) ? $initialState : null,
                $parts,
                $field->getMode(),
                $field->getGranularity(),
                $field->getHourCycle(),
                $field->shouldForceLeadingZeros(),
                $normalizer,
                $locale,
                $monthDisplay,
            ),
            'range' => null,
        ];
    }

    /**
     * @return list<string>
     */
    public static function segmentParts(
        DateTimeFieldMode $mode,
        DateTimeGranularity $granularity,
        int $hourCycle,
        bool $showSeconds,
        ?string $locale = null,
        bool $showYearSegment = true,
    ): array {
        if ($mode === DateTimeFieldMode::Duration || $mode === DateTimeFieldMode::Time || $mode === DateTimeFieldMode::TimeRange) {
            $parts = ['hour', 'minute'];

            if ($showSeconds || $granularity === DateTimeGranularity::Second) {
                $parts[] = 'second';
            }

            if ($hourCycle === 12 && $mode !== DateTimeFieldMode::Duration && $mode !== DateTimeFieldMode::TimeRange) {
                $parts[] = 'dayPeriod';
            }

            return $parts;
        }

        if ($mode === DateTimeFieldMode::Month) {
            $parts = ['month'];

            if ($showYearSegment) {
                $parts[] = 'year';
            }

            return $parts;
        }

        if ($mode === DateTimeFieldMode::Year) {
            return ['year'];
        }

        if ($mode === DateTimeFieldMode::Date || $mode === DateTimeFieldMode::DateRange || $granularity === DateTimeGranularity::Day) {
            return DateTimeLocaleOrder::dateSegmentParts($locale);
        }

        $parts = [...DateTimeLocaleOrder::dateSegmentParts($locale), 'hour', 'minute'];

        if ($showSeconds || $granularity === DateTimeGranularity::Second) {
            $parts[] = 'second';
        }

        if ($hourCycle === 12) {
            $parts[] = 'dayPeriod';
        }

        return $parts;
    }

    public static function segmentPlaceholder(string $part, string $locale, MonthDisplay $monthDisplay = MonthDisplay::Numeric): string
    {
        return match ($part) {
            'month' => DateTimeMonthLabels::segmentPlaceholder($locale, $monthDisplay),
            'day' => 'dd',
            'year' => 'yyyy',
            'hour' => 'hh',
            'minute' => 'mm',
            'second' => 'ss',
            'dayPeriod' => str_starts_with($locale, 'pl') ? 'dd' : 'aa',
            default => $part,
        };
    }

    public static function segmentMaxLength(string $part, MonthDisplay $monthDisplay = MonthDisplay::Numeric, ?string $locale = null): int
    {
        return match ($part) {
            'month' => DateTimeMonthLabels::segmentMaxLength($locale, $monthDisplay),
            'year' => 4,
            'dayPeriod' => 2,
            default => 2,
        };
    }

    /**
     * @param  list<string>  $parts
     */
    public static function separatorAfter(string $part, array $parts, ?string $locale = null): string
    {
        $index = array_search($part, $parts, true);

        if ($index === false || $index >= count($parts) - 1) {
            return '';
        }

        if (in_array($part, ['month', 'day', 'year'], true)) {
            $nextPart = $parts[$index + 1] ?? null;

            if (! in_array($nextPart, ['month', 'day', 'year'], true)) {
                return '';
            }

            return DateTimeLocaleOrder::separatorAfter($part, $parts, $locale);
        }

        return match ($part) {
            'hour' => ':',
            'minute' => match (true) {
                ($parts[$index + 1] ?? null) === 'second' => ':',
                ($parts[$index + 1] ?? null) === 'dayPeriod' => ' ',
                default => '',
            },
            default => '',
        };
    }

    /**
     * @param  list<string>  $parts
     * @return array<string, string>
     */
    public static function segmentsFromStoredValue(
        ?string $value,
        array $parts,
        DateTimeFieldMode $mode,
        DateTimeGranularity $granularity,
        int $hourCycle,
        bool $forceLeadingZeros,
        DateTimeFieldValue $normalizer,
        ?string $locale = null,
        MonthDisplay $monthDisplay = MonthDisplay::Numeric,
    ): array {
        $segments = self::emptySegments($parts);

        if ($value === null) {
            return $segments;
        }

        $normalized = $normalizer->normalizeSingle($value);

        if ($normalized === null) {
            return $segments;
        }

        try {
            $parsed = $normalizer->parseValue($normalized);
        } catch (\Throwable) {
            return $segments;
        }

        if ($parsed === null) {
            return $segments;
        }

        if ($mode === DateTimeFieldMode::Time || $mode === DateTimeFieldMode::Duration || $mode === DateTimeFieldMode::TimeRange) {
            if (in_array('hour', $parts, true)) {
                $segments['hour'] = self::formatSegment('hour', (int) $parsed->format('H'), $hourCycle, $forceLeadingZeros);
            }

            if (in_array('minute', $parts, true)) {
                $segments['minute'] = self::formatSegment('minute', (int) $parsed->format('i'), $hourCycle, $forceLeadingZeros);
            }

            if (in_array('second', $parts, true)) {
                $segments['second'] = self::formatSegment('second', (int) $parsed->format('s'), $hourCycle, $forceLeadingZeros);
            }

            if (in_array('dayPeriod', $parts, true)) {
                $segments['dayPeriod'] = (int) $parsed->format('H') >= 12 ? 'PM' : 'AM';
            }

            return $segments;
        }

        if ($mode === DateTimeFieldMode::Month) {
            if (in_array('month', $parts, true)) {
                $segments['month'] = self::formatSegment('month', (int) $parsed->format('m'), $hourCycle, $forceLeadingZeros, $locale, $monthDisplay);
            }

            if (in_array('year', $parts, true)) {
                $segments['year'] = self::formatSegment('year', (int) $parsed->format('Y'), $hourCycle, $forceLeadingZeros, $locale, $monthDisplay);
            }

            return $segments;
        }

        if ($mode === DateTimeFieldMode::Year) {
            if (in_array('year', $parts, true)) {
                $segments['year'] = self::formatSegment('year', (int) $parsed->format('Y'), $hourCycle, $forceLeadingZeros, $locale, $monthDisplay);
            }

            return $segments;
        }

        if (in_array('month', $parts, true)) {
            $segments['month'] = self::formatSegment('month', (int) $parsed->format('m'), $hourCycle, $forceLeadingZeros, $locale, $monthDisplay);
        }

        if (in_array('day', $parts, true)) {
            $segments['day'] = self::formatSegment('day', (int) $parsed->format('d'), $hourCycle, $forceLeadingZeros);
        }

        if (in_array('year', $parts, true)) {
            $segments['year'] = self::formatSegment('year', (int) $parsed->format('Y'), $hourCycle, $forceLeadingZeros);
        }

        if (in_array('hour', $parts, true)) {
            $segments['hour'] = self::formatSegment('hour', (int) $parsed->format('H'), $hourCycle, $forceLeadingZeros);
        }

        if (in_array('minute', $parts, true)) {
            $segments['minute'] = self::formatSegment('minute', (int) $parsed->format('i'), $hourCycle, $forceLeadingZeros);
        }

        if (in_array('second', $parts, true)) {
            $segments['second'] = self::formatSegment('second', (int) $parsed->format('s'), $hourCycle, $forceLeadingZeros);
        }

        if (in_array('dayPeriod', $parts, true)) {
            $segments['dayPeriod'] = (int) $parsed->format('H') >= 12 ? 'PM' : 'AM';
        }

        return $segments;
    }

    /**
     * @return array{hour: string, minute: string}
     */
    public static function segmentsFromScheduleTime(?string $value): array
    {
        $empty = [
            'hour' => '',
            'minute' => '',
        ];

        if ($value === null || trim($value) === '') {
            return $empty;
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})$/', trim($value), $matches)) {
            return $empty;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return $empty;
        }

        return [
            'hour' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT),
            'minute' => str_pad((string) $minute, 2, '0', STR_PAD_LEFT),
        ];
    }

    /**
     * @param  list<string>  $parts
     * @return array<string, string>
     */
    protected static function emptySegments(array $parts): array
    {
        return array_fill_keys($parts, '');
    }

    protected static function formatSegment(
        string $part,
        int $value,
        int $hourCycle,
        bool $forceLeadingZeros,
        ?string $locale = null,
        MonthDisplay $monthDisplay = MonthDisplay::Numeric,
    ): string {
        if ($part === 'month') {
            return DateTimeMonthLabels::format($value, $locale, $monthDisplay, $forceLeadingZeros);
        }

        if ($part === 'year') {
            return str_pad((string) $value, 4, $forceLeadingZeros ? '0' : ' ', STR_PAD_LEFT);
        }

        if ($part === 'hour' && $hourCycle === 12) {
            $hour = $value % 12 ?: 12;

            return str_pad((string) $hour, 2, $forceLeadingZeros ? '0' : ' ', STR_PAD_LEFT);
        }

        return str_pad((string) $value, 2, $forceLeadingZeros ? '0' : ' ', STR_PAD_LEFT);
    }
}

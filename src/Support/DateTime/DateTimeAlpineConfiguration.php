<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\DateTime;

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimeField;

class DateTimeAlpineConfiguration
{
    /**
     * @return array<string, mixed>
     */
    public static function forField(FlexDateTimeField $field): array
    {
        $mode = $field->getMode();
        $granularity = $field->getGranularity();
        $showSeconds = $field->shouldShowSeconds();
        $hourCycle = $field->getHourCycle();
        $storageFormat = $field->getStorageFormat();
        $displayFormat = $field->getDisplayFormat();
        $normalizer = new DateTimeFieldValue($mode, $granularity, $showSeconds, $storageFormat);
        $constraints = new DateTimeConstraintResolver(
            $normalizer,
            $field->getMinValue(),
            $field->getMaxValue(),
            $field->getIsDateUnavailableCallback(),
        );

        $minValue = $constraints->resolveMin();
        $maxValue = $constraints->resolveMax();
        $initialState = $field->resolveInitialStateForAlpine();

        return [
            'mode' => $mode->value,
            'granularity' => $granularity->value,
            'locale' => $field->getLocale(),
            'timeZone' => $field->getTimeZone(),
            'hourCycle' => $hourCycle,
            'displayFormat' => $displayFormat,
            'storageFormat' => $storageFormat,
            'forceLeadingZeros' => $field->shouldForceLeadingZeros(),
            'minValue' => $minValue,
            'maxValue' => $maxValue,
            'unavailableDates' => $constraints->unavailableDatesBetween($minValue, $maxValue),
            'hasDateUnavailable' => $field->getIsDateUnavailableCallback() !== null,
            'rangeSeparator' => $field->getRangeSeparator(),
            'allowSameDay' => $field->shouldAllowSameDay(),
            'highlightToday' => $field->shouldHighlightToday(),
            'showCalendar' => $field->shouldShowCalendar(),
            'showCalendarButton' => $field->shouldShowCalendarButton(),
            'closeOnSelect' => $field->shouldCloseOnSelect(),
            'firstDayOfWeek' => $field->getFirstDayOfWeek(),
            'hideTimeZone' => $field->shouldHideTimeZone(),
            'hideTimeSection' => $field->shouldHideTimeSection(),
            'showSeconds' => $showSeconds,
            'showYearSegment' => $field->shouldShowYearSegment(),
            'monthDisplay' => $field->getMonthDisplay()->value,
            'placeholder' => $field->getPlaceholder() ?? self::defaultPlaceholder($mode),
            'labels' => self::labels(),
            'initialState' => $initialState,
            'initialDisplay' => self::initialDisplay($normalizer, $initialState, $displayFormat, $mode, $field->getRangeSeparator()),
            'initialSegments' => DateTimeSegmentHydrator::forField($field),
            'segmentInvalidMessage' => __('filament-flex-fields::default.date_time.validation.invalid'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'calendar' => __('filament-flex-fields::default.date_time.calendar'),
            'today' => __('filament-flex-fields::default.date_time.today'),
            'am' => __('filament-flex-fields::default.date_time.am'),
            'pm' => __('filament-flex-fields::default.date_time.pm'),
            'hour' => __('filament-flex-fields::default.date_time.hour'),
            'minute' => __('filament-flex-fields::default.date_time.minute'),
            'second' => __('filament-flex-fields::default.date_time.second'),
            'timezone' => __('filament-flex-fields::default.date_time.timezone'),
            'range_start' => __('filament-flex-fields::default.date_time.range_start'),
            'range_end' => __('filament-flex-fields::default.date_time.range_end'),
            'time' => __('filament-flex-fields::default.date_time.time'),
        ];
    }

    protected static function defaultPlaceholder(DateTimeFieldMode $mode): string
    {
        return match ($mode) {
            DateTimeFieldMode::Date => __('filament-flex-fields::default.date_time.placeholder_date'),
            DateTimeFieldMode::Time => __('filament-flex-fields::default.date_time.placeholder_time'),
            DateTimeFieldMode::DateTime => __('filament-flex-fields::default.date_time.placeholder_date_time'),
            DateTimeFieldMode::DateRange => __('filament-flex-fields::default.date_time.placeholder_date_range'),
            DateTimeFieldMode::Duration => __('filament-flex-fields::default.date_time.placeholder_duration'),
            DateTimeFieldMode::TimeRange => __('filament-flex-fields::default.date_time.placeholder_time_range'),
            DateTimeFieldMode::Month => __('filament-flex-fields::default.date_time.placeholder_month'),
            DateTimeFieldMode::Year => __('filament-flex-fields::default.date_time.placeholder_year'),
        };
    }

    /**
     * @return string|array{start: string|null, end: string|null, combined: string|null}|null
     */
    protected static function initialDisplay(
        DateTimeFieldValue $normalizer,
        string|array|null $initialState,
        string $displayFormat,
        DateTimeFieldMode $mode,
        string $rangeSeparator,
    ): string|array|null {
        if ($initialState === null) {
            return null;
        }

        if (! in_array($mode, [DateTimeFieldMode::DateRange, DateTimeFieldMode::TimeRange], true) || ! is_array($initialState)) {
            return is_string($initialState)
                ? $normalizer->formatForDisplay($initialState, $displayFormat)
                : null;
        }

        $start = $normalizer->formatForDisplay($initialState['start'] ?? null, $displayFormat);
        $end = $normalizer->formatForDisplay($initialState['end'] ?? null, $displayFormat);
        $combined = filled($start) && filled($end)
            ? "{$start}{$rangeSeparator}{$end}"
            : (filled($start) ? $start : (filled($end) ? $end : null));

        return [
            'start' => $start,
            'end' => $end,
            'combined' => $combined,
        ];
    }
}

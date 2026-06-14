<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDatePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDurationField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMonthPicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexYearPicker;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeSegmentHydrator;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Carbon\Carbon;

it('exposes date field configuration api', function () {
    $field = FlexDateField::make('starts_on')
        ->size('lg')
        ->locale('en_US')
        ->timeZone('Europe/Warsaw')
        ->hourCycle(24)
        ->granularity(DateTimeGranularity::Day)
        ->minValue('2026-01-01')
        ->maxValue(Carbon::parse('2026-12-31'))
        ->forceLeadingZeros()
        ->highlightToday()
        ->focusOutline();

    expect($field->getSize())->toBe('lg')
        ->and($field->getLocale())->toBe('en_US')
        ->and($field->getTimeZone())->toBe('Europe/Warsaw')
        ->and($field->getHourCycle())->toBe(24)
        ->and($field->getGranularity())->toBe(DateTimeGranularity::Day)
        ->and($field->shouldForceLeadingZeros())->toBeTrue()
        ->and($field->shouldHighlightToday())->toBeTrue()
        ->and($field->shouldShowFocusOutline())->toBeTrue()
        ->and($field->shouldShowCalendar())->toBeFalse();
});

it('normalizes single date values to storage format', function () {
    $field = FlexDateField::make('starts_on');

    expect($field->normalizeState('2026-06-15'))->toBe('2026-06-15')
        ->and($field->normalizeState('06/15/2026'))->toBe('2026-06-15')
        ->and($field->normalizeState(null))->toBeNull();
});

it('normalizes range values to start and end keys', function () {
    $field = FlexDateRangeField::make('booking_range');

    expect($field->normalizeState([
        'start' => '2026-06-10',
        'end' => '2026-06-14',
    ]))->toBe([
        'start' => '2026-06-10',
        'end' => '2026-06-14',
    ]);
});

it('normalizes time values', function () {
    $field = FlexTimeField::make('starts_at')->showSeconds();

    expect($field->normalizeState('14:30:00'))->toBe('14:30:00')
        ->and($field->normalizeState('2:30 PM'))->toBe('14:30:00');
});

it('applies recommended defaults per mode', function () {
    expect(FlexDatePicker::make('date')->shouldShowCalendar())->toBeTrue()
        ->and(FlexDatePicker::make('date')->shouldShowCalendarButton())->toBeTrue()
        ->and(FlexTimeField::make('time')->withRecommendedDefaults()->getHourCycle())->toBe(12)
        ->and(FlexDateTimePicker::make('date_time')->withRecommendedDefaults()->getHourCycle())->toBe(24)
        ->and(FlexDateTimePicker::make('date_time')->withRecommendedDefaults()->getGranularity())->toBe(DateTimeGranularity::Minute)
        ->and(FlexDateTimePicker::make('date_time')->withRecommendedDefaults()->shouldCloseOnSelect())->toBeFalse()
        ->and(FlexDateTimePicker::make('date_time')->shouldShowCalendar())->toBeTrue()
        ->and(FlexDateRangeField::make('range')->withRecommendedDefaults()->shouldCloseOnSelect())->toBeFalse();
});

it('builds alpine configuration with constraints and labels', function () {
    $field = FlexDateField::make('starts_on')
        ->minValue('2026-06-01')
        ->maxValue('2026-06-30')
        ->default('2026-06-15');

    $config = $field->getAlpineConfiguration();

    expect($config)->toHaveKeys([
        'mode',
        'granularity',
        'locale',
        'timeZone',
        'hourCycle',
        'displayFormat',
        'storageFormat',
        'minValue',
        'maxValue',
        'unavailableDates',
        'labels',
        'initialState',
        'initialDisplay',
        'initialSegments',
        'segmentInvalidMessage',
    ])
        ->and($config['mode'])->toBe('date')
        ->and($config['minValue'])->toBe('2026-06-01')
        ->and($config['maxValue'])->toBe('2026-06-30')
        ->and($config['initialState'])->toBe('2026-06-15');
});

it('includes wrapper classes for size and variant', function () {
    $field = FlexDatePicker::make('starts_on')
        ->size('sm')
        ->variant('secondary');

    expect($field->getWrapperClasses())->toBe([
        'fff-date-time-field',
        'fff-flex-text-input-field',
        'fff-date-time-field--date',
        'fff-date-time-field--sm',
        'fff-flex-text-input-field--sm',
        'fff-date-time-field--secondary',
        'fff-flex-text-input-field--secondary',
    ]);
});

it('rejects unsupported hour cycles', function () {
    FlexTimeField::make('starts_at')->hourCycle(48)->getHourCycle();
})->throws(InvalidArgumentException::class);

it('validates required date fields through custom rule', function () {
    $field = FlexDateField::make('starts_on')->required()->label('Start date');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('starts_on', null, function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('validation.required', ['attribute' => 'Start date']));
});

it('rejects values outside configured min and max', function () {
    $field = FlexDateField::make('starts_on')
        ->minValue('2026-06-01')
        ->maxValue('2026-06-30')
        ->label('Start date');

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('starts_on', '2026-05-01', function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.date_time.validation.before_min'));
});

it('accepts minDate and maxDate as aliases for minValue and maxValue', function () {
    $field = FlexDateField::make('birth_date')
        ->minDate(now()->subYears(150))
        ->maxDate(now())
        ->label('Birth date');

    $config = $field->getAlpineConfiguration();

    expect($config['minValue'])->not->toBeNull()
        ->and($config['maxValue'])->not->toBeNull();

    $rule = collect($field->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);

    $message = null;
    $rule('birth_date', now()->addDay()->format('Y-m-d'), function (string $failMessage) use (&$message): void {
        $message = $failMessage;
    });

    expect($message)->toBe(__('filament-flex-fields::default.date_time.validation.after_max'));
});

it('registers date time field playground variants', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state)->toHaveKeys([
        'date_time__date_field',
        'date_time__date_picker',
        'date_time__date_range',
        'date_time__time_field',
        'date_time__time_field_24h',
        'date_time__date_time_field',
    ]);
});

it('resolves display and storage formats', function () {
    $field = FlexDateField::make('starts_on')
        ->displayFormat('d/m/Y')
        ->storageFormat('d.m.Y');

    expect($field->getDisplayFormat())->toBe('d/m/Y')
        ->and($field->getStorageFormat())->toBe('d.m.Y')
        ->and($field->normalizeState('2026-06-15'))->toBe('15.06.2026');

    $dateTimeField = FlexDateTimePicker::make('starts_at')
        ->granularity(DateTimeGranularity::Second)
        ->showSeconds();

    expect($dateTimeField->getStorageFormat())->toBe('Y-m-d\TH:i:s')
        ->and($dateTimeField->getDisplayFormat())->toBe('m/d/Y H:i:s');

    $hourField = FlexDateTimePicker::make('starts_at')
        ->granularity(DateTimeGranularity::Hour)
        ->hourCycle(12);

    expect($hourField->getDisplayFormat())->toBe('m/d/Y g:i A')
        ->and($hourField->getStorageFormat())->toBe('Y-m-d\TH:00:00');
});

it('hydrates server-side segments for stable initial render', function () {
    $field = FlexDateField::make('starts_on')->default('2026-06-15');

    expect($field->getViewSegments()['parts'])->toBe(['month', 'day', 'year'])
        ->and($field->getViewSegments()['single'])->toBe([
            'month' => '06',
            'day' => '15',
            'year' => '2026',
        ])
        ->and($field->getViewSegments()['range'])->toBeNull();

    $timeField = FlexTimeField::make('starts_at')
        ->hourCycle(12)
        ->granularity(DateTimeGranularity::Minute)
        ->default('14:30');

    expect($timeField->getViewSegments()['single'])->toBe([
        'hour' => '02',
        'minute' => '30',
        'dayPeriod' => 'PM',
    ]);

    expect(DateTimeSegmentHydrator::segmentMaxLength('month'))->toBe(2)
        ->and(DateTimeSegmentHydrator::segmentMaxLength('day'))->toBe(2)
        ->and(DateTimeSegmentHydrator::segmentMaxLength('year'))->toBe(4)
        ->and(DateTimeSegmentHydrator::segmentMaxLength('dayPeriod'))->toBe(2);

    $time24Field = FlexTimeField::make('starts_at')
        ->hourCycle(24)
        ->default('14:30');

    expect($time24Field->getViewSegments()['single'])->toBe([
        'hour' => '14',
        'minute' => '30',
    ]);

    $dateTimeField = FlexDateTimePicker::make('starts_at')
        ->hourCycle(24)
        ->granularity(DateTimeGranularity::Minute)
        ->default('2026-06-15T14:30:00');

    expect($dateTimeField->getViewSegments()['single'])->toBe([
        'month' => '06',
        'day' => '15',
        'year' => '2026',
        'hour' => '14',
        'minute' => '30',
    ]);

    $rangeField = FlexDateRangeField::make('booking_range')->default([
        'start' => '2026-06-10',
        'end' => '2026-06-14',
    ]);

    expect($rangeField->getViewSegments()['range'])->toBe([
        'start' => [
            'month' => '06',
            'day' => '10',
            'year' => '2026',
        ],
        'end' => [
            'month' => '06',
            'day' => '14',
            'year' => '2026',
        ],
    ]);
});

it('normalizes duration and month values', function () {
    $duration = FlexDurationField::make('length')->showSeconds();

    expect($duration->normalizeState('02:30:00'))->toBe('02:30:00')
        ->and($duration->getMode()->value)->toBe('duration');

    $month = FlexMonthPicker::make('period');

    expect($month->normalizeState('2026-06'))->toBe('2026-06')
        ->and($month->getViewSegments()['parts'])->toBe(['month', 'year']);

    $monthOnly = FlexMonthPicker::make('period')->showYearSegment(false);

    expect($monthOnly->getViewSegments()['parts'])->toBe(['month']);

    $year = FlexYearPicker::make('season');

    expect($year->normalizeState('2026'))->toBe('2026');

    $timeRange = FlexTimeRangeField::make('hours')->default([
        'start' => '09:00',
        'end' => '17:00',
    ]);

    expect($timeRange->normalizeState([
        'start' => '09:00',
        'end' => '17:00',
    ]))->toBe([
        'start' => '09:00',
        'end' => '17:00',
    ]);
});

it('includes unavailable dates in alpine configuration when callback is set', function () {
    $field = FlexDatePicker::make('starts_on')
        ->minValue('2026-06-01')
        ->maxValue('2026-06-07')
        ->isDateUnavailable(fn (Carbon $date): bool => $date->isWeekend());

    $config = $field->getAlpineConfiguration();

    expect($config['hasDateUnavailable'])->toBeTrue()
        ->and($config['unavailableDates'])->toContain('2026-06-06', '2026-06-07');
});

it('uses locale-aware date segment order in view segments', function () {
    $field = FlexDateField::make('starts_on')
        ->locale('pl_PL')
        ->default('2026-06-15');

    expect($field->getViewSegments()['parts'])->toBe(['day', 'month', 'year'])
        ->and($field->getViewSegments()['single'])->toBe([
            'day' => '15',
            'month' => '06',
            'year' => '2026',
        ]);
});

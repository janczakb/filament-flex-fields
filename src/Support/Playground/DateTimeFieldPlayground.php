<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;
use Bjanczak\FilamentFlexFields\Enums\MonthDisplay;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDatePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDurationField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMonthPicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeSegmentsField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexYearPicker;
use Carbon\Carbon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;

class DateTimeFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'date_time__date_field' => '2026-06-15',
            'date_time__date_picker' => '2026-07-04',
            'date_time__date_range' => [
                'start' => '2026-06-10T09:30:00',
                'end' => '2026-06-14T17:00:00',
            ],
            'date_time__date_range_day' => [
                'start' => '2026-06-10',
                'end' => '2026-06-14',
            ],
            'date_time__time_field' => '14:30',
            'date_time__time_field_24h' => '14:30',
            'date_time__time_segments' => '09:30',
            'date_time__time_segments_step' => '14:07',
            'date_time__date_time_field' => '2026-06-15T14:30:00',
            'date_time__hour_granularity' => '2026-02-03T08:00:00',
            'date_time__second_granularity' => '2026-02-03T08:45:00',
            'date_time__12h' => '2026-02-03T08:45:00',
            'date_time__with_timezone' => '2026-02-03T08:45:00',
            'date_time__display_format' => '2026-06-15',
            'date_time__storage_format' => '2026-06-15',
            'date_time__bounded' => '2026-06-15',
            'date_time__duration' => '02:30:00',
            'date_time__time_range' => [
                'start' => '09:00',
                'end' => '17:00',
            ],
            'date_time__month' => '2026-06',
            'date_time__month_only' => '2026-06',
            'date_time__month_text' => '2026-06',
            'date_time__date_long_month' => '2026-06-15',
            'date_time__date_short_month' => '2026-03-15',
            'date_time__datetime_long_month' => '2026-06-15T14:30:00',
            'date_time__year' => '2026',
            'date_time__picker_validation' => null,
            'date_time__range_validation' => null,
            'date_time__picker_format_options' => '2026-02-03T08:45:00',
            'date_time__range_format_options' => [
                'start' => '2026-02-03T08:45:00',
                'end' => '2026-02-10T18:45:00',
            ],
            'date_time__indian_calendar' => null,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Date & time fields')
                ->description('Spectrum-style segmented date/time inputs powered by @internationalized/date.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    FlexDateField::make('date_time__date_field')
                        ->label('Date field')
                        ->required()
                        ->minValue(Carbon::today())
                        ->helperText('Segmented date input without calendar popover.')
                        ->withRecommendedDefaults(),
                    FlexDatePicker::make('date_time__date_picker')
                        ->label('Date picker')
                        ->withRecommendedDefaults()
                        ->default(Carbon::parse('2026-07-04'))
                        ->helperText('Segmented input with calendar popover. Use default() for initial value.'),
                    FlexDateRangeField::make('date_time__date_range')
                        ->label('Date range (with time)')
                        ->withRecommendedDefaults()
                        ->granularity(DateTimeGranularity::Minute)
                        ->helperText('Open the calendar — start/end time rows appear under the grid. Call withRecommendedDefaults() first, then granularity(Minute); defaults last would reset to Day.'),
                    FlexDateRangeField::make('date_time__date_range_day')
                        ->label('Date range (day only)')
                        ->withRecommendedDefaults()
                        ->helperText('Date-only range preset from withRecommendedDefaults() (granularity Day — no time rows).'),
                    FlexTimeField::make('date_time__time_field')
                        ->label('Time field (12h)')
                        ->hourCycle(12)
                        ->minValue('09:00')
                        ->maxValue('18:00')
                        ->withRecommendedDefaults()
                        ->helperText('12-hour segmented time with min/max constraints.'),
                    FlexTimeField::make('date_time__time_field_24h')
                        ->label('Time field (24h)')
                        ->hourCycle(24)
                        ->minValue('09:00')
                        ->maxValue('18:00')
                        ->hideTimeZone()
                        ->granularity(DateTimeGranularity::Minute)
                        ->helperText('24-hour segmented time with min/max constraints.'),
                    FlexTimeSegmentsField::make('date_time__time_segments')
                        ->label('Time segments (dropdown)')
                        ->minuteStep(15)
                        ->withRecommendedDefaults()
                        ->helperText('Dropdown hour/minute picker in flex-text-input shell. Stores HH:MM (24h). Reused by ScheduleField.'),
                    FlexTimeSegmentsField::make('date_time__time_segments_step')
                        ->label('Time segments · 5 min step')
                        ->minuteStep(5)
                        ->variant('soft')
                        ->size('sm')
                        ->helperText('minuteStep(5) snaps minutes to 00, 05, 10, … 55.'),
                    FlexDateTimePicker::make('date_time__date_time_field')
                        ->label('Date & time')
                        ->hourCycle(24)
                        ->hideTimeZone()
                        ->withRecommendedDefaults()
                        ->helperText('Combined date and time in one segmented input with calendar.'),
                    FlexDurationField::make('date_time__duration')
                        ->label('Duration')
                        ->showSeconds()
                        ->withRecommendedDefaults()
                        ->helperText('Segmented HH:MM:SS duration without date.'),
                    FlexTimeRangeField::make('date_time__time_range')
                        ->label('Time range')
                        ->minValue('08:00')
                        ->maxValue('20:00')
                        ->withRecommendedDefaults()
                        ->helperText('Dual segmented time groups with range separator.'),
                    FlexMonthPicker::make('date_time__month')
                        ->label('Month picker')
                        ->locale('pl_PL')
                        ->withRecommendedDefaults()
                        ->helperText('Month + year segments: calendar opens on year grid, then month grid.'),
                    FlexYearPicker::make('date_time__year')
                        ->label('Year picker')
                        ->withRecommendedDefaults()
                        ->helperText('Year segment with year grid calendar.'),
                ]),
            Section::make('Month display')
                ->description('monthDisplay(): numeric (06), short (cze), long (czerwiec). Works in any field with a month segment.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    FlexMonthPicker::make('date_time__month_only')
                        ->label('Month only · long')
                        ->showYearSegment(false)
                        ->monthDisplay(MonthDisplay::Long)
                        ->locale('pl_PL')
                        ->withRecommendedDefaults()
                        ->helperText('Tylko miesiąc jako tekst (czerwiec).'),
                    FlexMonthPicker::make('date_time__month_text')
                        ->label('Month + year · long')
                        ->monthDisplay(MonthDisplay::Long)
                        ->locale('pl_PL')
                        ->withRecommendedDefaults()
                        ->helperText('czerwiec + 2026 — segment miesiąca dopasowuje szerokość do tekstu.'),
                    FlexDatePicker::make('date_time__date_long_month')
                        ->label('dd · miesiąc · yyyy')
                        ->locale('pl_PL')
                        ->monthDisplay(MonthDisplay::Long)
                        ->withRecommendedDefaults()
                        ->default('2026-06-15')
                        ->helperText('15 · czerwiec · 2026 — kolejność segmentów z locale (pl_PL → dzień pierwszy).'),
                    FlexDateField::make('date_time__date_short_month')
                        ->label('dd · miesiąc · yyyy (short)')
                        ->locale('pl_PL')
                        ->monthDisplay(MonthDisplay::Short)
                        ->withRecommendedDefaults()
                        ->default('2026-03-15')
                        ->helperText('15 · mar · 2026 — skrót miesiąca w segmencie.'),
                    FlexDateTimePicker::make('date_time__datetime_long_month')
                        ->label('dd · miesiąc · yyyy · HH:MM')
                        ->locale('pl_PL')
                        ->monthDisplay(MonthDisplay::Long)
                        ->hourCycle(24)
                        ->hideTimeZone()
                        ->withRecommendedDefaults()
                        ->helperText('Data z tekstowym miesiącem i czasem w jednym inpucie.'),
                ]),
            Section::make('Date & time configuration')
                ->description('Spectrum-style options: granularity(), hourCycle(), hideTimeZone(), forceLeadingZeros(), hideTimeSection().')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    FlexDateTimePicker::make('date_time__hour_granularity')
                        ->label('Date and time')
                        ->granularity(DateTimeGranularity::Hour)
                        ->hourCycle(24)
                        ->hideTimeZone()
                        ->forceLeadingZeros()
                        ->helperText('granularity(Hour), hourCycle(24), hideTimeZone(), forceLeadingZeros()'),
                    FlexDateTimePicker::make('date_time__second_granularity')
                        ->label('Date and time')
                        ->granularity(DateTimeGranularity::Second)
                        ->showSeconds()
                        ->hourCycle(24)
                        ->helperText('granularity(Second), showSeconds(), hourCycle(24)'),
                    FlexDateTimePicker::make('date_time__12h')
                        ->label('Date and time')
                        ->hourCycle(12)
                        ->granularity(DateTimeGranularity::Minute)
                        ->helperText('hourCycle(12) adds an AM/PM segment'),
                    FlexDateTimePicker::make('date_time__with_timezone')
                        ->label('Date and time')
                        ->hourCycle(24)
                        ->granularity(DateTimeGranularity::Minute)
                        ->helperText('Timezone label is visible by default; use hideTimeZone() to hide it.'),
                ]),
            Section::make('Validation & format demos')
                ->description('Segment validation, minValue, format options, international calendar, and calendar popover UX.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    FlexDatePicker::make('date_time__picker_validation')
                        ->label('Appointment date')
                        ->required()
                        ->minValue(Carbon::today())
                        ->segmentInvalidMessage('Date must be today or in the future.')
                        ->withRecommendedDefaults()
                        ->helperText('minValue = today with a custom segment error on blur when the value is in the past.'),
                    FlexDateRangeField::make('date_time__range_validation')
                        ->label('Booking period')
                        ->required()
                        ->minValue(Carbon::today())
                        ->segmentInvalidMessage('Select a valid range starting today or later.')
                        ->withRecommendedDefaults()
                        ->helperText('minValue on both ends; end must be after start.'),
                    FlexDateTimePicker::make('date_time__picker_format_options')
                        ->label('Date and time')
                        ->granularity(DateTimeGranularity::Minute)
                        ->hourCycle(12)
                        ->forceLeadingZeros()
                        ->withRecommendedDefaults()
                        ->default('2026-02-03T08:45:00')
                        ->helperText('Minute granularity, 12-hour cycle, forced leading zeros, time row under the calendar.'),
                    FlexDateRangeField::make('date_time__range_format_options')
                        ->label('Date range with time')
                        ->granularity(DateTimeGranularity::Minute)
                        ->hourCycle(12)
                        ->forceLeadingZeros()
                        ->withRecommendedDefaults()
                        ->default([
                            'start' => '2026-02-03T08:45:00',
                            'end' => '2026-02-10T18:45:00',
                        ])
                        ->helperText('Separate start/end time rows under the range calendar.'),
                    FlexDatePicker::make('date_time__indian_calendar')
                        ->label('Event date (Indian calendar)')
                        ->locale('hi-IN-u-ca-indian')
                        ->withRecommendedDefaults()
                        ->helperText('BCP 47 locale with -u-ca-indian uses the Indian national calendar in segments and the popover grid.'),
                ]),
            Section::make('Date & time bounds and formats')
                ->description('minValue(), maxValue(), default(), displayFormat(), storageFormat().')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    FlexDatePicker::make('date_time__bounded')
                        ->label('Bounded date')
                        ->minValue('2026-06-01')
                        ->maxValue('2026-06-30')
                        ->default('2026-06-15')
                        ->withRecommendedDefaults()
                        ->helperText('minValue("2026-06-01"), maxValue("2026-06-30"), default("2026-06-15"). Calendar disables out-of-range days.'),
                    FlexDatePicker::make('date_time__display_format')
                        ->label('European display format')
                        ->displayFormat('d/m/Y')
                        ->locale('pl_PL')
                        ->withRecommendedDefaults()
                        ->helperText('displayFormat("d/m/Y") affects server-side initialDisplay. Segmented input order follows locale (e.g. pl_PL → dd/mm/yyyy).'),
                    FlexDateField::make('date_time__storage_format')
                        ->label('Custom storage format')
                        ->storageFormat('d.m.Y')
                        ->default('2026-06-15')
                        ->withRecommendedDefaults()
                        ->helperText('storageFormat("d.m.Y") — persisted value uses dots instead of Y-m-d.'),
                ]),
        ];
    }
}

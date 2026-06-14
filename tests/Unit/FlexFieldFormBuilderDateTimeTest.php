<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDatePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDurationField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMonthPicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexYearPicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;

it('maps date field type to flex date picker with config', function () {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'starts_on',
            'label' => 'Starts on',
            'type' => FieldType::Date->value,
            'config' => [
                'locale' => 'pl_PL',
                'min_date' => '2026-06-01',
                'max_date' => '2026-06-30',
                'display_format' => 'd/m/Y',
                'close_on_select' => true,
            ],
        ]),
    );

    expect($field)->toBeInstanceOf(FlexDatePicker::class)
        ->and($field->getLocale())->toBe('pl_PL')
        ->and($field->shouldShowCalendar())->toBeTrue()
        ->and($field->getAlpineConfiguration()['minValue'])->toBe('2026-06-01')
        ->and($field->getAlpineConfiguration()['maxValue'])->toBe('2026-06-30')
        ->and($field->getDisplayFormat())->toBe('d/m/Y');
});

it('maps duration and time range field types to dedicated components', function () {
    $builder = new FlexFieldFormBuilder;

    $duration = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'length',
            'label' => 'Length',
            'type' => FieldType::Duration->value,
            'config' => ['show_seconds' => true],
        ]),
    );

    $timeRange = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'hours',
            'label' => 'Hours',
            'type' => FieldType::TimeRange->value,
            'config' => [
                'min_value' => '08:00',
                'max_value' => '18:00',
            ],
        ]),
    );

    expect($duration)->toBeInstanceOf(FlexDurationField::class)
        ->and($duration->shouldShowSeconds())->toBeTrue()
        ->and($timeRange)->toBeInstanceOf(FlexTimeRangeField::class)
        ->and($timeRange->getAlpineConfiguration()['minValue'])->toBe('08:00');
});

it('maps month and year field types to picker components', function () {
    $builder = new FlexFieldFormBuilder;

    $month = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'period',
            'label' => 'Period',
            'type' => FieldType::Month->value,
        ]),
    );

    $year = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'season',
            'label' => 'Season',
            'type' => FieldType::Year->value,
        ]),
    );

    expect($month)->toBeInstanceOf(FlexMonthPicker::class)
        ->and($month->shouldShowCalendar())->toBeTrue()
        ->and($year)->toBeInstanceOf(FlexYearPicker::class)
        ->and($year->getMode()->value)->toBe('year');
});

it('maps timezone field type with configureTimezoneField', function () {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'timezone',
            'label' => 'Timezone',
            'type' => FieldType::Timezone->value,
            'config' => [
                'default_timezone' => 'Europe/Warsaw',
                'timezones' => ['Europe/Warsaw', 'UTC'],
                'show_offset' => false,
            ],
        ]),
    );

    expect($field)->toBeInstanceOf(TimezoneField::class)
        ->and($field->getDefaultTimezoneIdentifier())->toBe('Europe/Warsaw')
        ->and($field->shouldShowOffset())->toBeFalse();
});

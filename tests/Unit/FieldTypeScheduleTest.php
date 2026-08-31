<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldCategory;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ScheduleField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;

it('declares schedule field type in datetime category', function () {
    expect(FieldType::Schedule)->toBeInstanceOf(FieldType::class)
        ->and(FieldType::Schedule->value)->toBe('schedule')
        ->and(FieldType::Schedule->category())->toBe(FieldCategory::DateTime)
        ->and(FieldType::Schedule->icon())->toBe('heroicon-o-calendar-days')
        ->and(FieldType::Schedule->isCustomComponent())->toBeTrue()
        ->and(FieldType::Schedule->assetComponents())->toBe(['schedule-field']);
});

it('maps schedule field type to schedule field via form builder', function () {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'business_hours',
            'label' => 'Business hours',
            'type' => FieldType::Schedule->value,
            'config' => [
                'timezone' => 'Europe/Warsaw',
                'time_step' => 15,
                'min_slots' => 2,
                'max_slots' => 8,
                'copy_source_day' => 'tue',
                'allow_copy_to_weekdays' => false,
            ],
        ]),
    );

    expect($field)->toBeInstanceOf(ScheduleField::class)
        ->and($field->getDefaultTimezoneIdentifier())->toBe('Europe/Warsaw')
        ->and($field->getTimeStep())->toBe(15)
        ->and($field->getMinSlots())->toBe(2)
        ->and($field->getMaxSlots())->toBe(8)
        ->and($field->getCopySourceDay())->toBe('tue')
        ->and($field->shouldAllowCopyToWeekdays())->toBeFalse();
});

it('exposes schedule defaults from field type registry config', function () {
    $defaults = FieldType::Schedule->defaultConfig();

    expect($defaults)->toMatchArray([
        'size' => 'md',
        'variant' => 'primary',
        'timezone' => 'UTC',
        'time_step' => 5,
        'min_slots' => 1,
        'max_slots' => 10,
        'allow_copy_to_weekdays' => true,
        'copy_source_day' => 'mon',
        'require_slots_for_enabled_days' => true,
    ]);
});

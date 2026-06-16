<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeSegmentsField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

it('exposes flex time segments field configuration api', function () {
    $field = FlexTimeSegmentsField::make('starts_at')
        ->size('sm')
        ->variant('soft')
        ->minuteStep(5)
        ->hourCycle(12)
        ->minValue('09:00')
        ->maxValue('18:00')
        ->locale('pl_PL')
        ->storageFormat('H:i');

    expect($field->getSize())->toBe('sm')
        ->and($field->getVariant())->toBe('soft')
        ->and($field->getMinuteStep())->toBe(5)
        ->and($field->getHourCycle())->toBe(12)
        ->and($field->getResolvedMinValue())->toBe('09:00')
        ->and($field->getResolvedMaxValue())->toBe('18:00')
        ->and($field->getLocale())->toBe('pl_PL')
        ->and($field->getStorageFormat())->toBe('H:i')
        ->and($field->getAlpineConfiguration()['hourCycle'])->toBe(12)
        ->and($field->getAlpineConfiguration()['minValue'])->toBe('09:00');
});

it('normalizes flex time segments state to HH:MM', function () {
    $field = FlexTimeSegmentsField::make('starts_at');

    expect($field->normalizeState('9:30'))->toBe('09:30')
        ->and($field->normalizeState('23:59'))->toBe('23:59')
        ->and($field->normalizeState(null))->toBeNull()
        ->and($field->normalizeState(''))->toBeNull()
        ->and($field->normalizeState('25:00'))->toBeNull();
});

it('registers flex time segments assets for lazy loading', function () {
    expect(FlexFieldAssets::hasLazyStylesheet('flex-time-segments'))->toBeTrue()
        ->and(FlexFieldAssets::STYLESHEET_DEPENDENCIES['flex-time-segments'])->toBe(['flex-text-input']);
});

it('renders flex time segments field view with entangled alpine bindings', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-time-segments-field.blade.php');
    $partial = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/flex-time-segments.blade.php');

    expect($blade)
        ->toContain('flex-time-segments-field')
        ->toContain('load-stylesheet')
        ->toContain('flex-time-segments')
        ->toContain('$entangle')
        ->and($partial)->toContain('flexTimeSegmentsComponent')
        ->and($partial)->toContain('x-load-src');
});

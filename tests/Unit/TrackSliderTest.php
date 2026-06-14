<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CellSlider;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrackSlider;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\Playground\TrackSliderPlayground;

it('exposes track slider configuration via fluent api', function () {
    $field = TrackSlider::make('spacing')
        ->min(0)
        ->max(1)
        ->step(0.01)
        ->integer(false)
        ->decimalPlaces(2)
        ->variant('secondary')
        ->size(ControlSize::Lg)
        ->suffix('%')
        ->showOutput();

    expect($field->getMin())->toBe(0)
        ->and($field->getTrackLabel())->toBeNull()
        ->and($field->getMax())->toBe(1)
        ->and($field->getStep())->toBe(0.01)
        ->and($field->isInteger())->toBeFalse()
        ->and($field->getDecimalPlaces())->toBe(2)
        ->and($field->getVariant())->toBe('secondary')
        ->and($field->getSize())->toBe('lg')
        ->and($field->getDisplaySuffix())->toBe('%')
        ->and($field->shouldShowOutput())->toBeTrue()
        ->and($field->isNumeric())->toBeTrue();
});

it('supports an optional track label caption inside the bar', function () {
    $field = TrackSlider::make('spacing')
        ->label('Spacing field')
        ->trackLabel('Spacing');

    expect($field->getTrackLabel())->toBe('Spacing');
});

it('keeps cell slider as a deprecated alias of track slider', function () {
    expect(CellSlider::make('spacing'))->toBeInstanceOf(TrackSlider::class);
});

it('registers track slider playground variants', function () {
    $state = (new TrackSliderPlayground)->defaultState();

    expect($state)->toHaveKeys([
        'track_slider__controlled',
        'track_slider__volume',
        'track_slider__step_5',
        'track_slider__secondary_spacing',
        'track_slider__guests',
        'track_slider__disabled_spacing',
    ]);
});

it('merges track slider default state into playground builder', function () {
    $state = app(FlexFieldsPlaygroundBuilder::class)->defaultState();

    expect($state)->toHaveKey('track_slider__spacing');
});

it('sets track label on all track slider playground helpers', function () {
    $playground = new TrackSliderPlayground;

    $decimalField = (new ReflectionMethod(TrackSliderPlayground::class, 'decimalField'))
        ->invoke($playground, 'track_slider__spacing', 'Spacing');
    $integerField = (new ReflectionMethod(TrackSliderPlayground::class, 'integerField'))
        ->invoke($playground, 'track_slider__volume', 'Volume', 0, 100, 1);

    expect($decimalField->getTrackLabel())->toBe('Spacing')
        ->and($integerField->getTrackLabel())->toBe('Volume');
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField;
use Filament\Support\Icons\Heroicon;

it('exposes rating configuration via fluent api', function () {
    $field = RatingField::make('score')
        ->label('Score')
        ->stars(10)
        ->size(ControlSize::Lg)
        ->color('success')
        ->icon(Heroicon::Heart)
        ->showValue(false);

    expect($field->getMax())->toBe(10)
        ->and($field->getSize())->toBe('lg')
        ->and($field->getColor())->toBe('success')
        ->and($field->getIcon())->toBe(Heroicon::Heart)
        ->and($field->shouldShowValue())->toBeFalse();
});

it('defaults to five warning stars', function () {
    $field = RatingField::make('score');

    expect($field->getMax())->toBe(5)
        ->and($field->getColor())->toBe('warning')
        ->and($field->getIcon())->toBe(Heroicon::Star)
        ->and($field->shouldShowValue())->toBeTrue();
});

it('calculates fractional fill percentages for read only display', function () {
    $field = RatingField::make('score');

    expect($field->getFillPercentageForValue(3.7, 1))->toBe(1.0)
        ->and($field->getFillPercentageForValue(3.7, 4))->toEqualWithDelta(0.7, 0.0001)
        ->and($field->getFillPercentageForValue(3.7, 5))->toBe(0.0)
        ->and($field->getFillPercentageForValue(null, 1))->toBe(0.0);
});

it('validates numeric range and integer input values', function () {
    $field = RatingField::make('score')->stars(5);

    expect($field->getValidationRules())->toContain('numeric')
        ->and($field->getValidationRules())->toContain('max:5')
        ->and($field->getValidationRules())->toContain('integer');
});

it('allows fractional values when read only', function () {
    $field = RatingField::make('score')->readOnly();

    expect($field->getValidationRules())->not->toContain('integer');
});

it('rejects invalid max values', function () {
    RatingField::make('score')->max(0);
})->throws(InvalidArgumentException::class);

it('supports required validation like other fields', function () {
    $field = RatingField::make('score')->required();

    expect($field->isRequired())->toBeTrue();
});

it('returns sequential item indexes based on max', function () {
    $field = RatingField::make('score')->stars(4);

    expect($field->getItemIndexes())->toBe([1, 2, 3, 4]);
});

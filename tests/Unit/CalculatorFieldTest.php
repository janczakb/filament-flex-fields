<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CalculatorField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

it('exposes calculator field configuration api', function () {
    $field = CalculatorField::make('weight')
        ->size('lg')
        ->variant('soft')
        ->minValue(0)
        ->maxValue(9999)
        ->step(0.5)
        ->integer()
        ->decimalPlaces(2)
        ->maxLength(8)
        ->roundingMode('floor')
        ->calculatorIcon(GravityIcon::make('calculator'));

    expect($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('soft')
        ->and($field->getMinValue())->toBe(0)
        ->and($field->getMaxValue())->toBe(9999)
        ->and($field->getStep())->toBe(0.5)
        ->and($field->isInteger())->toBeTrue()
        ->and($field->getDecimalPlaces())->toBe(2)
        ->and($field->getMaxLength())->toBe(8)
        ->and($field->getRoundingMode())->toBe('floor')
        ->and($field->getCalculatorIcon())->toBe(GravityIcon::make('calculator'))
        ->and($field->getWrapperClasses())->toContain('fff-calculator-field')
        ->and($field->getWrapperClasses())->toContain('fff-flex-text-input-field--soft');
});

it('rejects unsupported numeric rounding modes', function () {
    CalculatorField::make('weight')->roundingMode('bankers')->getRoundingMode();
})->throws(InvalidArgumentException::class);

it('rejects unsupported calculator field variants', function () {
    CalculatorField::make('weight')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('uses truncate as the default rounding mode', function () {
    $field = CalculatorField::make('weight');

    expect($field->getRoundingMode())->toBe('truncate');
});

it('uses calculator icon by default', function () {
    $field = CalculatorField::make('weight');

    expect($field->getCalculatorIcon())->toBe(GravityIcon::make('calculator'));
});

it('resolves a stable calculator field id from state path', function () {
    $field = CalculatorField::make('cargo.weight');

    expect($field->getCalculatorFieldId())->toBe('cargo.weight');
});

it('registers calculator field playground slug', function () {
    $definition = Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry::definitions()['calculator-field'] ?? null;

    expect($definition)->not->toBeNull()
        ->and($definition['playground'])->toBe(Bjanczak\FilamentFlexFields\Support\Playground\CalculatorFieldPlayground::class);
});

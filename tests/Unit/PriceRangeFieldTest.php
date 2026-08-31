<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField;

it('exposes price range styling and configuration api', function () {
    $field = PriceRangeField::make('budget')
        ->min(0)
        ->max(5000)
        ->step(10)
        ->prefix('$')
        ->histogram([30, 80, 55])
        ->size('lg')
        ->variant('flat')
        ->showInputs(false)
        ->integer(false)
        ->decimalPlaces(2);

    expect($field->getMin())->toBe(0)
        ->and($field->getMax())->toBe(5000)
        ->and($field->getStep())->toBe(10)
        ->and($field->getPrefix())->toBe('$')
        ->and($field->getHistogram())->toBe([30.0, 80.0, 55.0])
        ->and($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('flat')
        ->and($field->shouldShowInputs())->toBeFalse()
        ->and($field->isInteger())->toBeFalse()
        ->and($field->getDecimalPlaces())->toBe(2);
});

it('uses a default histogram when none is configured', function () {
    $field = PriceRangeField::make('budget');

    expect($field->getHistogram())->toHaveCount(32);
});

it('normalizes state and keeps values within bounds', function () {
    $field = PriceRangeField::make('budget')
        ->min(0)
        ->max(1000)
        ->step(1);

    expect($field->normalizeState(['min' => 1200, 'max' => -10]))
        ->toBe(['min' => 1000, 'max' => 1000]);
});

it('pushes max up when min exceeds max during normalization', function () {
    $field = PriceRangeField::make('budget')
        ->min(0)
        ->max(1000);

    expect($field->normalizeState(['min' => 900, 'max' => 100]))
        ->toBe(['min' => 900, 'max' => 900]);
});

it('rejects unsupported price range variants', function () {
    PriceRangeField::make('budget')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('includes wrapper classes for size and variant', function () {
    $field = PriceRangeField::make('budget')
        ->size('sm')
        ->variant('secondary');

    expect($field->getWrapperClasses())->toBe([
        'fff-price-range-field',
        'fff-price-range-field--sm',
        'fff-rounding-md',
        'fff-price-range-field--secondary',
    ]);
});

it('normalizes legacy price range variant names', function () {
    expect(PriceRangeField::make('budget')->variant('bordered')->getVariant())->toBe('primary')
        ->and(PriceRangeField::make('budget')->variant('faded')->getVariant())->toBe('secondary');
});

it('allows disabling the currency prefix', function () {
    $field = PriceRangeField::make('budget')->withoutPrefix();

    expect($field->hasPrefix())->toBeFalse()
        ->and($field->getPrefix())->toBeNull();
});

it('uses translated default input labels', function () {
    $field = PriceRangeField::make('budget');

    expect($field->getMinInputLabel())->toBe(__('filament-flex-fields::default.price_range.min'))
        ->and($field->getMaxInputLabel())->toBe(__('filament-flex-fields::default.price_range.max'));
});

it('styles inputs and histogram caps from global rounding tokens', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/components/price-range.css');

    expect($css)
        ->toContain('--fff-price-range-radius: var(--fff-global-radius, 0.75rem)')
        ->toContain('border-radius: var(--fff-price-range-radius)')
        ->toContain('--fff-price-range-text-size: var(--fff-text-lg)')
        ->toContain('--fff-price-range-input-p: 0.75rem')
        ->toContain('transition-[border-color,box-shadow]')
        ->toContain('outline: var(--fff-field-focus-ring-width) solid transparent')
        ->not->toContain('transition-[border-color,box-shadow,outline-color]');
});

it('supports explicit rounding api values', function () {
    expect(PriceRangeField::make('budget')->rounding('full')->getWrapperClasses())
        ->toContain('fff-rounding-full')
        ->and(PriceRangeField::make('budget')->rounding('native')->getWrapperClasses())
        ->toContain('fff-rounding-native');
});

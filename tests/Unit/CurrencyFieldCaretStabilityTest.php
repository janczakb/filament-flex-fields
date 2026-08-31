<?php

declare(strict_types=1);

/**
 * Pure-function helpers for CurrencyField caret/typing stability tests.
 * (Mirrors assertions also covered in tests/js/currency-field.test.mjs.)
 */

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;

it('keeps currency field caret CSS layout-neutral (no digit shove)', function (): void {
    $css = file_get_contents(__DIR__.'/../../resources/css/components/currency-field.css');

    expect($css)
        ->toContain('.fff-currency-field__caret')
        ->toContain('width: 0')
        ->toContain('.fff-currency-field__caret::after')
        ->toContain('min-width: 1ch')
        ->and($css)->not->toContain('margin-inline: 2px');
});

it('does not force decimal mode on focus in alpine source', function (): void {
    $js = file_get_contents(__DIR__.'/../../resources/js/components/currency-field.js');

    expect($js)
        ->toContain('Do not force `inDecimal` on focus')
        ->and($js)->not->toMatch('/onFocus\(\)\s*\{[\s\S]*if \(decimals > 0 && ! this\.isEmpty\) \{\s*this\.edit\.inDecimal = true/');
});

it('does not pad visible fraction digits on blur in alpine source', function (): void {
    $js = file_get_contents(__DIR__.'/../../resources/js/components/currency-field.js');

    expect($js)
        ->toContain('normalizeEditForBlur')
        ->toContain('Already focused: focus will not re-fire')
        ->and($js)->not->toMatch('/onBlur\([\s\S]*?padEnd\(decimals,\s*\'0\'\)/');
});

it('ships cursor positioning hooks on the currency field blade', function (): void {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/currency-field.blade.php');

    expect($blade)
        ->toContain('onHiddenPointerDown')
        ->toContain('focusInput()')
        ->toContain('data-fff-cursor-before')
        ->toContain('data-fff-cursor-after')
        ->toContain('fff-currency-field__caret');
});

it('defaults to animated digits with commit-on-blur decimals', function (): void {
    $field = CurrencyField::make('price')->currency('PLN');

    expect($field->isAnimated())->toBeTrue()
        ->and($field->shouldCommitDecimalsOnBlur())->toBeTrue();
});

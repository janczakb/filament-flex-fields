<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField;
use Bjanczak\FilamentFlexFields\StateCasts\CountryFieldStateCast;
use Bjanczak\FilamentFlexFields\StateCasts\CurrencyFieldStateCast;
use Bjanczak\FilamentFlexFields\StateCasts\PhoneFieldStateCast;
use Bjanczak\FilamentFlexFields\StateCasts\PriceRangeFieldStateCast;

it('registers currency field state cast', function () {
    $field = CurrencyField::make('amount')->currency('PLN');
    $casts = collect($field->getDefaultStateCasts());

    expect($casts->contains(fn ($cast): bool => $cast instanceof CurrencyFieldStateCast))->toBeTrue();
});

it('normalizes currency state through state cast', function () {
    $field = CurrencyField::make('amount')->currency('PLN');
    $cast = app(CurrencyFieldStateCast::class, ['field' => $field]);

    expect($cast->set(1250))->toBe(1250)
        ->and($cast->get(1250))->toBe(1250);
});

it('registers phone field state cast', function () {
    $field = PhoneField::make('phone')->defaultCountry('PL');
    $casts = collect($field->getDefaultStateCasts());

    expect($casts->contains(fn ($cast): bool => $cast instanceof PhoneFieldStateCast))->toBeTrue();
});

it('normalizes phone state through state cast', function () {
    $field = PhoneField::make('phone')->defaultCountry('PL');
    $cast = app(PhoneFieldStateCast::class, ['field' => $field]);

    $normalized = $cast->set([
        'country' => 'PL',
        'national' => '512345678',
        'e164' => '',
    ]);

    expect($normalized['country'])->toBe('PL')
        ->and($normalized['national'])->toBe('512345678')
        ->and($cast->get($normalized))->toBe($normalized);
});

it('registers country field state cast', function () {
    $field = CountryField::make('country')->countries(['PL', 'DE']);
    $casts = collect($field->getDefaultStateCasts());

    expect($casts->contains(fn ($cast): bool => $cast instanceof CountryFieldStateCast))->toBeTrue();
});

it('normalizes country state through state cast', function () {
    $field = CountryField::make('country')->countries(['PL', 'DE']);
    $cast = app(CountryFieldStateCast::class, ['field' => $field]);

    expect($cast->set('pl'))->toBe('PL')
        ->and($cast->get('PL'))->toBe('PL');
});

it('registers price range field state cast', function () {
    $field = PriceRangeField::make('budget')->min(0)->max(1000);
    $casts = collect($field->getDefaultStateCasts());

    expect($casts->contains(fn ($cast): bool => $cast instanceof PriceRangeFieldStateCast))->toBeTrue();
});

it('normalizes price range state through state cast', function () {
    $field = PriceRangeField::make('budget')->min(0)->max(1000);
    $cast = app(PriceRangeFieldStateCast::class, ['field' => $field]);

    expect($cast->set(['min' => 50, 'max' => 250]))->toBe([
        'min' => 50,
        'max' => 250,
    ])->and($cast->get(['min' => 50, 'max' => 250]))->toBe([
        'min' => 50,
        'max' => 250,
    ]);
});

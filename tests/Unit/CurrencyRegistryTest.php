<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Support\CurrencyCountries;
use Bjanczak\FilamentFlexFields\Support\CurrencyRegistry;
use Bjanczak\FilamentFlexFields\Support\CurrencyRegistryQueue;

it('queues the iso currency registry pool', function () {
    CurrencyRegistryQueue::reset();

    expect(CurrencyRegistryQueue::enqueue(CurrencyRegistry::POOL_ISO))->toBeTrue()
        ->and(CurrencyRegistryQueue::enqueue(CurrencyRegistry::POOL_ISO))->toBeFalse()
        ->and(CurrencyRegistryQueue::pools())->toBe([CurrencyRegistry::POOL_ISO]);
});

it('exports a compact currency registry payload', function () {
    $payload = CurrencyRegistry::payload([CurrencyRegistry::POOL_ISO]);

    expect($payload)
        ->toHaveKeys(['locale', 'pools'])
        ->and($payload['pools'][CurrencyRegistry::POOL_ISO]['PLN'])->toHaveKeys(['s', 'n', 'd', 'l'])
        ->and(count($payload['pools'][CurrencyRegistry::POOL_ISO]))->toBe(count(CurrencyCountries::allSupportedCodes()));
});

it('keeps single-currency and small whitelists inline', function () {
    $single = CurrencyField::make('price')->currency('PLN');
    $small = CurrencyField::make('price')->currencies(['EUR', 'USD', 'PLN']);

    expect($single->shouldUseCurrencyRegistry())->toBeFalse()
        ->and($small->shouldUseCurrencyRegistry())->toBeFalse()
        ->and(count($small->getCurrenciesMetadata()))->toBe(3);
});

it('defers large currency whitelists through the shared registry', function () {
    CurrencyRegistryQueue::reset();

    $codes = CurrencyCountries::allSupportedCodes();

    expect(count($codes))->toBeGreaterThan(CurrencyRegistry::INLINE_METADATA_MAX);

    $field = CurrencyField::make('budget')
        ->currencies($codes)
        ->currency('EUR');

    CurrencyRegistryQueue::enqueue(CurrencyRegistry::POOL_ISO);

    expect($field->shouldUseCurrencyRegistry())->toBeTrue()
        ->and($field->getCurrencyFilterKey())->not->toBeNull()
        ->and(method_exists($field, 'getSelectedCurrencySeed'))->toBeTrue();

    $html = CurrencyRegistryQueue::renderScriptOnce();

    expect($html)
        ->toContain('<template id="fff-currency-registry-data"')
        ->toContain('"iso"');
});

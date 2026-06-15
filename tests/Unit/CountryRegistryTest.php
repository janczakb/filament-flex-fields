<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Support\Countries;
use Bjanczak\FilamentFlexFields\Support\CountryRegistry;
use Bjanczak\FilamentFlexFields\Support\CountryRegistryQueue;
use Bjanczak\FilamentFlexFields\Support\PhoneCountries;

it('queues separate country registry pools for iso and phone fields', function () {
    CountryRegistryQueue::reset();

    CountryRegistryQueue::enqueue(CountryRegistry::POOL_ISO);
    CountryRegistryQueue::enqueue(CountryRegistry::POOL_PHONE);

    expect(CountryRegistryQueue::pools())
        ->toBe([CountryRegistry::POOL_ISO, CountryRegistry::POOL_PHONE]);
});

it('deduplicates queued country registry pools', function () {
    CountryRegistryQueue::reset();

    expect(CountryRegistryQueue::enqueue(CountryRegistry::POOL_ISO))->toBeTrue()
        ->and(CountryRegistryQueue::enqueue(CountryRegistry::POOL_ISO))->toBeFalse()
        ->and(CountryRegistryQueue::pools())->toBe([CountryRegistry::POOL_ISO]);
});

it('exports compact iso and phone registry payloads separately', function () {
    $payload = CountryRegistry::payload([
        CountryRegistry::POOL_ISO,
        CountryRegistry::POOL_PHONE,
    ]);

    expect($payload)
        ->toHaveKeys(['locale', 'pools'])
        ->and($payload['pools'])->toHaveKeys([CountryRegistry::POOL_ISO, CountryRegistry::POOL_PHONE])
        ->and($payload['pools'][CountryRegistry::POOL_ISO]['PL'])->toHaveKeys(['c', 'n', 'd', 'f'])
        ->and($payload['pools'][CountryRegistry::POOL_PHONE]['PL'])->toHaveKeys(['c', 'n', 'd', 'f'])
        ->and(count($payload['pools'][CountryRegistry::POOL_ISO]))->toBe(count(Countries::allCodes()))
        ->and(count($payload['pools'][CountryRegistry::POOL_PHONE]))->toBe(count(PhoneCountries::allSupportedCodes()));
});

it('expands compact country registry entries', function () {
    expect(CountryRegistry::expand([
        'c' => 'PL',
        'n' => 'Poland',
        'd' => '+48',
        'f' => 'https://example.test/pl.svg',
    ]))->toBe([
        'code' => 'PL',
        'name' => 'Poland',
        'dial_code' => '+48',
        'flag_url' => 'https://example.test/pl.svg',
    ]);
});

it('exposes iso pool on country field and phone pool on phone field', function () {
    expect(CountryField::make('country')->getCountryPool())->toBe(CountryRegistry::POOL_ISO)
        ->and(PhoneField::make('phone')->getCountryPool())->toBe(CountryRegistry::POOL_PHONE);
});

it('exposes selected country metadata api on both fields', function () {
    expect(method_exists(CountryField::make('country'), 'getSelectedCountryMetadata'))->toBeTrue()
        ->and(method_exists(PhoneField::make('phone'), 'getSelectedCountryMetadata'))->toBeTrue()
        ->and(method_exists(CountryField::make('country'), 'getCountryPool'))->toBeTrue()
        ->and(method_exists(PhoneField::make('phone'), 'getResolvedCountryCodes'))->toBeTrue();
});

it('country and phone field blades use the shared registry instead of embedding countries', function () {
    $countryBlade = file_get_contents(__DIR__.'/../../resources/views/forms/components/country-field.blade.php');
    $phoneBlade = file_get_contents(__DIR__.'/../../resources/views/forms/components/phone-field.blade.php');

    expect($countryBlade)
        ->toContain('countryPool:')
        ->toContain('countryFilterKey:')
        ->toContain('selectedCountrySeed:')
        ->not->toContain('countries: @js($countries)')
        ->not->toContain('allowedCountryCodes:');

    expect($phoneBlade)
        ->toContain('countryPool:')
        ->toContain('countryFilterKey:')
        ->toContain('selectedCountrySeed:')
        ->not->toContain('countries: @js($countries)')
        ->not->toContain('allowedCountryCodes:');
});

it('renders shared country registry data from the requested pools', function () {
    CountryRegistryQueue::reset();
    CountryRegistryQueue::enqueue(CountryRegistry::POOL_ISO);

    $html = view('filament-flex-fields::partials.country-registry-data', [
        'pools' => CountryRegistryQueue::pools(),
    ])->render();

    expect($html)
        ->toContain('<template id="fff-country-registry-data"')
        ->toContain('"iso"')
        ->not->toContain('"phone"');

    expect(CountryRegistryQueue::renderScriptOnce())
        ->toContain('id="fff-country-registry-data"');

    expect(CountryRegistryQueue::renderScriptOnce())->toBe('');
});

it('re-renders country registry when additional pools are queued after the first field', function () {
    CountryRegistryQueue::reset();
    CountryRegistryQueue::enqueue(CountryRegistry::POOL_ISO);

    $first = CountryRegistryQueue::renderScriptOnce();

    expect($first)
        ->toContain('"iso"')
        ->not->toContain('"phone"');

    CountryRegistryQueue::enqueue(CountryRegistry::POOL_PHONE);

    $second = CountryRegistryQueue::renderScriptOnce();

    expect($second)
        ->toContain('"iso"')
        ->toContain('"phone"');
});

it('renders registry template inline when the first country field loads assets', function () {
    CountryRegistryQueue::reset();

    $html = view('filament-flex-fields::partials.load-stylesheet', [
        'component' => 'country-field',
    ])->render();

    expect($html)->not->toContain('fff-country-registry-data');

    $fieldHtml = CountryRegistryQueue::renderScriptOnce();

    expect($fieldHtml)
        ->toContain('<template id="fff-country-registry-data"')
        ->toContain('"iso"')
        ->not->toContain('"phone"');
});

it('enqueues only the iso pool when a country field loads assets', function () {
    CountryRegistryQueue::reset();

    view('filament-flex-fields::partials.load-stylesheet', [
        'component' => 'country-field',
    ])->render();

    expect(CountryRegistryQueue::pools())->toBe([CountryRegistry::POOL_ISO]);
});

it('enqueues only the phone pool when a phone field loads assets', function () {
    CountryRegistryQueue::reset();

    view('filament-flex-fields::partials.load-stylesheet', [
        'component' => 'phone-field',
    ])->render();

    expect(CountryRegistryQueue::pools())->toBe([CountryRegistry::POOL_PHONE]);
});

it('deduplicates shared country filters in the registry payload', function () {
    CountryRegistryQueue::reset();
    CountryRegistryQueue::enqueue(CountryRegistry::POOL_ISO);

    $firstKey = CountryRegistryQueue::registerCountryFilter(['PL', 'DE']);
    $secondKey = CountryRegistryQueue::registerCountryFilter(['DE', 'PL']);

    $html = view('filament-flex-fields::partials.country-registry-data', [
        'pools' => CountryRegistryQueue::pools(),
        'filters' => app(CountryRegistryQueue::class)->registeredFilters(),
    ])->render();

    expect($firstKey)->toBe($secondKey)
        ->and($html)->toContain('"filters"')
        ->and($html)->toContain('"PL"')
        ->and($html)->toContain('"DE"');
});

it('exposes country filter helpers on both fields', function () {
    expect(CountryField::make('country')->hasCustomCountryCodeFilter())->toBeFalse()
        ->and(CountryField::make('country')->getCountryFilterKey())->toBeNull()
        ->and(CountryField::make('country')->countries(['PL', 'DE'])->hasCustomCountryCodeFilter())->toBeTrue()
        ->and(CountryField::make('country')->countries(['PL', 'DE'])->getCountryFilterKey())->not->toBeNull();
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Countries;
use Bjanczak\FilamentFlexFields\Support\PhoneCountries;

it('exposes a complete iso country list separate from phone regions', function () {
    $isoCodes = Countries::allCodes();
    $phoneCodes = PhoneCountries::allSupportedCodes();

    expect(count($isoCodes))->toBeGreaterThan(count($phoneCodes))
        ->and($isoCodes)->toContain('PL', 'US', 'AQ', 'TF', 'UM')
        ->and(array_diff($phoneCodes, $isoCodes))->toBe([]);
});

it('resolves country names and flags from translations', function () {
    expect(Countries::name('PL'))->toBe(__('filament-flex-fields::countries.PL'))
        ->and(Countries::flagUrl('PL'))->toBe(PhoneCountries::flagUrl('PL'));
});

it('returns dial code only for phone supported regions', function () {
    expect(Countries::dialCode('PL'))->toBe('+48')
        ->and(Countries::dialCode('AQ'))->toBeNull();
});

it('maps browser locale to country code', function () {
    expect(Countries::fromBrowserLocale(['PL', 'US'], 'pl-PL'))->toBe('PL')
        ->and(Countries::fromBrowserLocale(['PL', 'US'], 'en-US'))->toBe('US')
        ->and(Countries::fromBrowserLocale(['PL', 'DE'], 'de'))->toBe('DE');
});

it('maps ordered browser languages to the first supported country', function () {
    expect(Countries::fromBrowserLanguages(['de-DE', 'pl-PL'], ['PL', 'DE']))->toBe('DE')
        ->and(Countries::fromBrowserLanguages(['fr-FR', 'pl-PL'], ['PL', 'DE']))->toBe('PL');
});

it('exposes country metadata with nullable dial codes', function () {
    $metadata = Countries::metadata(['PL', 'AQ']);

    expect($metadata)->toHaveCount(2)
        ->and(collect($metadata)->firstWhere('code', 'PL')['dial_code'])->toBe('+48')
        ->and(collect($metadata)->firstWhere('code', 'AQ')['dial_code'])->toBeNull();
});

it('sorts preferred country first in metadata list', function () {
    $metadata = Countries::metadata(['PL', 'US', 'DE']);
    $sorted = Countries::sortWithPreferredFirst($metadata, 'US');

    expect($sorted[0]['code'])->toBe('US');
});

it('PhoneCountries::metadata caches country names correctly per locale', function () {
    app()->setLocale('pl');
    $plMetadata = PhoneCountries::metadata(['PL']);
    $plName = $plMetadata[0]['name'];

    app()->setLocale('en');
    $enMetadata = PhoneCountries::metadata(['PL']);
    $enName = $enMetadata[0]['name'];

    expect($plName)->toBe(__('filament-flex-fields::countries.PL', [], 'pl'))
        ->and($enName)->toBe(__('filament-flex-fields::countries.PL', [], 'en'));
});

it('lazily resolves and caches country metadata on demand', function () {
    $cacheProperty = new ReflectionProperty(Countries::class, 'metadataCache');
    $cacheProperty->setAccessible(true);
    $cacheProperty->setValue(null, []);

    $metadata = Countries::metadata(['PL']);
    expect($metadata)->toHaveCount(1)
        ->and($cacheProperty->getValue(null)[app()->getLocale()])->toHaveKey('PL')
        ->and($cacheProperty->getValue(null)[app()->getLocale()])->not->toHaveKey('US');
});

it('lazily resolves and caches phone country metadata on demand', function () {
    $cacheProperty = new ReflectionProperty(PhoneCountries::class, 'metadataCache');
    $cacheProperty->setAccessible(true);
    $cacheProperty->setValue(null, []);

    $metadata = PhoneCountries::metadata(['PL']);
    expect($metadata)->toHaveCount(1)
        ->and($cacheProperty->getValue(null)[app()->getLocale()])->toHaveKey('PL')
        ->and($cacheProperty->getValue(null)[app()->getLocale()])->not->toHaveKey('US');
});

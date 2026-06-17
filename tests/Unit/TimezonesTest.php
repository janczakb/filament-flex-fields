<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Timezones;

it('exposes all iana timezone identifiers', function () {
    expect(count(Timezones::allIdentifiers()))->toBeGreaterThan(400)
        ->and(Timezones::allIdentifiers())->toContain('Europe/Warsaw', 'UTC', 'America/New_York');
});

it('formats timezone labels with utc offset', function () {
    expect(Timezones::label('UTC'))->toBe('UTC (UTC+00:00)')
        ->and(Timezones::formatOffset('Europe/Warsaw'))->toMatch('/^UTC[+-]\d{2}:\d{2}$/');
});

it('resolves localized timezone display names via intl when available', function () {
    if (! extension_loaded('intl')) {
        expect(Timezones::displayName('Europe/Warsaw'))->toBe('Warsaw');

        return;
    }

    app()->setLocale('en');

    expect(Timezones::displayName('Europe/Warsaw'))->toContain('Poland')
        ->and(Timezones::displayName('America/New_York'))->not->toBe('America/New_York');

    app()->setLocale('pl');

    expect(Timezones::displayName('Europe/Warsaw'))->toContain('Polska');
});

it('prefers published translation overrides over intl', function () {
    app()->setLocale('en');

    $displayNameCache = new ReflectionProperty(Timezones::class, 'displayNameCache');
    $displayNameCache->setAccessible(true);
    $displayNameCache->setValue(null, []);

    expect(Timezones::translationKey('Europe/Warsaw'))->toBe('Europe__Warsaw')
        ->and(Timezones::displayName('UTC'))->toBe('UTC');

    app('translator')->addLines([
        'timezones.America__Chicago' => 'Custom Chicago',
    ], 'en', 'filament-flex-fields');

    expect(Timezones::displayName('America/Chicago'))->toBe('Custom Chicago')
        ->and(Timezones::label('America/Chicago'))->toMatch('/^Custom Chicago \(UTC[+-]\d{2}:\d{2}\)$/');
});

it('invalidates timezone metadata cache when locale changes', function () {
    if (! extension_loaded('intl')) {
        expect(true)->toBeTrue();

        return;
    }

    app()->setLocale('en');

    $english = Timezones::metadata(['Europe/Warsaw'])[0]['label'];

    app()->setLocale('pl');

    $polish = Timezones::metadata(['Europe/Warsaw'])[0]['label'];

    expect($polish)->not->toBe($english)
        ->and($polish)->toMatch('/\(UTC[+-]\d{2}:\d{2}\)$/');
});

it('resolves timezone whitelist and blacklist', function () {
    expect(Timezones::resolve(['Europe/Warsaw', 'UTC', 'Invalid/Zone']))
        ->toBe(['Europe/Warsaw', 'UTC']);

    expect(Timezones::resolve(null, ['Etc/GMT+1']))
        ->not->toContain('Etc/GMT+1');
});

it('builds timezone metadata and select options', function () {
    $metadata = Timezones::metadata(['Europe/Warsaw', 'UTC']);

    expect($metadata)->toHaveCount(2)
        ->and($metadata[0])->toHaveKeys(['id', 'label', 'offset', 'offset_seconds', 'region'])
        ->and(Timezones::selectOptions(['UTC']))->toHaveKey('UTC');
});

it('sorts preferred timezone first in metadata list', function () {
    $metadata = Timezones::metadata(['Europe/Warsaw', 'UTC', 'America/New_York']);
    $sorted = Timezones::sortWithPreferredFirst($metadata, 'UTC');

    expect($sorted[0]['id'])->toBe('UTC');
});

it('maps browser timezone candidates to allowed identifiers', function () {
    expect(Timezones::fromBrowserTimezoneCandidates(['Europe/Warsaw', 'Invalid/Zone'], ['Europe/Warsaw', 'UTC']))
        ->toBe('Europe/Warsaw');
});

it('invalidates timezone metadata cache when date changes', function () {
    $property = new ReflectionProperty(Timezones::class, 'cachedDate');
    $property->setAccessible(true);
    $property->setValue(null, '2020-01-01');

    $cacheProperty = new ReflectionProperty(Timezones::class, 'metadataCache');
    $cacheProperty->setAccessible(true);
    $cacheProperty->setValue(null, ['UTC' => ['id' => 'UTC', 'label' => 'Cached UTC', 'offset' => 'UTC+00:00', 'offset_seconds' => 0, 'region' => 'UTC']]);

    $metadata = Timezones::metadata(['UTC']);
    expect($metadata[0]['label'])->not->toBe('Cached UTC')
        ->and($metadata[0]['label'])->toBe('UTC (UTC+00:00)');
});

it('lazily resolves and caches timezone metadata on demand', function () {
    $cacheProperty = new ReflectionProperty(Timezones::class, 'metadataCache');
    $cacheProperty->setAccessible(true);
    $cacheProperty->setValue(null, []);

    $metadata = Timezones::metadata(['UTC']);
    expect($metadata)->toHaveCount(1)
        ->and($cacheProperty->getValue(null))->toHaveKey('UTC')
        ->and($cacheProperty->getValue(null))->not->toHaveKey('Europe/Warsaw');
});

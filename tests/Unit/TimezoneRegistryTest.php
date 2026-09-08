<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;
use Bjanczak\FilamentFlexFields\Support\TimezoneRegistry;
use Bjanczak\FilamentFlexFields\Support\TimezoneRegistryQueue;
use Bjanczak\FilamentFlexFields\Support\Timezones;

it('queues the iana timezone registry pool', function () {
    TimezoneRegistryQueue::reset();

    expect(TimezoneRegistryQueue::enqueue(TimezoneRegistry::POOL_IANA))->toBeTrue()
        ->and(TimezoneRegistryQueue::enqueue(TimezoneRegistry::POOL_IANA))->toBeFalse()
        ->and(TimezoneRegistryQueue::pools())->toBe([TimezoneRegistry::POOL_IANA]);
});

it('exports a compact iana timezone registry payload', function () {
    $payload = TimezoneRegistry::payload([TimezoneRegistry::POOL_IANA]);

    expect($payload)
        ->toHaveKeys(['locale', 'pools'])
        ->and($payload['pools'][TimezoneRegistry::POOL_IANA]['Europe/Warsaw'])->toHaveKeys(['l', 'o', 'r'])
        ->and(count($payload['pools'][TimezoneRegistry::POOL_IANA]))->toBe(count(Timezones::allIdentifiers()));
});

it('renders the timezone registry template once per request', function () {
    TimezoneRegistryQueue::reset();
    TimezoneRegistryQueue::enqueue(TimezoneRegistry::POOL_IANA);

    $first = TimezoneRegistryQueue::renderScriptOnce();

    expect($first)
        ->toContain('<template id="fff-timezone-registry-data"')
        ->toContain('"iana"')
        ->toContain('Europe\\/Warsaw');

    expect(TimezoneRegistryQueue::renderScriptOnce())->toBe('');
});

it('uses the shared registry for the default full iana list', function () {
    $full = TimezoneField::make('timezone');
    $whitelist = TimezoneField::make('timezone')->timezones(['Europe/Warsaw', 'UTC']);

    expect($full->shouldUseTimezoneRegistry())->toBeTrue()
        ->and($full->getOptionsForJs())->toBe([])
        ->and($full->getBrowserTimezoneBootCatalog())->toBe([])
        ->and($whitelist->shouldUseTimezoneRegistry())->toBeFalse()
        ->and($whitelist->getOptionsForJs())->not->toBeEmpty();
});

it('builds selected timezone seed metadata for ssr when an id is known', function () {
    $metadata = Timezones::metadata(['Europe/Warsaw'], [], 'en')[0] ?? null;

    expect($metadata)->not->toBeNull()
        ->and($metadata['id'])->toBe('Europe/Warsaw')
        ->and($metadata['label'])->not->toBe('')
        ->and($metadata['offset'])->toStartWith('UTC');
});

it('registers timezone filters for exceptTimezones on the full catalog', function () {
    TimezoneRegistryQueue::reset();
    TimezoneRegistryQueue::enqueue(TimezoneRegistry::POOL_IANA);

    $field = TimezoneField::make('timezone')->exceptTimezones(['America/Adak']);
    $key = $field->getTimezoneFilterKey();

    expect($key)->not->toBeNull()
        ->and(app(TimezoneRegistryQueue::class)->registeredFilters()[$key] ?? null)
        ->not->toContain('America/Adak')
        ->and(app(TimezoneRegistryQueue::class)->registeredFilters()[$key] ?? null)
        ->toContain('Europe/Warsaw');
});

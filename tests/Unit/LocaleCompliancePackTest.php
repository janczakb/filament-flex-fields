<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\Compliance\CompliancePack;
use Bjanczak\FilamentFlexFields\Support\Locale\FlexFieldsLocale;
use Carbon\Carbon;

it('lists supported locales including new locale stubs', function (): void {
    expect(FlexFieldsLocale::supportedLocales())->toBe([
        'en',
        'pl',
        'de',
        'fr',
        'es',
        'pt_BR',
        'nl',
        'it',
    ]);
});

it('resolves locale codes with normalization and fallback', function (): void {
    expect(FlexFieldsLocale::resolve('de'))->toBe('de')
        ->and(FlexFieldsLocale::resolve('pt-br'))->toBe('pt_BR')
        ->and(FlexFieldsLocale::resolve('PT_br'))->toBe('pt_BR')
        ->and(FlexFieldsLocale::resolve(null))->toBe('en')
        ->and(FlexFieldsLocale::resolve(''))->toBe('en')
        ->and(FlexFieldsLocale::resolve('xx'))->toBe('en');
});

it('formats money with intl or deterministic fallback', function (): void {
    $formatted = FlexFieldsLocale::formatMoney(1234.5, 'usd', 'en');

    if (class_exists(NumberFormatter::class)) {
        expect($formatted)->toContain('1');
    } else {
        expect($formatted)->toBe('USD 1234.50');
    }
});

it('formats dates with intl or iso fallback', function (): void {
    $date = Carbon::parse('2026-03-15');
    $formatted = FlexFieldsLocale::formatDate($date, 'en');

    if (extension_loaded('intl')) {
        expect($formatted)->toContain('2026');
    } else {
        expect($formatted)->toBe('2026-03-15');
    }
});

it('counts field types for compliance inventory', function (): void {
    expect(CompliancePack::inventoryFields())->toBe(count(FieldType::cases()));
});

it('builds aa matrix with baseline pass components and pending remainder', function (): void {
    CompliancePack::clearOverrides();

    $matrix = CompliancePack::aaMatrix();

    expect($matrix)->not->toBeEmpty()
        ->and($matrix)->toHaveKey('select-field')
        ->and($matrix['select-field'])->toBe(CompliancePack::STATUS_PASS)
        ->and(in_array('pending', array_values($matrix), true))->toBeTrue()
        ->and(CompliancePack::baselinePassComponents())->toContain('select-field');
});

it('allows compliance status overrides for audit workflows', function (): void {
    CompliancePack::clearOverrides();
    CompliancePack::mark('select-field', CompliancePack::STATUS_FAIL);

    expect(CompliancePack::aaMatrix()['select-field'])->toBe(CompliancePack::STATUS_FAIL);

    CompliancePack::clearOverrides();
});

it('exports compliance report snapshot', function (): void {
    CompliancePack::clearOverrides();

    $report = CompliancePack::exportReport();

    expect($report)->toHaveKeys(['generated_at', 'locales', 'field_type_count', 'aa_matrix', 'summary', 'criteria', 'standard'])
        ->and($report['locales'])->toBe(FlexFieldsLocale::supportedLocales())
        ->and($report['field_type_count'])->toBe(CompliancePack::inventoryFields())
        ->and($report['aa_matrix'])->toBe(CompliancePack::aaMatrix())
        ->and($report['summary']['pass'])->toBeGreaterThan(0)
        ->and($report['criteria'])->not->toBeEmpty()
        ->and($report['generated_at'])->not->toBe('');
});

it('loads locale stub translation files for new locales', function (string $locale): void {
    $path = __DIR__."/../../resources/lang/{$locale}/default.php";

    expect(file_exists($path))->toBeTrue();

    $translations = require $path;

    expect($translations)->toHaveKeys(['categories', 'field_types', 'select_field'])
        ->and($translations['field_types'])->toHaveKey('select')
        ->and($translations['field_types'])->toHaveKey('schedule')
        ->and($translations['select_field'])->toHaveKeys(['loading', 'searching', 'no_options', 'no_search_results']);
})->with(['de', 'fr', 'es', 'pt_BR', 'nl', 'it']);

it('translates german select field loading beyond the english copy', function (): void {
    $translations = require __DIR__.'/../../resources/lang/de/default.php';
    $english = require __DIR__.'/../../resources/lang/en/default.php';

    expect($translations['select_field']['loading'])
        ->not->toBe($english['select_field']['loading'])
        ->not->toBe('Loading...')
        ->not->toBe('Loading…')
        ->and($translations['select_field']['loading'])->toBe('Wird geladen…');
});

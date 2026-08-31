<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Schema\SchemaBlueprintPacks;
use Bjanczak\FilamentFlexFields\Support\Schema\SchemaImportExport;

it('exposes wave-1 and wave-2 blueprint packs', function () {
    expect(SchemaBlueprintPacks::names())->toBe([
        'crm',
        'hr',
        'booking',
        'inventory',
        'support',
        'onboarding',
    ]);
});

it('returns sample field groups with two or more fields per pack', function () {
    foreach (SchemaBlueprintPacks::names() as $name) {
        $pack = SchemaBlueprintPacks::pack($name);

        expect($pack)->toBeArray()
            ->and($pack)->toHaveKeys(['key', 'label', 'target', 'fields'])
            ->and(count($pack['fields']))->toBeGreaterThanOrEqual(3);
    }
});

it('validates blueprint packs through schema import export dry run', function () {
    $validator = new SchemaImportExport;

    foreach (SchemaBlueprintPacks::names() as $name) {
        $pack = SchemaBlueprintPacks::pack($name);

        expect($validator->dryRunValidate($pack))
            ->toBe(['ok' => true, 'errors' => []]);
    }
});

it('returns null for unknown blueprint pack names', function () {
    expect(SchemaBlueprintPacks::pack('unknown-pack'))->toBeNull();
});

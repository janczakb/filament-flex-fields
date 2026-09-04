<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

it('keeps stylesheet dependency keys and targets in lazy registry', function (): void {
    $lazy = FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS;

    foreach (array_keys(FlexFieldAssets::STYLESHEET_DEPENDENCIES) as $component) {
        expect(in_array($component, $lazy, true))
            ->toBeTrue("Stylesheet dependency key [{$component}] must be registered in LAZY_COMPONENT_STYLESHEETS.");
    }

    foreach (FlexFieldAssets::STYLESHEET_DEPENDENCIES as $dependencies) {
        foreach ($dependencies as $target) {
            expect(in_array($target, $lazy, true))
                ->toBeTrue("Stylesheet dependency target [{$target}] must be registered in LAZY_COMPONENT_STYLESHEETS.");
        }
    }
});

it('registers segment-tabs lazy stylesheet with segment-control dependency', function (): void {
    expect(FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS)->toContain('segment-tabs')
        ->and(FlexFieldAssets::stylesheetsFor('segment-tabs'))->toBe(['segment-control', 'segment-tabs'])
        ->and(FlexFieldAssets::ssrScriptsFor('segment-tabs'))->toBe([FlexFieldAssets::SEGMENT_OVERFLOW_SSR_SCRIPT_ID])
        ->and(FlexFieldAssets::ssrScriptsFor('segment-control'))->toBe([FlexFieldAssets::SEGMENT_OVERFLOW_SSR_SCRIPT_ID]);
});

it('loads overlay-runtime stylesheet before teleported-menu', function (): void {
    expect(FlexFieldAssets::STYLESHEET_DEPENDENCIES['teleported-menu'])->toBe(['overlay-runtime'])
        ->and(FlexFieldAssets::stylesheetsFor('teleported-menu'))->toBe(['overlay-runtime', 'teleported-menu'])
        ->and(FlexFieldAssets::stylesheetsFor('select-field'))->toContain('overlay-runtime')
        ->and(FlexFieldAssets::stylesheetsFor('select-field'))->toContain('teleported-menu');
});

it('dedupes teleported-menu when planning country timezone and currency fields', function (): void {
    $planned = FlexFieldAssets::planAssetsForComponents([
        'country-field',
        'timezone-field',
        'currency-field',
    ]);

    expect(array_count_values($planned['stylesheets'])['teleported-menu'] ?? 0)->toBe(1);
});

it('dedupes searchable-select-menu and search-normalize chunks across select family fields', function (): void {
    $components = [
        'select-field',
        'phone-field',
        'country-field',
        'timezone-field',
        'currency-field',
        'tags-field',
    ];

    $planned = FlexFieldAssets::planAssetsForComponents($components);
    $manifest = FlexFieldAssets::alpineManifest();
    $chunkModules = $manifest['__chunk_modules__'] ?? [];

    $selectMenuChunk = collect($chunkModules)
        ->filter(fn (array $modules): bool => in_array('core/searchable-select-menu.js', $modules, true))
        ->keys()
        ->first();

    $searchNormalizeChunk = collect($chunkModules)
        ->filter(fn (array $modules): bool => in_array('core/search-normalize.js', $modules, true))
        ->keys()
        ->first();

    expect($selectMenuChunk)->toBeString()
        ->and($searchNormalizeChunk)->toBeString()
        ->and(array_count_values($planned['stylesheets'])['teleported-menu'] ?? 0)->toBe(1)
        ->and(array_count_values($planned['chunks'])[$selectMenuChunk] ?? 0)->toBe(1)
        ->and(array_count_values($planned['chunks'])[$searchNormalizeChunk] ?? 0)->toBe(1)
        ->and($planned['chunks'])->toBe(array_values(array_unique($planned['chunks'])))
        ->and($planned['stylesheets'])->toBe(array_values(array_unique($planned['stylesheets'])));

    $rawSelectMenuCount = 0;
    $rawSearchNormalizeCount = 0;

    foreach ($components as $component) {
        $chunks = FlexFieldAssets::alpineChunksFor($component);

        if (in_array($selectMenuChunk, $chunks, true)) {
            $rawSelectMenuCount++;
        }

        if (in_array($searchNormalizeChunk, $chunks, true)) {
            $rawSearchNormalizeCount++;
        }
    }

    expect($rawSelectMenuCount)->toBeGreaterThan(1)
        ->and($rawSearchNormalizeCount)->toBeGreaterThan(1);
});

it('exports the asset registry with expected top-level keys', function (): void {
    $registry = FlexFieldAssets::exportRegistry();

    expect($registry)->toHaveKeys([
        'lazy_stylesheets',
        'stylesheet_dependencies',
        'playground_aliases',
        'playground_extras',
        'critical_preload',
        'bundles',
    ])
        ->and($registry['lazy_stylesheets'])->toBe(FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS)
        ->and($registry['stylesheet_dependencies'])->toBe(FlexFieldAssets::STYLESHEET_DEPENDENCIES)
        ->and($registry['playground_aliases'])->toBe(FlexFieldAssets::PLAYGROUND_STYLESHEET_ALIASES)
        ->and($registry['playground_extras'])->toBe(FlexFieldAssets::PLAYGROUND_EXTRA_STYLESHEETS)
        ->and($registry['critical_preload'])->toBe(FlexFieldAssets::CRITICAL_PRELOAD_STYLESHEETS);
});

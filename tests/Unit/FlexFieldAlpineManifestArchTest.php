<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

/**
 * Stylesheet bundles that never register an Alpine entry file.
 *
 * @var list<string>
 */
const CSS_ONLY_STYLESHEETS = [
    'teleported-menu',
    'tag-chips',
    'user-display',
    'emoji-picker',
    'switch',
    'item-card',
    'track-slider',
    'progress-bar',
    'progress-circle',
    'color-swatch',
    'cover-card',
    'choice-cards',
    'rich-editor-field',
    'user-column',
    'rating-column',
    'icon-column',
    'map-picker-dropdown',
    'matrix-choice-field',
];

/**
 * Lazy stylesheet ids that share another component's Alpine entry.
 *
 * @var array<string, string>
 */
const STYLESHEET_ALPINE_ENTRY_ALIASES = [
    'user-select' => 'select-field',
    'flex-textarea' => 'flex-textarea',
];

it('maps interactive lazy stylesheets to alpine manifest entries', function (): void {
    $manifest = FlexFieldAssets::alpineManifest();

    foreach (FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS as $stylesheet) {
        if (in_array($stylesheet, CSS_ONLY_STYLESHEETS, true)) {
            continue;
        }

        $entry = STYLESHEET_ALPINE_ENTRY_ALIASES[$stylesheet] ?? $stylesheet;

        expect(array_key_exists($entry, $manifest))
            ->toBeTrue("Missing alpine manifest entry for stylesheet [{$stylesheet}] (expected key [{$entry}]).");
    }
});

it('ships dist files for every alpine entry', function (): void {
    $distPath = dirname(__DIR__, 2).'/resources/dist/components';

    foreach (FlexFieldAssets::alpineEntryNames() as $entry) {
        expect($distPath.'/'.$entry.'.js')
            ->toBeFile("Missing built Alpine entry [{$entry}.js]. Run npm run build:js.");
    }
});

it('allows css-only lazy stylesheets without alpine entries', function (): void {
    $manifest = FlexFieldAssets::alpineManifest();

    foreach (CSS_ONLY_STYLESHEETS as $stylesheet) {
        if (! in_array($stylesheet, FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS, true)) {
            continue;
        }

        expect($manifest)->not->toHaveKey($stylesheet);
    }
});

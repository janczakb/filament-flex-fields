<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;

it('maps every documented playground hub to an existing docs file', function (): void {
    $docsRoot = dirname(__DIR__, 2).'/docs';
    $exempt = FlexFieldsPlaygroundRegistry::docsExemptSlugs();

    foreach (FlexFieldsPlaygroundRegistry::definitions() as $slug => $definition) {
        if (in_array($slug, $exempt, true)) {
            continue;
        }

        $docsPath = $definition['docs_path'] ?? FlexFieldsPlaygroundRegistry::docsPathFor($slug);

        expect($docsPath)
            ->not->toBeNull("Playground slug [{$slug}] must declare a docs_path or registry docs map entry.");

        expect($docsRoot.'/'.$docsPath)
            ->toBeFile("Playground slug [{$slug}] references missing docs file [{$docsPath}].");
    }
});

it('keeps docs-exempt hubs out of the public docs map', function (): void {
    foreach (FlexFieldsPlaygroundRegistry::docsExemptSlugs() as $slug) {
        expect(FlexFieldsPlaygroundRegistry::docsPathFor($slug))->toBeNull();
    }
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Bjanczak\FilamentFlexFields\Support\Playground\PlaygroundRelatedHubs;

it('links layout playground hubs to each other', function (): void {
    $entries = PlaygroundRelatedHubs::viewData('composition-recipes');

    expect($entries['currentSlug'])->toBe('composition-recipes')
        ->and($entries['hubs'])->toHaveCount(4)
        ->and(array_column($entries['hubs'], 'slug'))->toContain('translatable-fields', 'segment-tabs', 'form-layouts', 'item-card-group')
        ->and(array_column($entries['hubs'], 'slug'))->not->toContain('composition-recipes')
        ->and($entries['hubs'][0]['url'] ?? null)->toBeString()->not->toBeEmpty();
});

it('returns no related hubs for non-layout slugs', function (): void {
    expect(PlaygroundRelatedHubs::slugsFor('phone-field'))->toBe([]);
});

it('maps v3 meta hubs to customer docs pages', function (): void {
    $docsRoot = dirname(__DIR__, 2).'/docs';

    foreach ([
        'focus-outline' => 'focus-outline.md',
        'schema-conditions' => 'schema-conditions.md',
        'field-intelligence' => 'field-intelligence.md',
        'admin-columns' => 'admin-columns.md',
    ] as $slug => $filename) {
        $definition = FlexFieldsPlaygroundRegistry::find($slug);

        expect($definition)->not->toBeNull()
            ->and($definition['docs_path'] ?? null)->toBe($filename)
            ->and($docsRoot.'/'.$filename)->toBeFile();
    }

    expect(FlexFieldsPlaygroundRegistry::find('field-intelligence')['label'])->toBe('Calculated formulas');
});

it('keeps composition-recipes as the only docs-exempt meta hub', function (): void {
    expect(FlexFieldsPlaygroundRegistry::docsExemptSlugs())->toBe(['composition-recipes']);
});

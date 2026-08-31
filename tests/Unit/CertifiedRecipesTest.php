<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Composition\CertifiedRecipes;

it('lists certified composition recipes with ids descriptions and nesting', function (): void {
    $recipes = CertifiedRecipes::all();

    expect($recipes)->not->toBeEmpty()
        ->and(CertifiedRecipes::ids())->toContain(CertifiedRecipes::TRANSLATABLE_SEGMENT_TABS_ITEM_CARD);

    foreach ($recipes as $recipe) {
        expect($recipe)
            ->toHaveKeys(['id', 'description', 'nesting'])
            ->and($recipe['id'])->toBeString()->not->toBeEmpty()
            ->and($recipe['description'])->toBeString()->not->toBeEmpty()
            ->and($recipe['nesting'])->toBeArray()->not->toBeEmpty();
    }
});

it('documents the translatable segment tabs item card recipe', function (): void {
    $recipe = CertifiedRecipes::find(CertifiedRecipes::TRANSLATABLE_SEGMENT_TABS_ITEM_CARD);

    expect($recipe)->not->toBeNull()
        ->and($recipe['nesting'])->toBe(['TranslatableFields', 'SegmentTabs', 'ItemCard'])
        ->and($recipe['description'])->toContain('TranslatableFields');
});

it('resolves recipes by id and returns null for unknown ids', function (): void {
    expect(CertifiedRecipes::find(CertifiedRecipes::SEGMENT_TABS_ITEM_CARD))
        ->not->toBeNull()
        ->and(CertifiedRecipes::find('unknown-recipe'))->toBeNull();
});

it('exposes stable recipe id constants', function (): void {
    expect(CertifiedRecipes::ids())->toBe([
        CertifiedRecipes::TRANSLATABLE_SEGMENT_TABS_ITEM_CARD,
        CertifiedRecipes::TRANSLATABLE_SEGMENT_TABS,
        CertifiedRecipes::SEGMENT_TABS_ITEM_CARD,
    ]);
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Bjanczak\FilamentFlexFields\Support\Wow\CommandPaletteCatalog;
use Bjanczak\FilamentFlexFields\Support\Wow\MicroInteractionCookbook;

it('lists micro interaction recipes with ids descriptions and css classes', function (): void {
    $recipes = MicroInteractionCookbook::all();

    expect($recipes)->toHaveCount(4)
        ->and(MicroInteractionCookbook::ids())->toBe([
            MicroInteractionCookbook::CHECK_ENTER,
            MicroInteractionCookbook::CHIP_ADD,
            MicroInteractionCookbook::SHEET_SNAP,
            MicroInteractionCookbook::HOLD_PULSE,
        ]);

    foreach ($recipes as $recipe) {
        expect($recipe)
            ->toHaveKeys(['id', 'description', 'classes'])
            ->and($recipe['classes'])->toBeArray()->not->toBeEmpty()
            ->and($recipe['classes'][0])->toStartWith('fff-wow-');
    }
});

it('documents the check enter micro interaction recipe', function (): void {
    $recipe = MicroInteractionCookbook::find(MicroInteractionCookbook::CHECK_ENTER);

    expect($recipe)->not->toBeNull()
        ->and($recipe['classes'])->toContain('fff-select-option-selected-check');
});

it('resolves micro interaction recipes by id', function (): void {
    expect(MicroInteractionCookbook::find(MicroInteractionCookbook::HOLD_PULSE))
        ->not->toBeNull()
        ->and(MicroInteractionCookbook::find('unknown-recipe'))->toBeNull();
});

it('indexes every field type for the command palette catalog', function (): void {
    $entries = CommandPaletteCatalog::all();

    expect($entries)->toHaveCount(count(FieldType::cases()));

    foreach ($entries as $entry) {
        expect($entry)
            ->toHaveKeys(['id', 'label', 'playground_slug'])
            ->and($entry['id'])->toBeString()->not->toBeEmpty()
            ->and($entry['label'])->toBeString()->not->toBeEmpty();
    }
});

it('maps select field types to the select field playground slug', function (): void {
    expect(CommandPaletteCatalog::find(FieldType::Select->value))
        ->not->toBeNull()
        ->and(CommandPaletteCatalog::find(FieldType::Select->value)['playground_slug'])->toBe('select-field');
});

it('searches the command palette catalog by id label and slug', function (): void {
    expect(CommandPaletteCatalog::search('select'))
        ->not->toBeEmpty()
        ->and(collect(CommandPaletteCatalog::search('select'))->pluck('id'))->toContain(FieldType::Select->value);

    expect(CommandPaletteCatalog::search('phone-field'))
        ->not->toBeEmpty()
        ->and(CommandPaletteCatalog::search('phone-field')[0]['playground_slug'])->toBe('phone-field');

    expect(CommandPaletteCatalog::search(''))->toHaveCount(count(FieldType::cases()));
});

it('only returns playground slugs that exist in the registry', function (): void {
    foreach (CommandPaletteCatalog::all() as $entry) {
        if ($entry['playground_slug'] === null) {
            continue;
        }

        expect(FlexFieldsPlaygroundRegistry::find($entry['playground_slug']))->not->toBeNull();
    }
});

it('defines motion tokens in base css with reduced motion law', function (): void {
    $css = file_get_contents(__DIR__.'/../../resources/css/base.css');

    expect($css)
        ->toContain('--fff-motion-duration-fast: 150ms')
        ->toContain('--fff-motion-duration-base: 250ms')
        ->toContain('--fff-motion-duration-slow: 400ms')
        ->toContain('--fff-motion-ease-standard: cubic-bezier(0.16, 1, 0.3, 1)')
        ->toMatch('/@media \(prefers-reduced-motion: reduce\)[\s\S]*--fff-motion-duration-fast: 0\.01ms/');
});

it('documents motion tokens in the public token contract', function (): void {
    $contract = json_decode(
        (string) file_get_contents(__DIR__.'/../../resources/dist/tokens/fff-token-contract.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($contract['tokens']['motion'])->toHaveKeys([
        '--fff-motion-duration-fast',
        '--fff-motion-duration-base',
        '--fff-motion-duration-slow',
        '--fff-motion-ease-standard',
    ]);
});

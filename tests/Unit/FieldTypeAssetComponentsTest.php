<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

it('declares asset components for every field type without invalid ids', function (): void {
    foreach (FieldType::cases() as $type) {
        $components = $type->assetComponents();

        expect($components)->toBeArray();

        foreach ($components as $component) {
            expect($component)->toBeString()->not->toBeEmpty();

            $resolved = FlexFieldAssets::resolveStylesheetComponent($component);
            $stylesheets = FlexFieldAssets::stylesheetsFor($component);

            expect($resolved)->toBeString()->not->toBeEmpty()
                ->and($stylesheets)->not->toBeEmpty(
                    "FieldType [{$type->value}] asset component [{$component}] must resolve to at least one lazy stylesheet.",
                );
        }
    }
});

it('dedupes shared stylesheet dependencies across components', function (): void {
    $planned = FlexFieldAssets::planAssetsForComponents([
        'phone-field',
        'currency-field',
        'country-field',
    ]);

    $counts = array_count_values($planned['stylesheets']);

    expect($planned['stylesheets'])->toContain('emoji-picker', 'flex-text-input', 'teleported-menu', 'phone-field', 'currency-field', 'country-field')
        ->and($counts['flex-text-input'])->toBe(1)
        ->and($counts['emoji-picker'])->toBe(1)
        ->and($counts['teleported-menu'])->toBe(1);
});

it('keeps dependency order before parent stylesheets', function (): void {
    $planned = FlexFieldAssets::planAssetsForComponents(['phone-field']);

    expect(array_search('emoji-picker', $planned['stylesheets'], true))
        ->toBeLessThan(array_search('flex-text-input', $planned['stylesheets'], true))
        ->and(array_search('flex-text-input', $planned['stylesheets'], true))
        ->toBeLessThan(array_search('phone-field', $planned['stylesheets'], true));
});

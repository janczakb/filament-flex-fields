<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ColorSwatchField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Playground\ColorSwatchPlayground;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\In;

it('exposes color swatch configuration via fluent api', function () {
    $colors = [
        'charcoal' => '#18181b',
        'magenta' => '#d946ef',
        'blue' => '#3b82f6',
        'green' => '#22c55e',
        'yellow' => '#eab308',
        'white' => '#ffffff',
    ];

    $labels = [
        'green' => 'Emerald',
        'blue' => 'Royal Blue',
    ];

    $field = ColorSwatchField::make('accent_color')
        ->label('Accent color')
        ->size('lg')
        ->sectionLabel('Predefined')
        ->tooltips($labels)
        ->colors($colors)
        ->default('green');

    expect($field->getColors())->toBe($colors)
        ->and($field->getSize())->toBe('lg')
        ->and($field->hasTooltips())->toBeTrue()
        ->and($field->getColorLabel('green'))->toBe('Emerald')
        ->and($field->getColorLabel('blue'))->toBe('Royal Blue')
        ->and($field->getSectionLabel())->toBe('Predefined')
        ->and($field->getSectionIcon())->toBe(GravityIcon::Palette)
        ->and($field->isLightSwatch('#ffffff'))->toBeTrue()
        ->and($field->isLightSwatch('#22c55e'))->toBeFalse();
});

it('validates selected values against configured color keys', function () {
    $field = ColorSwatchField::make('accent_color')
        ->colors([
            'green' => '#22c55e',
            'blue' => '#3b82f6',
        ]);

    $rules = $field->getValidationRules();
    $inRule = collect($rules)->first(fn ($rule) => $rule instanceof In || (is_string($rule) && str_starts_with($rule, 'in:')));

    expect($rules)->toContain('nullable')
        ->and($inRule)->not->toBeNull()
        ->and((string) $inRule)->toBe('in:"green","blue"');
});

it('registers color swatch playground variants', function () {
    $state = (new ColorSwatchPlayground)->defaultState();

    expect($state)->toHaveKeys([
        'color_swatch__sm',
        'color_swatch__md',
        'color_swatch__lg',
        'color_swatch__compact',
        'color_swatch__tooltips',
    ]);
});

it('merges color swatch playground state into the builder', function () {
    $state = app(FlexFieldsPlaygroundBuilder::class)->defaultState();

    expect($state)->toHaveKeys([
        'color_swatch__sm',
        'color_swatch__md',
        'color_swatch__lg',
        'color_swatch__compact',
        'color_swatch__tooltips',
    ]);
});

it('derives tooltip labels from color keys when no custom label is provided', function () {
    $field = ColorSwatchField::make('accent_color')
        ->tooltips()
        ->colors(['forest_green' => '#22c55e']);

    expect($field->hasTooltips())->toBeTrue()
        ->and($field->getColorLabel('forest_green'))->toBe('Forest Green');
});

it('can disable tooltips explicitly', function () {
    expect(ColorSwatchField::make('accent_color')->tooltips(false)->hasTooltips())->toBeFalse();
});

it('supports control sizes', function () {
    expect(ColorSwatchField::make('accent_color')->size('sm')->getSize())->toBe('sm')
        ->and(ColorSwatchField::make('accent_color')->size('lg')->getSize())->toBe('lg');
});

it('uses gravity ui palette as the default section icon when a section label is set', function () {
    expect(ColorSwatchField::make('accent_color')->getSectionIcon())->toBeNull()
        ->and(ColorSwatchField::make('accent_color')->sectionLabel('Predefined')->getSectionIcon())->toBe(GravityIcon::Palette)
        ->and(ColorSwatchField::make('accent_color')->sectionLabel('Predefined')->getDefaultSectionIcon())->toBe(GravityIcon::Palette)
        ->and(ColorSwatchField::make('accent_color')->sectionLabel('Predefined')->sectionIcon(Heroicon::Swatch)->getSectionIcon())->toBe(Heroicon::Swatch);
});

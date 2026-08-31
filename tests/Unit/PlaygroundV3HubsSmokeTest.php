<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Playground\AdminColumnsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CompositionRecipesPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FieldIntelligencePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\HoldConfirmPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\MapPickerPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SchemaConditionsPlayground;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;

it('smokes interactive v3 playground hubs with default state and components', function (string $class): void {
    $playground = new $class;

    expect($playground->defaultState())->toBeArray()
        ->and($playground->components())->not->toBeEmpty();
})->with([
    AdminColumnsPlayground::class,
    SchemaConditionsPlayground::class,
    FieldIntelligencePlayground::class,
    CompositionRecipesPlayground::class,
    HoldConfirmPlayground::class,
    MapPickerPlayground::class,
]);

it('ships multiple live formula scenarios on the calculated formulas playground', function (): void {
    $playground = new FieldIntelligencePlayground;
    $state = $playground->defaultState();
    $components = $playground->components();

    expect($state)->toHaveKeys([
        'formula__day_rate',
        'formula__charter_fee',
        'formula__deal_grand',
        'formula__grand_total',
        'formula__progress_pct',
        'formula__margin_pct',
        'formula__lab_expr',
        'formula__cycle_mode',
        'formula__reject_expr',
    ])
        ->and($components)->toHaveCount(8)
        ->and(collect($components)->map(fn ($section) => $section->getHeading())->all())
        ->toContain(
            'Calculated formulas',
            'Enterprise deal desk',
            'Invoice with VAT',
            'Completion progress',
            'Margin',
            'Formula lab',
            'Cycle guard',
            'Security wall',
        );
});

it('embeds real hold-confirm actions in the hold confirm playground', function (): void {
    $components = (new HoldConfirmPlayground)->components();
    $section = $components[0];

    expect($section)->toBeInstanceOf(Section::class);

    $actions = collect($section->getDefaultChildComponents())
        ->first(fn ($component): bool => $component instanceof Actions);

    expect($actions)->toBeInstanceOf(Actions::class);

    $property = null;
    $class = new ReflectionClass($actions);

    while ($class !== false) {
        if ($class->hasProperty('childComponents')) {
            $property = $class->getProperty('childComponents');

            break;
        }

        $class = $class->getParentClass();
    }

    expect($property)->not->toBeNull();

    /** @var array<string, list<object>> $stored */
    $stored = $property->getValue($actions);
    $holdActions = $stored['default'] ?? [];

    expect($holdActions)->toHaveCount(4)
        ->and($holdActions[0]->getName())->toBe('holdConfirmUpdate')
        ->and($holdActions[0]->hasHoldConfirm())->toBeTrue()
        ->and($holdActions[1]->getHoldConfirmDuration())->toBe(800);
});

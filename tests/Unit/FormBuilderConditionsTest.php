<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\Schema\JsonFieldConditions;

it('parses visibleWhen requiredWhen and disabledWhen from field arrays', function (): void {
    $definition = FlexFieldDefinition::fromArray([
        'slug' => 'vat_id',
        'label' => 'VAT ID',
        'type' => FieldType::FlexTextInput->value,
        'visibleWhen' => [
            ['field' => 'country', 'operator' => 'equals', 'value' => 'PL'],
        ],
        'required_when' => [
            ['field' => 'type', 'operator' => 'equals', 'value' => 'company'],
        ],
        'disabledWhen' => [
            ['field' => 'locked', 'operator' => 'equals', 'value' => true],
        ],
    ]);

    expect($definition->visibleWhen)->toBe([
        ['field' => 'country', 'operator' => 'equals', 'value' => 'PL'],
    ])
        ->and($definition->requiredWhen)->toBe([
            ['field' => 'type', 'operator' => 'equals', 'value' => 'company'],
        ])
        ->and($definition->disabledWhen)->toBe([
            ['field' => 'locked', 'operator' => 'equals', 'value' => true],
        ])
        ->and($definition->hasDynamicVisibility())->toBeTrue();
});

it('skips statically hidden fields unless visibleWhen is configured', function (): void {
    $builder = new FlexFieldFormBuilder;

    $hidden = $builder->build([
        FlexFieldDefinition::fromArray([
            'slug' => 'secret',
            'label' => 'Secret',
            'type' => FieldType::FlexTextInput->value,
            'is_visible' => false,
        ]),
    ]);

    $conditional = $builder->build([
        FlexFieldDefinition::fromArray([
            'slug' => 'vat_id',
            'label' => 'VAT ID',
            'type' => FieldType::FlexTextInput->value,
            'is_visible' => false,
            'visibleWhen' => [
                ['field' => 'country', 'operator' => 'equals', 'value' => 'PL'],
            ],
        ]),
    ]);

    expect($hidden)->toHaveCount(0)
        ->and($conditional)->toHaveCount(1)
        ->and($conditional[0])->toBeInstanceOf(FlexTextInput::class);
});

it('applies visibleWhen closure to built components', function (): void {
    $rules = [
        ['field' => 'country', 'operator' => 'equals', 'value' => 'PL'],
    ];

    $component = app(FlexFieldFormBuilder::class)->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'vat_id',
            'label' => 'VAT ID',
            'type' => FieldType::FlexTextInput->value,
            'visibleWhen' => $rules,
        ]),
        'flex_field_values',
    );

    expect($component)->toBeInstanceOf(FlexTextInput::class);

    $getPoland = fn (string $field): mixed => $field === 'country' ? 'PL' : null;
    $getGermany = fn (string $field): mixed => $field === 'country' ? 'DE' : null;

    expect(JsonFieldConditions::evaluate($rules, $getPoland))->toBeTrue()
        ->and(JsonFieldConditions::evaluate($rules, $getGermany))->toBeFalse()
        ->and(JsonFieldConditions::compileVisibleWhen($rules))->toBeCallable();
});

it('applies requiredWhen closure alongside static isRequired', function (): void {
    $alwaysRequired = app(FlexFieldFormBuilder::class)->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'name',
            'label' => 'Name',
            'type' => FieldType::FlexTextInput->value,
            'is_required' => true,
            'requiredWhen' => [
                ['field' => 'type', 'operator' => 'equals', 'value' => 'company'],
            ],
        ]),
    );

    $conditionallyRequired = app(FlexFieldFormBuilder::class)->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'vat_id',
            'label' => 'VAT ID',
            'type' => FieldType::FlexTextInput->value,
            'requiredWhen' => [
                ['field' => 'type', 'operator' => 'equals', 'value' => 'company'],
            ],
        ]),
    );

    expect($alwaysRequired)->toBeInstanceOf(FlexTextInput::class)
        ->and($conditionallyRequired)->toBeInstanceOf(FlexTextInput::class);
});

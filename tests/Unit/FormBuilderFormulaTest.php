<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Livewire\Livewire;

it('parses formula and calculated keys from field definition arrays', function (): void {
    $fromTopLevel = FlexFieldDefinition::fromArray([
        'slug' => 'total',
        'label' => 'Total',
        'type' => FieldType::FlexTextInput->value,
        'formula' => '{price}*{qty}',
    ]);

    $fromConfig = FlexFieldDefinition::fromArray([
        'slug' => 'total',
        'label' => 'Total',
        'type' => FieldType::FlexTextInput->value,
        'config' => [
            'calculated' => '{price}+{tax}',
        ],
    ]);

    expect($fromTopLevel->formula)->toBe('{price}*{qty}')
        ->and($fromTopLevel->hasFormula())->toBeTrue()
        ->and($fromConfig->formula)->toBe('{price}+{tax}')
        ->and(FormulaEngine::fieldReferences('{price}*{qty}'))->toBe(['price', 'qty']);
});

it('disables formula target fields when a formula is configured', function (): void {
    $component = app(FlexFieldFormBuilder::class)->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'total',
            'label' => 'Total',
            'type' => FieldType::FlexTextInput->value,
            'formula' => '{price}*{qty}',
        ]),
        '',
    );

    expect($component)->toBeInstanceOf(FlexTextInput::class)
        ->and($component->isDisabled())->toBeTrue();
});

it('updates formula target state when dependency fields change', function (): void {
    $components = (new FlexFieldFormBuilder)->build([
        FlexFieldDefinition::fromArray([
            'slug' => 'price',
            'label' => 'Price',
            'type' => FieldType::FlexTextInput->value,
            'default_value' => '12.5',
        ]),
        FlexFieldDefinition::fromArray([
            'slug' => 'qty',
            'label' => 'Qty',
            'type' => FieldType::FlexTextInput->value,
            'default_value' => '4',
        ]),
        FlexFieldDefinition::fromArray([
            'slug' => 'total',
            'label' => 'Total',
            'type' => FieldType::FlexTextInput->value,
            'formula' => '{price}*{qty}',
        ]),
    ], '');

    TestableTranslatableForm::$formSchema = $components;

    Livewire::test(TestableTranslatableForm::class)
        ->call('fillPlaygroundState', [
            'price' => '12.5',
            'qty' => '4',
            'total' => '',
        ])
        ->assertSet('data.total', '50')
        ->set('data.qty', '2')
        ->assertSet('data.total', '25');
});

it('updates chained formula targets when a seed dependency changes', function (): void {
    $components = (new FlexFieldFormBuilder)->build([
        FlexFieldDefinition::fromArray([
            'slug' => 'price',
            'label' => 'Price',
            'type' => FieldType::FlexTextInput->value,
            'default_value' => '10',
        ]),
        FlexFieldDefinition::fromArray([
            'slug' => 'qty',
            'label' => 'Qty',
            'type' => FieldType::FlexTextInput->value,
            'default_value' => '2',
        ]),
        FlexFieldDefinition::fromArray([
            'slug' => 'total',
            'label' => 'Total',
            'type' => FieldType::FlexTextInput->value,
            'formula' => '{price}*{qty}',
        ]),
        FlexFieldDefinition::fromArray([
            'slug' => 'tax',
            'label' => 'Tax',
            'type' => FieldType::FlexTextInput->value,
            'formula' => '{total}*pct(23)',
        ]),
    ], '');

    TestableTranslatableForm::$formSchema = $components;

    Livewire::test(TestableTranslatableForm::class)
        ->call('fillPlaygroundState', [
            'price' => '10',
            'qty' => '2',
            'total' => '',
            'tax' => '',
        ])
        ->assertSet('data.total', '20')
        ->assertSet('data.tax', '4.6')
        ->set('data.qty', '5')
        ->assertSet('data.total', '50')
        ->assertSet('data.tax', '11.5');
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\CreateFlexFieldGroup;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupValidator;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldOptionStorage;
use Illuminate\Auth\GenericUser;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(new GenericUser([
        'id' => 1,
        'email' => 'admin@example.com',
    ]));
});

it('normalizes stored options for the admin repeater', function (): void {
    $items = FlexFieldOptionStorage::configToRepeater([
        ['value' => 'sales', 'label' => 'Sales'],
        ['value' => 'support', 'label' => 'Support', 'color' => '#ff0000'],
    ], FieldType::Select);

    expect($items)->toHaveCount(2)
        ->and($items[0]['value'])->toBe('sales')
        ->and($items[1]['color'])->toBe('#ff0000');
});

it('persists admin option repeater rows into field config', function (): void {
    $validator = app(FlexFieldGroupValidator::class);

    $normalized = $validator->normalizeFieldAttributes([
        'slug' => 'department',
        'label' => 'Department',
        'type' => FieldType::Select->value,
        'field_options' => [
            ['label' => 'Sales', 'value' => 'sales'],
            ['label' => 'Support', 'value' => 'support'],
        ],
    ]);

    expect($normalized['config']['options'])->toHaveCount(2)
        ->and($normalized['config']['options'][0]['label'])->toBe('Sales')
        ->and($normalized)->not->toHaveKey('field_options');
});

it('hydrates saved config options back into the admin repeater state', function (): void {
    $validator = app(FlexFieldGroupValidator::class);

    $prepared = $validator->prepareFieldsForForm([
        [
            'slug' => 'priority',
            'label' => 'Priority',
            'type' => FieldType::Radio->value,
            'config' => [
                'options' => [
                    ['value' => 'low', 'label' => 'Low'],
                    ['value' => 'high', 'label' => 'High'],
                ],
            ],
        ],
    ]);

    expect($prepared[0]['field_options'])->toHaveCount(2)
        ->and($prepared[0]['field_options'][1]['label'])->toBe('High');
});

it('creates a select field group with option repeater values via Livewire', function (): void {
    Livewire::test(CreateFlexFieldGroup::class)
        ->fillForm([
            'name' => 'CRM',
            'slug' => 'crm',
            'order' => 0,
            'fields' => [
                [
                    'slug' => 'department',
                    'label' => 'Department',
                    'type' => 'select',
                    'sort' => 0,
                    'field_options' => [
                        ['label' => 'Sales', 'value' => 'sales'],
                        ['label' => 'Support', 'value' => 'support'],
                    ],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $group = FlexFieldGroup::query()->where('slug', 'crm')->first();

    expect($group)->not->toBeNull()
        ->and($group->fields[0]['config']['options'])->toHaveCount(2)
        ->and($group->fields[0]['config']['options'][0]['value'])->toBe('sales');
});

it('builds a select field component from saved option config', function (): void {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'department',
            'label' => 'Department',
            'type' => FieldType::Select->value,
            'config' => [
                'options' => [
                    ['value' => 'sales', 'label' => 'Sales'],
                    ['value' => 'support', 'label' => 'Support'],
                ],
            ],
        ]),
    );

    expect($field)->toBeInstanceOf(SelectField::class)
        ->and($field->getOptions())->toBe([
            'sales' => 'Sales',
            'support' => 'Support',
        ]);
});

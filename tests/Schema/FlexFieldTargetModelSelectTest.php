<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldEntity;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\CreateFlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldEntityRegistry;
use Illuminate\Auth\GenericUser;
use Livewire\Livewire;

beforeEach(function (): void {
    config([
        'filament-flex-fields.schema.entities' => [
            'App\\Models\\Lead' => ['label' => 'Leads', 'sort' => 1],
            'App\\Models\\Contact' => ['label' => 'Contacts', 'sort' => 2],
        ],
    ]);

    app(FlexFieldEntityRegistry::class)->forgetCache();

    $this->actingAs(new GenericUser([
        'id' => 1,
        'email' => 'admin@example.com',
    ]));
});

it('builds target model select options from the entity registry', function (): void {
    $options = app(FlexFieldEntityRegistry::class)->selectOptions();

    expect($options)->toHaveKeys(['App\\Models\\Lead', 'App\\Models\\Contact'])
        ->and($options['App\\Models\\Lead'])->toBe('Leads');
});

it('includes the persisted target model in select options on edit', function (): void {
    $options = app(FlexFieldEntityRegistry::class)->selectOptions('App\\Models\\LegacyLead');

    expect($options)->toHaveKey('App\\Models\\LegacyLead')
        ->and($options['App\\Models\\LegacyLead'])->toBe('LegacyLead');
});

it('renders target model as a select on the create page', function (): void {
    app(FlexFieldEntityRegistry::class)->register(new FlexFieldEntity(
        modelClass: 'App\\Models\\Lead',
        label: 'Leads',
    ));

    Livewire::test(CreateFlexFieldGroup::class)
        ->assertSuccessful()
        ->assertFormFieldExists('target_type');
});

it('prefills target model from the studio query parameter', function (): void {
    Livewire::withQueryParams(['target_type' => 'App\\Models\\Contact'])
        ->test(CreateFlexFieldGroup::class)
        ->assertFormSet([
            'target_type' => 'App\\Models\\Contact',
        ]);
});

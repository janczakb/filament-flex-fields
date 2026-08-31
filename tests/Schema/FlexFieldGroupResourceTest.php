<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\CreateFlexFieldGroup;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\EditFlexFieldGroup;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\ListFlexFieldGroups;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Models\FlexFieldSchemaVersion;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupResourceRegistrar;
use Illuminate\Auth\GenericUser;
use Livewire\Livewire;

beforeEach(function (): void {
    SchemaRegistry::clear();

    $this->actingAs(new GenericUser([
        'id' => 1,
        'name' => 'Admin',
        'email' => 'admin@example.com',
    ]));
});

it('registers the field group resource when schema.resource_enabled is true', function (): void {
    expect(FlexFieldGroupResourceRegistrar::isEnabled())->toBeTrue();
});

it('lists field groups via Livewire', function (): void {
    $group = FlexFieldGroup::factory()->create([
        'name' => 'CRM Contact',
        'slug' => 'crm-contact',
    ]);

    Livewire::test(ListFlexFieldGroups::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$group]);
});

it('creates a field group via Livewire', function (): void {
    Livewire::test(CreateFlexFieldGroup::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => 'HR Profile',
            'slug' => 'hr-profile',
            'order' => 2,
            'tenant_id' => null,
            'fields' => [
                [
                    'slug' => 'department',
                    'label' => 'Department',
                    'type' => 'select',
                    'sort' => 0,
                    'field_options' => [
                        ['label' => 'Sales', 'value' => 'sales'],
                    ],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(FlexFieldGroup::query()->where('slug', 'hr-profile')->exists())->toBeTrue();

    $group = FlexFieldGroup::query()->where('slug', 'hr-profile')->first();

    expect($group)->not->toBeNull()
        ->and($group->name)->toBe('HR Profile')
        ->and($group->order)->toBe(2)
        ->and($group->fields)->toHaveCount(1)
        ->and($group->fields[0]['slug'])->toBe('department');
});

it('publishes and rolls back group versions through SchemaRegistry', function (): void {
    expect(SchemaRegistry::usesDatabase())->toBeTrue();

    $group = FlexFieldGroup::factory()->create([
        'name' => 'Booking',
        'slug' => 'booking',
        'fields' => [
            [
                'slug' => 'check_in',
                'label' => 'Check in',
                'type' => 'date',
                'sort' => 0,
            ],
        ],
    ]);

    $v1 = $group->publishToRegistry('admin@example.com', SchemaRegistry::STATE_LIVE);

    expect(FlexFieldSchemaVersion::query()->count())->toBe(1)
        ->and(FlexFieldSchemaVersion::query()->first()?->flex_field_group_id)->toBe($group->id);

    $group->update([
        'fields' => [
            [
                'slug' => 'check_out',
                'label' => 'Check out',
                'type' => 'date',
                'sort' => 0,
            ],
        ],
    ]);

    $group->publishToRegistry('admin@example.com', SchemaRegistry::STATE_LIVE);

    $rolled = $group->fresh()->rollbackRegistryVersion($v1);

    expect($rolled['version'])->toBeGreaterThan($v1)
        ->and($group->fresh()->fields[0]['slug'])->toBe('check_in')
        ->and(FlexFieldSchemaVersion::query()->count())->toBe(3);
});

it('exposes publish and rollback header actions on edit page', function (): void {
    $group = FlexFieldGroup::factory()->create(['slug' => 'with-actions']);

    Livewire::test(EditFlexFieldGroup::class, ['record' => $group->getRouteKey()])
        ->assertSuccessful()
        ->assertActionVisible('publishSchemalive');
});

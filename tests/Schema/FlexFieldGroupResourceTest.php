<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\CreateFlexFieldGroup;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\EditFlexFieldGroup;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\ListFlexFieldGroups;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Models\FlexFieldSchemaVersion;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupResourceRegistrar;
use Bjanczak\FilamentFlexFields\Support\Schema\SchemaImportExport;
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

describe('schema header actions', function (): void {
    it('S54: imports valid schema json with sections through the edit page action', function (): void {
        $group = FlexFieldGroup::factory()->create([
            'slug' => 'before-import',
            'fields' => [
                ['slug' => 'old', 'label' => 'Old', 'type' => 'single_line_text', 'sort' => 0],
            ],
        ]);

        $json = app(SchemaImportExport::class)->export([
            'key' => 'after-import',
            'label' => 'Imported label',
            'target' => 'App\\Models\\Lead',
            'sections' => [
                ['id' => 'basics', 'label' => 'Basics', 'type' => 'section', 'sort' => 0],
            ],
            'fields' => [
                [
                    'slug' => 'note',
                    'label' => 'Note',
                    'type' => 'single_line_text',
                    'sort' => 0,
                    'section_id' => 'basics',
                ],
            ],
        ]);

        Livewire::test(EditFlexFieldGroup::class, ['record' => $group->getRouteKey()])
            ->callAction('importSchemaJson', data: ['json' => $json])
            ->assertNotified();

        $fresh = $group->fresh();

        expect($fresh->name)->toBe('Imported label')
            ->and($fresh->target_type)->toBe('App\\Models\\Lead')
            ->and($fresh->sections)->toHaveCount(1)
            ->and($fresh->sections[0]['id'])->toBe('basics')
            ->and($fresh->fields)->toHaveCount(1)
            ->and($fresh->fields[0]['slug'])->toBe('note')
            ->and($fresh->fields[0]['section_id'])->toBe('basics');
    });

    it('S55: exports schema json as a downloadable file from the edit page action', function (): void {
        $group = FlexFieldGroup::factory()->create([
            'slug' => 'export-me',
            'name' => 'Export me',
            'sections' => [
                ['id' => 'profile', 'label' => 'Profile', 'type' => 'section', 'sort' => 0],
            ],
            'fields' => [
                [
                    'slug' => 'bio',
                    'label' => 'Bio',
                    'type' => 'multi_line_text',
                    'sort' => 0,
                    'section_id' => 'profile',
                ],
            ],
        ]);

        $expected = app(SchemaImportExport::class)->export($group->toRegistrySchema());

        Livewire::test(EditFlexFieldGroup::class, ['record' => $group->getRouteKey()])
            ->callAction('exportSchemaJson')
            ->assertFileDownloaded('export-me-schema.json', $expected, 'application/json');
    });

    it('S56: applies a blueprint pack through the edit page action', function (): void {
        $group = FlexFieldGroup::factory()->create([
            'slug' => 'before-blueprint',
            'fields' => [],
            'sections' => [],
        ]);

        Livewire::test(EditFlexFieldGroup::class, ['record' => $group->getRouteKey()])
            ->callAction('applyBlueprintPack', data: ['pack' => 'crm'])
            ->assertNotified();

        $fresh = $group->fresh();

        expect($fresh->name)->toBe('CRM contact profile')
            ->and($fresh->target_type)->toBe('App\\Models\\Contact')
            ->and($fresh->sections)->not->toBeEmpty()
            ->and($fresh->fields)->not->toBeEmpty()
            ->and(collect($fresh->fields)->pluck('slug')->all())->toContain('company_name', 'deal_stage');
    });

    it('S57: persists is_encrypted when creating a field group via Livewire', function (): void {
        Livewire::test(CreateFlexFieldGroup::class)
            ->fillForm([
                'name' => 'Secure group',
                'slug' => 'secure-group',
                'target_type' => 'App\\Models\\Lead',
                'order' => 0,
                'tenant_id' => null,
                'fields' => [
                    [
                        'slug' => 'secret',
                        'label' => 'Secret',
                        'type' => 'single_line_text',
                        'sort' => 0,
                        'is_encrypted' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $group = FlexFieldGroup::query()->where('slug', 'secure-group')->first();

        expect($group)->not->toBeNull()
            ->and($group->fields)->toHaveCount(1)
            ->and($group->fields[0]['slug'])->toBe('secret')
            ->and($group->fields[0]['is_encrypted'] ?? false)->toBeTrue();
    });
});

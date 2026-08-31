<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\CreateFlexFieldGroup;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\EditFlexFieldGroup;
use Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Pages\ListFlexFieldGroups;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Models\FlexFieldSchemaVersion;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupRegistrySync;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function (): void {
    SchemaRegistry::clear();

    Gate::define('manageFlexFieldSchemas', fn (?object $user = null): bool => $user !== null);

    $this->actingAs(new GenericUser([
        'id' => 1,
        'name' => 'Admin',
        'email' => 'admin@example.com',
    ]));
});

describe('RBAC policy', function (): void {
    it('allows resource access when gate ability is granted', function (): void {
        $user = new GenericUser(['id' => 1, 'email' => 'admin@example.com']);

        expect(Gate::forUser($user)->allows('viewAny', FlexFieldGroup::class))->toBeTrue()
            ->and(Gate::forUser($user)->allows('create', FlexFieldGroup::class))->toBeTrue();

        $group = FlexFieldGroup::factory()->create();

        expect(Gate::forUser($user)->allows('update', $group))->toBeTrue()
            ->and(Gate::forUser($user)->allows('publish', $group))->toBeTrue()
            ->and(Gate::forUser($user)->allows('rollback', $group))->toBeTrue();
    });

    it('denies resource access when gate ability is revoked', function (): void {
        Gate::define('manageFlexFieldSchemas', fn (): bool => false);

        $user = new GenericUser(['id' => 1, 'email' => 'admin@example.com']);
        $group = FlexFieldGroup::factory()->create();

        expect(Gate::forUser($user)->allows('viewAny', FlexFieldGroup::class))->toBeFalse()
            ->and(Gate::forUser($user)->allows('publish', $group))->toBeFalse();
    });

    it('blocks list page when gate ability is revoked', function (): void {
        Gate::define('manageFlexFieldSchemas', fn (): bool => false);

        expect(FlexFieldGroupResource::canAccess())->toBeFalse();
    });
});

describe('composite slug validation', function (): void {
    it('rejects duplicate slug for the same tenant via Livewire create', function (): void {
        FlexFieldGroup::factory()->create([
            'slug' => 'duplicate-me',
            'tenant_id' => '',
        ]);

        Livewire::test(CreateFlexFieldGroup::class)
            ->fillForm([
                'name' => 'Second',
                'slug' => 'duplicate-me',
                'target_type' => 'App\\Models\\Lead',
                'order' => 0,
                'tenant_id' => null,
                'fields' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    });

    it('allows duplicate slug for different tenants via Livewire create', function (): void {
        FlexFieldGroup::factory()->create([
            'slug' => 'shared',
            'tenant_id' => 'tenant-a',
        ]);

        Livewire::test(CreateFlexFieldGroup::class)
            ->fillForm([
                'name' => 'Tenant B shared',
                'slug' => 'shared',
                'target_type' => 'App\\Models\\Lead',
                'order' => 0,
                'tenant_id' => 'tenant-b',
                'fields' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(FlexFieldGroup::query()->where('slug', 'shared')->count())->toBe(2);
    });

    it('normalizes null tenant_id to empty string on save', function (): void {
        $group = FlexFieldGroup::factory()->make([
            'tenant_id' => null,
        ]);

        $group->save();

        expect($group->fresh()->tenant_id)->toBe('');
    });
});

describe('registry sync edge cases', function (): void {
    it('removes stale registry key when slug changes', function (): void {
        $group = FlexFieldGroup::factory()->create(['slug' => 'before-rename']);

        expect(app(FlexFieldSchemaRegistry::class)->find('before-rename'))->not->toBeNull();

        $group->update(['slug' => 'after-rename']);

        expect(app(FlexFieldSchemaRegistry::class)->find('before-rename'))->toBeNull()
            ->and(app(FlexFieldSchemaRegistry::class)->find('after-rename'))->not->toBeNull();
    });

    it('removes stale registry key when tenant_id changes', function (): void {
        $group = FlexFieldGroup::factory()->create([
            'slug' => 'profile',
            'tenant_id' => 'tenant-a',
        ]);

        expect(app(FlexFieldSchemaRegistry::class)->find('tenant-a:profile'))->not->toBeNull();

        $group->update(['tenant_id' => 'tenant-b']);

        expect(app(FlexFieldSchemaRegistry::class)->find('tenant-a:profile'))->toBeNull()
            ->and(app(FlexFieldSchemaRegistry::class)->find('tenant-b:profile'))->not->toBeNull();
    });

    it('does not sync when sync_on_save is disabled', function (): void {
        config()->set('filament-flex-fields.schema.sync_on_save', false);

        FlexFieldGroup::factory()->create(['slug' => 'no-sync']);

        expect(app(FlexFieldSchemaRegistry::class)->find('no-sync'))->toBeNull();
    });

    it('does not boot hydrate when sync_from_database is disabled', function (): void {
        config()->set('filament-flex-fields.schema.sync_from_database', false);
        config()->set('filament-flex-fields.schema.sync_on_save', false);

        FlexFieldGroup::factory()->create(['slug' => 'db-only']);

        app(FlexFieldGroupRegistrySync::class)->syncAllFromDatabase();

        expect(app(FlexFieldSchemaRegistry::class)->find('db-only'))->toBeNull();
    });

    it('lets database groups override config schemas with the same key', function (): void {
        app(FlexFieldSchemaRegistry::class)->register(
            FlexFieldSchema::make('shared-key', 'App\\Models\\ConfigModel')
                ->label('From config')
                ->fields([
                    ['slug' => 'config_field', 'label' => 'Config', 'type' => 'single_line_text', 'sort' => 0],
                ]),
        );

        FlexFieldGroup::factory()->create([
            'slug' => 'shared-key',
            'target_type' => 'App\\Models\\DatabaseModel',
            'fields' => [
                ['slug' => 'db_field', 'label' => 'DB', 'type' => 'single_line_text', 'sort' => 0],
            ],
        ]);

        $schema = app(FlexFieldSchemaRegistry::class)->find('shared-key');

        expect($schema)->not->toBeNull()
            ->and($schema->targetType)->toBe('App\\Models\\DatabaseModel')
            ->and($schema->getFields()[0]->slug)->toBe('db_field');
    });
});

describe('SchemaRegistry integration', function (): void {
    it('links schema versions to groups through Eloquent relationship', function (): void {
        $group = FlexFieldGroup::factory()->create(['slug' => 'linked']);

        $group->publishToRegistry('admin@example.com', SchemaRegistry::STATE_DRAFT);
        $group->publishToRegistry('admin@example.com', SchemaRegistry::STATE_LIVE);

        expect($group->schemaVersions()->count())->toBe(2)
            ->and($group->schemaVersions()->pluck('flex_field_group_id')->unique()->all())->toBe([$group->id]);
    });

    it('leaves flex_field_group_id null for generic schema registry publishes', function (): void {
        SchemaRegistry::publish('standalone-schema', [
            'target' => 'App\\Models\\Lead',
            'fields' => [],
        ], 'system');

        expect(FlexFieldSchemaVersion::query()->first()?->flex_field_group_id)->toBeNull();
    });

    it('restores label and target_type on rollback', function (): void {
        $group = FlexFieldGroup::factory()->create([
            'name' => 'Original',
            'slug' => 'rollback-meta',
            'target_type' => 'App\\Models\\Original',
            'fields' => [
                ['slug' => 'a', 'label' => 'A', 'type' => 'single_line_text', 'sort' => 0],
            ],
        ]);

        $v1 = $group->publishToRegistry('admin@example.com', SchemaRegistry::STATE_LIVE);

        $group->update([
            'name' => 'Changed',
            'target_type' => 'App\\Models\\Changed',
            'fields' => [
                ['slug' => 'b', 'label' => 'B', 'type' => 'single_line_text', 'sort' => 0],
            ],
        ]);

        $group->fresh()->rollbackRegistryVersion($v1);

        $fresh = $group->fresh();

        expect($fresh->name)->toBe('Original')
            ->and($fresh->target_type)->toBe('App\\Models\\Original')
            ->and($fresh->fields[0]['slug'])->toBe('a');
    });

    it('supports draft review and live approval states', function (): void {
        $group = FlexFieldGroup::factory()->create(['slug' => 'workflow']);

        $draft = $group->publishToRegistry('editor@example.com', SchemaRegistry::STATE_DRAFT);

        SchemaRegistry::setApprovalState($group->registrySchemaId(), $draft, SchemaRegistry::STATE_REVIEW);
        SchemaRegistry::setApprovalState($group->registrySchemaId(), $draft, SchemaRegistry::STATE_LIVE);

        $versions = SchemaRegistry::versions($group->registrySchemaId());

        expect(collect($versions)->firstWhere('version', $draft)['state'])->toBe(SchemaRegistry::STATE_LIVE);
    });
});

describe('Filament publish and rollback actions', function (): void {
    it('publishes a live version through the edit page action', function (): void {
        $group = FlexFieldGroup::factory()->create([
            'slug' => 'publish-action',
            'fields' => [
                ['slug' => 'note', 'label' => 'Note', 'type' => 'single_line_text', 'sort' => 0],
            ],
        ]);

        Livewire::test(EditFlexFieldGroup::class, ['record' => $group->getRouteKey()])
            ->callAction('publishSchemalive')
            ->assertNotified();

        expect(FlexFieldSchemaVersion::query()->count())->toBe(1)
            ->and(FlexFieldSchemaVersion::query()->first()?->state)->toBe(SchemaRegistry::STATE_LIVE)
            ->and(app(FlexFieldSchemaRegistry::class)->find('publish-action')?->getFields()[0]->slug)->toBe('note');
    });

    it('rolls back through the edit page action and syncs runtime registry', function (): void {
        $group = FlexFieldGroup::factory()->create([
            'slug' => 'rollback-action',
            'fields' => [
                ['slug' => 'v1', 'label' => 'V1', 'type' => 'single_line_text', 'sort' => 0],
            ],
        ]);

        $v1 = $group->publishToRegistry('admin@example.com', SchemaRegistry::STATE_LIVE);

        $group->update([
            'fields' => [
                ['slug' => 'v2', 'label' => 'V2', 'type' => 'single_line_text', 'sort' => 0],
            ],
        ]);

        Livewire::test(EditFlexFieldGroup::class, ['record' => $group->getRouteKey()])
            ->callAction('rollbackRegistryVersion', data: ['version' => $v1])
            ->assertNotified();

        expect($group->fresh()->fields[0]['slug'])->toBe('v1')
            ->and(app(FlexFieldSchemaRegistry::class)->find('rollback-action')?->getFields()[0]->slug)->toBe('v1');
    });

    it('hides rollback action when no versions exist', function (): void {
        $group = FlexFieldGroup::factory()->create(['slug' => 'no-versions']);

        Livewire::test(EditFlexFieldGroup::class, ['record' => $group->getRouteKey()])
            ->assertActionHidden('rollbackRegistryVersion');
    });
});

describe('registry schema id helper', function (): void {
    it('builds global and tenant scoped ids consistently', function (): void {
        expect(FlexFieldGroup::registrySchemaIdFrom('crm', null))->toBe('crm')
            ->and(FlexFieldGroup::registrySchemaIdFrom('crm', ''))->toBe('crm')
            ->and(FlexFieldGroup::registrySchemaIdFrom('crm', 'acme'))->toBe('acme:crm');
    });
});

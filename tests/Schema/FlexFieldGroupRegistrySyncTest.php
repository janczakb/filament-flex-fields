<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Models\FlexFieldSchemaVersion;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupRegistrySync;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    SchemaRegistry::clear();
});

it('syncs saved groups into FlexFieldSchemaRegistry', function (): void {
    $group = FlexFieldGroup::factory()->create([
        'slug' => 'crm-contact',
        'name' => 'CRM Contact',
        'target_type' => 'App\\Models\\Lead',
        'fields' => [
            ['slug' => 'email', 'label' => 'Email', 'type' => 'single_line_text', 'sort' => 0],
        ],
    ]);

    $schema = app(FlexFieldSchemaRegistry::class)->find('crm-contact');

    expect($schema)->not->toBeNull()
        ->and($schema->targetType)->toBe('App\\Models\\Lead')
        ->and($schema->getFields())->toHaveCount(1)
        ->and($schema->getFields()[0]->slug)->toBe('email');
});

it('scopes registry keys by tenant id', function (): void {
    FlexFieldGroup::factory()->create([
        'slug' => 'profile',
        'tenant_id' => 'tenant-a',
        'target_type' => 'App\\Models\\User',
    ]);

    expect(app(FlexFieldSchemaRegistry::class)->find('tenant-a:profile'))->not->toBeNull()
        ->and(app(FlexFieldSchemaRegistry::class)->find('profile'))->toBeNull();
});

it('removes registry entries when a group is deleted', function (): void {
    $group = FlexFieldGroup::factory()->create(['slug' => 'temporary']);

    expect(app(FlexFieldSchemaRegistry::class)->find('temporary'))->not->toBeNull();

    $group->delete();

    expect(app(FlexFieldSchemaRegistry::class)->find('temporary'))->toBeNull();
});

it('hydrates registry from database on boot sync', function (): void {
    FlexFieldGroup::factory()->create([
        'slug' => 'boot-sync',
        'target_type' => 'App\\Models\\Post',
    ]);

    app(FlexFieldSchemaRegistry::class)->unregister('boot-sync');

    app(FlexFieldGroupRegistrySync::class)->syncAllFromDatabase();

    expect(app(FlexFieldSchemaRegistry::class)->find('boot-sync'))->not->toBeNull();
});

it('stores flex_field_group_id on published schema versions', function (): void {
    $group = FlexFieldGroup::factory()->create(['slug' => 'booking']);

    $group->publishToRegistry('admin@example.com', SchemaRegistry::STATE_LIVE);

    expect(FlexFieldSchemaVersion::query()->first()?->flex_field_group_id)->toBe($group->id);
});

it('enforces composite slug uniqueness per tenant at database level', function (): void {
    FlexFieldGroup::factory()->create([
        'slug' => 'shared-slug',
        'tenant_id' => '',
    ]);

    expect(fn () => FlexFieldGroup::factory()->create([
        'slug' => 'shared-slug',
        'tenant_id' => '',
    ]))->toThrow(Illuminate\Database\QueryException::class);

    FlexFieldGroup::factory()->create([
        'slug' => 'shared-slug',
        'tenant_id' => 'tenant-b',
    ]);

    expect(FlexFieldGroup::query()->where('slug', 'shared-slug')->count())->toBe(2);
});

it('denies field group access without the configured gate ability', function (): void {
    Gate::define('manageFlexFieldSchemas', fn (): bool => false);

    $user = new GenericUser(['id' => 1, 'email' => 'user@example.com']);

    expect(Gate::forUser($user)->allows('manageFlexFieldSchemas'))->toBeFalse();
});

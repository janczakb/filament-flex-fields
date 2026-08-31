<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\Enterprise\FieldRbacMatrix;
use Bjanczak\FilamentFlexFields\Support\Enterprise\TenantFieldPacks;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupValidator;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldSchemaResolver;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldStudio;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    TenantFieldPacks::clear();
    FieldRbacMatrix::reset();
});

describe('FlexFieldSchemaResolver', function (): void {
    it('includes global schemas and tenant-scoped schemas for matching tenant', function (): void {
        $registry = app(FlexFieldSchemaRegistry::class);
        $resolver = app(FlexFieldSchemaResolver::class);

        $registry->register(FlexFieldSchema::make('global-profile', 'App\\Models\\Lead')
            ->fields([
                ['slug' => 'global_note', 'label' => 'Global', 'type' => 'single_line_text', 'sort' => 0],
            ]));

        $registry->register(FlexFieldSchema::make('tenant-a:profile', 'App\\Models\\Lead')
            ->fields([
                ['slug' => 'tenant_note', 'label' => 'Tenant', 'type' => 'single_line_text', 'sort' => 1],
            ]));

        $registry->register(FlexFieldSchema::make('tenant-b:profile', 'App\\Models\\Lead')
            ->fields([
                ['slug' => 'other', 'label' => 'Other', 'type' => 'single_line_text', 'sort' => 2],
            ]));

        $forTenantA = $resolver->definitionsForTarget('App\\Models\\Lead', 'tenant-a');

        expect(collect($forTenantA)->pluck('slug')->all())
            ->toBe(['global_note', 'tenant_note']);
    });

    it('filters field types using tenant packs', function (): void {
        TenantFieldPacks::registerPack('acme', ['single_line_text']);

        $registry = app(FlexFieldSchemaRegistry::class);
        $resolver = app(FlexFieldSchemaResolver::class);

        $registry->register(FlexFieldSchema::make('acme:pack', 'App\\Models\\Lead')
            ->fields([
                ['slug' => 'allowed', 'label' => 'Allowed', 'type' => 'single_line_text', 'sort' => 0],
                ['slug' => 'blocked', 'label' => 'Blocked', 'type' => 'toggle', 'sort' => 1],
            ]));

        $definitions = $resolver->definitionsForTarget('App\\Models\\Lead', 'acme');

        expect(collect($definitions)->pluck('slug')->all())->toBe(['allowed']);
    });

    it('respects field rbac matrix for view and edit abilities', function (): void {
        FieldRbacMatrix::deny('editor@example.com', FieldRbacMatrix::ABILITY_EDIT, 'toggle');

        $registry = app(FlexFieldSchemaRegistry::class);
        $resolver = app(FlexFieldSchemaResolver::class);

        $registry->register(FlexFieldSchema::make('rbac', 'App\\Models\\Lead')
            ->fields([
                ['slug' => 'text', 'label' => 'Text', 'type' => 'single_line_text', 'sort' => 0],
                ['slug' => 'flag', 'label' => 'Flag', 'type' => 'toggle', 'sort' => 1],
            ]));

        $viewSlugs = collect($resolver->definitionsForTarget('App\\Models\\Lead', null, 'editor@example.com'))
            ->pluck('slug')
            ->all();

        $editSlugs = collect($resolver->definitionsForTarget(
            'App\\Models\\Lead',
            null,
            'editor@example.com',
            FieldRbacMatrix::ABILITY_EDIT,
        ))->pluck('slug')->all();

        expect($viewSlugs)->toBe(['text', 'flag'])
            ->and($editSlugs)->toBe(['text']);
    });
});

describe('FlexFieldStudio', function (): void {
    it('builds form section components from registry definitions', function (): void {
        app(FlexFieldSchemaRegistry::class)->register(FlexFieldSchema::make('studio', 'App\\Models\\Lead')
            ->fields([
                ['slug' => 'company', 'label' => 'Company', 'type' => 'single_line_text', 'sort' => 0],
            ]));

        $components = app(FlexFieldStudio::class)
            ->form()
            ->forModel('App\\Models\\Lead')
            ->components();

        expect($components)->not->toBeEmpty();
    });

    it('builds table columns for flex field values', function (): void {
        app(FlexFieldSchemaRegistry::class)->register(FlexFieldSchema::make('table-studio', 'App\\Models\\Lead')
            ->fields([
                ['slug' => 'score', 'label' => 'Score', 'type' => 'number_stepper', 'sort' => 0],
            ]));

        $columns = app(FlexFieldStudio::class)
            ->table()
            ->forModel('App\\Models\\Lead')
            ->columns();

        expect($columns)->toHaveCount(1)
            ->and($columns[0]->getName())->toBe('flex_score');
    });
});

describe('FlexFieldGroupValidator', function (): void {
    it('rejects duplicate slugs within a group', function (): void {
        $group = FlexFieldGroup::factory()->make([
            'fields' => [
                ['slug' => 'dup', 'label' => 'One', 'type' => 'single_line_text', 'sort' => 0],
                ['slug' => 'dup', 'label' => 'Two', 'type' => 'single_line_text', 'sort' => 1],
            ],
        ]);

        expect(fn (): mixed => app(FlexFieldGroupValidator::class)->assertValidGroup($group))
            ->toThrow(ValidationException::class);
    });

    it('normalizes json string config on save through model hook', function (): void {
        $group = FlexFieldGroup::factory()->create([
            'fields' => [
                [
                    'slug' => 'stage',
                    'label' => 'Stage',
                    'type' => 'select',
                    'sort' => 0,
                    'config' => '{"options":{"open":"Open","won":"Won"}}',
                ],
            ],
        ]);

        expect($group->fresh()->fields[0]['config']['options']['won'])->toBe('Won');
    });
});

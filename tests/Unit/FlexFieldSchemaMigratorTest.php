<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaMigrator;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;

it('migrates legacy schema field key to fields', function () {
    $migrator = new FlexFieldSchemaMigrator;

    $migrated = $migrator->migrate([
        'target' => 'App\\Models\\User',
        'field' => [
            [
                'name' => 'bio',
                'label' => 'Bio',
                'type' => 'multi_line_text',
                'default' => 'legacy default',
            ],
        ],
    ]);

    expect($migrated['version'])->toBe(FlexFieldSchema::CURRENT_VERSION)
        ->and($migrated)->not->toHaveKey('field')
        ->and($migrated['fields'][0]['slug'])->toBe('bio')
        ->and($migrated['fields'][0]['default_value'])->toBe('legacy default');
});

it('migrates legacy field attributes when loading from config', function () {
    $registry = new FlexFieldSchemaRegistry;

    $registry->registerFromConfig([
        'profile' => [
            'target' => 'App\\Models\\User',
            'field' => [
                [
                    'name' => 'theme',
                    'label' => 'Theme',
                    'type' => 'single_line_text',
                    'default' => 'dark',
                    'required' => true,
                ],
            ],
        ],
    ]);

    $schema = $registry->find('profile');

    expect($schema?->version)->toBe(FlexFieldSchema::CURRENT_VERSION)
        ->and($registry->fieldsForTarget('App\\Models\\User')[0]->slug)->toBe('theme')
        ->and($registry->fieldsForTarget('App\\Models\\User')[0]->defaultValue)->toBe('dark')
        ->and($registry->fieldsForTarget('App\\Models\\User')[0]->isRequired)->toBeTrue();
});

it('leaves current version schemas unchanged', function () {
    $migrator = new FlexFieldSchemaMigrator;

    $schema = [
        'version' => FlexFieldSchema::CURRENT_VERSION,
        'target' => 'App\\Models\\Company',
        'fields' => [
            [
                'slug' => 'vat_id',
                'label' => 'VAT ID',
                'type' => 'single_line_text',
            ],
        ],
    ];

    expect($migrator->migrate($schema))->toBe($schema);
});

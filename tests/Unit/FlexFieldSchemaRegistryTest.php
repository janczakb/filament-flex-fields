<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;

it('registers schemas without database', function () {
    $registry = new FlexFieldSchemaRegistry;

    $registry->register(
        FlexFieldSchema::make('profile', 'App\\Models\\User')
            ->fields([
                [
                    'slug' => 'bio',
                    'label' => 'Bio',
                    'type' => 'multi_line_text',
                ],
            ]),
    );

    expect($registry->forTarget('App\\Models\\User'))->toHaveCount(1)
        ->and($registry->fieldsForTarget('App\\Models\\User')[0]->slug)->toBe('bio');
});

it('loads schemas from config arrays', function () {
    $registry = new FlexFieldSchemaRegistry;

    $registry->registerFromConfig([
        'company' => [
            'target' => 'App\\Models\\Company',
            'fields' => [
                [
                    'slug' => 'vat_id',
                    'label' => 'VAT ID',
                    'type' => 'single_line_text',
                ],
            ],
        ],
    ]);

    expect($registry->find('company')?->targetType)->toBe('App\\Models\\Company')
        ->and($registry->fieldsForTarget('App\\Models\\Company')[0]->label)->toBe('VAT ID');
});

it('caches resolved schemas and clears cache on registration', function () {
    $registry = new FlexFieldSchemaRegistry;

    $registry->register(
        FlexFieldSchema::make('profile', 'App\\Models\\User')
            ->fields([
                [
                    'slug' => 'bio',
                    'label' => 'Bio',
                    'type' => 'multi_line_text',
                ],
            ]),
    );

    // First resolution populates cache
    $first = $registry->forTarget('App\\Models\\User');
    expect($first)->toHaveCount(1);

    // Registering a new schema busts the cache
    $registry->register(
        FlexFieldSchema::make('settings', 'App\\Models\\User')
            ->fields([
                [
                    'slug' => 'theme',
                    'label' => 'Theme',
                    'type' => 'single_line_text',
                ],
            ]),
    );

    $second = $registry->forTarget('App\\Models\\User');
    expect($second)->toHaveCount(2);
});

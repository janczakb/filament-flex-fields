<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Schema\SchemaImportExport;

it('exports and imports schema json with version key', function () {
    $service = new SchemaImportExport;

    $schema = [
        'target' => 'App\\Models\\User',
        'label' => 'Profile',
        'fields' => [
            [
                'slug' => 'bio',
                'label' => 'Bio',
                'type' => 'multi_line_text',
            ],
        ],
    ];

    $json = $service->export($schema);

    expect($json)->toContain('"version": '.SchemaImportExport::EXPORT_VERSION)
        ->and($service->import($json))->toBe($schema);
});

it('validates schema shape during dry run', function () {
    $service = new SchemaImportExport;

    $result = $service->dryRunValidate([
        'target' => 'App\\Models\\User',
        'fields' => [
            [
                'slug' => 'bio',
                'label' => 'Bio',
                'type' => 'multi_line_text',
            ],
        ],
    ]);

    expect($result['ok'])->toBeTrue()
        ->and($result['errors'])->toBe([]);
});

it('reports dry run validation errors without mutating schema', function () {
    $service = new SchemaImportExport;

    $result = $service->dryRunValidate([
        'fields' => [
            [
                'slug' => '',
                'label' => '',
                'type' => 'unknown_type',
            ],
        ],
    ]);

    expect($result['ok'])->toBeFalse()
        ->and($result['errors'])->not->toBeEmpty();
});

it('produces stable checksums for equivalent schema arrays', function () {
    $service = new SchemaImportExport;

    $left = [
        'target' => 'App\\Models\\Company',
        'fields' => [
            ['slug' => 'vat_id', 'label' => 'VAT', 'type' => 'single_line_text'],
        ],
    ];

    $right = [
        'target' => 'App\\Models\\Company',
        'fields' => [
            ['slug' => 'vat_id', 'label' => 'VAT', 'type' => 'single_line_text'],
        ],
    ];

    expect($service->checksum($left))->toBe($service->checksum($right))
        ->and(strlen($service->checksum($left)))->toBe(64);
});

it('rejects unsupported export versions on import', function () {
    $service = new SchemaImportExport;

    $json = json_encode([
        'version' => 99,
        'schema' => ['target' => 'App\\Models\\User', 'fields' => []],
    ], JSON_THROW_ON_ERROR);

    expect(fn () => $service->import($json))->toThrow(JsonException::class);
});

it('rejects fields referencing unknown section ids during dry run', function () {
    $service = new SchemaImportExport;

    $result = $service->dryRunValidate([
        'target' => 'App\\Models\\User',
        'sections' => [
            ['id' => 'basics', 'label' => 'Basics', 'type' => 'section'],
        ],
        'fields' => [
            [
                'slug' => 'bio',
                'label' => 'Bio',
                'type' => 'multi_line_text',
                'section_id' => 'missing',
            ],
        ],
    ]);

    expect($result['ok'])->toBeFalse()
        ->and($result['errors'][0])->toContain('unknown section');
});

it('rejects circular formula dependencies during dry run', function () {
    $service = new SchemaImportExport;

    $result = $service->dryRunValidate([
        'target' => 'App\\Models\\User',
        'fields' => [
            ['slug' => 'a', 'label' => 'A', 'type' => 'number_stepper', 'formula' => '{b} + 1'],
            ['slug' => 'b', 'label' => 'B', 'type' => 'number_stepper', 'formula' => '{a} + 1'],
        ],
    ]);

    expect($result['ok'])->toBeFalse()
        ->and($result['errors'][0])->toContain('circular dependency');
});

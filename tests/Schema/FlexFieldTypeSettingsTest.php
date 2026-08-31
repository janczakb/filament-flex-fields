<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupValidator;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldTypeSettingsStorage;

it('merges typed admin settings into field config', function (): void {
    $validator = app(FlexFieldGroupValidator::class);

    $normalized = $validator->normalizeFieldAttributes([
        'slug' => 'budget',
        'label' => 'Budget',
        'type' => FieldType::Currency->value,
        'type_settings' => [
            'currency' => 'EUR',
            'locale' => 'de_DE',
            'allow_negative' => false,
        ],
    ]);

    expect($normalized['config']['currency'])->toBe('EUR')
        ->and($normalized['config']['locale'])->toBe('de_DE')
        ->and($normalized['config']['allow_negative'])->toBeFalse()
        ->and($normalized)->not->toHaveKey('type_settings');
});

it('hydrates saved config into typed admin settings', function (): void {
    $validator = app(FlexFieldGroupValidator::class);

    $prepared = $validator->prepareFieldsForForm([
        [
            'slug' => 'score',
            'label' => 'Score',
            'type' => FieldType::Rating->value,
            'config' => [
                'max' => 10,
            ],
        ],
    ]);

    expect($prepared[0]['type_settings']['max'])->toBe(10)
        ->and($prepared[0])->not->toHaveKey('config');
});

it('extracts non-reserved config keys for optionable fields', function (): void {
    $settings = FlexFieldTypeSettingsStorage::extractFromConfig(FieldType::Select, [
        'options' => [['value' => 'a', 'label' => 'A']],
        'multiple' => true,
        'searchable' => true,
    ]);

    expect($settings)->toMatchArray([
        'multiple' => true,
        'searchable' => true,
    ])->and($settings)->not->toHaveKey('options');
});

it('merges matrix rows and columns from dedicated repeaters', function (): void {
    $validator = app(FlexFieldGroupValidator::class);

    $normalized = $validator->normalizeFieldAttributes([
        'slug' => 'satisfaction',
        'label' => 'Satisfaction',
        'type' => FieldType::MatrixChoice->value,
        'field_matrix_rows' => [
            ['label' => 'Quality', 'value' => 'quality'],
        ],
        'field_matrix_columns' => [
            ['label' => 'Good', 'value' => 'good', 'icon' => 'heroicon-o-check'],
        ],
        'type_settings' => [
            'mode' => 'radio',
        ],
    ]);

    expect($normalized['config']['rows'])->toHaveCount(1)
        ->and($normalized['config']['columns'][0]['icon'])->toBe('heroicon-o-check')
        ->and($normalized['config']['mode'])->toBe('radio');
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Schema\JsonFieldConditions;

it('evaluates equals and not_equals operators', function () {
    $get = fn (string $field): mixed => match ($field) {
        'status' => 'active',
        'count' => 3,
        default => null,
    };

    expect(JsonFieldConditions::evaluate([
        'field' => 'status',
        'operator' => 'equals',
        'value' => 'active',
    ], $get))->toBeTrue()
        ->and(JsonFieldConditions::evaluate([
            'field' => 'status',
            'operator' => 'not_equals',
            'value' => 'inactive',
        ], $get))->toBeTrue()
        ->and(JsonFieldConditions::evaluate([
            'field' => 'count',
            'operator' => 'equals',
            'value' => '3',
        ], $get))->toBeTrue();
});

it('evaluates filled and empty operators', function () {
    $get = fn (string $field): mixed => match ($field) {
        'name' => 'Ada',
        'notes' => '',
        'optional' => null,
        default => null,
    };

    expect(JsonFieldConditions::evaluate([
        'field' => 'name',
        'operator' => 'filled',
    ], $get))->toBeTrue()
        ->and(JsonFieldConditions::evaluate([
            'field' => 'notes',
            'operator' => 'empty',
        ], $get))->toBeTrue()
        ->and(JsonFieldConditions::evaluate([
            'field' => 'optional',
            'operator' => 'empty',
        ], $get))->toBeTrue();
});

it('evaluates in operator against allowed values', function () {
    $get = fn (string $field): mixed => match ($field) {
        'tier' => 'pro',
        default => null,
    };

    expect(JsonFieldConditions::evaluate([
        'field' => 'tier',
        'operator' => 'in',
        'value' => ['starter', 'pro', 'enterprise'],
    ], $get))->toBeTrue()
        ->and(JsonFieldConditions::evaluate([
            'field' => 'tier',
            'operator' => 'in',
            'value' => ['starter'],
        ], $get))->toBeFalse();
});

it('requires all rules in a simple and list', function () {
    $get = fn (string $field): mixed => match ($field) {
        'type' => 'company',
        'vat_id' => 'PL123',
        default => null,
    };

    $rules = [
        [
            'field' => 'type',
            'operator' => 'equals',
            'value' => 'company',
        ],
        [
            'field' => 'vat_id',
            'operator' => 'filled',
        ],
    ];

    expect(JsonFieldConditions::evaluate($rules, $get))->toBeTrue();

    $getMissingVat = fn (string $field): mixed => match ($field) {
        'type' => 'company',
        default => null,
    };

    expect(JsonFieldConditions::evaluate($rules, $getMissingVat))->toBeFalse();
});

it('supports and wrapper key for rule groups', function () {
    $get = fn (string $field): mixed => match ($field) {
        'country' => 'PL',
        'city' => 'Gdansk',
        default => null,
    };

    expect(JsonFieldConditions::evaluate([
        'and' => [
            ['field' => 'country', 'operator' => 'equals', 'value' => 'PL'],
            ['field' => 'city', 'operator' => 'filled'],
        ],
    ], $get))->toBeTrue();
});

it('returns closures suitable for filament get utilities', function () {
    $visible = JsonFieldConditions::compileVisibleWhen([
        'field' => 'enabled',
        'operator' => 'equals',
        'value' => true,
    ]);

    expect($visible)->toBeCallable();
});

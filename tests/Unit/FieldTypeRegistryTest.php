<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\FieldTypeRegistry;

it('exposes more than twenty field types', function () {
    expect(FieldTypeRegistry::count())->toBeGreaterThan(20)
        ->and(count(FieldType::cases()))->toBe(FieldTypeRegistry::count());
});

it('groups field types by category', function () {
    $grouped = FieldTypeRegistry::groupedByCategory();

    expect($grouped)->toHaveKeys(['text', 'number', 'choice', 'datetime', 'media', 'advanced'])
        ->and(collect($grouped)->flatten(1))->toHaveCount(FieldTypeRegistry::count());
});

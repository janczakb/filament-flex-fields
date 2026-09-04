<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FlexFieldWidth;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\Enterprise\SchemaRegistry;

it('persists section layout through rollback', function (): void {
    $group = FlexFieldGroup::factory()->create([
        'slug' => 'rollback-sections',
        'fields' => [
            ['slug' => 'company', 'label' => 'Company', 'type' => 'single_line_text', 'sort' => 0, 'section_id' => 'basics'],
        ],
        'sections' => [
            ['id' => 'basics', 'label' => 'Basics', 'type' => 'section', 'sort' => 0],
        ],
    ]);

    $group->publishToRegistry('tester@example.com', SchemaRegistry::STATE_LIVE);

    $group->update([
        'sections' => [],
        'fields' => [
            ['slug' => 'company', 'label' => 'Company', 'type' => 'single_line_text', 'sort' => 0],
        ],
    ]);

    $rolled = $group->rollbackRegistryVersion(1);

    expect($rolled['schema']['sections'])->toHaveCount(1)
        ->and($group->fresh()->sections)->toHaveCount(1)
        ->and($group->fresh()->fields[0]['section_id'] ?? null)->toBe('basics');
});

it('resolves flex field width from schema attributes', function (): void {
    $definition = FlexFieldDefinition::fromArray([
        'slug' => 'half',
        'label' => 'Half',
        'type' => 'single_line_text',
        'width' => FlexFieldWidth::Half->value,
    ]);

    expect($definition->width)->toBe(FlexFieldWidth::Half);
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Choice\RichOptionSchema;
use Bjanczak\FilamentFlexFields\Support\Choice\RichOptionSchemaV2;

it('builds rich option schema from array rows', function (): void {
    $schema = RichOptionSchema::fromArray([
        'value' => 'pro',
        'label' => 'Pro',
        'description' => 'For teams',
        'icon' => 'users',
        'badge' => 'Popular',
        'image' => 'https://example.test/pro.png',
        'disabled' => true,
        'meta' => ['tier' => 'paid'],
    ]);

    expect($schema->value)->toBe('pro')
        ->and($schema->label)->toBe('Pro')
        ->and($schema->description)->toBe('For teams')
        ->and($schema->icon)->toBe('users')
        ->and($schema->badge)->toBe('Popular')
        ->and($schema->image)->toBe('https://example.test/pro.png')
        ->and($schema->disabled)->toBeTrue()
        ->and($schema->meta)->toBe(['tier' => 'paid']);
});

it('normalizes desc alias and string meta on rich option schema', function (): void {
    $schema = RichOptionSchema::fromArray([
        'value' => 'starter',
        'label' => 'Starter',
        'desc' => 'Basic plan',
        'meta' => 'Best for solo',
    ]);

    expect($schema->description)->toBe('Basic plan')
        ->and($schema->meta)->toBe(['text' => 'Best for solo']);
});

it('serializes rich option schema to array omitting empty optional fields', function (): void {
    $schema = RichOptionSchema::fromArray([
        'value' => 'basic',
        'label' => 'Basic',
    ]);

    expect($schema->toArray())->toBe([
        'value' => 'basic',
        'label' => 'Basic',
    ]);
});

it('normalizes many list and map option shapes into a keyed collection', function (): void {
    $fromList = RichOptionSchema::normalizeMany([
        ['value' => 'docs', 'label' => 'Documents', 'icon' => 'folder'],
        'budget',
    ]);

    $fromMap = RichOptionSchema::normalizeMany([
        'express' => ['label' => 'Express', 'description' => '2-5 days'],
        'pickup' => 'Pickup',
    ]);

    expect($fromList)->toHaveCount(2)
        ->and($fromList['docs']->label)->toBe('Documents')
        ->and($fromList['budget']->value)->toBe('budget')
        ->and($fromMap['express']->description)->toBe('2-5 days')
        ->and($fromMap['pickup']->label)->toBe('Pickup');
});

it('validates choice option payloads via rich option schema v2', function (): void {
    expect(RichOptionSchemaV2::validate([]))->toBeTrue()
        ->and(RichOptionSchemaV2::validate([
            ['value' => 'a', 'label' => 'Alpha'],
            'bravo',
        ]))->toBeTrue()
        ->and(RichOptionSchemaV2::validate([
            'a' => 'Alpha',
            'b' => ['label' => 'Bravo'],
        ]))->toBeTrue()
        ->and(RichOptionSchemaV2::validate('invalid'))->toBeFalse()
        ->and(RichOptionSchemaV2::validate([['label' => '']]))->toBeFalse();
});

it('normalizes cards and checklist options to plain or rich maps', function (): void {
    $raw = [
        ['value' => 'starter', 'label' => 'Starter'],
        ['value' => 'pro', 'label' => 'Pro', 'description' => 'Teams', 'icon' => 'users'],
    ];

    expect(RichOptionSchemaV2::normalizeCards($raw))->toBe([
        'starter' => 'Starter',
        'pro' => [
            'label' => 'Pro',
            'description' => 'Teams',
            'icon' => 'users',
        ],
    ])->and(RichOptionSchemaV2::normalizeChecklist($raw))->toBe([
        'starter' => 'Starter',
        'pro' => [
            'label' => 'Pro',
            'description' => 'Teams',
            'icon' => 'users',
        ],
    ]);
});

it('normalizes dual list checklist tags and matrix profiles', function (): void {
    $raw = [
        ['value' => 'docs', 'label' => 'Documents', 'description' => 'Shared folder', 'disabled' => true],
        ['value' => 'budget', 'label' => 'Budget'],
    ];

    expect(RichOptionSchemaV2::normalizeDualList($raw))->toBe([
        'docs' => [
            'label' => 'Documents',
            'description' => 'Shared folder',
            'disabled' => true,
        ],
        'budget' => [
            'label' => 'Budget',
            'description' => null,
            'disabled' => false,
        ],
    ])->and(RichOptionSchemaV2::normalizeTags(['alpha', 'beta']))->toBe(['alpha', 'beta'])
        ->and(RichOptionSchemaV2::normalizeTags($raw))->toBe(['Documents', 'Budget'])
        ->and(RichOptionSchemaV2::normalizeMatrix(['Quality', 'Speed']))->toBe([
            'Quality' => 'Quality',
            'Speed' => 'Speed',
        ])
        ->and(RichOptionSchemaV2::profiles())->toContain(
            RichOptionSchemaV2::PROFILE_CARDS,
            RichOptionSchemaV2::PROFILE_MATRIX,
        );
});

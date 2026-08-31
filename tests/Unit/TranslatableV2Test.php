<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Composition\TranslatableV2;

it('reports locales with missing or empty values', function (): void {
    expect(TranslatableV2::missingLocales(
        ['en' => 'Hello', 'pl' => '', 'de' => 'Hallo'],
        ['en', 'pl', 'de', 'fr'],
    ))->toBe(['pl', 'fr'])
        ->and(TranslatableV2::missingLocales(
            ['EN' => 'Hello', 'PL' => 'Cześć'],
            ['en' => 'English', 'pl' => 'Polish'],
        ))->toBe([]);
});

it('treats whitespace and empty tiptap documents as missing locale values', function (): void {
    $emptyDoc = ['type' => 'doc', 'content' => []];
    $filledDoc = [
        'type' => 'doc',
        'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hello']]],
        ],
    ];

    expect(TranslatableV2::missingLocales(
        ['en' => '  ', 'pl' => $emptyDoc, 'de' => $filledDoc],
        ['en', 'pl', 'de'],
    ))->toBe(['en', 'pl']);
});

it('copies locale values without mutating the original array reference semantics', function (): void {
    $values = ['en' => 'Hello', 'pl' => ''];

    $copied = TranslatableV2::copyFromLocale($values, 'en', 'pl');

    expect($copied)->toBe(['en' => 'Hello', 'pl' => 'Hello'])
        ->and($values)->toBe(['en' => 'Hello', 'pl' => '']);
});

it('ignores invalid copy requests', function (): void {
    $values = ['en' => 'Hello'];

    expect(TranslatableV2::copyFromLocale($values, 'en', 'en'))->toBe($values)
        ->and(TranslatableV2::copyFromLocale($values, 'fr', 'de'))->toBe($values)
        ->and(TranslatableV2::copyFromLocale($values, '', 'pl'))->toBe($values);
});

it('resolves fallback values across locales', function (): void {
    expect(TranslatableV2::fallbackValue(['en' => 'Hello', 'pl' => ''], 'pl', 'en'))->toBe('Hello')
        ->and(TranslatableV2::fallbackValue(['en' => '', 'pl' => 'Cześć'], 'en', 'pl'))->toBe('Cześć')
        ->and(TranslatableV2::fallbackValue(['en' => 'Hello'], 'en', 'pl'))->toBe('Hello')
        ->and(TranslatableV2::fallbackValue(['en' => ''], 'en', null))->toBeNull()
        ->and(TranslatableV2::fallbackValue(['en' => ''], 'en', 'en'))->toBeNull()
        ->and(TranslatableV2::fallbackValue(['en' => ''], 'en', 'fr'))->toBeNull();
});

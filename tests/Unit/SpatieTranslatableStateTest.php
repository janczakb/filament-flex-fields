<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Translatable\SpatieTranslatableState;

it('keeps tiptap document arrays when dehydrating for spatie', function (): void {
    $doc = [
        'type' => 'doc',
        'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hello']]],
        ],
    ];

    expect(SpatieTranslatableState::dehydrate($doc))->toBe($doc)
        ->and(SpatieTranslatableState::dehydrate([]))->toBeNull()
        ->and(SpatieTranslatableState::dehydrate('  '))->toBeNull()
        ->and(SpatieTranslatableState::dehydrate('<p>Hi</p>'))->toBe('<p>Hi</p>')
        ->and(SpatieTranslatableState::dehydrate(123))->toBeNull();
});

it('hydrates tiptap arrays and json-encoded tiptap strings', function (): void {
    $doc = [
        'type' => 'doc',
        'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hello']]],
        ],
    ];

    expect(SpatieTranslatableState::hydrate($doc))->toBe($doc)
        ->and(SpatieTranslatableState::hydrate(json_encode($doc)))->toBe($doc)
        ->and(SpatieTranslatableState::hydrate('<p>Legacy HTML</p>'))->toBe('<p>Legacy HTML</p>')
        ->and(SpatieTranslatableState::hydrate(null))->toBeNull()
        ->and(SpatieTranslatableState::hydrate(''))->toBeNull();
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Select\EntityMentionQuery;

it('parses active entity mention search term', function (): void {
    $parsed = EntityMentionQuery::parse('assign @jan', '@');

    expect($parsed)->toBe([
        'active' => true,
        'trigger' => '@',
        'term' => 'jan',
        'search' => 'jan',
    ]);
});

it('returns inactive mention for plain search', function (): void {
    $parsed = EntityMentionQuery::parse('plain', '@');

    expect($parsed['active'])->toBeFalse()
        ->and($parsed['search'])->toBe('plain');
});

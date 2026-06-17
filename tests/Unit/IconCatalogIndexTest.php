<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Icons\IconCatalogIndex;
use Bjanczak\FilamentFlexFields\Support\Icons\IconCatalogResolver;
use Bjanczak\FilamentFlexFields\Support\Icons\IconSvgCache;
use Illuminate\Support\Facades\Cache;

it('ranks exact icon name matches ahead of partial matches', function () {
    $index = new IconCatalogIndex(
        entries: [
            ['name' => 'heroicon-o-star', 'label' => 'O Star', 'set' => 'heroicons', 'nameLower' => 'heroicon-o-star', 'labelLower' => 'o star'],
            ['name' => 'heroicon-o-star-half', 'label' => 'O Star Half', 'set' => 'heroicons', 'nameLower' => 'heroicon-o-star-half', 'labelLower' => 'o star half'],
            ['name' => 'heroicon-o-heart', 'label' => 'O Heart', 'set' => 'heroicons', 'nameLower' => 'heroicon-o-heart', 'labelLower' => 'o heart'],
        ],
        entriesBySet: [
            'heroicons' => [
                ['name' => 'heroicon-o-star', 'label' => 'O Star', 'set' => 'heroicons', 'nameLower' => 'heroicon-o-star', 'labelLower' => 'o star'],
                ['name' => 'heroicon-o-star-half', 'label' => 'O Star Half', 'set' => 'heroicons', 'nameLower' => 'heroicon-o-star-half', 'labelLower' => 'o star half'],
                ['name' => 'heroicon-o-heart', 'label' => 'O Heart', 'set' => 'heroicons', 'nameLower' => 'heroicon-o-heart', 'labelLower' => 'o heart'],
            ],
        ],
        allowedLookup: [
            'heroicon-o-star' => true,
            'heroicon-o-star-half' => true,
            'heroicon-o-heart' => true,
        ],
        setSummaries: [],
    );

    $results = $index->search('heroicon-o-star', null, 1, 10);

    expect($results['icons'][0]['name'])->toBe('heroicon-o-star');
});

it('checks allowed icons in constant time via lookup', function () {
    $resolver = app(IconCatalogResolver::class);
    $catalog = $resolver->catalogFor(['heroicons']);

    $index = $resolver->indexFor(
        catalog: $catalog,
        whitelist: ['heroicon-o-star', 'heroicon-o-heart'],
    );

    expect($index->isAllowed('heroicon-o-star'))->toBeTrue()
        ->and($index->isAllowed('heroicon-o-x-mark'))->toBeFalse();
});

it('omits set summaries unless explicitly requested', function () {
    $resolver = app(IconCatalogResolver::class);
    $catalog = $resolver->catalogFor(['heroicons']);

    $withoutSets = $resolver->search(
        catalog: $catalog,
        query: 'star',
        page: 1,
        perPage: 5,
        includeSetSummaries: false,
    );

    expect($withoutSets['sets'])->toBe([]);
});

it('caches rendered svg html across requests', function () {
    Cache::flush();

    $cache = app(IconSvgCache::class);

    $first = $cache->rememberMany(
        ['heroicon-o-star'],
        fn (array $missing): array => ['heroicon-o-star' => '<svg>star</svg>'],
    );

    $second = $cache->rememberMany(
        ['heroicon-o-star'],
        fn (): array => ['heroicon-o-star' => '<svg>should-not-run</svg>'],
    );

    expect($first['heroicon-o-star'])->toBe('<svg>star</svg>')
        ->and($second['heroicon-o-star'])->toBe('<svg>star</svg>');
});

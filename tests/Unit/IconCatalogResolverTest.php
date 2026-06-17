<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Icons\IconCatalogResolver;

it('resolves configured sets by set name or prefix', function () {
    $resolver = app(IconCatalogResolver::class);

    expect($resolver->resolveSetNames(['heroicons']))
        ->toBe(['heroicons'])
        ->and($resolver->resolveSetNames(['heroicon']))
        ->toBe(['heroicons'])
        ->and($resolver->resolveSetNames(['gravity-icons', 'gravityui']))
        ->toBe(['gravity-icons']);
});

it('builds searchable catalogs for installed icon sets', function () {
    $resolver = app(IconCatalogResolver::class);
    $catalog = $resolver->catalogFor(['heroicons']);

    expect($catalog)->toHaveKey('heroicons')
        ->and($catalog['heroicons']['prefix'])->toBe('heroicon')
        ->and($catalog['heroicons']['icons'])->toContain('heroicon-o-star');
});

it('filters icons by whitelist exclude and set', function () {
    $resolver = app(IconCatalogResolver::class);
    $catalog = $resolver->catalogFor(['heroicons']);

    $results = $resolver->search(
        catalog: $catalog,
        query: 'star',
        set: 'heroicons',
        page: 1,
        perPage: 10,
        whitelist: ['heroicon-o-star', 'heroicon-o-heart', 'heroicon-o-x-mark'],
        exclude: ['heroicon-o-x-mark'],
    );

    expect($results['icons'])->toHaveCount(1)
        ->and($results['icons'][0]['name'])->toBe('heroicon-o-star')
        ->and($results['icons'][0]['label'])->toBe('O Star')
        ->and($results['total'])->toBe(1)
        ->and($results['hasMore'])->toBeFalse();
});

it('formats icon labels for search results', function () {
    $resolver = app(IconCatalogResolver::class);

    expect($resolver->formatIconLabel('heroicon-o-star'))->toBe('O Star')
        ->and($resolver->formatIconLabel('gravityui-star'))->toBe('Star');
});

it('summarizes set counts after whitelist and exclude filters', function () {
    $resolver = app(IconCatalogResolver::class);
    $catalog = $resolver->catalogFor(['heroicons']);

    $sets = $resolver->summarizeSets(
        catalog: $catalog,
        whitelist: ['heroicon-o-star', 'heroicon-o-heart', 'heroicon-o-x-mark'],
        exclude: ['heroicon-o-x-mark'],
    );

    expect($sets[0]['count'])->toBe(2);
});

it('paginates icon search results', function () {
    $resolver = app(IconCatalogResolver::class);
    $catalog = $resolver->catalogFor(['heroicons']);

    $pageOne = $resolver->search(
        catalog: $catalog,
        query: 'o-',
        set: 'heroicons',
        page: 1,
        perPage: 5,
    );

    $pageTwo = $resolver->search(
        catalog: $catalog,
        query: 'o-',
        set: 'heroicons',
        page: 2,
        perPage: 5,
    );

    expect($pageOne['icons'])->toHaveCount(5)
        ->and($pageOne['icons'][0])->toHaveKeys(['name', 'label'])
        ->and($pageOne['hasMore'])->toBeTrue()
        ->and($pageTwo['icons'])->toHaveCount(5)
        ->and($pageTwo['page'])->toBe(2)
        ->and(array_intersect(
            array_column($pageOne['icons'], 'name'),
            array_column($pageTwo['icons'], 'name'),
        ))->toBeEmpty();
});

it('limits icons per set when configured', function () {
    $resolver = app(IconCatalogResolver::class);
    $catalog = $resolver->catalogFor(['heroicons']);

    $icons = $resolver->collectIcons(
        catalog: $catalog,
        limitPerSet: 3,
    );

    expect($icons)->toHaveCount(3);
});

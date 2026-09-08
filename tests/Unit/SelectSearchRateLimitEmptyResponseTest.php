<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;
use Bjanczak\FilamentFlexFields\Support\Select\SelectSearchRateLimiter;
use Illuminate\Support\Facades\RateLimiter;

it('returns controlled empty search results when the select rate limiter blocks', function (): void {
    config()->set('filament-flex-fields.select.search_rate_limit_per_minute', 1);

    $limiter = app(SelectSearchRateLimiter::class);
    RateLimiter::clear($limiter->key('country'));
    expect($limiter->attempt('country'))->toBeTrue();

    $field = SelectField::make('country')
        ->options(['pl' => 'Poland', 'de' => 'Germany'])
        ->searchable()
        ->getSearchResultsUsing(fn (): array => ['pl' => 'Poland']);

    expect($field->getSearchResultsForJs('pol'))->toBe([])
        ->and($field->getSearchResultsPageForJs('pol'))->toMatchArray([
            'items' => [],
            'hasMore' => false,
        ]);

    RateLimiter::clear($limiter->key('country'));
});

it('returns controlled empty tag search results when the rate limiter blocks', function (): void {
    config()->set('filament-flex-fields.select.search_rate_limit_per_minute', 1);

    $limiter = app(SelectSearchRateLimiter::class);
    RateLimiter::clear($limiter->key('tags'));
    expect($limiter->attempt('tags'))->toBeTrue();

    $field = TagsField::make('tags')
        ->suggestions(['laravel', 'php'])
        ->getSearchResultsUsing(fn (): array => ['laravel']);

    expect($field->getTagSearchResults('la'))->toBe([]);

    RateLimiter::clear($limiter->key('tags'));
});

it('returns controlled empty icon search and svg previews when the rate limiter blocks', function (): void {
    config()->set('filament-flex-fields.select.search_rate_limit_per_minute', 1);

    $limiter = app(SelectSearchRateLimiter::class);
    RateLimiter::clear($limiter->key('icon'));
    expect($limiter->attempt('icon'))->toBeTrue();

    $field = IconPickerField::make('icon');

    expect($field->getIconPickerSearchResults('star', 'heroicons', 2))->toMatchArray([
        'icons' => [],
        'page' => 2,
        'hasMore' => false,
        'sets' => [],
    ])->and($field->getIconPickerSvgPreviews(['heroicon-o-star']))->toBe([]);

    RateLimiter::clear($limiter->key('icon'));
});

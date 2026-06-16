<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\UrlMeta\UrlMetaScraper;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    config()->set('filament-flex-fields.link_preview.rate_limit_per_minute', 2);
    config()->set('filament-flex-fields.link_preview.cache_ttl_seconds', 0);

    $this->withoutMiddleware(Authenticate::class);

    RateLimiter::clear('fff-url-meta:127.0.0.1');
});

it('returns scraped metadata for a valid public url', function (): void {
    $this->mock(UrlMetaScraper::class, function ($mock): void {
        $mock->shouldReceive('isScrapableUrl')
            ->once()
            ->with('https://example.com/page')
            ->andReturn(true);

        $mock->shouldReceive('scrape')
            ->once()
            ->with('https://example.com/page')
            ->andReturn([
                'title' => 'Example Page',
                'description' => 'An example description',
                'image' => 'https://example.com/image.jpg',
            ]);
    });

    $this->getJson(route('filament-flex-fields.url-meta.scrape', ['url' => 'https://example.com/page']))
        ->assertOk()
        ->assertJson([
            'title' => 'Example Page',
            'description' => 'An example description',
            'image' => 'https://example.com/image.jpg',
        ]);
});

it('rejects invalid scrape urls', function (): void {
    $this->mock(UrlMetaScraper::class, function ($mock): void {
        $mock->shouldReceive('isScrapableUrl')
            ->once()
            ->with('not-a-url')
            ->andReturn(false);
    });

    $this->getJson(route('filament-flex-fields.url-meta.scrape', ['url' => 'not-a-url']))
        ->assertStatus(400)
        ->assertJsonPath('error', __('filament-flex-fields::default.link_preview.invalid_url'));
});

it('rate limits url meta scrape requests', function (): void {
    $this->mock(UrlMetaScraper::class, function ($mock): void {
        $mock->shouldReceive('isScrapableUrl')->andReturn(true);
        $mock->shouldReceive('scrape')->andReturn([]);
    });

    $this->getJson(route('filament-flex-fields.url-meta.scrape', ['url' => 'https://example.com/one']))->assertOk();
    $this->getJson(route('filament-flex-fields.url-meta.scrape', ['url' => 'https://example.com/two']))->assertOk();

    $this->withoutExceptionHandling();

    expect(fn () => $this->getJson(route('filament-flex-fields.url-meta.scrape', ['url' => 'https://example.com/three'])))
        ->toThrow(ValidationException::class);
});

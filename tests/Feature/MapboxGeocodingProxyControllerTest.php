<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    config()->set('filament-flex-fields.mapbox.access_token', 'pk.test-token');
    config()->set('filament-flex-fields.mapbox.rate_limit_per_minute', 2);
    config()->set('filament-flex-fields.mapbox.cache_ttl_seconds', 0);

    $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);

    RateLimiter::clear('fff-geocode:127.0.0.1');
});

it('proxies mapbox search requests', function (): void {
    Http::fake([
        'api.mapbox.com/*' => Http::response([
            'features' => [
                [
                    'id' => 'place.1',
                    'place_name' => 'Warszawa, Poland',
                    'center' => [21.0122, 52.2297],
                    'place_type' => ['place'],
                    'text' => 'Warszawa',
                ],
            ],
        ]),
    ]);

    $response = $this->postJson(route('filament-flex-fields.geocode.search'), [
        'query' => 'Warszawa',
        'language' => 'pl',
        'limit' => 3,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('features.0.place_name', 'Warszawa, Poland');

    Http::assertSentCount(1);
});

it('proxies mapbox reverse geocoding requests', function (): void {
    Http::fake([
        'api.mapbox.com/*' => Http::response([
            'features' => [
                [
                    'id' => 'address.1',
                    'place_name' => 'Marszałkowska 1, Warszawa, Poland',
                    'center' => [21.0122, 52.2297],
                    'place_type' => ['address'],
                    'text' => 'Marszałkowska',
                    'address' => '1',
                ],
            ],
        ]),
    ]);

    $response = $this->postJson(route('filament-flex-fields.geocode.reverse'), [
        'lng' => 21.0122,
        'lat' => 52.2297,
        'language' => 'pl',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('feature.place_name', 'Marszałkowska 1, Warszawa, Poland');
});

it('rate limits geocoding proxy requests', function (): void {
    Http::fake([
        'api.mapbox.com/*' => Http::response(['features' => []]),
    ]);

    $this->postJson(route('filament-flex-fields.geocode.search'), ['query' => 'One'])->assertOk();
    $this->postJson(route('filament-flex-fields.geocode.search'), ['query' => 'Two'])->assertOk();

    $this->withoutExceptionHandling();

    expect(fn () => $this->postJson(route('filament-flex-fields.geocode.search'), ['query' => 'Three']))
        ->toThrow(ValidationException::class);
});

it('validates geocoding search payloads', function (): void {
    $this->withoutExceptionHandling();

    expect(fn () => $this->postJson(route('filament-flex-fields.geocode.search'), []))
        ->toThrow(ValidationException::class);
});

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Mapbox;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MapboxGeocodingClient
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{features: list<array<string, mixed>>, error: string|null}
     */
    public function search(array $options): array
    {
        $query = (string) $options['query'];
        $cacheKey = $this->cacheKey('search', $options);

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($options, $query): array {
            $url = sprintf(
                'https://api.mapbox.com/geocoding/v5/mapbox.places/%s.json',
                rawurlencode($query),
            );

            $response = Http::timeout(12)->get($url, $this->queryParams($options, [
                'limit' => (int) ($options['limit'] ?? 6),
                'autocomplete' => ($options['autocomplete'] ?? true) ? 'true' : 'false',
            ]));

            return $this->normalizeResponse($response->throw()->json());
        });
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{feature: array<string, mixed>|null, error: string|null}
     */
    public function reverse(array $options): array
    {
        $lng = (float) $options['lng'];
        $lat = (float) $options['lat'];
        $cacheKey = $this->cacheKey('reverse', $options);

        return Cache::remember($cacheKey, $this->cacheTtl(), function () use ($options, $lng, $lat): array {
            $url = sprintf(
                'https://api.mapbox.com/geocoding/v5/mapbox.places/%s,%s.json',
                $lng,
                $lat,
            );

            $response = Http::timeout(12)->get($url, $this->queryParams($options));

            $payload = $this->normalizeResponse($response->throw()->json());
            $feature = $payload['features'][0] ?? null;

            return [
                'feature' => is_array($feature) ? $feature : null,
                'error' => $payload['error'],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $extra
     * @return array<string, string>
     */
    protected function queryParams(array $options, array $extra = []): array
    {
        $token = config('filament-flex-fields.mapbox.access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Mapbox access token is not configured.');
        }

        $params = [
            'access_token' => $token,
            'language' => (string) ($options['language'] ?? config('filament-flex-fields.mapbox.default_language', 'pl')),
        ];

        if (filled($options['types'] ?? null)) {
            $params['types'] = (string) $options['types'];
        }

        if (is_array($options['countries'] ?? null) && $options['countries'] !== []) {
            $params['country'] = strtolower(implode(',', $options['countries']));
        }

        return array_merge($params, array_map(
            fn (mixed $value): string => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
            $extra,
        ));
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array{features: list<array<string, mixed>>, error: string|null}
     */
    protected function normalizeResponse(?array $payload): array
    {
        return [
            'features' => is_array($payload['features'] ?? null) ? $payload['features'] : [],
            'error' => isset($payload['message']) ? (string) $payload['message'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function cacheKey(string $mode, array $options): string
    {
        return 'fff-geocode:'.$mode.':'.sha1(json_encode($options, JSON_THROW_ON_ERROR));
    }

    protected function cacheTtl(): int
    {
        return max(0, (int) config('filament-flex-fields.mapbox.cache_ttl_seconds', 3600));
    }
}

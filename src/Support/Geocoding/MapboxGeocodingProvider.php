<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Geocoding;

use Bjanczak\FilamentFlexFields\Support\Mapbox\MapboxGeocodingClient;

final class MapboxGeocodingProvider implements GeocodingProviderContract
{
    public function __construct(
        private readonly MapboxGeocodingClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array{features: list<array<string, mixed>>, error: string|null}
     */
    public function search(array $options): array
    {
        return $this->client->search($options);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{feature: array<string, mixed>|null, error: string|null}
     */
    public function reverse(array $options): array
    {
        return $this->client->reverse($options);
    }
}

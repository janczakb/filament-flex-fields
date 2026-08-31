<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Geocoding;

interface GeocodingProviderContract
{
    /**
     * @param  array<string, mixed>  $options
     * @return array{features: list<array<string, mixed>>, error: string|null}
     */
    public function search(array $options): array;

    /**
     * @param  array<string, mixed>  $options
     * @return array{feature: array<string, mixed>|null, error: string|null}
     */
    public function reverse(array $options): array;
}

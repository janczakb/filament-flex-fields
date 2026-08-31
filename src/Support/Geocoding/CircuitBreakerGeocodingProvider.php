<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Geocoding;

use Illuminate\Support\Facades\Cache;

final class CircuitBreakerGeocodingProvider implements GeocodingProviderContract
{
    public function __construct(
        private readonly GeocodingProviderContract $provider,
        private readonly int $failureThreshold = 5,
        private readonly int $openSeconds = 60,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array{features: list<array<string, mixed>>, error: string|null}
     */
    public function search(array $options): array
    {
        if ($this->isOpen()) {
            return [
                'features' => [],
                'error' => __('filament-flex-fields::default.geocoding.circuit_open'),
            ];
        }

        try {
            $result = $this->provider->search($options);
            $this->recordSuccess();

            return $result;
        } catch (\Throwable $throwable) {
            $this->recordFailure();

            return [
                'features' => [],
                'error' => $throwable->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{feature: array<string, mixed>|null, error: string|null}
     */
    public function reverse(array $options): array
    {
        if ($this->isOpen()) {
            return [
                'feature' => null,
                'error' => __('filament-flex-fields::default.geocoding.circuit_open'),
            ];
        }

        try {
            $result = $this->provider->reverse($options);
            $this->recordSuccess();

            return $result;
        } catch (\Throwable $throwable) {
            $this->recordFailure();

            return [
                'feature' => null,
                'error' => $throwable->getMessage(),
            ];
        }
    }

    private function isOpen(): bool
    {
        return (bool) Cache::get($this->openKey(), false);
    }

    private function recordFailure(): void
    {
        $key = $this->failureKey();
        $failures = (int) Cache::get($key, 0) + 1;

        Cache::put($key, $failures, now()->addMinutes(5));

        if ($failures >= $this->failureThreshold) {
            Cache::put($this->openKey(), true, now()->addSeconds($this->openSeconds));
            Cache::forget($key);
        }
    }

    private function recordSuccess(): void
    {
        Cache::forget($this->failureKey());
        Cache::forget($this->openKey());
    }

    private function failureKey(): string
    {
        return 'fff-geocode:circuit:failures';
    }

    private function openKey(): string
    {
        return 'fff-geocode:circuit:open';
    }
}

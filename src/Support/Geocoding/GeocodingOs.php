<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Geocoding;

use Bjanczak\FilamentFlexFields\Support\Mapbox\MapboxGeocodingClient;

final class GeocodingOs
{
    private static ?GeocodingProviderContract $provider = null;

    public static function bootFromConfig(?array $config = null): void
    {
        $config ??= config('filament-flex-fields.geocoding', []);

        if (! is_array($config)) {
            self::$provider = self::buildMapboxProvider($config);

            return;
        }

        $driver = (string) ($config['driver'] ?? 'mapbox');

        $inner = match ($driver) {
            'mapbox' => self::buildMapboxProvider($config),
            default => self::buildMapboxProvider($config),
        };

        if (($config['circuit_breaker']['enabled'] ?? true) === true) {
            self::$provider = new CircuitBreakerGeocodingProvider(
                $inner,
                (int) ($config['circuit_breaker']['failure_threshold'] ?? 5),
                (int) ($config['circuit_breaker']['open_seconds'] ?? 60),
            );

            return;
        }

        self::$provider = $inner;
    }

    public static function provider(): GeocodingProviderContract
    {
        if (self::$provider === null) {
            self::bootFromConfig();
        }

        return self::$provider ?? self::buildMapboxProvider([]);
    }

    public static function registerProvider(?GeocodingProviderContract $provider): void
    {
        self::$provider = $provider;
    }

    public static function reset(): void
    {
        self::$provider = null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function buildMapboxProvider(array $config): GeocodingProviderContract
    {
        $class = $config['mapbox_provider'] ?? MapboxGeocodingProvider::class;

        if (is_string($class) && class_exists($class) && is_subclass_of($class, GeocodingProviderContract::class)) {
            return app($class);
        }

        return new MapboxGeocodingProvider(app(MapboxGeocodingClient::class));
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

/**
 * Studio / FormBuilder presets for Mapbox Geocoding `types`.
 *
 * Mapbox Geocoding v5 does not filter POIs by category (restaurant vs cafe);
 * the {@see self::Poi} scope returns general points of interest.
 */
enum GeocodingSearchScope: string
{
    case All = 'all';

    case Address = 'address';

    case Cities = 'cities';

    case Regions = 'regions';

    case Countries = 'countries';

    case Admin = 'admin';

    case Poi = 'poi';

    case Custom = 'custom';

    /**
     * @return list<string>|null null = all Mapbox types
     */
    public function searchTypes(): ?array
    {
        return match ($this) {
            self::All, self::Custom => null,
            self::Address => [MapboxSearchType::Address->value],
            self::Cities => [
                MapboxSearchType::Place->value,
                MapboxSearchType::Locality->value,
                MapboxSearchType::Neighborhood->value,
            ],
            self::Regions => [
                MapboxSearchType::Region->value,
                MapboxSearchType::District->value,
            ],
            self::Countries => [MapboxSearchType::Country->value],
            self::Admin => [
                MapboxSearchType::Country->value,
                MapboxSearchType::Region->value,
                MapboxSearchType::District->value,
                MapboxSearchType::Place->value,
                MapboxSearchType::Locality->value,
                MapboxSearchType::Neighborhood->value,
                MapboxSearchType::Postcode->value,
            ],
            self::Poi => [MapboxSearchType::Poi->value],
        };
    }

    public function streetAddressesOnly(): bool
    {
        return $this === self::Address;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            fn (self $scope): string => $scope->value,
            self::cases(),
        );
    }
}

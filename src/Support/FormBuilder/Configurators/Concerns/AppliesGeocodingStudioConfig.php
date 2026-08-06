<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\Concerns;

use Bjanczak\FilamentFlexFields\Enums\GeocodingSearchScope;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField;

trait AppliesGeocodingStudioConfig
{
    /**
     * @param  array<string, mixed>  $config
     */
    protected function applyGeocodingStudioConfig(AddressAutocompleteField|MapPickerField $field, array $config): void
    {
        $scope = GeocodingSearchScope::tryFrom((string) ($config['search_scope'] ?? 'all'))
            ?? GeocodingSearchScope::All;

        if ($scope === GeocodingSearchScope::Custom) {
            $field->streetAddressesOnly((bool) ($config['street_addresses_only'] ?? false));
            $field->searchTypes(
                array_key_exists('search_types', $config) && is_array($config['search_types'])
                    ? $config['search_types']
                    : null,
            );
        } else {
            $field->streetAddressesOnly($scope->streetAddressesOnly());
            $field->searchTypes($scope->searchTypes());
        }

        $searchArea = (string) ($config['search_area'] ?? 'global');

        if ($searchArea === 'countries' && array_key_exists('countries', $config) && is_array($config['countries'])) {
            $codes = array_values(array_filter(array_map(
                static fn (mixed $code): string => strtoupper(trim((string) $code)),
                $config['countries'],
            )));

            $field->countries($codes === [] ? null : $codes);
        } else {
            $field->countries(null);
        }

        $languageMode = (string) ($config['language_mode'] ?? 'auto');

        if ($languageMode === 'manual' && array_key_exists('language', $config) && is_string($config['language']) && $config['language'] !== '') {
            $field->language($config['language']);
        } else {
            $field->language(null);
        }

        if (array_key_exists('min_search_length', $config)) {
            $field->minSearchLength((int) $config['min_search_length']);
        }

        if (array_key_exists('search_debounce', $config)) {
            $field->searchDebounce((int) $config['search_debounce']);
        }
    }
}

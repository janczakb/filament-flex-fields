<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class AddressAutocompleteFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof AddressAutocompleteField);

        return $this->configureAddressAutocompleteField($field, $config);
    }

    public function configureAddressAutocompleteField(AddressAutocompleteField $field, array $config): AddressAutocompleteField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.address_autocomplete_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.address_autocomplete_variant', 'primary'));

        if (array_key_exists('fields', $config) && is_array($config['fields'])) {
            $field->fields($config['fields']);
        }

        if (array_key_exists('store_format', $config)) {
            $field->storeFormat((string) $config['store_format']);
        }

        if (array_key_exists('string_format', $config)) {
            $field->stringFormat((string) $config['string_format']);
        }

        if (array_key_exists('required_fields', $config) && is_array($config['required_fields'])) {
            $field->requiredFields($config['required_fields']);
        }

        if (array_key_exists('searchable', $config)) {
            $field->searchable((bool) $config['searchable']);
        }

        if (array_key_exists('countries', $config)) {
            $field->countries(is_array($config['countries']) ? $config['countries'] : null);
        }

        if (array_key_exists('language', $config)) {
            $field->language((string) $config['language']);
        }

        if (array_key_exists('mapbox_token', $config)) {
            $field->mapboxToken(is_string($config['mapbox_token']) ? $config['mapbox_token'] : null);
        }

        if (array_key_exists('street_addresses_only', $config)) {
            $field->streetAddressesOnly((bool) $config['street_addresses_only']);
        }

        if (array_key_exists('search_types', $config)) {
            $field->searchTypes(is_array($config['search_types']) ? $config['search_types'] : null);
        }

        if (array_key_exists('min_search_length', $config)) {
            $field->minSearchLength((int) $config['min_search_length']);
        }

        if (array_key_exists('search_debounce', $config)) {
            $field->searchDebounce((int) $config['search_debounce']);
        }

        if (array_key_exists('placeholder', $config) && is_string($config['placeholder'])) {
            $field->placeholder($config['placeholder']);
        }

        return $field;
    }
}

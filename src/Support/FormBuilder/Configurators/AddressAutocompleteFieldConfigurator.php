<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\Concerns\AppliesGeocodingStudioConfig;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class AddressAutocompleteFieldConfigurator implements FieldConfigurator
{
    use AppliesGeocodingStudioConfig;

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

        if (array_key_exists('mapbox_token', $config)) {
            $field->mapboxToken(is_string($config['mapbox_token']) ? $config['mapbox_token'] : null);
        }

        if (array_key_exists('placeholder', $config) && is_string($config['placeholder'])) {
            $field->placeholder($config['placeholder']);
        }

        $this->applyGeocodingStudioConfig($field, $config);

        return $field;
    }
}

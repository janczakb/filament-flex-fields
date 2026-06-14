<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class CountryFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof CountryField);

        return $this->configureCountryField($field, $config);
    }

    public function configureCountryField(CountryField $field, array $config): CountryField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.country_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.country_variant', 'primary'))
            ->defaultCountry($config['default_country'] ?? config('filament-flex-fields.ui.country_default_country', 'PL'));

        if (array_key_exists('countries', $config) && is_array($config['countries'])) {
            $field->countries($config['countries']);
        }

        if (array_key_exists('except_countries', $config) && is_array($config['except_countries'])) {
            $field->exceptCountries($config['except_countries']);
        }

        if (array_key_exists('searchable', $config)) {
            $field->searchable((bool) $config['searchable']);
        }

        if (array_key_exists('browser_locale_default', $config)) {
            $field->browserLocaleDefault((bool) $config['browser_locale_default']);
        }

        if (array_key_exists('browser_locale_sort_first', $config)) {
            $field->browserLocaleSortFirst((bool) $config['browser_locale_sort_first']);
        }

        if (array_key_exists('show_country_code', $config)) {
            $field->showCountryCode((bool) $config['show_country_code']);
        }

        if (array_key_exists('show_dial_code', $config)) {
            $field->showDialCode((bool) $config['show_dial_code']);
        }

        return $field;
    }
}

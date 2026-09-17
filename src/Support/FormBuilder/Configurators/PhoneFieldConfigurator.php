<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;
use libphonenumber\PhoneNumberType;

final class PhoneFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof PhoneField);

        return $this->configurePhoneField($field, $config);
    }

    public function configurePhoneField(PhoneField $field, array $config): PhoneField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.phone_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.phone_variant', 'primary'))
            ->defaultCountry($config['default_country'] ?? config('filament-flex-fields.ui.phone_default_country', 'PL'));

        if (array_key_exists('countries', $config) && is_array($config['countries'])) {
            $field->countries($config['countries']);
        }

        if (array_key_exists('except_countries', $config) && is_array($config['except_countries'])) {
            $field->exceptCountries($config['except_countries']);
        }

        if (array_key_exists('searchable', $config)) {
            $field->searchable((bool) $config['searchable']);
        }

        if (array_key_exists('suffix_icon', $config)) {
            $suffixIcon = $config['suffix_icon'];

            if (is_bool($suffixIcon)) {
                $field->suffixIcon($suffixIcon);
            } elseif (filled($suffixIcon)) {
                $field->suffixIcon($suffixIcon);
            }
        }

        if (array_key_exists('international_prefix', $config)) {
            $field->internationalPrefix((bool) $config['international_prefix']);
        }

        if (array_key_exists('mobile_only', $config)) {
            $field->mobileOnly((bool) $config['mobile_only']);
        }

        if (array_key_exists('fixed_line_only', $config)) {
            $field->fixedLineOnly((bool) $config['fixed_line_only']);
        }

        if (array_key_exists('allow_types', $config) && is_array($config['allow_types'])) {
            $field->allowTypes(array_values(array_filter(
                $config['allow_types'],
                static fn (mixed $type): bool => is_int($type) || is_string($type) || $type instanceof PhoneNumberType,
            )));
        }

        if (array_key_exists('strict_types', $config)) {
            $field->strictTypes((bool) $config['strict_types']);
        }

        if (array_key_exists('validate_for_region', $config)) {
            $field->validateForRegion((bool) $config['validate_for_region']);
        }

        if (array_key_exists('national_format', $config) && filled($config['national_format'])) {
            $field->nationalFormat((string) $config['national_format']);
        }

        if (array_key_exists('include_formats', $config) && is_array($config['include_formats'])) {
            $field->includeFormats(array_values(array_filter(
                $config['include_formats'],
                static fn (mixed $format): bool => is_string($format),
            )));
        }

        if (array_key_exists('include_metadata', $config) && is_array($config['include_metadata'])) {
            $field->includeMetadata(array_values(array_filter(
                $config['include_metadata'],
                static fn (mixed $meta): bool => is_string($meta),
            )));
        }

        if (array_key_exists('browser_locale_default', $config)) {
            $field->browserLocaleDefault((bool) $config['browser_locale_default']);
        }

        if (array_key_exists('browser_locale_sort_first', $config)) {
            $field->browserLocaleSortFirst((bool) $config['browser_locale_sort_first']);
        }

        if (array_key_exists('locale', $config) && filled($config['locale'])) {
            $field->locale((string) $config['locale']);
        }

        return $field;
    }
}

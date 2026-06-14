<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class TimezoneFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof TimezoneField);

        return $this->configureTimezoneField($field, $config);
    }

    public function configureTimezoneField(TimezoneField $field, array $config): TimezoneField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.timezone_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.timezone_variant', 'primary'));

        if (array_key_exists('default_timezone', $config) && filled($config['default_timezone'])) {
            $field->defaultTimezone($config['default_timezone']);
        }

        if (array_key_exists('timezones', $config) && is_array($config['timezones'])) {
            $field->timezones($config['timezones']);
        }

        if (array_key_exists('except_timezones', $config) && is_array($config['except_timezones'])) {
            $field->exceptTimezones($config['except_timezones']);
        }

        if (array_key_exists('searchable', $config)) {
            $field->searchable((bool) $config['searchable']);
        }

        if (array_key_exists('show_offset', $config)) {
            $field->showOffset((bool) $config['show_offset']);
        }

        if (array_key_exists('browser_timezone_default', $config)) {
            $field->browserTimezoneDefault((bool) $config['browser_timezone_default']);
        }

        if (array_key_exists('browser_timezone_sort_first', $config)) {
            $field->browserTimezoneSortFirst((bool) $config['browser_timezone_sort_first']);
        }

        if (array_key_exists('prefix_icon', $config)) {
            $field->prefixIcon($config['prefix_icon']);
        }

        return $field;
    }
}

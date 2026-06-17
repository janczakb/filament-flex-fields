<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class IconPickerFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof IconPickerField);

        return $this->configureIconPickerField($field, $config);
    }

    public function configureIconPickerField(IconPickerField $field, array $config): IconPickerField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.icon_picker_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.icon_picker_variant', 'bordered'));

        if (array_key_exists('sets', $config)) {
            $field->sets($config['sets']);
        }

        if (array_key_exists('icons', $config) && is_array($config['icons'])) {
            $field->icons($config['icons']);
        }

        if (array_key_exists('exclude_icons', $config) && is_array($config['exclude_icons'])) {
            $field->excludeIcons($config['exclude_icons']);
        }

        if (array_key_exists('search_results_layout', $config) && filled($config['search_results_layout'])) {
            $field->searchResultsLayout((string) $config['search_results_layout']);
        } elseif (array_key_exists('layout', $config) && filled($config['layout'])) {
            $field->searchResultsLayout((string) $config['layout']);
        }

        if (array_key_exists('close_on_select', $config)) {
            $field->closeOnSelect((bool) $config['close_on_select']);
        }

        if (array_key_exists('grid_columns', $config)) {
            $field->gridColumns((int) $config['grid_columns']);
        }

        if (array_key_exists('preload', $config)) {
            $field->preload((bool) $config['preload']);
        }

        if (array_key_exists('limit_per_set', $config)) {
            $field->limitPerSet(is_numeric($config['limit_per_set']) ? (int) $config['limit_per_set'] : null);
        }

        if (array_key_exists('per_page', $config)) {
            $field->perPage((int) $config['per_page']);
        }

        return $field;
    }
}

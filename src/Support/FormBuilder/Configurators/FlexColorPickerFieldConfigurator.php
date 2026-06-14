<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexColorPickerField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class FlexColorPickerFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof FlexColorPickerField);

        return $this->configureFlexColorPickerField($field, $config);
    }

    public function configureFlexColorPickerField(FlexColorPickerField $field, array $config): FlexColorPickerField
    {
        $field = $field->size($config['size'] ?? 'md');

        if (array_key_exists('variant', $config) && filled($config['variant'])) {
            $field->variant($config['variant']);
        }

        if (array_key_exists('layout', $config) && filled($config['layout'])) {
            $field->layout($config['layout']);
        }

        if (array_key_exists('format', $config) && filled($config['format'])) {
            $field->format($config['format']);
        }

        if (array_key_exists('alpha', $config)) {
            $field->alpha((bool) $config['alpha']);
        }

        if (array_key_exists('eyedropper', $config)) {
            $field->eyedropper((bool) $config['eyedropper']);
        }

        if (array_key_exists('grid_columns', $config)) {
            $field->gridColumns((int) $config['grid_columns']);
        }

        if (array_key_exists('grid_rows', $config)) {
            $field->gridRows((int) $config['grid_rows']);
        }

        if (array_key_exists('grid_colors', $config)) {
            $colors = $config['grid_colors'];

            $field->gridColors(is_array($colors) ? $colors : null);
        }

        return $field;
    }
}

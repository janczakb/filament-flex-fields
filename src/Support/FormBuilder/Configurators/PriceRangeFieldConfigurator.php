<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class PriceRangeFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof PriceRangeField);

        return $this->configurePriceRangeField($field, $config);
    }

    public function configurePriceRangeField(PriceRangeField $field, array $config): PriceRangeField
    {
        $field = $field
            ->min($config['min'] ?? 0)
            ->max($config['max'] ?? 1000)
            ->step($config['step'] ?? 1)
            ->size($config['size'] ?? config('filament-flex-fields.ui.price_range_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.price_range_variant', 'primary'));

        if (array_key_exists('prefix', $config)) {
            $field->prefix($config['prefix']);
        }

        if (isset($config['histogram']) && is_array($config['histogram'])) {
            $field->histogram($config['histogram']);
        }

        if (array_key_exists('integer', $config)) {
            $field->integer((bool) $config['integer']);
        }

        if (array_key_exists('decimal_places', $config)) {
            $field->decimalPlaces($config['decimal_places']);
        }

        if (array_key_exists('show_inputs', $config)) {
            $field->showInputs((bool) $config['show_inputs']);
        }

        if (isset($config['min_input_label'])) {
            $field->minInputLabel($config['min_input_label']);
        }

        if (isset($config['max_input_label'])) {
            $field->maxInputLabel($config['max_input_label']);
        }

        return $field;
    }
}

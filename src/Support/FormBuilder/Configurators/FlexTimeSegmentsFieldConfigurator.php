<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeSegmentsField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class FlexTimeSegmentsFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof FlexTimeSegmentsField);

        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.date_time_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.date_time_variant', 'primary'));

        if (array_key_exists('minute_step', $config)) {
            $field->minuteStep((int) $config['minute_step']);
        }

        if (array_key_exists('hour_cycle', $config)) {
            $field->hourCycle((int) $config['hour_cycle']);
        }

        if (array_key_exists('min_value', $config) && filled($config['min_value'])) {
            $field->minValue($config['min_value']);
        }

        if (array_key_exists('max_value', $config) && filled($config['max_value'])) {
            $field->maxValue($config['max_value']);
        }

        if (array_key_exists('storage_format', $config) && filled($config['storage_format'])) {
            $field->storageFormat($config['storage_format']);
        }

        if (array_key_exists('locale', $config) && filled($config['locale'])) {
            $field->locale($config['locale']);
        }

        return $field;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrafficSplit;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class TrafficSplitFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof TrafficSplit);

        return $this->configureTrafficSplitField($field, $config);
    }

    public function configureTrafficSplitField(TrafficSplit $field, array $config): TrafficSplit
    {
        $field = $field
            ->segmentCount($config['segment_count'] ?? 3)
            ->minWeight($config['min_weight'] ?? 12)
            ->valueThreshold($config['value_threshold'] ?? 18)
            ->size($config['size'] ?? config('filament-flex-fields.ui.traffic_split_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.traffic_split_variant', 'default'));

        if (isset($config['labels']) && is_array($config['labels'])) {
            $field->labels($config['labels']);
        }

        if (isset($config['locked_segments']) && is_array($config['locked_segments'])) {
            $field->lockedSegments($config['locked_segments']);
        }

        if (isset($config['linked_repeater']) && filled($config['linked_repeater'])) {
            $field->linkedToRepeater(
                (string) $config['linked_repeater'],
                (bool) ($config['linked_repeater_numeric_labels'] ?? true),
                (int) ($config['linked_repeater_minimum_items'] ?? 2),
            );
        }

        return $field;
    }
}

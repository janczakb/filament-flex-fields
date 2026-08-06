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
        $segmentCount = $config['segment_count'] ?? 3;

        $field = $field
            ->segmentCount(is_numeric($segmentCount) ? (int) $segmentCount : 3)
            ->minWeight($config['min_weight'] ?? 12)
            ->valueThreshold($config['value_threshold'] ?? 18)
            ->size($config['size'] ?? config('filament-flex-fields.ui.traffic_split_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.traffic_split_variant', 'default'));

        $labels = $config['labels'] ?? null;

        if (is_array($labels) && $labels !== []) {
            $field->labels(array_values(array_map(
                static fn (mixed $label): string => (string) $label,
                $labels,
            )));
        }

        $lockedSegments = $config['locked_segments'] ?? null;

        if (is_array($lockedSegments) && $lockedSegments !== []) {
            $field->lockedSegments(array_values(array_map(
                static fn (mixed $index): int => (int) $index,
                $lockedSegments,
            )));
        }

        if (isset($config['linked_repeater']) && filled($config['linked_repeater'])) {
            $field->linkedToRepeater(
                (string) $config['linked_repeater'],
                (bool) ($config['linked_repeater_numeric_labels'] ?? true),
                (int) ($config['linked_repeater_minimum_items'] ?? 2),
            );
        }

        if (array_key_exists('disabled', $config)) {
            $field->disabled((bool) $config['disabled']);
        }

        return $field;
    }
}

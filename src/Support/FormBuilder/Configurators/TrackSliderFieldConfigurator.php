<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrackSlider;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class TrackSliderFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof TrackSlider);

        return $this->configureTrackSliderField($field, $config);
    }

    public function configureTrackSliderField(TrackSlider $field, array $config): TrackSlider
    {
        $field = $field
            ->min($config['min'] ?? 0)
            ->max($config['max'] ?? 100)
            ->step($config['step'] ?? 1)
            ->size($config['size'] ?? config('filament-flex-fields.ui.slider_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.slider_variant', 'default'));

        $showOutput = array_key_exists('show_output', $config)
            ? (bool) $config['show_output']
            : true;

        $field->showOutput($showOutput);

        $suffix = $config['suffix'] ?? null;

        if (is_string($suffix) && $suffix !== '') {
            $field->suffix($suffix);
        }

        $trackLabel = $config['track_label'] ?? null;

        if (is_string($trackLabel) && $trackLabel !== '') {
            $field->trackLabel($trackLabel);
        }

        return $field;
    }
}

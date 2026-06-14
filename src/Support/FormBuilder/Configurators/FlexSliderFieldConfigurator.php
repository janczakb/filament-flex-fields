<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class FlexSliderFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof FlexSlider);

        return $this->configureFlexSliderField($field, $config);
    }

    public function configureFlexSliderField(FlexSlider $field, array $config): FlexSlider
    {
        $min = $config['min'] ?? 0;
        $max = $config['max'] ?? 100;

        $field = $field
            ->range($min, $max)
            ->step($config['step'] ?? 1)
            ->size($config['size'] ?? config('filament-flex-fields.ui.slider_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.slider_variant', 'default'));

        if (array_key_exists('show_value', $config)) {
            $field->showValue((bool) $config['show_value']);
        }

        if (isset($config['prefix'])) {
            $field->prefix($config['prefix']);
        }

        if (isset($config['suffix'])) {
            $field->suffix($config['suffix']);
        }

        if (isset($config['track_label'])) {
            $field->trackLabel($config['track_label']);
        }

        if (array_key_exists('hide_thumb_until_interaction', $config)) {
            $field->hideThumbUntilInteraction((bool) $config['hide_thumb_until_interaction']);
        }

        if (array_key_exists('show_step_dots', $config)) {
            $field->showStepDots((bool) $config['show_step_dots']);
        }

        if (isset($config['value_position'])) {
            $field->valuePosition($config['value_position']);
        }

        if (array_key_exists('fill_track', $config)) {
            $fillTrack = $config['fill_track'];

            if (is_array($fillTrack)) {
                $field->fillTrack($fillTrack);
            } elseif ((bool) $fillTrack) {
                $field->fillTrack();
            }
        } elseif (array_key_exists('auto_fill', $config)) {
            $field->autoFill((bool) $config['auto_fill']);
        }

        if (isset($config['color'])) {
            $field->color($config['color']);
        }

        if (isset($config['fill_color'])) {
            $field->fillColor($config['fill_color']);
        }

        if (array_key_exists('decimal_places', $config)) {
            $field->decimalPlaces((int) $config['decimal_places']);
        }

        return $field;
    }
}

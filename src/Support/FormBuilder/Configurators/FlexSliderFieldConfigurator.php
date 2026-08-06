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
        $isRange = (bool) ($config['is_range'] ?? false);

        $field = $field
            ->range($min, $max)
            ->step($config['step'] ?? 1)
            ->size($config['size'] ?? config('filament-flex-fields.ui.slider_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.slider_variant', 'default'));

        if (array_key_exists('show_value', $config)) {
            $field->showValue((bool) $config['show_value']);
        }

        if (isset($config['prefix']) && is_string($config['prefix']) && $config['prefix'] !== '') {
            $field->prefix($config['prefix']);
        }

        if (isset($config['suffix']) && is_string($config['suffix']) && $config['suffix'] !== '') {
            $field->suffix($config['suffix']);
        }

        if (isset($config['track_label']) && is_string($config['track_label']) && $config['track_label'] !== '') {
            $field->trackLabel($config['track_label']);
        }

        if (array_key_exists('hide_thumb_until_interaction', $config)) {
            $field->hideThumbUntilInteraction((bool) $config['hide_thumb_until_interaction']);
        }

        if (array_key_exists('show_step_dots', $config)) {
            $field->showStepDots((bool) $config['show_step_dots']);
        }

        if (isset($config['value_position']) && is_string($config['value_position']) && $config['value_position'] !== '') {
            $field->valuePosition($config['value_position']);
        }

        if ($isRange) {
            // Dual-handle mode is state-shaped; mid-segment fill matches playground range demos.
            $field->rangeHandles();

            if (array_key_exists('auto_fill', $config)) {
                $field->autoFill((bool) $config['auto_fill']);
            } elseif (is_array($config['fill_track'] ?? null)) {
                $field->fillTrack($config['fill_track']);
            } else {
                $field->autoFill();
            }

            $rangeDefault = self::defaultRangeValues($config);
            $field->default($rangeDefault);
            $field->afterStateHydrated(function (FlexSlider $component, mixed $state) use ($rangeDefault): void {
                if (is_array($state) && count($state) >= 2) {
                    return;
                }

                $component->state($rangeDefault);
            });
        } elseif (array_key_exists('fill_track', $config)) {
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

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: float|int, 1: float|int}
     */
    public static function defaultRangeValues(array $config): array
    {
        $min = is_numeric($config['min'] ?? null) ? $config['min'] + 0 : 0;
        $max = is_numeric($config['max'] ?? null) ? $config['max'] + 0 : 100;

        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }

        $span = $max - $min;
        $low = $min + ($span * 0.2);
        $high = $min + ($span * 0.8);
        $step = is_numeric($config['step'] ?? null) ? max(0, $config['step'] + 0) : 1;

        if ($step > 0) {
            $low = round($low / $step) * $step;
            $high = round($high / $step) * $step;
        }

        if ($low < $min) {
            $low = $min;
        }

        if ($high > $max) {
            $high = $max;
        }

        if ($low >= $high) {
            $low = $min;
            $high = $max;
        }

        return [$low, $high];
    }
}

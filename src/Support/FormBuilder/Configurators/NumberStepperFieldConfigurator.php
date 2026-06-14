<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class NumberStepperFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof NumberStepper);

        return $this->configureNumberStepperField($field, $config);
    }

    public function configureNumberStepperField(NumberStepper $field, array $config): NumberStepper
    {
        $field = $field
            ->minValue($config['min'] ?? null)
            ->maxValue($config['max'] ?? null)
            ->step($config['step'] ?? 1)
            ->size($config['size'] ?? config('filament-flex-fields.ui.number_stepper_size', 'md'));

        if (array_key_exists('icons', $config) && is_array($config['icons'])) {
            $field->icons($config['icons']);
        } elseif (array_key_exists('decrement_icon', $config) || array_key_exists('increment_icon', $config)) {
            if (array_key_exists('decrement_icon', $config) && filled($config['decrement_icon'])) {
                $field->decrementIcon($config['decrement_icon']);
            }

            if (array_key_exists('increment_icon', $config) && filled($config['increment_icon'])) {
                $field->incrementIcon($config['increment_icon']);
            }
        }

        return $field;
    }
}

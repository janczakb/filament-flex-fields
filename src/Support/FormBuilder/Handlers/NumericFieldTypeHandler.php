<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrackSlider;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrafficSplit;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\CurrencyFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexSliderFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexTextInputFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\NumberStepperFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\PriceRangeFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\TrackSliderFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\TrafficSplitFieldConfigurator;
use Filament\Schemas\Components\Component;

final class NumericFieldTypeHandler extends AbstractFieldTypeHandler
{
    public function __construct(
        private readonly NumberStepperFieldConfigurator $numberStepper = new NumberStepperFieldConfigurator,
        private readonly CurrencyFieldConfigurator $currency = new CurrencyFieldConfigurator,
        private readonly TrackSliderFieldConfigurator $trackSlider = new TrackSliderFieldConfigurator,
        private readonly FlexSliderFieldConfigurator $flexSlider = new FlexSliderFieldConfigurator,
        private readonly PriceRangeFieldConfigurator $priceRange = new PriceRangeFieldConfigurator,
        private readonly TrafficSplitFieldConfigurator $trafficSplit = new TrafficSplitFieldConfigurator,
        private readonly FlexTextInputFieldConfigurator $flexTextInput = new FlexTextInputFieldConfigurator,
    ) {}

    protected function supportedTypesList(): array
    {
        return [
            FieldType::Integer,
            FieldType::Decimal,
            FieldType::NumberStepper,
            FieldType::Currency,
            FieldType::Percentage,
            FieldType::RangeSlider,
            FieldType::RangeMinMax,
            FieldType::FlexSlider,
            FieldType::PriceRange,
            FieldType::TrafficSplit,
        ];
    }

    public function make(FlexFieldDefinition $definition, string $statePath): Component
    {
        $config = $definition->config;

        return match ($definition->type) {
            FieldType::Integer => $this->makeFlexNumberInput(
                $statePath,
                $config,
                integer: ($config['allow_decimals'] ?? false) !== true,
            ),
            FieldType::Decimal => $this->makeFlexNumberInput($statePath, $config, integer: false),
            FieldType::NumberStepper => $this->numberStepper->configure(NumberStepper::make($statePath), $config),
            FieldType::Currency => $this->currency->configure(CurrencyField::make($statePath), $config),
            FieldType::Percentage,
            FieldType::RangeSlider,
            FieldType::RangeMinMax => $this->trackSlider->configure(TrackSlider::make($statePath), $config),
            FieldType::FlexSlider => $this->flexSlider->configure(FlexSlider::make($statePath), $config),
            FieldType::PriceRange => $this->priceRange->configure(PriceRangeField::make($statePath), $config),
            FieldType::TrafficSplit => $this->trafficSplit->configure(TrafficSplit::make($statePath), $config),
            default => throw new \InvalidArgumentException("Unsupported field type [{$definition->type->value}] for numeric handler."),
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function makeFlexNumberInput(string $statePath, array $config, bool $integer): FlexTextInput
    {
        $field = FlexTextInput::make($statePath);

        if ($integer) {
            $field->integer();
        } else {
            $field->numeric();
        }

        if (isset($config['min']) && is_numeric($config['min'])) {
            $field->minValue($config['min'] + 0);
        }

        if (isset($config['max']) && is_numeric($config['max'])) {
            $field->maxValue($config['max'] + 0);
        }

        if (isset($config['step']) && is_numeric($config['step'])) {
            $field->step($config['step'] + 0);
        }

        return $this->flexTextInput->configure($field, $config);
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexSliderFieldConfigurator;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;

trait AppliesFieldDefinitionMeta
{
    protected function applyDefinitionMeta(Component $field, FlexFieldDefinition $definition): Component
    {
        if ($field instanceof Field) {
            $field->label($definition->label);

            if ($definition->hiddenLabel) {
                $field->hiddenLabel();
            }

            if (filled($definition->helpText)) {
                $field->helperText($definition->helpText);
            }

            if (filled($definition->placeholder)) {
                $field->placeholder($definition->placeholder);
            }

            if ($definition->isRequired) {
                $field->required();
            }

            $minLength = $definition->config['min_length'] ?? null;
            $maxLength = $definition->config['max_length'] ?? null;

            if (is_numeric($minLength) && method_exists($field, 'minLength')) {
                $field->minLength((int) $minLength);
            }

            if (is_numeric($maxLength) && method_exists($field, 'maxLength')) {
                $field->maxLength((int) $maxLength);
            }

            foreach ($definition->validation as $rule) {
                if (is_string($rule)) {
                    $field->rule($rule);
                }
            }

            $this->applyDefaultValue($field, $definition);
        }

        return $field;
    }

    protected function applyDefaultValue(Field $field, FlexFieldDefinition $definition): void
    {
        $isRangeFlexSlider = $field instanceof FlexSlider
            && (bool) ($definition->config['is_range'] ?? false);

        if ($definition->defaultValue !== null) {
            $default = $definition->defaultValue;

            if ($isRangeFlexSlider && ! is_array($default)) {
                $default = FlexSliderFieldConfigurator::defaultRangeValues($definition->config);
            }

            $field->default($default);

            return;
        }

        if ($isRangeFlexSlider) {
            $field->default(FlexSliderFieldConfigurator::defaultRangeValues($definition->config));
        }
    }
}

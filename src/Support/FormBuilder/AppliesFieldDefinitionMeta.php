<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FlexFieldWidth;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexSliderFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Bjanczak\FilamentFlexFields\Support\Schema\JsonFieldConditions;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Throwable;

trait AppliesFieldDefinitionMeta
{
    protected function applyDefinitionMeta(Component $field, FlexFieldDefinition $definition, string $statePathPrefix = ''): Component
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

            if ($definition->isRequired && $definition->requiredWhen === null) {
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
            $this->applyFormulaMeta($field, $definition, $statePathPrefix);
        }

        $this->applyFieldConditions($field, $definition, $statePathPrefix);

        return $field;
    }

    protected function applyFormulaMeta(Field $field, FlexFieldDefinition $definition, string $statePathPrefix = ''): void
    {
        if (! $definition->hasFormula()) {
            return;
        }

        $expression = (string) $definition->formula;
        $targetSlug = $definition->slug;

        $field->disabled();
        $field->dehydrated();

        $field->afterStateHydrated(function (Field $component, mixed $state) use ($expression, $targetSlug, $statePathPrefix): void {
            if (filled($state)) {
                return;
            }

            $get = $component->makeGetUtility();
            $set = $component->makeSetUtility();

            self::recalculateFormulaTarget($expression, $targetSlug, $get, $set, $statePathPrefix);
        });
    }

    /**
     * @param  array<string, Component>  $componentsBySlug
     * @param  array<string, string>  $formulas  slug => expression
     */
    protected function wireFormulaDependencies(array $componentsBySlug, array $formulas, string $statePathPrefix = ''): void
    {
        if ($formulas === []) {
            return;
        }

        if (FormulaEngine::detectCycle($formulas) !== []) {
            return;
        }

        $inputSlugs = [];

        foreach ($formulas as $expression) {
            foreach (FormulaEngine::fieldReferences($expression) as $dependencySlug) {
                if (! array_key_exists($dependencySlug, $formulas)) {
                    $inputSlugs[$dependencySlug] = true;
                }
            }
        }

        foreach (array_keys($inputSlugs) as $dependencySlug) {
            $component = $componentsBySlug[$dependencySlug] ?? null;

            if (! $component instanceof Field) {
                continue;
            }

            $component->live();
            $component->afterStateUpdated(function (mixed $state, Set $set, Get $get) use ($formulas, $statePathPrefix): void {
                self::recalculateFormulaMap($formulas, $get, $set, $statePathPrefix);
            });
        }
    }

    /**
     * @param  array<string, string>  $formulas
     */
    protected static function recalculateFormulaMap(
        array $formulas,
        Get $get,
        Set $set,
        string $statePathPrefix = '',
    ): void {
        $pathFor = static fn (string $slug): string => filled($statePathPrefix)
            ? "{$statePathPrefix}.{$slug}"
            : $slug;

        $values = [];

        foreach ($formulas as $expression) {
            foreach (FormulaEngine::fieldReferences($expression) as $fieldKey) {
                if (array_key_exists($fieldKey, $formulas) || array_key_exists($fieldKey, $values)) {
                    continue;
                }

                $values[$fieldKey] = $get($pathFor($fieldKey));
            }
        }

        try {
            $results = FormulaEngine::evaluateMap($formulas, $values);
        } catch (Throwable) {
            return;
        }

        foreach ($results as $targetSlug => $result) {
            $set($pathFor($targetSlug), (string) $result);
        }
    }

    protected static function recalculateFormulaTarget(
        string $expression,
        string $targetSlug,
        Get $get,
        Set $set,
        string $statePathPrefix = '',
    ): void {
        self::recalculateFormulaMap(
            [$targetSlug => $expression],
            $get,
            $set,
            $statePathPrefix,
        );
    }

    protected function applyFieldConditions(Component $field, FlexFieldDefinition $definition, string $statePathPrefix = ''): void
    {
        if ($definition->visibleWhen !== null) {
            $field->visible(JsonFieldConditions::compileVisibleWhen($definition->visibleWhen, $statePathPrefix));
        }

        if ($definition->disabledWhen !== null) {
            $field->disabled(JsonFieldConditions::compileDisabledWhen($definition->disabledWhen, $statePathPrefix));
        }

        if ($definition->width !== FlexFieldWidth::Full) {
            $field->columnSpan($definition->width->columnSpan());
        }

        if ($field instanceof Field) {
            $this->applyRequiredWhen($field, $definition);
        }
    }

    protected function applyRequiredWhen(Field $field, FlexFieldDefinition $definition): void
    {
        if ($definition->requiredWhen === null) {
            return;
        }

        $requiredWhen = JsonFieldConditions::compileRequiredWhen($definition->requiredWhen);

        if ($definition->isRequired) {
            $field->required();

            return;
        }

        $field->required($requiredWhen);
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

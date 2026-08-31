<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Support\Enterprise\FieldRbacMatrix;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\AppliesFieldDefinitionMeta;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\FieldComponentFactory;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldSchemaResolver;
use Filament\Schemas\Components\Component;

class FlexFieldFormBuilder
{
    use AppliesFieldDefinitionMeta;

    public function __construct(
        private readonly FieldComponentFactory $factory = new FieldComponentFactory,
    ) {}

    /**
     * @param  iterable<FlexFieldDefinition>  $definitions
     * @return list<Component>
     */
    public function build(
        iterable $definitions,
        string $statePathPrefix = 'flex_field_values',
        ?string $rbacUserKey = null,
    ): array {
        $activeDefinitions = [];
        $componentsBySlug = [];

        foreach ($definitions as $definition) {
            if (! $definition->isActive || $this->shouldSkipDefinition($definition, $rbacUserKey)) {
                continue;
            }

            $activeDefinitions[] = $definition;
            $component = $this->makeComponent($definition, $statePathPrefix);

            if ($component !== null) {
                $componentsBySlug[$definition->slug] = $component;
            }
        }

        $formulas = [];

        foreach ($activeDefinitions as $definition) {
            if ($definition->hasFormula()) {
                $formulas[$definition->slug] = (string) $definition->formula;
            }
        }

        $this->wireFormulaDependencies($componentsBySlug, $formulas, $statePathPrefix);

        return array_values($componentsBySlug);
    }

    public function makeComponent(FlexFieldDefinition $definition, string $statePathPrefix = 'flex_field_values'): ?Component
    {
        return $this->factory->makeComponent($definition, $statePathPrefix);
    }

    /**
     * Wire live recalculation for formula/calculated fields after components were built
     * via {@see makeComponent()} (e.g. host builders that loop instead of {@see build()}).
     *
     * @param  list<FlexFieldDefinition>  $definitions
     * @param  array<string, Component>  $componentsBySlug
     */
    public function wireFormulaFields(array $definitions, array $componentsBySlug, string $statePathPrefix = ''): void
    {
        $formulas = [];

        foreach ($definitions as $definition) {
            if ($definition->hasFormula()) {
                $formulas[$definition->slug] = (string) $definition->formula;
            }
        }

        $this->wireFormulaDependencies($componentsBySlug, $formulas, $statePathPrefix);
    }

    protected function shouldSkipDefinition(FlexFieldDefinition $definition, ?string $rbacUserKey = null): bool
    {
        if ($rbacUserKey !== null && $rbacUserKey !== '') {
            $resolver = app(FlexFieldSchemaResolver::class);

            if (! $resolver->fieldAllowedForUser($definition, $rbacUserKey, FieldRbacMatrix::ABILITY_EDIT)) {
                return true;
            }
        }

        if ($definition->hasDynamicVisibility()) {
            return false;
        }

        return ! $definition->isVisible;
    }
}

<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\FieldComponentFactory;
use Filament\Schemas\Components\Component;

class FlexFieldFormBuilder
{
    public function __construct(
        private readonly FieldComponentFactory $factory = new FieldComponentFactory,
    ) {}

    /**
     * @param  iterable<FlexFieldDefinition>  $definitions
     * @return list<Component>
     */
    public function build(iterable $definitions, string $statePathPrefix = 'flex_field_values'): array
    {
        $components = [];

        foreach ($definitions as $definition) {
            if (! $definition->isActive || ! $definition->isVisible) {
                continue;
            }

            $component = $this->makeComponent($definition, $statePathPrefix);

            if ($component !== null) {
                $components[] = $component;
            }
        }

        return $components;
    }

    public function makeComponent(FlexFieldDefinition $definition, string $statePathPrefix = 'flex_field_values'): ?Component
    {
        return $this->factory->makeComponent($definition, $statePathPrefix);
    }
}

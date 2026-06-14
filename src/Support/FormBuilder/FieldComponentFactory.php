<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Registry\FieldTypeHandlerRegistry;
use Filament\Schemas\Components\Component;

final class FieldComponentFactory
{
    public function __construct(
        private readonly FieldTypeHandlerRegistry $registry = new FieldTypeHandlerRegistry,
    ) {}

    public function makeComponent(FlexFieldDefinition $definition, string $statePathPrefix = 'flex_field_values'): ?Component
    {
        return $this->registry->makeComponent($definition, $statePathPrefix);
    }
}

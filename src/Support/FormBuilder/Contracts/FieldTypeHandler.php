<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Filament\Schemas\Components\Component;

interface FieldTypeHandler
{
    /**
     * @return list<FieldType>
     */
    public function supportedTypes(): array;

    public function supports(FieldType $type): bool;

    public function make(FlexFieldDefinition $definition, string $statePath): Component;
}

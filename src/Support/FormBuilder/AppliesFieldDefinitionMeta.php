<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;

trait AppliesFieldDefinitionMeta
{
    protected function applyDefinitionMeta(Component $field, FlexFieldDefinition $definition): Component
    {
        if ($field instanceof Field) {
            $field
                ->label($definition->label)
                ->helperText($definition->helpText);

            if (filled($definition->placeholder)) {
                $field->placeholder($definition->placeholder);
            }

            if ($definition->isRequired) {
                $field->required();
            }

            foreach ($definition->validation as $rule) {
                if (is_string($rule)) {
                    $field->rule($rule);
                }
            }

            if ($definition->defaultValue !== null) {
                $field->default($definition->defaultValue);
            }
        }

        return $field;
    }
}

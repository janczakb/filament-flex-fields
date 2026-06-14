<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

final class PassthroughFieldTypeHandler extends AbstractFieldTypeHandler
{
    protected function supportedTypesList(): array
    {
        return [
            FieldType::KeyValue,
            FieldType::Repeater,
            FieldType::Code,
            FieldType::Json,
            FieldType::Hidden,
            FieldType::ReadOnly,
        ];
    }

    public function make(FlexFieldDefinition $definition, string $statePath): Component
    {
        return match ($definition->type) {
            FieldType::KeyValue => KeyValue::make($statePath),
            FieldType::Repeater => Repeater::make($statePath),
            FieldType::Code => Textarea::make($statePath)->rows(8)->extraAttributes(['class' => 'font-mono text-sm']),
            FieldType::Json => Textarea::make($statePath)->rows(8)->extraAttributes(['class' => 'font-mono text-sm']),
            FieldType::Hidden => Hidden::make($statePath),
            FieldType::ReadOnly => TextInput::make($statePath)->readOnly(),
            default => throw new \InvalidArgumentException("Unsupported field type [{$definition->type->value}] for passthrough handler."),
        };
    }
}

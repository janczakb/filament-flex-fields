<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ColorSwatchField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexColorPickerField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\ColorSwatchFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexColorPickerFieldConfigurator;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Component;

final class ColorFieldTypeHandler extends AbstractFieldTypeHandler
{
    public function __construct(
        private readonly ColorSwatchFieldConfigurator $colorSwatch = new ColorSwatchFieldConfigurator,
        private readonly FlexColorPickerFieldConfigurator $flexColorPicker = new FlexColorPickerFieldConfigurator,
    ) {}

    protected function supportedTypesList(): array
    {
        return [
            FieldType::Color,
            FieldType::ColorPresets,
            FieldType::FlexColorPicker,
        ];
    }

    public function make(FlexFieldDefinition $definition, string $statePath): Component
    {
        $config = $definition->config;

        return match ($definition->type) {
            FieldType::Color => ColorPicker::make($statePath),
            FieldType::ColorPresets => $this->colorSwatch->configure(ColorSwatchField::make($statePath), $config),
            FieldType::FlexColorPicker => $this->flexColorPicker->configure(FlexColorPickerField::make($statePath), $config),
            default => throw new \InvalidArgumentException("Unsupported field type [{$definition->type->value}] for color handler."),
        };
    }
}

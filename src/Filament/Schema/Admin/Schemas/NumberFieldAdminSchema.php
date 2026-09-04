<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Schemas;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Concerns\BuildsCommonAdminFields;
use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Concerns\InteractsWithFieldTypeAdminContext;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;

/**
 * @internal Legacy grouped admin schema — superseded by FieldTypeAutoAdminSchema. Not referenced at runtime.
 */
final class NumberFieldAdminSchema
{
    use BuildsCommonAdminFields;
    use InteractsWithFieldTypeAdminContext;

    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        $types = [
            FieldType::Integer,
            FieldType::Decimal,
            FieldType::NumberStepper,
            FieldType::Currency,
            FieldType::Percentage,
            FieldType::RangeSlider,
            FieldType::RangeMinMax,
            FieldType::FlexSlider,
            FieldType::PriceRange,
        ];

        return [
            Fieldset::make(__('filament-flex-fields::default.schema.settings.number'))
                ->columns(3)
                ->schema([
                    ...self::minMaxStepFields(),
                    Toggle::make(self::settingsPath('allow_decimals'))
                        ->label(__('filament-flex-fields::default.schema.settings.allow_decimals'))
                        ->visible(fn (Get $get): bool => self::isType($get, FieldType::Integer)),
                    Toggle::make(self::settingsPath('allow_negative'))
                        ->label(__('filament-flex-fields::default.schema.settings.allow_negative'))
                        ->visible(fn (Get $get): bool => self::isType($get, FieldType::Currency)),
                    TextInput::make(self::settingsPath('currency'))
                        ->label(__('filament-flex-fields::default.schema.settings.currency'))
                        ->maxLength(3)
                        ->visible(fn (Get $get): bool => self::isType($get, FieldType::Currency)),
                    TextInput::make(self::settingsPath('locale'))
                        ->label(__('filament-flex-fields::default.schema.settings.locale'))
                        ->visible(fn (Get $get): bool => self::isType($get, FieldType::Currency)),
                    TextInput::make(self::settingsPath('suffix'))
                        ->label(__('filament-flex-fields::default.schema.settings.suffix'))
                        ->visible(fn (Get $get): bool => self::isType($get, FieldType::Percentage)),
                    TextInput::make(self::settingsPath('prefix'))
                        ->label(__('filament-flex-fields::default.schema.settings.prefix'))
                        ->visible(fn (Get $get): bool => self::isType($get, FieldType::PriceRange)),
                    Toggle::make(self::settingsPath('show_output'))
                        ->label(__('filament-flex-fields::default.schema.settings.show_output'))
                        ->visible(fn (Get $get): bool => self::isOneOf($get, [FieldType::Percentage, FieldType::RangeSlider, FieldType::RangeMinMax])),
                    Toggle::make(self::settingsPath('is_range'))
                        ->label(__('filament-flex-fields::default.schema.settings.is_range'))
                        ->visible(fn (Get $get): bool => self::isType($get, FieldType::FlexSlider)),
                    Toggle::make(self::settingsPath('show_value'))
                        ->label(__('filament-flex-fields::default.schema.settings.show_value'))
                        ->visible(fn (Get $get): bool => self::isType($get, FieldType::FlexSlider)),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isOneOf($get, $types))
                ->columnSpanFull(),
        ];
    }
}

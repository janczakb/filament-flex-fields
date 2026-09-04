<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Schemas;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Concerns\BuildsCommonAdminFields;
use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Concerns\InteractsWithFieldTypeAdminContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;

/**
 * @internal Legacy grouped admin schema — superseded by FieldTypeAutoAdminSchema. Not referenced at runtime.
 */
final class LocationPhoneColorAdminSchema
{
    use BuildsCommonAdminFields;
    use InteractsWithFieldTypeAdminContext;

    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        return [
            Fieldset::make(__('filament-flex-fields::default.schema.settings.phone'))
                ->columns(3)
                ->schema([
                    TextInput::make(self::settingsPath('default_country'))
                        ->label(__('filament-flex-fields::default.schema.settings.default_country')),
                    Toggle::make(self::settingsPath('searchable'))
                        ->label(__('filament-flex-fields::default.schema.settings.searchable')),
                    Toggle::make(self::settingsPath('international_prefix'))
                        ->label(__('filament-flex-fields::default.schema.settings.international_prefix')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isOneOf($get, [FieldType::Phone, FieldType::Country, FieldType::Timezone]))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.location'))
                ->columns(3)
                ->schema([
                    Select::make(self::settingsPath('layout'))
                        ->label(__('filament-flex-fields::default.schema.settings.layout'))
                        ->options([
                            'input' => 'Input',
                            'map' => 'Map',
                        ])
                        ->native(false),
                    Select::make(self::settingsPath('store_format'))
                        ->label(__('filament-flex-fields::default.schema.settings.store_format'))
                        ->options([
                            'structured' => 'Structured',
                            'string' => 'String',
                        ])
                        ->native(false),
                    Toggle::make(self::settingsPath('searchable'))
                        ->label(__('filament-flex-fields::default.schema.settings.searchable')),
                    TextInput::make(self::settingsPath('min_search_length'))
                        ->label(__('filament-flex-fields::default.schema.settings.min_search_length'))
                        ->numeric(),
                ])
                ->visible(fn (Get $get): bool => self::isOneOf($get, [FieldType::AddressAutocomplete, FieldType::MapPicker]))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.color'))
                ->columns(3)
                ->schema([
                    Select::make(self::settingsPath('layout'))
                        ->label(__('filament-flex-fields::default.schema.settings.layout'))
                        ->options([
                            'simple' => 'Simple',
                            'advanced' => 'Advanced',
                        ])
                        ->native(false),
                    Select::make(self::settingsPath('format'))
                        ->label(__('filament-flex-fields::default.schema.settings.color_format'))
                        ->options([
                            'hex' => 'Hex',
                            'rgb' => 'RGB',
                            'hsl' => 'HSL',
                        ])
                        ->native(false),
                    Toggle::make(self::settingsPath('alpha'))
                        ->label(__('filament-flex-fields::default.schema.settings.alpha')),
                    Toggle::make(self::settingsPath('tooltips'))
                        ->label(__('filament-flex-fields::default.schema.settings.tooltips')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isOneOf($get, [FieldType::Color, FieldType::ColorPresets, FieldType::FlexColorPicker]))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.barcode'))
                ->columns(3)
                ->schema([
                    Toggle::make(self::settingsPath('beep_on_scan'))
                        ->label(__('filament-flex-fields::default.schema.settings.beep_on_scan')),
                    Toggle::make(self::settingsPath('allow_manual_input'))
                        ->label(__('filament-flex-fields::default.schema.settings.allow_manual_input')),
                    TextInput::make(self::settingsPath('scan_delay'))
                        ->label(__('filament-flex-fields::default.schema.settings.scan_delay'))
                        ->numeric(),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Barcode))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.social_links'))
                ->columns(2)
                ->schema([
                    TextInput::make(self::settingsPath('max_links'))
                        ->label(__('filament-flex-fields::default.schema.settings.max_links'))
                        ->numeric(),
                    Toggle::make(self::settingsPath('reorderable'))
                        ->label(__('filament-flex-fields::default.schema.settings.reorderable')),
                    Toggle::make(self::settingsPath('auto_format_urls'))
                        ->label(__('filament-flex-fields::default.schema.settings.auto_format_urls')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::SocialLinks))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.traffic_split'))
                ->columns(3)
                ->schema([
                    TextInput::make(self::settingsPath('segment_count'))
                        ->label(__('filament-flex-fields::default.schema.settings.segment_count'))
                        ->numeric(),
                    TextInput::make(self::settingsPath('min_weight'))
                        ->label(__('filament-flex-fields::default.schema.settings.min_weight'))
                        ->numeric(),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::TrafficSplit))
                ->columnSpanFull(),
        ];
    }
}

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
final class ToggleTagsRatingAdminSchema
{
    use BuildsCommonAdminFields;
    use InteractsWithFieldTypeAdminContext;

    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        return [
            Fieldset::make(__('filament-flex-fields::default.schema.settings.toggle'))
                ->columns(3)
                ->schema([
                    Select::make(self::settingsPath('layout'))
                        ->label(__('filament-flex-fields::default.schema.settings.layout'))
                        ->options([
                            'row' => 'Row',
                            'card' => 'Card',
                        ])
                        ->native(false),
                    Select::make(self::settingsPath('label_position'))
                        ->label(__('filament-flex-fields::default.schema.settings.label_position'))
                        ->options([
                            'start' => 'Start',
                            'end' => 'End',
                        ])
                        ->native(false),
                    Toggle::make(self::settingsPath('inline'))
                        ->label(__('filament-flex-fields::default.schema.settings.inline')),
                    Toggle::make(self::settingsPath('ripple'))
                        ->label(__('filament-flex-fields::default.schema.settings.ripple')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Toggle))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.tags'))
                ->columns(3)
                ->schema([
                    TextInput::make(self::settingsPath('max_tags'))
                        ->label(__('filament-flex-fields::default.schema.settings.max_tags'))
                        ->numeric(),
                    Toggle::make(self::settingsPath('reorderable'))
                        ->label(__('filament-flex-fields::default.schema.settings.reorderable')),
                    Toggle::make(self::settingsPath('suggestions_only'))
                        ->label(__('filament-flex-fields::default.schema.settings.suggestions_only')),
                    Toggle::make(self::settingsPath('trim'))
                        ->label(__('filament-flex-fields::default.schema.settings.trim')),
                    Toggle::make(self::settingsPath('use_spatie_tags'))
                        ->label(__('filament-flex-fields::default.schema.settings.use_spatie_tags')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Tags))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.rating'))
                ->columns(2)
                ->schema([
                    TextInput::make(self::settingsPath('max'))
                        ->label(__('filament-flex-fields::default.schema.settings.max'))
                        ->numeric(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Rating))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.nps'))
                ->columns(3)
                ->schema([
                    Select::make(self::settingsPath('variant'))
                        ->label(__('filament-flex-fields::default.schema.settings.variant'))
                        ->options([
                            'pills' => 'Pills',
                            'emoji' => 'Emoji',
                        ])
                        ->native(false),
                    Toggle::make(self::settingsPath('color_coded'))
                        ->label(__('filament-flex-fields::default.schema.settings.color_coded')),
                    TextInput::make(self::settingsPath('min_label'))
                        ->label(__('filament-flex-fields::default.schema.settings.min_label')),
                    TextInput::make(self::settingsPath('max_label'))
                        ->label(__('filament-flex-fields::default.schema.settings.max_label')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Nps))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.repeater'))
                ->columns(2)
                ->schema([
                    TextInput::make(self::settingsPath('min_items'))
                        ->label(__('filament-flex-fields::default.schema.settings.min_items'))
                        ->numeric(),
                    TextInput::make(self::settingsPath('max_items'))
                        ->label(__('filament-flex-fields::default.schema.settings.max_items'))
                        ->numeric(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Repeater))
                ->columnSpanFull(),
        ];
    }
}

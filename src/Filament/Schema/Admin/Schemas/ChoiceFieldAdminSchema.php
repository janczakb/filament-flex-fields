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

final class ChoiceFieldAdminSchema
{
    use BuildsCommonAdminFields;
    use InteractsWithFieldTypeAdminContext;

    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        return [
            Fieldset::make(__('filament-flex-fields::default.schema.settings.select'))
                ->columns(3)
                ->schema([
                    Toggle::make(self::settingsPath('multiple'))
                        ->label(__('filament-flex-fields::default.schema.settings.multiple')),
                    Toggle::make(self::settingsPath('searchable'))
                        ->label(__('filament-flex-fields::default.schema.settings.searchable')),
                    Toggle::make(self::settingsPath('clearable'))
                        ->label(__('filament-flex-fields::default.schema.settings.clearable')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Select))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.dual_listbox'))
                ->columns(3)
                ->schema([
                    Toggle::make(self::settingsPath('searchable'))
                        ->label(__('filament-flex-fields::default.schema.settings.searchable')),
                    Toggle::make(self::settingsPath('reorderable'))
                        ->label(__('filament-flex-fields::default.schema.settings.reorderable')),
                    Toggle::make(self::settingsPath('show_transfer_buttons'))
                        ->label(__('filament-flex-fields::default.schema.settings.show_transfer_buttons')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::DualListbox))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.choice_cards'))
                ->columns(3)
                ->schema([
                    Select::make(self::settingsPath('layout'))
                        ->label(__('filament-flex-fields::default.schema.settings.layout'))
                        ->options([
                            'stack' => 'Stack',
                            'grid' => 'Grid',
                        ])
                        ->native(false),
                    TextInput::make(self::settingsPath('grid_columns'))
                        ->label(__('filament-flex-fields::default.schema.settings.grid_columns'))
                        ->numeric(),
                    Select::make(self::settingsPath('indicator'))
                        ->label(__('filament-flex-fields::default.schema.settings.indicator'))
                        ->options([
                            'radio' => 'Radio',
                            'checkbox' => 'Checkbox',
                            'none' => 'None',
                        ])
                        ->native(false),
                    Toggle::make(self::settingsPath('ripple'))
                        ->label(__('filament-flex-fields::default.schema.settings.ripple')),
                    TextInput::make(self::settingsPath('min_selections'))
                        ->label(__('filament-flex-fields::default.schema.settings.min_selections'))
                        ->numeric(),
                    TextInput::make(self::settingsPath('max_selections'))
                        ->label(__('filament-flex-fields::default.schema.settings.max_selections'))
                        ->numeric(),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isOneOf($get, [
                    FieldType::ChoiceCards,
                    FieldType::ChoiceCheckboxCards,
                    FieldType::ImageChoiceCards,
                ]))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.image_choice'))
                ->columns(3)
                ->schema([
                    Toggle::make(self::settingsPath('multiple'))
                        ->label(__('filament-flex-fields::default.schema.settings.multiple')),
                    TextInput::make(self::settingsPath('columns'))
                        ->label(__('filament-flex-fields::default.schema.settings.columns'))
                        ->numeric(),
                    TextInput::make(self::settingsPath('image_aspect_ratio'))
                        ->label(__('filament-flex-fields::default.schema.settings.image_aspect_ratio')),
                    Select::make(self::settingsPath('image_fit'))
                        ->label(__('filament-flex-fields::default.schema.settings.image_fit'))
                        ->options([
                            'cover' => 'Cover',
                            'contain' => 'Contain',
                        ])
                        ->native(false),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::ImageChoiceCards))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.segment_control'))
                ->columns(3)
                ->schema([
                    Toggle::make(self::settingsPath('full_width'))
                        ->label(__('filament-flex-fields::default.schema.settings.full_width')),
                    Toggle::make(self::settingsPath('separators'))
                        ->label(__('filament-flex-fields::default.schema.settings.separators')),
                    Toggle::make(self::settingsPath('icon_only'))
                        ->label(__('filament-flex-fields::default.schema.settings.icon_only')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::SegmentControl))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.user_select'))
                ->columns(2)
                ->schema([
                    TextInput::make(self::settingsPath('option_model'))
                        ->label(__('filament-flex-fields::default.schema.settings.option_model'))
                        ->placeholder('App\\Models\\User'),
                    TextInput::make(self::settingsPath('name_column'))
                        ->label(__('filament-flex-fields::default.schema.settings.name_column')),
                    TextInput::make(self::settingsPath('email_column'))
                        ->label(__('filament-flex-fields::default.schema.settings.email_column')),
                    TextInput::make(self::settingsPath('avatar_column'))
                        ->label(__('filament-flex-fields::default.schema.settings.avatar_column')),
                    Toggle::make(self::settingsPath('multiple'))
                        ->label(__('filament-flex-fields::default.schema.settings.multiple')),
                    Toggle::make(self::settingsPath('searchable'))
                        ->label(__('filament-flex-fields::default.schema.settings.searchable')),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::UserSelect))
                ->columnSpanFull(),
        ];
    }
}

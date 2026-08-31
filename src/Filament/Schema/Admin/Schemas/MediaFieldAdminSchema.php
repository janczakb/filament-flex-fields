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

final class MediaFieldAdminSchema
{
    use BuildsCommonAdminFields;
    use InteractsWithFieldTypeAdminContext;

    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        return [
            Fieldset::make(__('filament-flex-fields::default.schema.settings.file_upload'))
                ->columns(2)
                ->schema([
                    TextInput::make(self::settingsPath('max_size'))
                        ->label(__('filament-flex-fields::default.schema.settings.max_size_kb'))
                        ->numeric(),
                ])
                ->visible(fn (Get $get): bool => self::isOneOf($get, [FieldType::File, FieldType::Image]))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.video'))
                ->columns(3)
                ->schema([
                    TextInput::make(self::settingsPath('ratio'))
                        ->label(__('filament-flex-fields::default.schema.settings.ratio')),
                    Toggle::make(self::settingsPath('controls'))
                        ->label(__('filament-flex-fields::default.schema.settings.controls')),
                    Toggle::make(self::settingsPath('autoplay'))
                        ->label(__('filament-flex-fields::default.schema.settings.autoplay')),
                    Toggle::make(self::settingsPath('loop'))
                        ->label(__('filament-flex-fields::default.schema.settings.loop')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Video))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.audio'))
                ->columns(2)
                ->schema([
                    Toggle::make(self::settingsPath('loop'))
                        ->label(__('filament-flex-fields::default.schema.settings.loop')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Audio))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.voice_note'))
                ->columns(2)
                ->schema([
                    TextInput::make(self::settingsPath('max_duration'))
                        ->label(__('filament-flex-fields::default.schema.settings.max_duration'))
                        ->numeric(),
                    TextInput::make(self::settingsPath('directory'))
                        ->label(__('filament-flex-fields::default.schema.settings.directory')),
                    Toggle::make(self::settingsPath('upload_immediately'))
                        ->label(__('filament-flex-fields::default.schema.settings.upload_immediately')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::VoiceNote))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.signature'))
                ->columns(2)
                ->schema([
                    TextInput::make(self::settingsPath('pen_color'))
                        ->label(__('filament-flex-fields::default.schema.settings.pen_color')),
                    TextInput::make(self::settingsPath('background_color'))
                        ->label(__('filament-flex-fields::default.schema.settings.background_color')),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Signature))
                ->columnSpanFull(),
        ];
    }
}

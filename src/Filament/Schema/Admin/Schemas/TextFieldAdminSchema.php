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
final class TextFieldAdminSchema
{
    use BuildsCommonAdminFields;
    use InteractsWithFieldTypeAdminContext;

    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        return [
            Fieldset::make(__('filament-flex-fields::default.schema.settings.text_input'))
                ->columns(3)
                ->schema([
                    TextInput::make(self::settingsPath('rows'))
                        ->label(__('filament-flex-fields::default.schema.settings.rows'))
                        ->numeric(),
                    Toggle::make(self::settingsPath('character_counter'))
                        ->label(__('filament-flex-fields::default.schema.settings.character_counter')),
                    Toggle::make(self::settingsPath('clearable'))
                        ->label(__('filament-flex-fields::default.schema.settings.clearable')),
                    Toggle::make(self::settingsPath('speech_dictation'))
                        ->label(__('filament-flex-fields::default.schema.settings.speech_dictation')),
                    Toggle::make(self::settingsPath('trim'))
                        ->label(__('filament-flex-fields::default.schema.settings.trim')),
                    Select::make(self::settingsPath('mask_preset'))
                        ->label(__('filament-flex-fields::default.schema.settings.mask_preset'))
                        ->options([
                            '' => 'None',
                            'phone' => 'Phone',
                            'custom' => 'Custom',
                        ])
                        ->native(false),
                    TextInput::make(self::settingsPath('mask'))
                        ->label(__('filament-flex-fields::default.schema.settings.mask')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isOneOf($get, [FieldType::FlexTextInput, FieldType::FlexTextarea]))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.slug'))
                ->columns(3)
                ->schema([
                    TextInput::make(self::settingsPath('source'))
                        ->label(__('filament-flex-fields::default.schema.settings.source_field')),
                    TextInput::make(self::settingsPath('separator'))
                        ->label(__('filament-flex-fields::default.schema.settings.separator')),
                    Toggle::make(self::settingsPath('auto_generate'))
                        ->label(__('filament-flex-fields::default.schema.settings.auto_generate')),
                    Toggle::make(self::settingsPath('permalink_preview'))
                        ->label(__('filament-flex-fields::default.schema.settings.permalink_preview')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Slug))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.verification_code'))
                ->columns(3)
                ->schema([
                    TextInput::make(self::settingsPath('length'))
                        ->label(__('filament-flex-fields::default.schema.settings.length'))
                        ->numeric(),
                    Select::make(self::settingsPath('allowed_characters'))
                        ->label(__('filament-flex-fields::default.schema.settings.allowed_characters'))
                        ->options([
                            'numeric' => 'Numeric',
                            'alpha' => 'Alpha',
                            'alphanumeric' => 'Alphanumeric',
                        ])
                        ->native(false),
                    Toggle::make(self::settingsPath('auto_submit'))
                        ->label(__('filament-flex-fields::default.schema.settings.auto_submit')),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::VerificationCode))
                ->columnSpanFull(),
        ];
    }
}

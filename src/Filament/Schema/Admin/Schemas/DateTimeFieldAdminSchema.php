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

final class DateTimeFieldAdminSchema
{
    use BuildsCommonAdminFields;
    use InteractsWithFieldTypeAdminContext;

    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        $types = [
            FieldType::Date,
            FieldType::Time,
            FieldType::DateTime,
            FieldType::DateRange,
            FieldType::Duration,
            FieldType::TimeRange,
            FieldType::Month,
            FieldType::Year,
        ];

        return [
            Fieldset::make(__('filament-flex-fields::default.schema.settings.datetime'))
                ->columns(3)
                ->schema([
                    Select::make(self::settingsPath('granularity'))
                        ->label(__('filament-flex-fields::default.schema.settings.granularity'))
                        ->options([
                            'day' => 'Day',
                            'hour' => 'Hour',
                            'minute' => 'Minute',
                            'second' => 'Second',
                        ])
                        ->native(false),
                    TextInput::make(self::settingsPath('minute_step'))
                        ->label(__('filament-flex-fields::default.schema.settings.minute_step'))
                        ->numeric(),
                    Toggle::make(self::settingsPath('show_seconds'))
                        ->label(__('filament-flex-fields::default.schema.settings.show_seconds')),
                    Toggle::make(self::settingsPath('close_on_select'))
                        ->label(__('filament-flex-fields::default.schema.settings.close_on_select')),
                    TextInput::make(self::settingsPath('display_format'))
                        ->label(__('filament-flex-fields::default.schema.settings.display_format')),
                    TextInput::make(self::settingsPath('time_zone'))
                        ->label(__('filament-flex-fields::default.schema.settings.time_zone')),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isOneOf($get, $types))
                ->columnSpanFull(),

            Fieldset::make(__('filament-flex-fields::default.schema.settings.schedule'))
                ->columns(3)
                ->schema([
                    TextInput::make(self::settingsPath('timezone'))
                        ->label(__('filament-flex-fields::default.schema.settings.time_zone')),
                    TextInput::make(self::settingsPath('time_step'))
                        ->label(__('filament-flex-fields::default.schema.settings.minute_step'))
                        ->numeric(),
                    TextInput::make(self::settingsPath('min_slots'))
                        ->label(__('filament-flex-fields::default.schema.settings.min_slots'))
                        ->numeric(),
                    TextInput::make(self::settingsPath('max_slots'))
                        ->label(__('filament-flex-fields::default.schema.settings.max_slots'))
                        ->numeric(),
                    Toggle::make(self::settingsPath('allow_copy_to_weekdays'))
                        ->label(__('filament-flex-fields::default.schema.settings.allow_copy_to_weekdays')),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::Schedule))
                ->columnSpanFull(),
        ];
    }
}

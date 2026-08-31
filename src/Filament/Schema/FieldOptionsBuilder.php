<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldOptionStorage;
use Closure;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

final class FieldOptionsBuilder
{
    public static function make(string $name = 'field_options'): Repeater
    {
        return Repeater::make($name)
            ->label(__('filament-flex-fields::default.schema.field_options'))
            ->table([
                TableColumn::make('label')
                    ->hiddenHeaderLabel(),
                TableColumn::make('value')
                    ->hiddenHeaderLabel(),
            ])
            ->schema([
                TextInput::make('label')
                    ->label(__('filament-flex-fields::default.schema.field_option_label'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                        if (filled($get('value'))) {
                            return;
                        }

                        if (! is_string($state) || trim($state) === '') {
                            return;
                        }

                        $set('value', Str::slug($state, '_'));
                    })
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                            if (blank($value)) {
                                return;
                            }

                            $siblings = $get('../../field_options');

                            if (! is_array($siblings)) {
                                return;
                            }

                            $hasDuplicate = collect($siblings)
                                ->pluck('label')
                                ->filter()
                                ->map(fn ($label): string => mb_strtolower((string) $label))
                                ->duplicates()
                                ->contains(mb_strtolower((string) $value));

                            if ($hasDuplicate) {
                                $fail(__('validation.distinct'));
                            }
                        },
                    ]),
                TextInput::make('value')
                    ->label(__('filament-flex-fields::default.schema.field_option_value'))
                    ->alphaDash()
                    ->required()
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                            if (blank($value)) {
                                return;
                            }

                            $siblings = $get('../../field_options');

                            if (! is_array($siblings)) {
                                return;
                            }

                            $hasDuplicate = collect($siblings)
                                ->pluck('value')
                                ->filter()
                                ->map(fn ($optionValue): string => mb_strtolower((string) $optionValue))
                                ->duplicates()
                                ->contains(mb_strtolower((string) $value));

                            if ($hasDuplicate) {
                                $fail(__('validation.distinct'));
                            }
                        },
                    ]),
                TextInput::make('description')
                    ->label(__('filament-flex-fields::default.schema.field_option_description'))
                    ->maxLength(500)
                    ->visible(fn (Get $get): bool => self::selectedType($get)?->supportsRichFieldOptions() ?? false),
                TextInput::make('icon')
                    ->label(__('filament-flex-fields::default.schema.field_option_icon'))
                    ->placeholder('heroicon-o-check')
                    ->visible(fn (Get $get): bool => self::selectedType($get)?->supportsRichFieldOptions() ?? false),
                TextInput::make('image')
                    ->label(__('filament-flex-fields::default.schema.field_option_image'))
                    ->url()
                    ->visible(fn (Get $get): bool => self::selectedType($get) === FieldType::ImageChoiceCards),
                TextInput::make('alt')
                    ->label(__('filament-flex-fields::default.schema.field_option_alt'))
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => self::selectedType($get) === FieldType::ImageChoiceCards),
                ColorPicker::make('color')
                    ->label(__('filament-flex-fields::default.schema.field_option_color'))
                    ->hexColor()
                    ->visible(fn (Get $get): bool => in_array(self::selectedType($get), [
                        FieldType::Select,
                        FieldType::Radio,
                        FieldType::CheckboxList,
                        FieldType::SegmentControl,
                        FieldType::ChoiceCards,
                        FieldType::ChoiceCheckboxCards,
                        FieldType::FlexChecklist,
                        FieldType::FlexRadiolist,
                    ], true)),
                Toggle::make('disabled')
                    ->label(__('filament-flex-fields::default.schema.field_option_disabled'))
                    ->inline(false),
            ])
            ->defaultItems(1)
            ->addActionLabel(__('filament-flex-fields::default.schema.field_options_add'))
            ->reorderable()
            ->collapsible()
            ->columnSpanFull()
            ->visible(fn (Get $get): bool => self::selectedType($get)?->supportsUserDefinedOptions() ?? false)
            ->required(fn (Get $get): bool => self::selectedType($get)?->requiresConfiguredOptions() ?? false);
    }

    private static function selectedType(Get $get): ?FieldType
    {
        $type = $get('type');

        if ($type instanceof FieldType) {
            return $type;
        }

        if (! is_string($type) || $type === '') {
            return null;
        }

        return FieldType::tryFrom($type);
    }
}

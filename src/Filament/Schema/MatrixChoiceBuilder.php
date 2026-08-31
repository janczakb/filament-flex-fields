<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema;

use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\FieldTypeAdminSchemaRegistry;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

final class MatrixChoiceBuilder
{
    public static function rowsRepeater(string $name = 'field_matrix_rows'): \Filament\Forms\Components\Repeater
    {
        return self::dimensionRepeater(
            name: $name,
            label: __('filament-flex-fields::default.schema.matrix_rows'),
            addLabel: __('filament-flex-fields::default.schema.matrix_rows_add'),
            withIcon: false,
        );
    }

    public static function columnsRepeater(string $name = 'field_matrix_columns'): \Filament\Forms\Components\Repeater
    {
        return self::dimensionRepeater(
            name: $name,
            label: __('filament-flex-fields::default.schema.matrix_columns'),
            addLabel: __('filament-flex-fields::default.schema.matrix_columns_add'),
            withIcon: true,
        );
    }

    private static function dimensionRepeater(
        string $name,
        string $label,
        string $addLabel,
        bool $withIcon,
    ): \Filament\Forms\Components\Repeater {
        $schema = [
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
                }),
            TextInput::make('value')
                ->label(__('filament-flex-fields::default.schema.field_option_value'))
                ->alphaDash()
                ->required(),
        ];

        if ($withIcon) {
            $schema[] = TextInput::make('icon')
                ->label(__('filament-flex-fields::default.schema.field_option_icon'))
                ->placeholder('heroicon-o-check');
        }

        return \Filament\Forms\Components\Repeater::make($name)
            ->label($label)
            ->schema($schema)
            ->defaultItems(1)
            ->addActionLabel($addLabel)
            ->reorderable()
            ->collapsible()
            ->columnSpanFull()
            ->visible(fn (Get $get): bool => ($get('type') ?? null) === 'matrix_choice');
    }
}

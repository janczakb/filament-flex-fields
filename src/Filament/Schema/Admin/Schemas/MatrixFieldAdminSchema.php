<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Schemas;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Concerns\BuildsCommonAdminFields;
use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\Concerns\InteractsWithFieldTypeAdminContext;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;

/**
 * @internal Legacy grouped admin schema — superseded by FieldTypeAutoAdminSchema. Not referenced at runtime.
 */
final class MatrixFieldAdminSchema
{
    use BuildsCommonAdminFields;
    use InteractsWithFieldTypeAdminContext;

    /**
     * @return list<Component>
     */
    public static function components(): array
    {
        return [
            Fieldset::make(__('filament-flex-fields::default.schema.settings.matrix_choice'))
                ->columns(3)
                ->schema([
                    Select::make(self::settingsPath('mode'))
                        ->label(__('filament-flex-fields::default.schema.settings.mode'))
                        ->options([
                            'radio' => 'Single choice',
                            'checkbox' => 'Multiple choice',
                        ])
                        ->native(false),
                    Select::make(self::settingsPath('color'))
                        ->label(__('filament-flex-fields::default.schema.settings.color'))
                        ->options([
                            'primary' => 'Primary',
                            'success' => 'Success',
                            'warning' => 'Warning',
                            'danger' => 'Danger',
                        ])
                        ->native(false),
                    ...self::sizeVariantFields(),
                ])
                ->visible(fn (Get $get): bool => self::isType($get, FieldType::MatrixChoice))
                ->columnSpanFull(),
        ];
    }
}

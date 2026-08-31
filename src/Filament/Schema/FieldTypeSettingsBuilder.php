<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema;

use Bjanczak\FilamentFlexFields\Filament\Schema\Admin\FieldTypeAdminSchemaRegistry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

final class FieldTypeSettingsBuilder
{
    public static function section(): Section
    {
        return Section::make(__('filament-flex-fields::default.schema.field_type_settings'))
            ->description(__('filament-flex-fields::default.schema.field_type_settings_help'))
            ->schema([
                Group::make(FieldTypeAdminSchemaRegistry::components())
                    ->columnSpanFull(),
            ])
            ->visible(fn (Get $get): bool => filled($get('type')))
            ->collapsible()
            ->collapsed(false)
            ->columnSpanFull();
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema;

use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldEntityRegistry;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

final class FlexFieldTargetModelSelect
{
    public static function make(string $name = 'target_type'): Select
    {
        return Select::make($name)
            ->label(__('filament-flex-fields::default.schema.target_model'))
            ->options(fn (?FlexFieldGroup $record): array => app(FlexFieldEntityRegistry::class)->selectOptions($record?->target_type))
            ->default(fn (): string => app(FlexFieldEntityRegistry::class)->defaultModelClass())
            ->required()
            ->searchable()
            ->native(false)
            ->disabled(fn (string $operation): bool => $operation === 'edit')
            ->dehydrated()
            ->live()
            ->helperText(fn (Get $get): ?string => filled($get($name))
                ? (string) $get($name)
                : __('filament-flex-fields::default.schema.target_model_help'))
            ->placeholder(__('filament-flex-fields::default.schema.target_model_placeholder'))
            ->visible(fn (): bool => app(FlexFieldEntityRegistry::class)->selectOptions() !== []);
    }

    public static function missingEntitiesPlaceholder(): Placeholder
    {
        return Placeholder::make('target_model_missing')
            ->label(__('filament-flex-fields::default.schema.target_model'))
            ->content(__('filament-flex-fields::default.schema.target_model_missing'))
            ->visible(fn (): bool => app(FlexFieldEntityRegistry::class)->selectOptions() === []);
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class TimezoneFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'timezone__basic' => 'Europe/Warsaw',
            'timezone__empty' => null,
            'timezone__limited' => 'America/New_York',
            'timezone__browser' => null,
            'timezone__sm' => 'Europe/Berlin',
            'timezone__soft' => 'Europe/Warsaw',
            'timezone__lg' => 'Asia/Tokyo',
            'timezone__disabled' => 'Europe/London',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Timezone field')
                ->description('Searchable IANA timezone picker with Gravity UI clock icon prefix.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    TimezoneField::make('timezone__basic')
                        ->label('Timezone')
                        ->defaultTimezone('Europe/Warsaw')
                        ->helperText('Stores IANA timezone identifier (e.g. Europe/Warsaw).')
                        ->required()
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            TimezoneField::make('timezone__empty')
                                ->label('Empty')
                                ->helperText('No default — shows placeholder until selected.'),
                            TimezoneField::make('timezone__limited')
                                ->label('Limited timezones')
                                ->timezones(['Europe/Warsaw', 'Europe/Berlin', 'America/New_York', 'UTC'])
                                ->defaultTimezone('UTC'),
                            TimezoneField::make('timezone__browser')
                                ->label('Browser timezone')
                                ->browserTimezoneDefault()
                                ->browserTimezoneSortFirst()
                                ->helperText('Defaults from Intl browser timezone when empty.'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            TimezoneField::make('timezone__sm')
                                ->label('Small')
                                ->size('sm')
                                ->defaultTimezone('Europe/Berlin'),
                            TimezoneField::make('timezone__soft')
                                ->label('Soft')
                                ->variant('soft')
                                ->defaultTimezone('Europe/Warsaw'),
                            TimezoneField::make('timezone__lg')
                                ->label('Large')
                                ->size('lg')
                                ->defaultTimezone('Asia/Tokyo'),
                            TimezoneField::make('timezone__disabled')
                                ->label('Disabled')
                                ->defaultTimezone('Europe/London')
                                ->disabled(),
                        ]),
                ]),
        ];
    }
}

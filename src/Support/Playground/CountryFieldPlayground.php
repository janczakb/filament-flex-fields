<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CountryFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'country__basic' => 'PL',
            'country__empty' => null,
            'country__limited' => 'DE',
            'country__codes' => 'US',
            'country__browser_locale' => null,
            'country__sm' => 'FR',
            'country__soft' => 'PL',
            'country__lg' => 'GB',
            'country__disabled' => 'PL',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Country field')
                ->description('Searchable country picker with circle flags — reuses the same country list as PhoneField.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    CountryField::make('country__basic')
                        ->label('Country')
                        ->defaultCountry('PL')
                        ->helperText('Stores ISO 3166-1 alpha-2 code (e.g. PL, US, DE).')
                        ->required()
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            CountryField::make('country__empty')
                                ->label('Empty')
                                ->helperText('No default — shows placeholder until selected.'),
                            CountryField::make('country__limited')
                                ->label('Limited countries')
                                ->countries(['PL', 'US', 'DE', 'GB', 'FR'])
                                ->defaultCountry('PL'),
                            CountryField::make('country__codes')
                                ->label('With ISO + dial code')
                                ->defaultCountry('US')
                                ->showCountryCode()
                                ->showDialCode(),
                            CountryField::make('country__browser_locale')
                                ->label('Browser locale')
                                ->browserLocaleDefault()
                                ->browserLocaleSortFirst()
                                ->helperText('Defaults and sorts by Accept-Language header.'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            CountryField::make('country__sm')
                                ->label('Small')
                                ->size('sm')
                                ->defaultCountry('FR'),
                            CountryField::make('country__soft')
                                ->label('Soft')
                                ->variant('soft')
                                ->defaultCountry('PL'),
                            CountryField::make('country__lg')
                                ->label('Large')
                                ->size('lg')
                                ->defaultCountry('GB'),
                            CountryField::make('country__disabled')
                                ->label('Disabled')
                                ->defaultCountry('PL')
                                ->disabled(),
                        ]),
                ]),
        ];
    }
}

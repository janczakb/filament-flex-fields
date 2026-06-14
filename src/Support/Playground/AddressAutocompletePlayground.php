<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class AddressAutocompletePlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'address_autocomplete__full' => [
                'street' => 'plac Defilad',
                'city' => 'Warszawa',
                'postcode' => '00-901',
                'country' => 'PL',
                'country_name' => 'Polska',
                'place_name' => 'plac Defilad, 00-901 Warszawa, Polska',
            ],
            'address_autocomplete__string' => 'Kraków, Polska',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Address autocomplete')
                ->description('Mapbox-powered address search without a map. Set MAPBOX_ACCESS_TOKEN in .env.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    AddressAutocompleteField::make('address_autocomplete__full')
                        ->label('Delivery address')
                        ->helperText('Primary variant (md). Structured output: street, city, postcode, country — no coordinates.')
                        ->fields(['street', 'city', 'postcode', 'country', 'country_name', 'place_name'])
                        ->storeFormat(AddressAutocompleteField::STORE_STRUCTURED)
                        ->requiredFields(['city'])
                        ->streetAddressesOnly()
                        ->countries(['PL'])
                        ->variant('primary')
                        ->size('md')
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->schema([
                            AddressAutocompleteField::make('address_autocomplete__string')
                                ->label('City only (string)')
                                ->helperText('Secondary variant, size sm. storeFormat(string) with fields [city, country_name, place_name].')
                                ->fields(['city', 'country_name', 'place_name'])
                                ->storeFormat(AddressAutocompleteField::STORE_STRING)
                                ->stringFormat('{city}, {country_name}')
                                ->variant('secondary')
                                ->size('sm'),
                        ]),
                ]),
        ];
    }
}

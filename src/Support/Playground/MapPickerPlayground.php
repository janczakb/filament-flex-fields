<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class MapPickerPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'map_picker__full' => [
                'lat' => 52.2297,
                'lng' => 21.0122,
                'street' => 'plac Defilad',
                'city' => 'Warszawa',
                'postcode' => '00-901',
                'country' => 'PL',
                'country_name' => 'Polska',
                'place_name' => 'plac Defilad, 00-901 Warszawa, Polska',
            ],
            'map_picker__city_only' => 'Kraków, Polska',
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Map picker')
                ->description('Mapbox map with search, draggable pin, configurable stored fields, and summary below the map. Set MAPBOX_ACCESS_TOKEN in .env.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    MapPickerField::make('map_picker__full')
                        ->label('Pickup location')
                        ->helperText('Structured output: street, city, postcode, country, coordinates.')
                        ->fields(['lat', 'lng', 'street', 'city', 'postcode', 'country', 'country_name', 'place_name'])
                        ->storeFormat(MapPickerField::STORE_STRUCTURED)
                        ->requiredFields(['city', 'lat', 'lng'])
                        ->streetAddressesOnly()
                        ->defaultCenter([52.2297, 21.0122])
                        ->defaultZoom(12)
                        ->countries(['PL'])
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->schema([
                            MapPickerField::make('map_picker__city_only')
                                ->label('City only (string)')
                                ->helperText('storeFormat(string) with fields [city, country, place_name].')
                                ->fields(['city', 'country', 'place_name'])
                                ->storeFormat(MapPickerField::STORE_STRING)
                                ->stringFormat('{city}, {country_name}')
                                ->requiredFields(['city'])
                                ->defaultCenter([50.0647, 19.9450])
                                ->defaultZoom(11),
                        ]),
                ]),
        ];
    }
}

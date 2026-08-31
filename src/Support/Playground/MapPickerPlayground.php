<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Enums\MapboxSearchType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;

class MapPickerPlayground
{
    private const string SPLIT_PREFIX = 'map_picker__split';

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        $split = [
            'lat' => 52.2297,
            'lng' => 21.0122,
            'street' => 'plac Defilad',
            'city' => 'Warszawa',
            'region' => 'mazowieckie',
            'postcode' => '00-901',
            'country' => 'PL',
            'country_name' => 'Polska',
            'place_name' => 'plac Defilad, 00-901 Warszawa, Polska',
        ];

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
            'map_picker__split' => $split,
            'map_picker__split_street' => $split['street'],
            'map_picker__split_city' => $split['city'],
            'map_picker__split_region' => $split['region'],
            'map_picker__split_postcode' => $split['postcode'],
            'map_picker__split_country' => $split['country'],
            'map_picker__split_country_name' => $split['country_name'],
            'map_picker__split_lat' => (string) $split['lat'],
            'map_picker__split_lng' => (string) $split['lng'],
            'map_picker__city_only' => 'Kraków, Polska',
            'map_picker__venue' => 'Marszałkowska 1, Warszawa (PL)',
            'map_picker__poi' => [
                'lat' => 50.0547,
                'lng' => 19.9354,
                'place_name' => 'Wawel Royal Castle, Kraków, Poland',
            ],
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Structured pickup')
                ->description('Full street address with coordinates. Search, click the map, or drag the pin — state is one structured array.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    MapPickerField::make('map_picker__full')
                        ->label('Pickup location')
                        ->helperText('fields([lat, lng, street, city, postcode, country, country_name, place_name]) · streetAddressesOnly() · countries([PL]).')
                        ->fields(['lat', 'lng', 'street', 'city', 'postcode', 'country', 'country_name', 'place_name'])
                        ->storeFormat(MapPickerField::STORE_STRUCTURED)
                        ->requiredFields(['city', 'lat', 'lng'])
                        ->streetAddressesOnly()
                        ->defaultCenter([52.2297, 21.0122])
                        ->defaultZoom(12)
                        ->countries(['PL'])
                        ->columnSpanFull(),
                ]),

            Section::make('Split address form')
                ->description('Map picker drives separate inputs (street, city, postcode, country, coordinates). Typical for checkout, CRM, or legacy schemas that expect flat columns.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    MapPickerField::make('map_picker__split')
                        ->label('Find on map')
                        ->helperText('Select on the map or via search — mirrored fields below update instantly via ->live()->afterStateUpdated().')
                        ->fields(['lat', 'lng', 'street', 'city', 'region', 'postcode', 'country', 'country_name', 'place_name'])
                        ->storeFormat(MapPickerField::STORE_STRUCTURED)
                        ->streetAddressesOnly()
                        ->defaultCenter([52.2297, 21.0122])
                        ->defaultZoom(13)
                        ->countries(['PL'])
                        ->live()
                        ->afterStateUpdated($this->syncSplitAddressFields(...))
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])
                        ->schema($this->splitAddressMirrorFields()),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;

MapPickerField::make('location')
    ->fields(['lat', 'lng', 'street', 'city', 'postcode', 'country', 'country_name'])
    ->streetAddressesOnly()
    ->live()
    ->afterStateUpdated(function (?array $state, Set $set): void {
        $set('street', data_get($state, 'street', ''));
        $set('city', data_get($state, 'city', ''));
        $set('postcode', data_get($state, 'postcode', ''));
        $set('country', data_get($state, 'country', ''));
        $set('country_name', data_get($state, 'country_name', ''));
        $set('latitude', (string) data_get($state, 'lat', ''));
        $set('longitude', (string) data_get($state, 'lng', ''));
    });

Grid::make(3)->schema([
    FlexTextInput::make('street')->disabled()->dehydrated(false),
    FlexTextInput::make('city')->disabled()->dehydrated(false),
    FlexTextInput::make('postcode')->disabled()->dehydrated(false),
    FlexTextInput::make('country')->disabled()->dehydrated(false),
    FlexTextInput::make('country_name')->label('Country name')->disabled()->dehydrated(false),
    FlexTextInput::make('latitude')->disabled()->dehydrated(false),
    FlexTextInput::make('longitude')->disabled()->dehydrated(false),
]);
PHP, filename: 'map-picker-split-address.php'),
                ]),

            Section::make('String output formats')
                ->description('Dehydrate as a single formatted string instead of a structured array.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->schema([
                            MapPickerField::make('map_picker__city_only')
                                ->label('City only')
                                ->helperText('searchTypes([place]) · stringFormat("{city}, {country_name}").')
                                ->fields(['city', 'country', 'country_name', 'place_name'])
                                ->searchTypes([MapboxSearchType::Place])
                                ->storeFormat(MapPickerField::STORE_STRING)
                                ->stringFormat('{city}, {country_name}')
                                ->requiredFields(['city'])
                                ->defaultCenter([50.0647, 19.9450])
                                ->defaultZoom(11),
                            MapPickerField::make('map_picker__venue')
                                ->label('Venue line')
                                ->helperText('Full address as one string: "{street}, {city} ({country})".')
                                ->fields(['street', 'city', 'country', 'place_name', 'lat', 'lng'])
                                ->storeFormat(MapPickerField::STORE_STRING)
                                ->stringFormat('{street}, {city} ({country})')
                                ->streetAddressesOnly()
                                ->defaultCenter([52.2297, 21.0122])
                                ->defaultZoom(14)
                                ->countries(['PL']),
                        ]),
                ]),

            Section::make('Search scopes')
                ->description('Limit geocoding results to POIs or broader place types.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    MapPickerField::make('map_picker__poi')
                        ->label('Pickup point (POI)')
                        ->helperText('searchTypes([poi]) · stores place_name + coordinates only.')
                        ->searchTypes([MapboxSearchType::Poi])
                        ->fields(['place_name', 'lat', 'lng'])
                        ->requiredFields(['lat', 'lng', 'place_name'])
                        ->defaultCenter([50.0647, 19.9450])
                        ->defaultZoom(14)
                        ->countries(['PL'])
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $state
     */
    public function syncSplitAddressFields(?array $state, Set $set): void
    {
        $fields = [
            'street' => '',
            'city' => '',
            'region' => '',
            'postcode' => '',
            'country' => '',
            'country_name' => '',
            'lat' => '',
            'lng' => '',
        ];

        foreach ($fields as $key => $fallback) {
            $value = data_get($state, $key, $fallback);

            if (is_float($value) || is_int($value)) {
                $value = (string) $value;
            }

            $set(self::SPLIT_PREFIX.'_'.$key, is_string($value) ? $value : (string) ($value ?? ''));
        }
    }

    /**
     * @return list<Component>
     */
    private function splitAddressMirrorFields(): array
    {
        $prefix = self::SPLIT_PREFIX;

        return [
            FlexTextInput::make("{$prefix}_street")
                ->label('Street')
                ->helperText('Filled from geocoding · street')
                ->disabled()
                ->dehydrated(false)
                ->columnSpan(['default' => 1, 'md' => 2, 'xl' => 3]),
            FlexTextInput::make("{$prefix}_city")
                ->label('City')
                ->disabled()
                ->dehydrated(false),
            FlexTextInput::make("{$prefix}_postcode")
                ->label('Postcode')
                ->disabled()
                ->dehydrated(false),
            FlexTextInput::make("{$prefix}_region")
                ->label('Region')
                ->disabled()
                ->dehydrated(false),
            FlexTextInput::make("{$prefix}_country")
                ->label('Country code')
                ->disabled()
                ->dehydrated(false),
            FlexTextInput::make("{$prefix}_country_name")
                ->label('Country')
                ->disabled()
                ->dehydrated(false),
            FlexTextInput::make("{$prefix}_lat")
                ->label('Latitude')
                ->disabled()
                ->dehydrated(false),
            FlexTextInput::make("{$prefix}_lng")
                ->label('Longitude')
                ->disabled()
                ->dehydrated(false),
        ];
    }
}

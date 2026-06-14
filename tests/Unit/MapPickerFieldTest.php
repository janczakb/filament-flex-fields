<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;

it('dehydrates structured state using configured fields only', function () {
    $field = MapPickerField::make('location')
        ->fields(['lat', 'lng', 'city', 'place_name']);

    $output = $field->dehydrateFromCanonical([
        'lat' => 52.2297,
        'lng' => 21.0122,
        'street' => 'Hidden Street',
        'city' => 'Warszawa',
        'place_name' => 'Warszawa, Polska',
    ]);

    expect($output)->toBe([
        'lat' => 52.2297,
        'lng' => 21.0122,
        'city' => 'Warszawa',
        'place_name' => 'Warszawa, Polska',
    ])
        ->and($output)->not->toHaveKey('street');
});

it('dehydrates string state using string format template', function () {
    $field = MapPickerField::make('location')
        ->fields(['city', 'country_name', 'place_name'])
        ->storeFormat(MapPickerField::STORE_STRING)
        ->stringFormat('{city}, {country_name}');

    $output = $field->dehydrateFromCanonical([
        'city' => 'Kraków',
        'country_name' => 'Polska',
        'place_name' => 'Kraków, Polska',
    ]);

    expect($output)->toBe('Kraków, Polska');
});

it('hydrates string state into canonical place name', function () {
    $field = MapPickerField::make('location');

    expect($field->hydrateToCanonical('Warszawa, Polska'))
        ->toMatchArray([
            'place_name' => 'Warszawa, Polska',
            'lat' => null,
            'lng' => null,
        ]);
});

it('validates required configured fields', function () {
    $field = MapPickerField::make('location')
        ->fields(['city', 'lat', 'lng'])
        ->requiredFields(['city']);

    expect($field->getValidationMessage([
        'lat' => 52.2,
        'lng' => 21.0,
    ]))->not->toBeNull();

    expect($field->getValidationMessage([
        'city' => 'Warszawa',
        'lat' => 52.2,
        'lng' => 21.0,
    ]))->toBeNull();
});

it('validates street addresses only mode requires a street name', function () {
    $field = MapPickerField::make('location')
        ->fields(['lat', 'lng', 'street', 'city'])
        ->streetAddressesOnly();

    expect($field->getValidationMessage([
        'lat' => 52.2,
        'lng' => 21.0,
        'city' => 'Warszawa',
    ]))->not->toBeNull();

    expect($field->getValidationMessage([
        'lat' => 52.2,
        'lng' => 21.0,
        'street' => 'Marszałkowska 1',
        'city' => 'Warszawa',
    ]))->toBeNull();
});

it('registers map picker playground defaults', function () {
    $state = app(FlexFieldsPlaygroundBuilder::class)->defaultState();

    expect($state)->toHaveKeys([
        'map_picker__full',
        'map_picker__city_only',
    ]);
});

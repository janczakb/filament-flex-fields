<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\MapboxSearchType;
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

it('limits search types to poi when configured', function () {
    $field = MapPickerField::make('location')
        ->searchTypes([MapboxSearchType::Poi]);

    expect($field->getSearchTypes())->toBe(['poi']);
});

it('limits search types to address when street addresses only is enabled', function () {
    $field = MapPickerField::make('location')
        ->searchTypes([MapboxSearchType::Poi])
        ->streetAddressesOnly();

    expect($field->getSearchTypes())->toBe(['address']);
});

it('rejects unsupported mapbox search types', function () {
    expect(fn () => MapPickerField::make('location')
        ->searchTypes(['shop'])
        ->getSearchTypes())
        ->toThrow(InvalidArgumentException::class);
});

it('registers map picker playground defaults', function () {
    $state = app(FlexFieldsPlaygroundBuilder::class)->defaultState();

    expect($state)->toHaveKeys([
        'map_picker__full',
        'map_picker__city_only',
    ]);
});

it('exposes server geocoding urls when proxy is enabled', function () {
    config()->set('filament-flex-fields.mapbox.use_server_proxy', true);

    $field = MapPickerField::make('location');

    expect($field->usesServerGeocoding())->toBeTrue()
        ->and($field->getGeocodeSearchUrl())->toContain('geocode/search')
        ->and($field->getGeocodeReverseUrl())->toContain('geocode/reverse');
});

it('hides server geocoding urls when proxy is disabled', function () {
    config()->set('filament-flex-fields.mapbox.use_server_proxy', false);

    $field = MapPickerField::make('location');

    expect($field->usesServerGeocoding())->toBeFalse()
        ->and($field->getGeocodeSearchUrl())->toBeNull()
        ->and($field->getGeocodeReverseUrl())->toBeNull();
});

it('supports language min search length and debounce configuration', function () {
    $field = MapPickerField::make('location')
        ->language('de')
        ->minSearchLength(3)
        ->searchDebounce(500);

    expect($field->getLanguage())->toBe('de')
        ->and($field->getMinSearchLength())->toBe(3)
        ->and($field->getSearchDebounce())->toBe(500);
});

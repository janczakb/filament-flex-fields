<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;

it('dehydrates structured state using configured fields only', function () {
    $field = AddressAutocompleteField::make('address')
        ->fields(['street', 'city', 'postcode', 'place_name']);

    $output = $field->dehydrateFromCanonical([
        'street' => 'Marszałkowska 1',
        'city' => 'Warszawa',
        'region' => 'mazowieckie',
        'postcode' => '00-001',
        'country' => 'PL',
        'country_name' => 'Polska',
        'place_name' => 'Marszałkowska 1, 00-001 Warszawa, Polska',
    ]);

    expect($output)->toBe([
        'street' => 'Marszałkowska 1',
        'city' => 'Warszawa',
        'postcode' => '00-001',
        'place_name' => 'Marszałkowska 1, 00-001 Warszawa, Polska',
    ])
        ->and($output)->not->toHaveKey('country')
        ->and($output)->not->toHaveKey('region');
});

it('dehydrates string state using string format template', function () {
    $field = AddressAutocompleteField::make('address')
        ->fields(['city', 'country_name', 'place_name'])
        ->storeFormat(AddressAutocompleteField::STORE_STRING)
        ->stringFormat('{city}, {country_name}');

    $output = $field->dehydrateFromCanonical([
        'city' => 'Kraków',
        'country_name' => 'Polska',
        'place_name' => 'Kraków, Polska',
    ]);

    expect($output)->toBe('Kraków, Polska');
});

it('hydrates string state into canonical place name', function () {
    $field = AddressAutocompleteField::make('address');

    expect($field->hydrateToCanonical('Warszawa, Polska'))
        ->toMatchArray([
            'place_name' => 'Warszawa, Polska',
            'street' => null,
            'city' => null,
        ]);
});

it('does not include coordinates in canonical state', function () {
    $field = AddressAutocompleteField::make('address');

    expect($field->getEmptyCanonicalState())
        ->not->toHaveKey('lat')
        ->not->toHaveKey('lng');
});

it('validates required configured fields', function () {
    $field = AddressAutocompleteField::make('address')
        ->fields(['city', 'street'])
        ->requiredFields(['city']);

    expect($field->getValidationMessage([
        'street' => 'Main Street',
    ]))->not->toBeNull();

    expect($field->getValidationMessage([
        'city' => 'Warszawa',
        'street' => 'Main Street',
    ]))->toBeNull();
});

it('validates street addresses only mode requires a street name', function () {
    $field = AddressAutocompleteField::make('address')
        ->fields(['street', 'city', 'place_name'])
        ->streetAddressesOnly();

    expect($field->getValidationMessage([
        'city' => 'Warszawa',
        'place_name' => 'Warszawa, Polska',
    ]))->not->toBeNull();

    expect($field->getValidationMessage([
        'street' => 'Marszałkowska 1',
        'city' => 'Warszawa',
        'place_name' => 'Marszałkowska 1, Warszawa, Polska',
    ]))->toBeNull();
});

it('registers address autocomplete playground defaults', function () {
    $state = app(FlexFieldsPlaygroundBuilder::class)->defaultState();

    expect($state)->toHaveKeys([
        'address_autocomplete__full',
        'address_autocomplete__string',
    ]);
});

it('defaults min search length to two characters and search debounce to 350ms', function () {
    $field = AddressAutocompleteField::make('address');

    expect($field->getMinSearchLength())->toBe(2)
        ->and($field->getSearchDebounce())->toBe(350);
});

it('allows overriding min search length and search debounce', function () {
    $field = AddressAutocompleteField::make('address')
        ->minSearchLength(3)
        ->searchDebounce(500);

    expect($field->getMinSearchLength())->toBe(3)
        ->and($field->getSearchDebounce())->toBe(500);
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\Playground\FormLayoutPlayground;

it('registers modern form layout playground demos with default state', function () {
    $playground = app(FormLayoutPlayground::class);
    $components = $playground->components();
    $state = $playground->defaultState();

    expect($components)->not->toBeEmpty()
        ->and($state)->toHaveKeys([
            'form_layout__name',
            'form_layout__budget',
            'form_layout__broker',
        ])
        ->and($state['form_layout__name'])->toBe('Azimut 55 Fly');
});

it('includes form layout playground in the builder', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state)->toHaveKey('form_layout__name')
        ->and($state)->toHaveKey('form_layout__public_listing');
});

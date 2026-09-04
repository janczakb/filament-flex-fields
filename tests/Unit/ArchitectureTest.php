<?php

declare(strict_types=1);

test('strict types')
    ->expect('Bjanczak\FilamentFlexFields')
    ->toUseStrictTypes();

test('globals')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

test('concerns are traits')
    ->expect('Bjanczak\FilamentFlexFields\Concerns')
    ->toBeTraits();

test('playground enums are backed enums')
    ->expect([
        'Bjanczak\FilamentFlexFields\Enums\PlaygroundCategory',
        'Bjanczak\FilamentFlexFields\Enums\FieldCategory',
    ])
    ->toBeEnums();

test('playground registry definitions include categories')
    ->expect('Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry')
    ->toHaveMethod('categoryForSlug');

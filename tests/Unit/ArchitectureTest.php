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

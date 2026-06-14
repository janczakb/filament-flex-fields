<?php

declare(strict_types=1);

use Bjanczak\BladeGravityIcons\BladeGravityIconsServiceProvider;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

it('builds gravity ui icon names for filament', function () {
    expect(GravityIcon::make('arrow-chevron-down'))->toBe('gravityui-arrow-chevron-down')
        ->and(GravityIcon::make('Magnifier'))->toBe('gravityui-magnifier')
        ->and(GravityIcon::make('trash_bin'))->toBe('gravityui-trash-bin');
});

it('requires blade gravity icons package', function () {
    expect(class_exists(BladeGravityIconsServiceProvider::class))->toBeTrue();
});

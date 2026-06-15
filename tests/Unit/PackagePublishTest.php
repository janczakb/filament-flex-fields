<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\FilamentFlexFieldsServiceProvider;
use Illuminate\Support\Facades\File;

it('registers translation publish tag', function () {
    $provider = new FilamentFlexFieldsServiceProvider(app());

    $provider->boot();

    $paths = FilamentFlexFieldsServiceProvider::pathsToPublish(
        FilamentFlexFieldsServiceProvider::class,
        'filament-flex-fields-translations',
    );

    expect($paths)->toHaveCount(1);

    $source = array_key_first($paths);
    $destination = $paths[$source];

    expect($source)->toEndWith('resources/lang')
        ->and(File::isDirectory($source))->toBeTrue()
        ->and($destination)->toBe(lang_path('vendor/filament-flex-fields'));
});

it('registers config publish tag', function () {
    $provider = new FilamentFlexFieldsServiceProvider(app());

    $provider->boot();

    $paths = FilamentFlexFieldsServiceProvider::pathsToPublish(
        FilamentFlexFieldsServiceProvider::class,
        'filament-flex-fields-config',
    );

    expect($paths)->toHaveCount(1);
});

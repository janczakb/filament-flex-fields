<?php

use Bjanczak\FilamentFlexFields\Http\Controllers\MapboxGeocodingProxyController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('filament-flex-fields.mapbox.proxy_middleware', ['web', 'auth']))
    ->prefix(config('filament-flex-fields.mapbox.proxy_prefix', 'flex-fields'))
    ->group(function (): void {
        Route::post('/geocode/search', [MapboxGeocodingProxyController::class, 'search'])
            ->name('filament-flex-fields.geocode.search');

        Route::post('/geocode/reverse', [MapboxGeocodingProxyController::class, 'reverse'])
            ->name('filament-flex-fields.geocode.reverse');
    });

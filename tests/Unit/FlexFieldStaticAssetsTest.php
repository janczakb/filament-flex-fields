<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BarcodeScannerField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Illuminate\Filesystem\Filesystem;

it('publishes bundled static media into public on package boot', function () {
    expect(is_file(public_path('filament-flex-fields-assets/barcode-scanner-field/barcode-scan-success.mp3')))->toBeTrue()
        ->and(is_file(public_path('filament-flex-fields-assets/nps-field/emojis/0.webp')))->toBeTrue();
});

it('builds versioned urls for published static assets', function () {
    $url = FlexFieldAssets::assetUrl('nps-field/emojis/0.webp');

    expect($url)
        ->toContain('filament-flex-fields-assets/nps-field/emojis/0.webp')
        ->toMatch('/\?v=\d+/');
});

it('resolves barcode scanner beep url through the static asset helper', function () {
    $url = BarcodeScannerField::make('code')->getBeepUrl();

    expect($url)
        ->toContain('filament-flex-fields-assets/barcode-scanner-field/barcode-scan-success.mp3')
        ->toMatch('/\?v=\d+/');
});

it('republishes static assets when a published file is missing', function () {
    $destination = public_path('filament-flex-fields-assets/nps-field/emojis/4.webp');

    if (is_file($destination)) {
        unlink($destination);
    }

    FlexFieldAssets::publishStaticAssetsIfStale(app(Filesystem::class));

    expect(is_file($destination))->toBeTrue();
});

it('skips republishing static assets when destination is newer', function () {
    $destination = public_path('filament-flex-fields-assets/barcode-scanner-field/barcode-scan-success.mp3');

    expect(is_file($destination))->toBeTrue();

    touch($destination, time() + 3600);

    $mtime = filemtime($destination);

    FlexFieldAssets::publishStaticAssetsIfStale(app(Filesystem::class));

    expect(filemtime($destination))->toBe($mtime);
});

it('does not publish stale package assets on production web requests', function () {
    app()->detectEnvironment(fn (): string => 'production');

    expect(FlexFieldAssets::shouldPublishStalePackageAssets(runningInConsole: false))->toBeFalse();
});

it('still publishes stale package assets on production console commands', function () {
    app()->detectEnvironment(fn (): string => 'production');

    expect(FlexFieldAssets::shouldPublishStalePackageAssets(runningInConsole: true))->toBeTrue();
});

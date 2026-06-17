<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

it('registers only user display and user column bundles for the column component', function (): void {
    expect(FlexFieldAssets::stylesheetsFor('user-column'))
        ->toBe(['user-display', 'user-column']);
});

it('renders queued table column stylesheets from the styles after hook', function (): void {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/queued-stylesheets.blade.php');

    expect($blade)
        ->toContain('FlexFieldStylesheetQueue::pending()')
        ->toContain('emit-assets');

    $emitBlade = file_get_contents(__DIR__.'/../../resources/views/partials/emit-assets.blade.php');

    expect($emitBlade)->toContain('data-navigate-track');
});

it('registers unified asset injector for flex fields lazy assets', function (): void {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/flex-field-asset-injector.blade.php');

    expect($blade)
        ->toContain('flex-field-asset-injector')
        ->toContain('FilamentAsset::getScriptSrc')
        ->toContain('data-navigate-track');
});

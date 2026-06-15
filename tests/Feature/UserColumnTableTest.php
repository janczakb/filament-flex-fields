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
        ->toContain('FlexFieldStylesheetQueue::registered()')
        ->toContain('data-fff-queued-stylesheet')
        ->toContain('data-navigate-track');
});

it('registers navigate dedupe script for flex fields lazy assets', function (): void {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/lazy-assets-navigate-dedupe.blade.php');

    expect($blade)
        ->toContain('livewire:navigated')
        ->toContain('filament-flex-fields');
});

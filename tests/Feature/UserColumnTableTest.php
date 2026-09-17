<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;

it('registers only user display and user column bundles for the column component', function (): void {
    expect(FlexFieldAssets::stylesheetsFor('user-column'))
        ->toBe(['user-display', 'user-column']);
});

it('renders queued table column stylesheets from the styles after hook', function (): void {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/queued-stylesheets.blade.php');

    expect($blade)
        ->toContain('FlexFieldStylesheetQueue::pending()')
        ->toContain('FlexFieldStylesheetQueue::pendingConsumers()')
        ->toContain('emit-assets')
        ->toContain("table-columns.'.\$rootConsumer['component']");

    $emitBlade = file_get_contents(__DIR__.'/../../resources/views/partials/emit-assets.blade.php');

    expect($emitBlade)
        ->toContain('data-fff-asset-batch')
        ->toContain('data-fff-stylesheets');
});

it('emits one root CRG consumer owning user-display and user-column together', function (): void {
    FlexFieldStylesheetQueue::reset();
    FlexFieldStylesheetQueue::enqueueFor('user-column');

    expect(FlexFieldStylesheetQueue::pendingConsumers())->toBe([
        [
            'component' => 'user-column',
            'stylesheets' => ['user-display', 'user-column'],
            'chunks' => [],
        ],
    ]);

    $html = view('filament-flex-fields::partials.queued-stylesheets')->render();

    expect($html)
        ->toContain('data-fff-asset-consumer="user-column"')
        ->toContain('data-fff-asset-consumer-id="table-columns.user-column"')
        ->toContain('flex-fields-user-display.css')
        ->toContain('flex-fields-user-column.css')
        ->not->toContain('data-fff-asset-consumer="user-display"')
        ->not->toContain('data-fff-asset-consumer-id="table-columns"');

    expect(substr_count($html, 'data-fff-asset-batch'))->toBe(1);
});

it('emits distinct root consumers when multiple table columns share a page', function (): void {
    FlexFieldStylesheetQueue::reset();
    FlexFieldStylesheetQueue::enqueueFor('rating-column');
    FlexFieldStylesheetQueue::enqueueFor('icon-column');
    FlexFieldStylesheetQueue::enqueueFor('user-column');

    $html = view('filament-flex-fields::partials.queued-stylesheets')->render();

    expect($html)
        ->toContain('data-fff-asset-consumer-id="table-columns.rating-column"')
        ->toContain('data-fff-asset-consumer-id="table-columns.icon-column"')
        ->toContain('data-fff-asset-consumer-id="table-columns.user-column"')
        ->toContain('flex-fields-user-display.css');

    expect(substr_count($html, 'data-fff-asset-batch'))->toBe(3);
});

it('emits Livewire-scoped DOM consumers from column cells and skips BODY_END duplicates', function (): void {
    FlexFieldStylesheetQueue::reset();

    $markup = FlexFieldStylesheetQueue::emitDomRootConsumerMarkup('user-column', 'lw-table-1.user-column');

    expect($markup)
        ->toContain('data-fff-asset-consumer="user-column"')
        ->toContain('data-fff-asset-consumer-id="lw-table-1.user-column"')
        ->toContain('flex-fields-user-display.css')
        ->toContain('flex-fields-user-column.css')
        ->and(FlexFieldStylesheetQueue::emitDomRootConsumerMarkup('user-column', 'lw-table-1.user-column'))
        ->toBe('')
        ->and(FlexFieldStylesheetQueue::pending())->toBe([])
        ->and(view('filament-flex-fields::partials.queued-stylesheets')->render())->toBe('');
});

it('registers unified asset injector for flex fields lazy assets', function (): void {
    $blade = file_get_contents(__DIR__.'/../../resources/views/partials/flex-field-asset-injector.blade.php');

    expect($blade)
        ->toContain('flex-field-asset-injector')
        ->toContain('FilamentAsset::getScriptSrc')
        ->toContain('data-navigate-track');
});

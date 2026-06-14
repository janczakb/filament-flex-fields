<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Filament\Support\Facades\FilamentAsset;

it('registers core and playground css asset ids', function () {
    expect(FlexFieldAssets::CORE_STYLESHEET_ID)->toBe('flex-fields-core')
        ->and(FlexFieldAssets::PLAYGROUND_STYLESHEET_ID)->toBe('flex-fields-playground')
        ->and(FlexFieldAssets::stylesheetId('phone-field'))->toBe('flex-fields-phone-field');
});

it('builds core and playground css bundles', function () {
    $core = __DIR__.'/../../resources/dist/css/core.css';
    $playground = __DIR__.'/../../resources/dist/css/playground.css';

    expect(is_file($core))->toBeTrue()
        ->and(is_file($playground))->toBeTrue()
        ->and(filesize($core))->toBeGreaterThan(1000)
        ->and(filesize($playground))->toBeGreaterThan(100);
});

it('includes table column styles in the core bundle', function () {
    $coreCss = file_get_contents(__DIR__.'/../../resources/dist/css/core.css');

    expect($coreCss)
        ->toContain('.fff-user-column')
        ->toContain('.fff-rating-column')
        ->toContain('.fff-rating__icon-clip');
});

it('includes hold confirm action styles in the core bundle', function () {
    $coreCss = file_get_contents(__DIR__.'/../../resources/dist/css/core.css');

    expect($coreCss)
        ->toContain('.fff-hold-confirm-action')
        ->toContain('.fff-hold-confirm-action__overlay');
});

it('builds cover card overlay styles in the lazy bundle', function () {
    $sourceCss = file_get_contents(__DIR__.'/../../resources/css/components/cover-card.css');
    $coverCardCss = file_get_contents(__DIR__.'/../../resources/dist/css/cover-card.css');

    expect($sourceCss)
        ->toContain('-webkit-backdrop-filter: blur(10px) saturate(1.5)')
        ->toContain('backdrop-filter: blur(10px) saturate(1.5)')
        ->toContain('mask-image: linear-gradient(180deg, #000 50%, #0000 100%)')
        ->toContain('mask-image: linear-gradient(0deg, #000 50%, #0000 100%)');

    expect($coverCardCss)
        ->toContain('.fff-cover-card__overlay--top')
        ->toContain('.fff-cover-card__overlay--bottom')
        ->toContain('.fff-cover-card__overlay')
        ->toContain('backdrop-filter:blur(10px)saturate(1.5)');
});

it('keeps cover card styles out of the core bundle', function () {
    $coreCss = file_get_contents(__DIR__.'/../../resources/dist/css/core.css');

    expect($coreCss)->not->toContain('.fff-cover-card__overlay--top');
});

it('builds lazy component css bundles for x-load form fields', function () {
    foreach (FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS as $component) {
        $path = __DIR__.'/../../resources/dist/css/'.$component.'.css';

        expect(is_file($path))->toBeTrue("Missing CSS bundle for {$component}");
    }
});

it('keeps playground-only demo styles out of the core bundle', function () {
    $coreCss = file_get_contents(__DIR__.'/../../resources/dist/css/core.css');
    $playgroundCss = file_get_contents(__DIR__.'/../../resources/dist/css/playground.css');

    expect($coreCss)->not->toContain('.fff-playground-toolbar')
        ->and($playgroundCss)->toContain('.fff-playground-toolbar')
        ->and($playgroundCss)->toContain('.fff-user-column-playground__table');
});

it('registers lazy stylesheets for every lazy component asset id', function () {
    $registered = collect(FilamentAsset::getStyles(['janczakb/filament-flex-fields']))
        ->map(fn ($asset) => $asset->getId())
        ->all();

    expect($registered)
        ->toContain(FlexFieldAssets::CORE_STYLESHEET_ID)
        ->toContain(FlexFieldAssets::PLAYGROUND_STYLESHEET_ID);

    foreach (FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS as $component) {
        expect($registered)->toContain(FlexFieldAssets::stylesheetId($component));
    }
});

it('renders lazy stylesheet links in form component blades', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/phone-field.blade.php');

    expect($blade)->toContain('partials.load-stylesheet')
        ->and($blade)->toContain("'component' => 'phone-field'");
});

it('renders playground stylesheet on the playground page', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/pages/flex-fields-playground-component.blade.php');

    expect($blade)->toContain('playgroundStylesheetHref()');
});

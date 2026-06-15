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

it('keeps the core bundle under the gzip budget', function () {
    $corePath = __DIR__.'/../../resources/dist/css/core.css';
    $gzipBytes = strlen((string) gzencode((string) file_get_contents($corePath), 9));
    $gzipKb = $gzipBytes / 1024;

    expect($gzipKb)->toBeLessThan(50);
});

it('keeps table column styles in lazy bundles instead of core', function () {
    $coreCss = file_get_contents(__DIR__.'/../../resources/dist/css/core.css');
    $userColumnCss = file_get_contents(__DIR__.'/../../resources/dist/css/user-column.css');
    $ratingColumnCss = file_get_contents(__DIR__.'/../../resources/dist/css/rating-column.css');

    expect($coreCss)
        ->not->toContain('.fff-user-column')
        ->not->toContain('.fff-rating-column')
        ->not->toContain('.fff-rating__icon-clip');

    expect($userColumnCss)->toContain('.fff-user-column');
    expect($ratingColumnCss)
        ->toContain('.fff-rating-column')
        ->toContain('.fff-rating__icon-clip');
});

it('keeps hold confirm action styles in the lazy bundle', function () {
    $coreCss = file_get_contents(__DIR__.'/../../resources/dist/css/core.css');
    $holdConfirmCss = file_get_contents(__DIR__.'/../../resources/dist/css/hold-confirm-action.css');

    expect($coreCss)
        ->not->toContain('.fff-hold-confirm-action')
        ->not->toContain('.fff-hold-confirm-action__overlay');

    expect($holdConfirmCss)
        ->toContain('.fff-hold-confirm-action')
        ->toContain('.fff-hold-confirm-action__overlay');
});

it('keeps moved component styles out of the core bundle', function () {
    $coreCss = file_get_contents(__DIR__.'/../../resources/dist/css/core.css');

    expect($coreCss)
        ->not->toContain('.fff-switch')
        ->not->toContain('.item-card')
        ->not->toContain('.fff-choice-cards')
        ->not->toContain('.fff-rating-field')
        ->not->toContain('.fff-color-swatch')
        ->not->toContain('.fff-select-field')
        ->not->toContain('.fff-track-slider')
        ->not->toContain('.fff-segment-control');
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

    expect($registered)->toContain(FlexFieldAssets::CORE_STYLESHEET_ID);

    foreach (FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS as $component) {
        expect($registered)->toContain(FlexFieldAssets::stylesheetId($component));
    }
});

it('registers playground css assets only when playground is enabled', function () {
    $provider = new Bjanczak\FilamentFlexFields\FilamentFlexFieldsServiceProvider(app());
    $method = new ReflectionMethod($provider, 'registeredStylesheets');

    config()->set('filament-flex-fields.playground.enabled', false);

    $disabledIds = array_map(
        fn (Bjanczak\FilamentFlexFields\Assets\FlexFieldsCss $asset): string => $asset->getId(),
        $method->invoke($provider),
    );

    config()->set('filament-flex-fields.playground.enabled', true);

    $enabledIds = array_map(
        fn (Bjanczak\FilamentFlexFields\Assets\FlexFieldsCss $asset): string => $asset->getId(),
        $method->invoke($provider),
    );

    expect($disabledIds)
        ->not->toContain(FlexFieldAssets::PLAYGROUND_STYLESHEET_ID)
        ->and($enabledIds)
        ->toContain(FlexFieldAssets::PLAYGROUND_STYLESHEET_ID)
        ->toContain(FlexFieldAssets::playgroundBundleStylesheetId('user-column'));
});

it('registers playground theme hook only when playground is enabled', function () {
    $providerSource = file_get_contents(__DIR__.'/../../src/FilamentFlexFieldsServiceProvider.php');

    expect($providerSource)
        ->toContain('if (FlexFieldsConfig::isPlaygroundEnabled())')
        ->toContain("Blade::render('filament-flex-fields::partials.playground-theme')");
});

it('renders lazy stylesheet links in form component blades', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/phone-field.blade.php');

    expect($blade)->toContain('partials.load-stylesheet')
        ->and($blade)->toContain("'component' => 'phone-field'");
});

it('renders playground stylesheet on the playground page', function () {
    $stylesPartial = file_get_contents(__DIR__.'/../../resources/views/partials/playground-page-stylesheets.blade.php');

    expect($stylesPartial)->toContain('playgroundStylesheetHrefForRequest()');
});

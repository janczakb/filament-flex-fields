<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields;

use Bjanczak\FilamentFlexFields\Assets\FlexFieldsAlpineComponent;
use Bjanczak\FilamentFlexFields\Assets\FlexFieldsCss;
use Bjanczak\FilamentFlexFields\Support\CountryRegistryQueue;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAlpineQueue;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\FieldComponentFactory;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Registry\FieldTypeHandlerRegistry;
use Bjanczak\FilamentFlexFields\Support\HtmlSanitizer;
use Bjanczak\FilamentFlexFields\Support\Translatable\RegistersTranslatableFieldMacros;
use Bjanczak\FilamentFlexFields\Support\UserSelectQueryCache;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FilamentFlexFieldsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filament-flex-fields.php', 'filament-flex-fields');

        $this->app->singleton(FlexFieldSchemaRegistry::class, function (): FlexFieldSchemaRegistry {
            $registry = new FlexFieldSchemaRegistry;

            $registry->registerFromConfig(config('filament-flex-fields.schemas', []));

            return $registry;
        });

        $this->app->singleton(FieldTypeHandlerRegistry::class);
        $this->app->singleton(FieldComponentFactory::class);
        $this->app->singleton(FlexFieldFormBuilder::class);
        $this->app->singleton(HtmlSanitizer::class);
        $this->app->singleton(FlexFieldsPlaygroundBuilder::class);
        $this->app->scoped(FlexFieldStylesheetQueue::class);
        $this->app->scoped(FlexFieldAlpineQueue::class);
        $this->app->scoped(CountryRegistryQueue::class);
        $this->app->scoped(UserSelectQueryCache::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/filament-flex-fields.php' => config_path('filament-flex-fields.php'),
        ], 'filament-flex-fields-config');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/filament-flex-fields'),
        ], 'filament-flex-fields-translations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-flex-fields');

        $langPath = __DIR__.'/../resources/lang';

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'filament-flex-fields');
        }

        FilamentAsset::register([
            ...$this->registeredStylesheets(),
            ...$this->registeredAlpineComponents(),
        ], package: FilamentFlexFieldsPlugin::PACKAGE_NAME);

        $this->publishStalePackageAssets();

        if (FlexFieldsConfig::isPlaygroundEnabled()) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('filament-flex-fields::partials.playground-theme'),
            );
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('filament-flex-fields::partials.hold-confirm-action-preload')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn (): string => view('filament-flex-fields::partials.queued-stylesheets')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn (): string => view('filament-flex-fields::partials.tooltip-overrides')->render(),
        );

        if (FlexFieldsConfig::isPlaygroundEnabled()) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::STYLES_BEFORE,
                fn (): string => request()->is('*flex-fields-playground*')
                    ? view('filament-flex-fields::partials.playground-page-stylesheets')->render()
                    : '',
            );

            FilamentView::registerRenderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => view('filament-flex-fields::partials.playground-assets')->render(),
            );
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            fn (): string => view('filament-flex-fields::partials.tooltip-glass-script')->render(),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            fn (): string => view('filament-flex-fields::partials.lazy-assets-navigate-dedupe')->render(),
        );

        RegistersTranslatableFieldMacros::boot();

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    /**
     * @return list<FlexFieldsCss>
     */
    protected function registeredStylesheets(): array
    {
        $distPath = __DIR__.'/../resources/dist/css';

        $assets = [
            FlexFieldsCss::make(FlexFieldAssets::CORE_STYLESHEET_ID, $distPath.'/core.css'),
            FlexFieldsCss::make('filament-flex-fields', $distPath.'/core.css'),
        ];

        if (FlexFieldsConfig::isPlaygroundEnabled()) {
            $assets[] = FlexFieldsCss::make(FlexFieldAssets::PLAYGROUND_STYLESHEET_ID, $distPath.'/playground.css')->loadedOnRequest();

            foreach (glob($distPath.'/playground-*.css') ?: [] as $bundlePath) {
                $slug = str_replace('playground-', '', basename($bundlePath, '.css'));

                $assets[] = FlexFieldsCss::make(
                    FlexFieldAssets::playgroundBundleStylesheetId($slug),
                    $bundlePath,
                )->loadedOnRequest();
            }
        }

        foreach (FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS as $component) {
            $assets[] = FlexFieldsCss::make(
                FlexFieldAssets::stylesheetId($component),
                $distPath.'/'.$component.'.css',
            )->loadedOnRequest();
        }

        return $assets;
    }

    /**
     * @return list<FlexFieldsAlpineComponent>
     */
    protected function registeredAlpineComponents(): array
    {
        $distPath = __DIR__.'/../resources/dist/components';
        $assets = [];

        foreach (glob($distPath.'/*.js') ?: [] as $path) {
            $id = basename($path, '.js');

            $assets[] = FlexFieldsAlpineComponent::make($id, $path);
        }

        return $assets;
    }

    protected function publishStalePackageAssets(): void
    {
        if (app()->isProduction() && ! app()->runningInConsole()) {
            return;
        }

        $filesystem = $this->app->make(Filesystem::class);

        $assets = [
            ...FilamentAsset::getStyles([FilamentFlexFieldsPlugin::PACKAGE_NAME]),
            ...FilamentAsset::getAlpineComponents([FilamentFlexFieldsPlugin::PACKAGE_NAME]),
        ];

        foreach ($assets as $asset) {
            if ($asset->isRemote()) {
                continue;
            }

            $source = $asset->getPath();

            if (! is_string($source) || ! is_file($source)) {
                continue;
            }

            $destination = $asset->getPublicPath();

            if (is_file($destination) && filemtime($source) <= filemtime($destination)) {
                continue;
            }

            $filesystem->ensureDirectoryExists(dirname($destination));
            $filesystem->copy($source, $destination);
        }

        $beepSource = FlexFieldAssets::barcodeScanBeepSourcePath();
        $beepDestination = public_path(FlexFieldAssets::barcodeScanBeepRelativePath());

        if (is_file($beepSource) && (! is_file($beepDestination) || filemtime($beepSource) > filemtime($beepDestination))) {
            $filesystem->ensureDirectoryExists(dirname($beepDestination));
            $filesystem->copy($beepSource, $beepDestination);
        }
    }
}

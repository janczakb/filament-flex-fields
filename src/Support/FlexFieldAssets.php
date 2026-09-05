<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Filesystem\Filesystem;

class FlexFieldAssets
{
    public const CORE_STYLESHEET_ID = 'flex-fields-core';

    public const PLAYGROUND_STYLESHEET_ID = 'flex-fields-playground';

    public const ASSET_INJECTOR_SCRIPT_ID = 'flex-field-asset-injector';

    public const FFF_LOAD_BOOTSTRAP_SCRIPT_ID = 'flex-fff-load-bootstrap';

    public const ASSET_INSPECTOR_SCRIPT_ID = 'flex-field-asset-inspector';

    public const FLEX_RICH_EDITOR_PASTE_EXTENSION_SCRIPT_ID = 'flex-rich-editor-paste-extension';

    public const FLEX_RICH_EDITOR_BLOCK_IMAGE_EXTENSION_SCRIPT_ID = 'flex-rich-editor-block-image-extension';

    public const FLEX_RICH_EDITOR_YOUTUBE_EXTENSION_SCRIPT_ID = 'flex-rich-editor-youtube-extension';

    public const PLAYGROUND_SKELETON_DEMO_SCRIPT_ID = 'playground-skeleton-demo';

    public const SEGMENT_OVERFLOW_SSR_SCRIPT_ID = 'flex-fields-segment-overflow-ssr';

    public const TIMEZONE_BROWSER_SSR_BOOT_SCRIPT_ID = 'flex-fields-timezone-browser-ssr-boot';

    public const STATIC_ASSETS_PUBLIC_DIRECTORY = 'filament-flex-fields-assets';

    /**
     * Blocking SSR scripts emitted by shared Blade shells when segment overflow CSS loads.
     *
     * @var array<string, list<string>>
     */
    public const STYLESHEET_SSR_SCRIPTS = [
        'segment-control' => [
            self::SEGMENT_OVERFLOW_SSR_SCRIPT_ID,
        ],
        'segment-tabs' => [
            self::SEGMENT_OVERFLOW_SSR_SCRIPT_ID,
        ],
    ];

    /**
     * Most common lazy bundles preloaded in <head> to reduce modal and form FOUC.
     *
     * @var list<string>
     */
    public const CRITICAL_PRELOAD_STYLESHEETS = [
        'overlay-runtime',
        'teleported-menu',
    ];

    /** @var list<string> */
    public const LAZY_COMPONENT_STYLESHEETS = [
        'cover-card',
        'emoji-picker',
        'number-stepper',
        'traffic-split',
        'dual-listbox',
        'bubble-choice',
        'todo-list-field',
        'price-range',
        'flex-textarea',
        'rich-editor-field',
        'flex-text-input',
        'link-preview-field',
        'barcode-scanner-field',
        'social-links-field',
        'schedule-field',
        'credit-card',
        'phone-field',
        'country-field',
        'timezone-field',
        'flex-date-time-field',
        'flex-time-segments',
        'flex-file-upload',
        'currency-field',
        'calculator-field',
        'calculator-panel',
        'slug-field',
        'video-field',
        'audio-field',
        'flex-slider',
        'flex-verification-code',
        'map-picker-dropdown',
        'map-picker',
        'address-autocomplete',
        'signature-field',
        'flex-color-picker',
        'flex-checklist',
        'progress-bar',
        'progress-circle',
        'matrix-choice-field',
        'tags-field',
        'tag-chips',
        'voice-note-recorder-field',
        'switch',
        'item-card',
        'choice-cards',
        'image-choice-cards',
        'rating-field',
        'color-swatch',
        'select-field',
        'icon-picker-field',
        'user-select',
        'user-display',
        'user-column',
        'rating-column',
        'icon-column',
        'progress-column',
        'status-chip-column',
        'signature-preview-column',
        'map-pin-column',
        'hold-confirm-action',
        'track-slider',
        'segment-control',
        'segment-tabs',
        'nps-field',
        'overlay-runtime',
        'teleported-menu',
    ];

    public static function stylesheetId(string $component): string
    {
        return 'flex-fields-'.$component;
    }

    public static function hasLazyStylesheet(string $component): bool
    {
        return in_array($component, self::LAZY_COMPONENT_STYLESHEETS, true);
    }

    public static function shouldLoadStylesheetsFor(string $component): bool
    {
        $component = self::resolveStylesheetComponent($component);

        return self::hasLazyStylesheet($component)
            || array_key_exists($component, self::STYLESHEET_DEPENDENCIES);
    }

    public static function resolveStylesheetComponent(string $component): string
    {
        return self::PLAYGROUND_STYLESHEET_ALIASES[$component] ?? $component;
    }

    /**
     * Declared stylesheet dependencies loaded before the component bundle.
     * Each dependency is a separate lazy CSS file — never duplicated in bundles.
     *
     * @var array<string, list<string>>
     */
    public const STYLESHEET_DEPENDENCIES = [
        'flex-text-input' => ['emoji-picker'],
        'flex-textarea' => ['emoji-picker'],
        'phone-field' => ['flex-text-input', 'teleported-menu'],
        'country-field' => ['flex-text-input', 'teleported-menu'],
        'timezone-field' => ['flex-text-input', 'teleported-menu'],
        'currency-field' => ['flex-text-input', 'teleported-menu'],
        'calculator-field' => ['flex-text-input'],
        'calculator-panel' => [],
        'address-autocomplete' => ['flex-text-input', 'teleported-menu', 'map-picker-dropdown'],
        'flex-color-picker' => ['flex-text-input'],
        'icon-picker-field' => ['teleported-menu', 'select-field'],
        'slug-field' => ['flex-text-input'],
        'link-preview-field' => ['flex-text-input'],
        'barcode-scanner-field' => ['flex-text-input'],
        'social-links-field' => ['flex-text-input', 'teleported-menu'],
        'schedule-field' => ['flex-text-input', 'switch', 'teleported-menu', 'timezone-field', 'flex-time-segments'],
        'tags-field' => ['flex-text-input', 'tag-chips', 'teleported-menu', 'select-field'],
        'flex-date-time-field' => ['flex-text-input'],
        'flex-time-segments' => ['flex-text-input', 'teleported-menu'],
        'map-picker-dropdown' => ['teleported-menu', 'select-field'],
        'map-picker' => ['flex-text-input', 'teleported-menu', 'map-picker-dropdown'],
        'select-field' => ['teleported-menu'],
        'nps-field' => ['segment-control'],
        'user-select' => ['teleported-menu', 'select-field', 'tag-chips', 'user-display'],
        'user-column' => ['user-display'],
        'voice-note-recorder-field' => ['emoji-picker'],
        'segment-tabs' => ['segment-control'],
        'teleported-menu' => ['overlay-runtime'],
        'audio-field' => ['switch'],
    ];

    /**
     * Playground navigation slugs that differ from lazy stylesheet component ids.
     *
     * @var array<string, string>
     */
    public const PLAYGROUND_STYLESHEET_ALIASES = [
        'admin-columns' => 'progress-column',
        'hold-confirm' => 'hold-confirm-action',
        'schema-conditions' => 'flex-text-input',
        'field-intelligence' => 'flex-text-input',
        'focus-outline' => 'flex-text-input',
        'date-time-fields' => 'flex-date-time-field',
        'file-upload' => 'flex-file-upload',
        'verification-code' => 'flex-verification-code',
        'flex-radiolist' => 'flex-checklist',
        'matrix-choice' => 'matrix-choice-field',
        'rating' => 'rating-field',
        'flex-rich-editor' => 'rich-editor-field',
        'translatable-fields' => 'segment-tabs',
    ];

    /**
     * Extra lazy stylesheets bundled into playground slug CSS beyond the resolved alias component.
     *
     * @var array<string, list<string>>
     */
    public const PLAYGROUND_EXTRA_STYLESHEETS = [
        'calculator-field' => ['calculator-panel'],
        'date-time-fields' => ['flex-time-segments'],
        'field-intelligence' => ['number-stepper', 'select-field', 'flex-textarea', 'currency-field'],
        'focus-outline' => ['select-field', 'flex-textarea', 'phone-field', 'credit-card'],
        'translatable-fields' => ['flex-text-input', 'flex-textarea'],
    ];

    /**
     * @return list<string>
     */
    public static function stylesheetsFor(string $component): array
    {
        $component = self::resolveStylesheetComponent($component);
        $stylesheets = [];
        $visited = [];

        $resolve = function (string $comp) use (&$resolve, &$stylesheets, &$visited) {
            if (isset($visited[$comp])) {
                return;
            }

            $visited[$comp] = true;

            foreach (self::STYLESHEET_DEPENDENCIES[$comp] ?? [] as $dep) {
                $resolve($dep);
            }

            if (self::hasLazyStylesheet($comp)) {
                if (! in_array($comp, $stylesheets, true)) {
                    $stylesheets[] = $comp;
                }
            }
        };

        $resolve($component);

        return $stylesheets;
    }

    /**
     * Blocking SSR scripts required by shared Blade shells for a component.
     *
     * @return list<string>
     */
    public static function ssrScriptsFor(string $component): array
    {
        $component = self::resolveStylesheetComponent($component);
        $scripts = [];
        $visited = [];

        $resolve = function (string $comp) use (&$resolve, &$scripts, &$visited): void {
            if (isset($visited[$comp])) {
                return;
            }

            $visited[$comp] = true;

            foreach (self::STYLESHEET_DEPENDENCIES[$comp] ?? [] as $dep) {
                $resolve($dep);
            }

            foreach (self::STYLESHEET_SSR_SCRIPTS[$comp] ?? [] as $scriptId) {
                if (! in_array($scriptId, $scripts, true)) {
                    $scripts[] = $scriptId;
                }
            }
        };

        $resolve($component);

        return $scripts;
    }

    /**
     * @return list<string>
     */
    public static function stylesheetHrefsFor(string $component): array
    {
        return array_map(
            fn (string $stylesheet): string => self::stylesheetHref($stylesheet),
            self::stylesheetsFor($component),
        );
    }

    /**
     * Deduped lazy CSS + Alpine chunks for a set of component ids, preserving
     * dependency order from stylesheetsFor() / alpineChunksFor().
     *
     * @param  list<string>  $components
     * @return array{stylesheets: list<string>, chunks: list<string>}
     */
    public static function planAssetsForComponents(array $components): array
    {
        $stylesheets = [];
        $chunks = [];

        foreach (array_values(array_unique($components)) as $component) {
            if (! is_string($component) || $component === '') {
                continue;
            }

            foreach (self::stylesheetsFor($component) as $stylesheet) {
                $stylesheets[$stylesheet] = true;
            }

            foreach (self::alpineChunksFor($component) as $chunk) {
                $chunks[$chunk] = true;
            }
        }

        return [
            'stylesheets' => array_keys($stylesheets),
            'chunks' => array_keys($chunks),
        ];
    }

    /**
     * @return list<string>
     */
    public static function playgroundStylesheetsFor(string $slug): array
    {
        $component = self::resolveStylesheetComponent($slug);
        $stylesheets = self::stylesheetsFor($component);

        foreach (self::PLAYGROUND_EXTRA_STYLESHEETS[$slug] ?? [] as $extra) {
            foreach (self::stylesheetsFor($extra) as $stylesheet) {
                if (! in_array($stylesheet, $stylesheets, true)) {
                    $stylesheets[] = $stylesheet;
                }
            }
        }

        return $stylesheets;
    }

    public static function stylesheetHref(string $component): string
    {
        return FilamentAsset::getStyleHref(
            self::stylesheetId($component),
            FilamentFlexFieldsPlugin::PACKAGE_NAME,
        );
    }

    /**
     * Minified blocking IIFE inlined next to each overflow shell (no network round-trip).
     */
    public static function segmentOverflowSsrInlineContents(): string
    {
        return once(function (): string {
            $path = dirname(__DIR__, 2).'/resources/dist/core/segment-overflow-ssr.js';

            if (! is_readable($path)) {
                return '';
            }

            return (string) file_get_contents($path);
        });
    }

    /**
     * Minified blocking IIFE inlined next to browserTimezoneDefault triggers
     * (paints Intl timezone before Alpine x-load — no network round-trip).
     */
    public static function timezoneBrowserSsrInlineContents(): string
    {
        return once(function (): string {
            $path = dirname(__DIR__, 2).'/resources/dist/core/timezone-browser-ssr-boot.js';

            if (! is_readable($path)) {
                return '';
            }

            return (string) file_get_contents($path);
        });
    }

    /**
     * @return list<string>
     */
    public static function criticalPreloadStylesheets(): array
    {
        $preloads = array_values(array_filter(
            self::CRITICAL_PRELOAD_STYLESHEETS,
            fn (string $component): bool => self::hasLazyStylesheet($component),
        ));

        if (! request()->is('*flex-fields-playground*')) {
            if (! FlexFieldStylesheetQueue::hasQueuedSelectFamilyPicker()) {
                return [];
            }

            return $preloads;
        }

        $slug = self::resolvePlaygroundSlugFromRequest();

        if (blank($slug)) {
            return $preloads;
        }

        if (self::hasPlaygroundBundleForSlug($slug)) {
            return [];
        }

        $needed = self::playgroundStylesheetsFor($slug);

        return array_values(array_filter(
            $preloads,
            fn (string $component): bool => in_array($component, $needed, true),
        ));
    }

    public static function playgroundStylesheetHref(): string
    {
        return FilamentAsset::getStyleHref(
            self::PLAYGROUND_STYLESHEET_ID,
            FilamentFlexFieldsPlugin::PACKAGE_NAME,
        );
    }

    public static function playgroundBundleStylesheetId(string $slug): string
    {
        return 'flex-fields-playground-'.$slug;
    }

    public static function playgroundBundleHrefForSlug(?string $slug): string
    {
        if (blank($slug)) {
            return self::playgroundStylesheetHref();
        }

        return FilamentAsset::getStyleHref(
            self::playgroundBundleStylesheetId($slug),
            FilamentFlexFieldsPlugin::PACKAGE_NAME,
        );
    }

    public static function playgroundBundlePathForSlug(string $slug): string
    {
        return __DIR__.'/../../resources/dist/css/playground-'.$slug.'.css';
    }

    public static function hasPlaygroundBundleForSlug(string $slug): bool
    {
        $path = self::playgroundBundlePathForSlug($slug);

        return is_file($path) && filesize($path) > 0;
    }

    public static function resolvePlaygroundSlugFromRequest(): ?string
    {
        if (preg_match('#flex-fields-playground/([^/]+)#', request()->path(), $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function playgroundStylesheetHrefsForSlug(?string $slug): array
    {
        if (blank($slug)) {
            return [self::playgroundStylesheetHref()];
        }

        if (self::hasPlaygroundBundleForSlug($slug)) {
            return [
                self::playgroundStylesheetHref(),
                self::playgroundBundleHrefForSlug($slug),
            ];
        }

        return [self::playgroundStylesheetHref()];
    }

    public static function playgroundStylesheetHrefForSlug(?string $slug): string
    {
        $hrefs = self::playgroundStylesheetHrefsForSlug($slug);

        return $hrefs[array_key_last($hrefs)] ?? self::playgroundStylesheetHref();
    }

    /**
     * @return list<string>
     */
    public static function playgroundStylesheetHrefsForRequest(): array
    {
        if (! request()->is('*flex-fields-playground*')) {
            return [];
        }

        $slug = self::resolvePlaygroundSlugFromRequest();

        return self::playgroundStylesheetHrefsForSlug($slug);
    }

    public static function playgroundStylesheetHrefForRequest(): ?string
    {
        $hrefs = self::playgroundStylesheetHrefsForRequest();

        if ($hrefs === []) {
            return null;
        }

        return $hrefs[array_key_last($hrefs)];
    }

    public static function resolveCanonicalComponent(string $component): string
    {
        return self::resolveStylesheetComponent($component);
    }

    /**
     * @return array{
     *     componentId: string,
     *     stylesheets: list<string>,
     *     chunks: list<string>,
     *     entry: string,
     *     kind: string,
     * }
     */
    public static function assetBundleFor(string $component): array
    {
        $canonical = self::resolveCanonicalComponent($component);
        $manifest = self::alpineManifest();
        $hasAlpineEntry = array_key_exists($canonical, $manifest)
            && ! str_starts_with($canonical, '__');

        return [
            'componentId' => $canonical,
            'stylesheets' => self::stylesheetHrefsFor($canonical),
            'chunks' => array_map(
                fn (string $chunk): string => self::alpineChunkSrc($chunk),
                self::alpineChunksFor($canonical),
            ),
            'entry' => $hasAlpineEntry ? self::alpineEntrySrc($canonical) : null,
            'kind' => $hasAlpineEntry ? 'full' : 'styles-only',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function consumerAttributesFor(string $livewireKey, string $component): array
    {
        return [
            'data-fff-asset-consumer' => self::resolveCanonicalComponent($component),
            'data-fff-asset-consumer-id' => $livewireKey,
        ];
    }

    /**
     * @return array<string, array{
     *     componentId: string,
     *     stylesheets: list<string>,
     *     chunks: list<string>,
     *     entry: string,
     *     kind: string,
     * }>
     */
    public static function exportConsumerBundles(): array
    {
        $bundles = [];

        foreach (self::LAZY_COMPONENT_STYLESHEETS as $component) {
            $bundles[$component] = self::assetBundleFor($component);
        }

        return $bundles;
    }

    /**
     * @return array{
     *     lazy_stylesheets: list<string>,
     *     stylesheet_dependencies: array<string, list<string>>,
     *     playground_aliases: array<string, string>,
     *     playground_extras: array<string, list<string>>,
     *     critical_preload: list<string>,
     *     bundles: array<string, array{
     *         componentId: string,
     *         stylesheets: list<string>,
     *         chunks: list<string>,
     *         entry: string,
     *         kind: string,
     *     }>,
     * }
     */
    public static function exportRegistry(): array
    {
        return [
            'lazy_stylesheets' => self::LAZY_COMPONENT_STYLESHEETS,
            'stylesheet_dependencies' => self::STYLESHEET_DEPENDENCIES,
            'playground_aliases' => self::PLAYGROUND_STYLESHEET_ALIASES,
            'playground_extras' => self::PLAYGROUND_EXTRA_STYLESHEETS,
            'critical_preload' => self::CRITICAL_PRELOAD_STYLESHEETS,
            'bundles' => self::exportConsumerBundles(),
        ];
    }

    public static function assetRegistryPath(): string
    {
        return __DIR__.'/../../resources/dist/asset-registry.json';
    }

    public static function alpineManifestPath(): string
    {
        return __DIR__.'/../../resources/dist/components/alpine-manifest.json';
    }

    /**
     * @return array<string, mixed>
     */
    public static function alpineManifest(): array
    {
        static $manifest = null;

        if ($manifest === null) {
            $path = self::alpineManifestPath();

            $manifest = is_file($path)
                ? (json_decode((string) file_get_contents($path), true) ?: [])
                : [];
        }

        return $manifest;
    }

    /**
     * @return list<string>
     */
    public static function alpineEntryNames(): array
    {
        return array_values(array_filter(
            array_keys(self::alpineManifest()),
            fn (string $key): bool => ! str_starts_with($key, '__'),
        ));
    }

    /**
     * @return list<string>
     */
    public static function alpineSharedChunkNames(): array
    {
        $chunks = self::alpineManifest()['__shared_chunks__'] ?? [];

        if (! is_array($chunks)) {
            return [];
        }

        return array_values(array_filter($chunks, fn (mixed $chunk): bool => is_string($chunk) && $chunk !== ''));
    }

    /**
     * @return list<string>
     */
    public static function alpineChunksFor(string $component): array
    {
        $manifest = self::alpineManifest();

        $chunks = $manifest[$component] ?? [];

        if (! is_array($chunks)) {
            $chunks = [];
        }

        if ($component === 'select-field') {
            $overlayCoordinatorChunk = self::overlayCoordinatorChunk($manifest);

            if (is_string($overlayCoordinatorChunk) && $overlayCoordinatorChunk !== '') {
                $chunks[] = $overlayCoordinatorChunk;
            }
        }

        $chunks = self::expandTransitiveAlpineChunks($chunks, $manifest);

        return array_values(array_filter(
            array_unique($chunks),
            fn (mixed $chunk): bool => is_string($chunk)
                && $chunk !== ''
                && ! str_starts_with($chunk, 'flex-fields-phone-lib'),
        ));
    }

    /**
     * Resolve nested dynamic-import chunks (e.g. ZXing fallback inside barcode scanner).
     *
     * @param  list<string>  $chunks
     * @return list<string>
     */
    public static function expandTransitiveAlpineChunks(array $chunks, ?array $manifest = null): array
    {
        $manifest ??= self::alpineManifest();
        $graph = $manifest['__chunk_imports__'] ?? [];

        if (! is_array($graph) || $graph === []) {
            return array_values(array_unique($chunks));
        }

        $expanded = [];
        $seen = [];
        $queue = array_values(array_unique(array_filter(
            $chunks,
            fn (mixed $chunk): bool => is_string($chunk) && $chunk !== '',
        )));

        while ($queue !== []) {
            $chunk = array_shift($queue);

            if (! is_string($chunk) || $chunk === '' || isset($seen[$chunk])) {
                continue;
            }

            $seen[$chunk] = true;
            $expanded[] = $chunk;

            foreach ($graph[$chunk] ?? [] as $nestedChunk) {
                if (is_string($nestedChunk) && $nestedChunk !== '' && ! isset($seen[$nestedChunk])) {
                    $queue[] = $nestedChunk;
                }
            }
        }

        return $expanded;
    }

    public static function overlayCoordinatorChunk(?array $manifest = null): ?string
    {
        $manifest ??= self::alpineManifest();

        foreach ($manifest['__chunk_modules__'] ?? [] as $chunk => $modules) {
            if (! is_string($chunk) || ! is_array($modules)) {
                continue;
            }

            if (in_array('core/flex-dropdown-coordinator.js', $modules, true)) {
                return $chunk;
            }
        }

        return null;
    }

    public static function alpineChunkSrc(string $chunk): string
    {
        return FilamentAsset::getAlpineComponentSrc(
            str_replace('.js', '', $chunk),
            FilamentFlexFieldsPlugin::PACKAGE_NAME,
        );
    }

    public static function alpineEntrySrc(string $component): string
    {
        return FilamentAsset::getAlpineComponentSrc(
            $component,
            FilamentFlexFieldsPlugin::PACKAGE_NAME,
        );
    }

    public static function whisperRuntimeModuleSrc(): string
    {
        return self::assetUrl('whisper/transformers.min.js');
    }

    public static function whisperRuntimeWasmBaseSrc(): string
    {
        $moduleSrc = self::whisperRuntimeModuleSrc();
        $base = preg_replace('#/[^/]+$#', '/', $moduleSrc) ?? $moduleSrc;

        return str_ends_with($base, '/') ? $base : "{$base}/";
    }

    /**
     * @return array<string, string>
     */
    public static function playgroundNavigateStylesheetMap(): array
    {
        if (! FlexFieldsConfig::isPlaygroundEnabled()) {
            return [];
        }

        $map = [];

        foreach (FlexFieldsPlaygroundRegistry::definitions() as $slug => $definition) {
            $component = self::resolveStylesheetComponent($slug);

            if (! self::shouldLoadStylesheetsFor($component)) {
                continue;
            }

            $map[$slug] = self::playgroundStylesheetHrefForSlug($slug);
        }

        return $map;
    }

    public static function assetUrl(string $path): string
    {
        $relative = self::STATIC_ASSETS_PUBLIC_DIRECTORY.'/'.ltrim($path, '/');
        $public = public_path($relative);
        $version = is_file($public) ? (string) filemtime($public) : '1';

        return asset($relative).'?v='.$version;
    }

    public static function staticAssetsSourceDirectory(): string
    {
        return __DIR__.'/../../resources/dist/assets';
    }

    public static function staticAssetsPublicDirectory(): string
    {
        return public_path(self::STATIC_ASSETS_PUBLIC_DIRECTORY);
    }

    public static function shouldPublishStalePackageAssets(?bool $runningInConsole = null): bool
    {
        $runningInConsole ??= app()->runningInConsole();

        return ! (app()->isProduction() && ! $runningInConsole);
    }

    public static function publishRegisteredFilamentAssetsIfStale(Filesystem $filesystem): void
    {
        $assets = [
            ...FilamentAsset::getStyles([FilamentFlexFieldsPlugin::PACKAGE_NAME]),
            ...FilamentAsset::getScripts([FilamentFlexFieldsPlugin::PACKAGE_NAME]),
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
    }

    public static function publishStaticAssetsIfStale(Filesystem $filesystem): void
    {
        $source = self::staticAssetsSourceDirectory();
        $destination = self::staticAssetsPublicDirectory();

        if (! is_dir($source)) {
            return;
        }

        $filesystem->ensureDirectoryExists($destination);

        foreach ($filesystem->allFiles($source) as $file) {
            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($source) + 1));
            $target = $destination.'/'.$relativePath;

            if (is_file($target) && filemtime($file->getPathname()) <= filemtime($target)) {
                continue;
            }

            $filesystem->ensureDirectoryExists(dirname($target));
            $filesystem->copy($file->getPathname(), $target);
        }
    }

    public static function publishStalePackageAssets(Filesystem $filesystem): void
    {
        self::publishRegisteredFilamentAssetsIfStale($filesystem);
        self::publishStaticAssetsIfStale($filesystem);
        self::publishTodoListAudioIfStale($filesystem);
    }

    public static function publishTodoListAudioIfStale(Filesystem $filesystem): void
    {
        $source = dirname(__DIR__, 2).'/resources/dist/audio/todo-list-field';

        if (! is_dir($source)) {
            return;
        }

        $destination = public_path('js/'.FilamentFlexFieldsPlugin::PACKAGE_NAME.'/audio/todo-list-field');
        $filesystem->ensureDirectoryExists($destination);

        foreach ($filesystem->files($source) as $file) {
            $target = $destination.'/'.$file->getFilename();

            if (is_file($target) && filemtime($file->getPathname()) <= filemtime($target)) {
                continue;
            }

            $filesystem->copy($file->getPathname(), $target);
        }
    }
}

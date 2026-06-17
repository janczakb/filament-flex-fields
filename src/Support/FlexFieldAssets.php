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

class FlexFieldAssets
{
    public const CORE_STYLESHEET_ID = 'flex-fields-core';

    public const PLAYGROUND_STYLESHEET_ID = 'flex-fields-playground';

    public const ASSET_INJECTOR_SCRIPT_ID = 'flex-field-asset-injector';

    public const PLAYGROUND_SKELETON_DEMO_SCRIPT_ID = 'playground-skeleton-demo';

    /**
     * Most common lazy bundles preloaded in <head> to reduce modal and form FOUC.
     *
     * @var list<string>
     */
    public const CRITICAL_PRELOAD_STYLESHEETS = [
        'flex-text-input',
        'teleported-menu',
    ];

    /** @var list<string> */
    public const LAZY_COMPONENT_STYLESHEETS = [
        'cover-card',
        'emoji-picker',
        'number-stepper',
        'traffic-split',
        'dual-listbox',
        'price-range',
        'flex-textarea',
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
        'rating-field',
        'color-swatch',
        'select-field',
        'user-select',
        'user-display',
        'user-column',
        'rating-column',
        'hold-confirm-action',
        'track-slider',
        'segment-control',
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
        'address-autocomplete' => ['flex-text-input', 'teleported-menu', 'map-picker-dropdown'],
        'flex-color-picker' => ['flex-text-input'],
        'slug-field' => ['flex-text-input'],
        'link-preview-field' => ['flex-text-input'],
        'barcode-scanner-field' => ['flex-text-input'],
        'social-links-field' => ['flex-text-input', 'teleported-menu'],
        'schedule-field' => ['flex-text-input', 'switch', 'teleported-menu', 'timezone-field', 'flex-time-segments'],
        'tags-field' => ['flex-text-input', 'tag-chips'],
        'flex-date-time-field' => ['flex-text-input'],
        'flex-time-segments' => ['flex-text-input', 'teleported-menu'],
        'map-picker-dropdown' => ['teleported-menu'],
        'map-picker' => ['teleported-menu', 'map-picker-dropdown'],
        'select-field' => ['teleported-menu'],
        'user-select' => ['teleported-menu', 'select-field', 'tag-chips', 'user-display'],
        'user-column' => ['user-display'],
        'voice-note-recorder-field' => ['emoji-picker'],
        'segment-tabs' => ['segment-control'],
    ];

    /**
     * Playground navigation slugs that differ from lazy stylesheet component ids.
     *
     * @var array<string, string>
     */
    public const PLAYGROUND_STYLESHEET_ALIASES = [
        'date-time-fields' => 'flex-date-time-field',
        'file-upload' => 'flex-file-upload',
        'verification-code' => 'flex-verification-code',
        'flex-radiolist' => 'flex-checklist',
    ];

    /**
     * Extra lazy stylesheets bundled into playground slug CSS beyond the resolved alias component.
     *
     * @var array<string, list<string>>
     */
    public const PLAYGROUND_EXTRA_STYLESHEETS = [
        'date-time-fields' => ['flex-time-segments'],
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
     * @return list<string>
     */
    public static function criticalPreloadStylesheets(): array
    {
        return array_values(array_filter(
            self::CRITICAL_PRELOAD_STYLESHEETS,
            fn (string $component): bool => self::hasLazyStylesheet($component),
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
        return is_file(self::playgroundBundlePathForSlug($slug));
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
        return [self::playgroundStylesheetHrefForSlug($slug)];
    }

    public static function playgroundStylesheetHrefForSlug(?string $slug): string
    {
        if (blank($slug)) {
            return self::playgroundStylesheetHref();
        }

        if (self::hasPlaygroundBundleForSlug($slug)) {
            return self::playgroundBundleHrefForSlug($slug);
        }

        return self::playgroundStylesheetHref();
    }

    /**
     * @return list<string>
     */
    public static function playgroundStylesheetHrefsForRequest(): array
    {
        if (! request()->is('*flex-fields-playground*')) {
            return [];
        }

        return [self::playgroundStylesheetHrefForRequest()];
    }

    public static function playgroundStylesheetHrefForRequest(): ?string
    {
        if (! request()->is('*flex-fields-playground*')) {
            return null;
        }

        return self::playgroundStylesheetHrefForSlug(self::resolvePlaygroundSlugFromRequest());
    }

    public static function alpineManifestPath(): string
    {
        return __DIR__.'/../../resources/dist/components/alpine-manifest.json';
    }

    /**
     * @return list<string>
     */
    public static function alpineChunksFor(string $component): array
    {
        static $manifest = null;

        if ($manifest === null) {
            $path = self::alpineManifestPath();

            $manifest = is_file($path)
                ? (json_decode((string) file_get_contents($path), true) ?: [])
                : [];
        }

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

        return array_values(array_filter(array_unique($chunks), fn (mixed $chunk): bool => is_string($chunk) && $chunk !== ''));
    }

    public static function overlayCoordinatorChunk(?array $manifest = null): ?string
    {
        if ($manifest === null) {
            $path = self::alpineManifestPath();

            $manifest = is_file($path)
                ? (json_decode((string) file_get_contents($path), true) ?: [])
                : [];
        }

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

    public static function barcodeScanBeepRelativePath(): string
    {
        $path = config('filament.assets_path', '');

        return ltrim("{$path}/audio/".FilamentFlexFieldsPlugin::PACKAGE_NAME.'/barcode-scan-success.mp3', '/');
    }

    public static function barcodeScanBeepSourcePath(): string
    {
        return dirname(__DIR__, 2).'/resources/dist/audio/barcode-scan-success.mp3';
    }

    public static function barcodeScanBeepUrl(): string
    {
        $relative = self::barcodeScanBeepRelativePath();
        $public = public_path($relative);
        $version = is_file($public) ? (string) filemtime($public) : '1';

        return asset($relative).'?v='.$version;
    }
}

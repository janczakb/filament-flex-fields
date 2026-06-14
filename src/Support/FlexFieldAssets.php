<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
use Filament\Support\Facades\FilamentAsset;

class FlexFieldAssets
{
    public const CORE_STYLESHEET_ID = 'flex-fields-core';

    public const PLAYGROUND_STYLESHEET_ID = 'flex-fields-playground';

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
        'credit-card',
        'phone-field',
        'country-field',
        'timezone-field',
        'flex-date-time-field',
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
        'voice-note-recorder-field',
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
        'phone-field' => ['flex-text-input'],
        'country-field' => ['flex-text-input'],
        'timezone-field' => ['flex-text-input'],
        'currency-field' => ['flex-text-input'],
        'address-autocomplete' => ['flex-text-input', 'map-picker-dropdown'],
        'flex-color-picker' => ['flex-text-input'],
        'slug-field' => ['flex-text-input'],
        'tags-field' => ['flex-text-input'],
        'flex-date-time-field' => ['flex-text-input'],
        'map-picker' => ['map-picker-dropdown'],
        'voice-note-recorder-field' => ['emoji-picker'],
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
     * @return list<string>
     */
    public static function stylesheetsFor(string $component): array
    {
        $component = self::resolveStylesheetComponent($component);
        $stylesheets = [];

        foreach ([...self::STYLESHEET_DEPENDENCIES[$component] ?? [], $component] as $stylesheet) {
            if (! in_array($stylesheet, $stylesheets, true)) {
                $stylesheets[] = $stylesheet;
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

    public static function playgroundStylesheetHref(): string
    {
        return FilamentAsset::getStyleHref(
            self::PLAYGROUND_STYLESHEET_ID,
            FilamentFlexFieldsPlugin::PACKAGE_NAME,
        );
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
            return [];
        }

        return array_values(array_filter($chunks, fn (mixed $chunk): bool => is_string($chunk) && $chunk !== ''));
    }

    public static function alpineChunkSrc(string $chunk): string
    {
        return FilamentAsset::getAlpineComponentSrc(
            str_replace('.js', '', $chunk),
            FilamentFlexFieldsPlugin::PACKAGE_NAME,
        );
    }
}

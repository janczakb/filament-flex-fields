<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;

/**
 * Maps playground hub slugs to package `art/*.webp` previews (GitHub raw URLs).
 * Missing entries intentionally fall back to the hub Gravity icon in the catalog UI.
 */
final class PlaygroundArtCatalog
{
    public const ART_BRANCH = 'main';

    /**
     * Hub slug → art filename under package `/art`.
     *
     * @var array<string, string>
     */
    private const ART_BY_SLUG = [
        'hold-confirm' => 'sc-21.webp',
        'select-field' => 'drawer-mobile.webp',
        'phone-field' => 'phone-field.webp',
        'icon-picker-field' => 'sc-32.webp',
        'dual-listbox' => 'sc-8.webp',
        'bubble-choice' => 'bubble.webp',
        'todo-list-field' => 'todolist.webp',
        'date-time-fields' => 'sc-26.webp',
        'schedule-field' => 'sc-27.webp',
        'color-swatch' => 'sc-24.webp',
        'flex-color-picker' => 'sc-11.webp',
        'segment-control' => 'sc-17.webp',
        'number-stepper' => 'sc-13.webp',
        'track-slider' => 'sc-16.webp',
        'flex-slider' => 'sc-16.webp',
        'rating' => 'sc-20.webp',
        'nps-field' => 'sc-33.webp',
        'price-range' => 'sc-9.webp',
        'choice-cards' => 'sc-14.webp',
        'image-choice-cards' => 'sc-image-check.webp',
        'matrix-choice' => 'sc-2.webp',
        'item-card-group' => 'sc-7.webp',
        'cover-card' => 'sc-18.webp',
        'flex-text-input' => 'sc-25.webp',
        'flex-textarea' => 'sc-3.webp',
        'slug-field' => 'sc-22.webp',
        'translatable-fields' => 'sc-22.webp',
        'calculator-field' => 'sc-34.webp',
        'currency-field' => 'sc-5.webp',
        'credit-card' => 'sc-10.webp',
        'social-links-field' => 'sc-29.webp',
        'progress-bar' => 'sc-4.webp',
        'progress-circle' => 'sc-19.webp',
        'file-upload' => 'sc-31.webp',
        'video-field' => 'videofield.webp',
        'audio-field' => 'sc-12.webp',
        'voice-note-recorder-field' => 'sc-12.webp',
        'link-preview-field' => 'sc-28.webp',
        'barcode-scanner-field' => 'sc-30.webp',
        'signature-field' => 'sc-1.webp',
        'map-picker' => 'sc-6.webp',
    ];

    public static function artDirectory(): string
    {
        return dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'art';
    }

    public static function filenameFor(string $slug): ?string
    {
        $filename = self::ART_BY_SLUG[$slug] ?? null;

        if ($filename === null) {
            return null;
        }

        $path = self::artDirectory().DIRECTORY_SEPARATOR.$filename;

        if (! is_file($path)) {
            return null;
        }

        return $filename;
    }

    public static function urlFor(string $slug): ?string
    {
        $filename = self::filenameFor($slug);

        if ($filename === null) {
            return null;
        }

        return self::githubRawUrl($filename);
    }

    public static function githubRawUrl(string $filename): string
    {
        $repo = str_replace('https://github.com/', '', FilamentFlexFieldsPlugin::PACKAGE_URL);

        return 'https://raw.githubusercontent.com/'.$repo.'/'.self::ART_BRANCH.'/art/'.$filename;
    }

    /**
     * @return array<string, string>
     */
    public static function mappedFilenames(): array
    {
        return self::ART_BY_SLUG;
    }
}

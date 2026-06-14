<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class FlexFieldsConfig
{
    public static function isEnabled(): bool
    {
        return (bool) config('filament-flex-fields.enabled', true);
    }

    public static function getValuesColumn(): string
    {
        return (string) config('filament-flex-fields.values_column', 'flex_field_values');
    }

    public static function allowHttpMedia(): bool
    {
        return (bool) config('filament-flex-fields.security.allow_http_media', false);
    }

    public static function isPlaygroundEnabled(): bool
    {
        return (bool) config('filament-flex-fields.playground.enabled', false);
    }

    public static function getPlaygroundNavigationGroup(): ?string
    {
        $group = config('filament-flex-fields.playground.navigation_group');

        return is_string($group) ? $group : null;
    }

    public static function getPlaygroundNavigationSort(): ?int
    {
        $sort = config('filament-flex-fields.playground.navigation_sort');

        return is_int($sort) || is_numeric($sort) ? (int) $sort : null;
    }

    public static function getMapboxAccessToken(): ?string
    {
        $token = config('filament-flex-fields.mapbox.access_token');

        return is_string($token) && filled($token) ? $token : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getCurrencies(): array
    {
        return (array) config('filament-flex-fields.currencies', []);
    }

    public static function getSlugFieldTitle(): string
    {
        return (string) config('filament-flex-fields.slug.field_title', 'title');
    }

    public static function getSlugFieldSlug(): string
    {
        return (string) config('filament-flex-fields.slug.field_slug', 'slug');
    }

    public static function getSlugUrlHost(): ?string
    {
        $host = config('filament-flex-fields.slug.url_host');

        return is_string($host) && filled($host) ? $host : null;
    }

    /**
     * @return array<string, string>|null
     */
    public static function getSlugTranslatableLocales(): ?array
    {
        $locales = config('filament-flex-fields.slug.translatable_locales');

        return is_array($locales) ? $locales : null;
    }

    public static function getSlugSourceLocale(): ?string
    {
        $locale = config('filament-flex-fields.slug.slug_source_locale');

        return is_string($locale) && filled($locale) ? $locale : null;
    }

    public static function getSlugRequiredTitleLocales(): mixed
    {
        return config('filament-flex-fields.slug.required_title_locales');
    }

    public static function isSlugSpatieTranslatable(): bool
    {
        return (bool) config('filament-flex-fields.slug.spatie_translatable', false);
    }

    public static function getUiDefault(string $key, mixed $default = null): mixed
    {
        return config("filament-flex-fields.ui.{$key}", $default);
    }

    /**
     * @return array<string, string>|list<string>|null
     */
    public static function getTranslatableLocales(): ?array
    {
        $locales = config('filament-flex-fields.translatable.locales')
            ?? config('filament-flex-fields.slug.translatable_locales');

        return is_array($locales) ? $locales : null;
    }

    /**
     * @return array<string, string>|null
     */
    public static function getTranslatableLocaleLabels(): ?array
    {
        $labels = config('filament-flex-fields.translatable.locale_labels');

        return is_array($labels) ? $labels : null;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Slug;

use Illuminate\Support\Str;

class SlugGenerator
{
    public const string HOMEPAGE_SLUG = '/';

    public const string DEFAULT_SEPARATOR = '-';

    public static function patternForSeparator(string $separator = '-', bool $allowHomepage = false): string
    {
        $escapedSeparator = preg_quote($separator, '/');
        $segment = "[a-z0-9]+(?:{$escapedSeparator}[a-z0-9]+)*";

        if ($allowHomepage) {
            return "/^(?:\/|{$segment})$/";
        }

        return "/^{$segment}$/";
    }

    public static function fromString(string $value, string $separator = '-', ?int $maxLength = null, ?string $locale = null): string
    {
        $slug = $locale !== null
            ? Str::slug($value, $separator, $locale)
            : Str::slug($value, $separator);

        if ($maxLength !== null && $maxLength > 0 && strlen($slug) > $maxLength) {
            $slug = substr($slug, 0, $maxLength);
            $slug = rtrim($slug, $separator);
        }

        return $slug;
    }

    public static function normalize(string $value, string $separator = '-', bool $allowHomepage = false): string
    {
        $value = trim($value);

        if ($allowHomepage && $value === self::HOMEPAGE_SLUG) {
            return self::HOMEPAGE_SLUG;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9'.preg_quote($separator, '/').']+/', $separator, $value) ?? '';
        $value = trim($value, $separator);
        $value = preg_replace('/'.preg_quote($separator, '/').'+/', $separator, $value) ?? $value;

        return $value;
    }

    public static function isHomepage(string $value): bool
    {
        return trim($value) === self::HOMEPAGE_SLUG;
    }

    public static function toEditableSlug(string $value, bool $allowHomepage = false): string
    {
        return $allowHomepage && self::isHomepage($value) ? '' : $value;
    }

    public static function normalizeEditableSlug(string $value, string $separator = '-'): string
    {
        $value = trim($value);
        $value = strtolower($value);
        $value = str_replace('/', '', $value);
        $value = preg_replace('/[^a-z0-9'.preg_quote($separator, '/').']+/', $separator, $value) ?? '';
        $value = trim($value, $separator);
        $value = preg_replace('/'.preg_quote($separator, '/').'+/', $separator, $value) ?? $value;

        return $value;
    }

    public static function fromEditableSlug(string $value, string $separator = '-', bool $allowHomepage = false): string
    {
        $editable = self::normalizeEditableSlug($value, $separator);

        if ($allowHomepage && $editable === '') {
            return self::HOMEPAGE_SLUG;
        }

        return self::normalize($editable, $separator, $allowHomepage);
    }

    public static function permalinkSlugSegment(string $slug, ?string $path = null, bool $allowHomepage = false): string
    {
        if ($allowHomepage && self::isHomepage($slug)) {
            return '';
        }

        if ($slug === '') {
            return '';
        }

        $path ??= '';

        if (str_ends_with($path, '/')) {
            return $slug;
        }

        return '/'.$slug;
    }

    public static function shouldShowPermalinkSlugSeparator(?string $path, ?string $host): bool
    {
        return blank($path) && filled($host);
    }
}

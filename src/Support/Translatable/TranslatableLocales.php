<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Closure;

class TranslatableLocales
{
    /**
     * @param  array<string, string>|list<string>|Closure|null  $locales
     * @param  array<string, string>|Closure|null  $localeLabels
     * @return array<string, string>
     */
    public static function resolve(
        array|Closure|null $locales,
        array|Closure|null $localeLabels = null,
    ): array {
        if ($locales instanceof Closure) {
            $locales = $locales();
        }

        if ($localeLabels instanceof Closure) {
            $localeLabels = $localeLabels();
        }

        if ($locales === null) {
            $locales = FlexFieldsConfig::getTranslatableLocales();
        }

        if ($localeLabels === null) {
            $localeLabels = FlexFieldsConfig::getTranslatableLocaleLabels() ?? [];
        }

        if ($locales === null || ! is_array($locales) || $locales === []) {
            return [];
        }

        /** @var array<string, string> $labels */
        $labels = is_array($localeLabels) ? $localeLabels : [];

        $resolved = [];

        foreach ($locales as $key => $value) {
            if (is_int($key)) {
                $locale = strtolower((string) $value);
                $resolved[$locale] = (string) ($labels[$locale] ?? $labels[$value] ?? strtoupper($locale));

                continue;
            }

            $locale = strtolower((string) $key);
            $resolved[$locale] = (string) $value;
        }

        return $resolved;
    }
}

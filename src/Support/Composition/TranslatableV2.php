<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Composition;

/**
 * Translatable v2 helpers for locale maps (JSON columns, Spatie attributes, nested form state).
 */
final class TranslatableV2
{
    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, string>|list<string>  $locales
     * @return list<string>
     */
    public static function missingLocales(array $values, array $locales): array
    {
        $normalizedValues = self::normalizeLocaleKeys($values);
        $missing = [];

        foreach (self::resolveLocaleCodes($locales) as $locale) {
            if (self::isEmptyValue($normalizedValues[$locale] ?? null)) {
                $missing[] = $locale;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public static function copyFromLocale(array $values, string $from, string $to): array
    {
        $from = strtolower(trim($from));
        $to = strtolower(trim($to));

        if ($from === '' || $to === '' || $from === $to) {
            return $values;
        }

        $normalizedValues = self::normalizeLocaleKeys($values);

        if (! array_key_exists($from, $normalizedValues)) {
            return $values;
        }

        $normalizedValues[$to] = $normalizedValues[$from];

        return $normalizedValues;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fallbackValue(array $values, string $locale, ?string $fallbackLocale): mixed
    {
        $normalizedValues = self::normalizeLocaleKeys($values);
        $locale = strtolower(trim($locale));

        $value = $normalizedValues[$locale] ?? null;

        if (! self::isEmptyValue($value)) {
            return $value;
        }

        if ($fallbackLocale === null) {
            return null;
        }

        $fallbackLocale = strtolower(trim($fallbackLocale));

        if ($fallbackLocale === '' || $fallbackLocale === $locale) {
            return null;
        }

        $fallbackValue = $normalizedValues[$fallbackLocale] ?? null;

        return self::isEmptyValue($fallbackValue) ? null : $fallbackValue;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private static function normalizeLocaleKeys(array $values): array
    {
        $normalized = [];

        foreach ($values as $locale => $value) {
            if (! is_string($locale)) {
                continue;
            }

            $normalized[strtolower(trim($locale))] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>|list<string>  $locales
     * @return list<string>
     */
    private static function resolveLocaleCodes(array $locales): array
    {
        $resolved = [];

        foreach ($locales as $key => $value) {
            if (is_int($key)) {
                $resolved[] = strtolower(trim((string) $value));

                continue;
            }

            $resolved[] = strtolower(trim((string) $key));
        }

        return array_values(array_filter($resolved, fn (string $locale): bool => $locale !== ''));
    }

    private static function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            if ($value === []) {
                return true;
            }

            if (($value['type'] ?? null) === 'doc') {
                return blank($value['content'] ?? []);
            }
        }

        return blank($value);
    }
}

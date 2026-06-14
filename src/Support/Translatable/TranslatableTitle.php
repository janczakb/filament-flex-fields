<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Closure;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Str;

class TranslatableTitle
{
    /**
     * @param  array<string, string>|list<string>|Closure|null  $locales
     * @return array<string, string>
     */
    public static function resolveLocales(array|Closure|null $locales): array
    {
        if ($locales === null) {
            $locales = FlexFieldsConfig::getSlugTranslatableLocales();
        }

        if ($locales instanceof Closure) {
            $locales = $locales();
        }

        if (! is_array($locales) || $locales === []) {
            return [];
        }

        $resolved = [];

        foreach ($locales as $key => $value) {
            if (is_int($key)) {
                $locale = strtolower((string) $value);
                $resolved[$locale] = strtoupper($locale);

                continue;
            }

            $locale = strtolower((string) $key);
            $resolved[$locale] = (string) $value;
        }

        return $resolved;
    }

    /**
     * @param  array<string, string>  $locales
     */
    public static function resolveSlugSourceLocale(string|Closure|null $locale, array $locales): string
    {
        if ($locales === []) {
            return (string) config('app.locale', 'en');
        }

        if ($locale instanceof Closure) {
            $locale = $locale();
        }

        if (blank($locale)) {
            $locale = FlexFieldsConfig::getSlugSourceLocale()
                ?? config('app.locale', 'en');
        }

        $locale = strtolower((string) $locale);

        if (array_key_exists($locale, $locales)) {
            return $locale;
        }

        return array_key_first($locales);
    }

    public static function sourcePath(string $fieldTitle, string $locale): string
    {
        return "{$fieldTitle}.{$locale}";
    }

    /**
     * @param  array<string, string>  $locales
     */
    public static function activeTabIndex(array $locales, string $slugSourceLocale): int
    {
        $index = 1;

        foreach ($locales as $locale => $label) {
            if ($locale === $slugSourceLocale) {
                return $index;
            }

            $index++;
        }

        return 1;
    }

    /**
     * @return array<string, string>|null
     */
    public static function normalizeHydratedState(mixed $state): ?array
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_array($state)) {
            return self::filterLocaleValues($state);
        }

        if (! is_string($state)) {
            return null;
        }

        $decoded = json_decode($state, true);

        if (is_array($decoded)) {
            return self::filterLocaleValues($decoded);
        }

        return null;
    }

    /**
     * @param  array<mixed, mixed>  $state
     * @return array<string, string>
     */
    public static function filterLocaleValues(array $state): array
    {
        $normalized = [];

        foreach ($state as $locale => $value) {
            if (! is_string($locale) || ! is_scalar($value)) {
                continue;
            }

            $normalized[strtolower($locale)] = trim((string) $value);
        }

        return $normalized;
    }

    public static function configureHydration(Component $component, bool $spatieTranslatable): void
    {
        $component->afterStateHydrated(function (Component $component, mixed $state): void {
            $normalized = self::normalizeHydratedState($state);

            if ($normalized === null) {
                return;
            }

            $component->state($normalized);
        });

        if ($spatieTranslatable) {
            $component->dehydrateStateUsing(function (mixed $state): ?array {
                if (! is_array($state)) {
                    return null;
                }

                $filtered = array_filter(
                    self::filterLocaleValues($state),
                    fn (string $value): bool => $value !== '',
                );

                return $filtered === [] ? null : $filtered;
            });
        }
    }

    public static function isEnabled(array|Closure|null $locales): bool
    {
        return self::resolveLocales($locales) !== [];
    }

    public static function defaultPlaceholder(string $fieldTitle, string $locale): string
    {
        return Str::headline("{$fieldTitle} ({$locale})");
    }

    /**
     * @param  'all'|list<string>|Closure|null  $requiredTitleLocales
     * @param  array<string, string>  $locales
     * @return list<string>
     */
    public static function resolveRequiredLocales(
        array|string|Closure|null $requiredTitleLocales,
        array $locales,
        string $slugSourceLocale,
    ): array {
        if ($requiredTitleLocales instanceof Closure) {
            $requiredTitleLocales = $requiredTitleLocales();
        }

        if ($requiredTitleLocales === null) {
            $requiredTitleLocales = FlexFieldsConfig::getSlugRequiredTitleLocales();
        }

        if ($requiredTitleLocales === null) {
            return [$slugSourceLocale];
        }

        if ($requiredTitleLocales === 'all') {
            return array_keys($locales);
        }

        if (! is_array($requiredTitleLocales)) {
            return [$slugSourceLocale];
        }

        $resolved = [];

        foreach ($requiredTitleLocales as $locale) {
            $locale = strtolower((string) $locale);

            if (array_key_exists($locale, $locales)) {
                $resolved[] = $locale;
            }
        }

        return $resolved !== [] ? $resolved : [$slugSourceLocale];
    }

    /**
     * @param  list<string>  $requiredLocales
     */
    public static function isLocaleRequired(string $locale, array $requiredLocales): bool
    {
        return in_array(strtolower($locale), array_map(strtolower(...), $requiredLocales), true);
    }

    /**
     * @param  array<int, string|Closure>|Closure  $titleRules
     * @param  list<string>  $requiredLocales
     * @return array<int, string|Closure>
     */
    public static function rulesForLocale(array|Closure $titleRules, string $locale, array $requiredLocales): array
    {
        $rules = is_array($titleRules) ? $titleRules : ['required', 'string'];

        if (self::isLocaleRequired($locale, $requiredLocales)) {
            if (! in_array('required', $rules, true)) {
                $rules[] = 'required';
            }

            return $rules;
        }

        return array_values(array_filter(
            $rules,
            fn (string|Closure $rule): bool => $rule !== 'required',
        ));
    }
}

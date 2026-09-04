<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use DateTime;
use DateTimeZone;

class Timezones
{
    /**
     * @var array<string, array<string, array{id: string, label: string, offset: string, offset_seconds: int, region: string}>>
     */
    protected static array $metadataCache = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected static array $displayNameCache = [];

    protected static ?string $cachedDate = null;

    /**
     * @return list<string>
     */
    public static function allIdentifiers(): array
    {
        return timezone_identifiers_list();
    }

    public static function translationKey(string $identifier): string
    {
        return str_replace('/', '__', $identifier);
    }

    public static function formatOffset(string $timezone): string
    {
        $offsetSeconds = (new DateTimeZone($timezone))->getOffset(new DateTime('now', new DateTimeZone('UTC')));

        return self::formatOffsetSeconds($offsetSeconds);
    }

    public static function formatOffsetSeconds(int $offsetSeconds): string
    {
        $sign = $offsetSeconds >= 0 ? '+' : '-';
        $absolute = abs($offsetSeconds);
        $hours = intdiv($absolute, 3600);
        $minutes = intdiv($absolute % 3600, 60);

        return sprintf('UTC%s%02d:%02d', $sign, $hours, $minutes);
    }

    public static function offsetSeconds(string $timezone): int
    {
        return (new DateTimeZone($timezone))->getOffset(new DateTime('now', new DateTimeZone('UTC')));
    }

    public static function region(string $timezone): string
    {
        $parts = explode('/', $timezone, 2);

        return $parts[0] ?? $timezone;
    }

    public static function displayName(string $timezone, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($timezone === 'UTC') {
            return self::$displayNameCache[$locale][$timezone] = 'UTC';
        }

        if (isset(self::$displayNameCache[$locale][$timezone])) {
            return self::$displayNameCache[$locale][$timezone];
        }

        $translationKey = 'filament-flex-fields::timezones.'.self::translationKey($timezone);
        $translated = trans($translationKey, [], $locale);

        if (is_string($translated) && $translated !== $translationKey) {
            return self::$displayNameCache[$locale][$timezone] = $translated;
        }

        $city = self::exemplarCity($timezone, $locale) ?? self::humanizeIdentifier($timezone);

        return self::$displayNameCache[$locale][$timezone] = self::formatCityCountry(
            $city,
            self::countryName(self::countryCode($timezone), $locale),
        );
    }

    /**
     * ISO 3166-1 alpha-2 for the timezone's home country (`PL` for Europe/Warsaw).
     * World/offset zones (`UTC`, `Etc/GMT+1`) return null.
     */
    public static function countryCode(string $timezone): ?string
    {
        if ($timezone === 'UTC' || ! class_exists(\IntlTimeZone::class)) {
            return null;
        }

        try {
            $region = \IntlTimeZone::getRegion($timezone);
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($region) || strlen($region) !== 2) {
            return null;
        }

        $code = strtoupper($region);

        if (in_array($code, ['001', 'ZZ'], true)) {
            return null;
        }

        return $code;
    }

    public static function countryName(?string $countryCode, ?string $locale = null): ?string
    {
        if ($countryCode === null || $countryCode === '') {
            return null;
        }

        $code = strtoupper($countryCode);
        $locale ??= app()->getLocale();
        $translationKey = "filament-flex-fields::countries.{$code}";
        $translated = trans($translationKey, [], $locale);

        if (is_string($translated) && $translated !== $translationKey) {
            return $translated;
        }

        if (function_exists('locale_get_display_region')) {
            $fallback = locale_get_display_region('-'.$code, $locale);

            if (is_string($fallback) && $fallback !== '' && $fallback !== $code) {
                return $fallback;
            }
        }

        return null;
    }

    public static function formatCityCountry(string $city, ?string $country): string
    {
        $city = trim($city);
        $country = is_string($country) ? trim($country) : '';

        if ($country === '' || strcasecmp($city, $country) === 0) {
            return $city;
        }

        return $city.', '.$country;
    }

    /**
     * ICU exemplar city (`VVV`) — "Warsaw" / "Warszawa", never "Poland Time".
     */
    public static function exemplarCity(string $timezone, string $locale): ?string
    {
        if (! class_exists(\IntlDateFormatter::class)) {
            return null;
        }

        try {
            $formatter = new \IntlDateFormatter(
                $locale,
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                $timezone,
                null,
                'VVV',
            );
            $formatted = $formatter->format(0);

            if (! is_string($formatted) || $formatted === '') {
                return null;
            }

            if (preg_match('/^(GMT|UTC|Unknown)\b/i', $formatted) === 1 || str_contains($formatted, '/')) {
                return null;
            }

            return $formatted;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function humanizeIdentifier(string $timezone): string
    {
        if ($timezone === 'UTC') {
            return 'UTC';
        }

        $segments = explode('/', $timezone);
        $city = str_replace('_', ' ', (string) (end($segments) ?: $timezone));

        return $city !== '' ? $city : $timezone;
    }

    public static function label(string $timezone, ?string $locale = null): string
    {
        return self::displayName($timezone, $locale);
    }

    /**
     * @param  list<string>|null  $allowed
     */
    public static function fromBrowserTimezone(?array $allowed = null): ?string
    {
        if (! app()->runningInConsole()) {
            $configured = (string) config('app.timezone', 'UTC');

            if ($configured !== '' && $configured !== 'UTC') {
                $resolved = self::resolve($allowed);

                if (in_array($configured, $resolved, true)) {
                    return $configured;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $candidates
     * @param  list<string>|null  $allowed
     */
    public static function fromBrowserTimezoneCandidates(array $candidates, ?array $allowed = null): ?string
    {
        $resolved = array_flip(self::resolve($allowed));

        foreach ($candidates as $candidate) {
            $candidate = (string) $candidate;

            if ($candidate !== '' && isset($resolved[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  list<string>|null  $only
     * @param  list<string>  $except
     * @return list<string>
     */
    public static function resolve(?array $only = null, array $except = []): array
    {
        $supported = array_flip(self::allIdentifiers());
        $except = array_values($except);

        if ($only !== null) {
            $identifiers = [];

            foreach ($only as $identifier) {
                $normalized = (string) $identifier;

                if (! isset($supported[$normalized]) || in_array($normalized, $except, true)) {
                    continue;
                }

                $identifiers[] = $normalized;
            }

            sort($identifiers);

            return array_values(array_unique($identifiers));
        }

        $identifiers = array_values(array_filter(
            self::allIdentifiers(),
            fn (string $identifier): bool => ! in_array($identifier, $except, true),
        ));

        sort($identifiers);

        return $identifiers;
    }

    /**
     * @param  list<array{id: string, label: string, offset: string, offset_seconds: int, region: string}>  $timezones
     * @return list<array{id: string, label: string, offset: string, offset_seconds: int, region: string}>
     */
    public static function sortWithPreferredFirst(array $timezones, ?string $preferredIdentifier): array
    {
        if ($preferredIdentifier === null) {
            return $timezones;
        }

        $preferred = null;
        $rest = [];

        foreach ($timezones as $timezone) {
            if ($timezone['id'] === $preferredIdentifier) {
                $preferred = $timezone;

                continue;
            }

            $rest[] = $timezone;
        }

        if ($preferred === null) {
            return $timezones;
        }

        return [$preferred, ...$rest];
    }

    /**
     * @param  list<string>|null  $only
     * @param  list<string>  $except
     * @return list<array{id: string, label: string, offset: string, offset_seconds: int, region: string}>
     */
    public static function metadata(?array $only = null, array $except = [], ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $today = date('Y-m-d');

        if (self::$cachedDate !== $today) {
            self::$metadataCache = [];
            self::$displayNameCache = [];
            self::$cachedDate = $today;
        }

        $resolved = self::resolve($only, $except);
        $results = [];
        $now = null;

        foreach ($resolved as $identifier) {
            if (! isset(self::$metadataCache[$locale][$identifier])) {
                if ($now === null) {
                    $now = new DateTime('now', new DateTimeZone('UTC'));
                }
                $tz = new DateTimeZone($identifier);
                $offsetSeconds = $tz->getOffset($now);
                $offset = self::formatOffsetSeconds($offsetSeconds);

                self::$metadataCache[$locale][$identifier] = [
                    'id' => $identifier,
                    'label' => self::label($identifier, $locale),
                    'offset' => $offset,
                    'offset_seconds' => $offsetSeconds,
                    'region' => self::region($identifier),
                ];
            }

            $results[] = self::$metadataCache[$locale][$identifier];
        }

        return $results;
    }

    /**
     * @param  list<string>|null  $only
     * @param  list<string>  $except
     * @return array<string, array{label: string, description: string}>
     */
    public static function selectOptions(?array $only = null, array $except = [], ?string $locale = null): array
    {
        $options = [];

        foreach (self::metadata($only, $except, $locale) as $timezone) {
            $options[$timezone['id']] = [
                'label' => $timezone['label'],
                'description' => $timezone['offset'],
            ];
        }

        return $options;
    }
}

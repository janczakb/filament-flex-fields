<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use DateTime;
use DateTimeZone;

class Timezones
{
    /**
     * @var array<string, array{id: string, label: string, offset: string, offset_seconds: int, region: string}>
     */
    protected static array $metadataCache = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected static array $displayNameCache = [];

    protected static ?string $cachedDate = null;

    protected static ?string $cachedLocale = null;

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
        $translated = __($translationKey);

        if (is_string($translated) && $translated !== $translationKey) {
            return self::$displayNameCache[$locale][$timezone] = $translated;
        }

        if (class_exists(\IntlTimeZone::class)) {
            $intlTimezone = \IntlTimeZone::createTimeZone($timezone);

            if ($intlTimezone->getID() !== 'Etc/Unknown') {
                $display = $intlTimezone->getDisplayName(false, \IntlTimeZone::DISPLAY_GENERIC_LOCATION, $locale);

                if (is_string($display) && $display !== '') {
                    return self::$displayNameCache[$locale][$timezone] = $display;
                }
            }
        }

        return self::$displayNameCache[$locale][$timezone] = self::humanizeIdentifier($timezone);
    }

    public static function humanizeIdentifier(string $timezone): string
    {
        if ($timezone === 'UTC') {
            return 'UTC';
        }

        $parts = explode('/', $timezone, 2);

        if (count($parts) === 2) {
            return str_replace('_', ' ', $parts[1]);
        }

        return $timezone;
    }

    public static function label(string $timezone, ?string $locale = null): string
    {
        return self::displayName($timezone, $locale).' ('.self::formatOffset($timezone).')';
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
    public static function metadata(?array $only = null, array $except = []): array
    {
        $locale = app()->getLocale();
        $today = date('Y-m-d');

        if (self::$cachedDate !== $today || self::$cachedLocale !== $locale) {
            self::$metadataCache = [];
            self::$displayNameCache = [];
            self::$cachedDate = $today;
            self::$cachedLocale = $locale;
        }

        $resolved = self::resolve($only, $except);
        $results = [];
        $now = null;

        foreach ($resolved as $identifier) {
            if (! isset(self::$metadataCache[$identifier])) {
                if ($now === null) {
                    $now = new DateTime('now', new DateTimeZone('UTC'));
                }
                $tz = new DateTimeZone($identifier);
                $offsetSeconds = $tz->getOffset($now);
                $offset = self::formatOffsetSeconds($offsetSeconds);

                self::$metadataCache[$identifier] = [
                    'id' => $identifier,
                    'label' => self::label($identifier, $locale),
                    'offset' => $offset,
                    'offset_seconds' => $offsetSeconds,
                    'region' => self::region($identifier),
                ];
            }

            $results[] = self::$metadataCache[$identifier];
        }

        return $results;
    }

    /**
     * @param  list<string>|null  $only
     * @param  list<string>  $except
     * @return array<string, array{label: string, description: string}>
     */
    public static function selectOptions(?array $only = null, array $except = []): array
    {
        $options = [];

        foreach (self::metadata($only, $except) as $timezone) {
            $options[$timezone['id']] = [
                'label' => $timezone['label'],
                'description' => $timezone['offset'],
            ];
        }

        return $options;
    }
}

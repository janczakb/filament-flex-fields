<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use libphonenumber\PhoneNumberUtil;

class Countries
{
    /**
     * @var list<string>|null
     */
    private static ?array $allCodesCache = null;

    /**
     * @var array<string, array<string, string>>
     */
    private static array $nameCache = [];

    /**
     * @var array<string, int>|null
     */
    private static ?array $phoneRegionsCache = null;

    /**
     * @var array<string, array<string, array{code: string, name: string, dial_code: string|null, flag_url: string}>>
     */
    private static array $metadataCache = [];

    /**
     * @var array<string, string>
     */
    private const LANGUAGE_COUNTRY_MAP = [
        'pl' => 'PL',
        'de' => 'DE',
        'fr' => 'FR',
        'es' => 'ES',
        'it' => 'IT',
        'pt' => 'PT',
        'nl' => 'NL',
        'sv' => 'SE',
        'nb' => 'NO',
        'nn' => 'NO',
        'da' => 'DK',
        'fi' => 'FI',
        'cs' => 'CZ',
        'sk' => 'SK',
        'uk' => 'UA',
        'ru' => 'RU',
        'ja' => 'JP',
        'ko' => 'KR',
        'zh' => 'CN',
        'en' => 'US',
    ];

    /**
     * @return list<string>
     */
    public static function allCodes(): array
    {
        if (self::$allCodesCache === null) {
            /** @var list<string> $codes */
            $codes = require __DIR__.'/../Data/iso3166-alpha2-codes.php';
            self::$allCodesCache = $codes;
        }

        return self::$allCodesCache;
    }

    public static function flagUrl(string $countryCode): string
    {
        return PhoneCountries::flagUrl($countryCode);
    }

    public static function name(string $countryCode): string
    {
        $code = strtoupper($countryCode);
        $locale = app()->getLocale();

        if (isset(self::$nameCache[$locale][$code])) {
            return self::$nameCache[$locale][$code];
        }

        $translationKey = "filament-flex-fields::countries.{$code}";
        $translated = __($translationKey);

        if (is_string($translated) && $translated !== $translationKey) {
            return self::$nameCache[$locale][$code] = $translated;
        }

        $fallback = locale_get_display_region('-'.$code, $locale);

        if (is_string($fallback) && $fallback !== '' && $fallback !== $code) {
            return self::$nameCache[$locale][$code] = $fallback;
        }

        return self::$nameCache[$locale][$code] = $code;
    }

    public static function dialCode(string $countryCode): ?string
    {
        $code = strtoupper($countryCode);

        if (self::$phoneRegionsCache === null) {
            self::$phoneRegionsCache = array_flip(PhoneNumberUtil::getInstance()->getSupportedRegions());
        }

        if (! isset(self::$phoneRegionsCache[$code])) {
            return null;
        }

        return PhoneCountries::dialCode($code);
    }

    /**
     * @param  list<string>|null  $allowed
     */
    public static function fromBrowserLocale(?array $allowed = null, ?string $locale = null): ?string
    {
        if ($locale !== null) {
            return self::matchLocaleToCountry($locale, $allowed);
        }

        if (! app()->runningInConsole()) {
            $languages = request()->getLanguages();

            foreach ($languages as $language) {
                $matched = self::matchLocaleToCountry($language, $allowed);

                if ($matched !== null) {
                    return $matched;
                }
            }
        }

        $appLocale = app()->getLocale();

        if (filled($appLocale)) {
            return self::matchLocaleToCountry($appLocale, $allowed);
        }

        return null;
    }

    /**
     * @param  list<string>  $languages
     * @param  list<string>|null  $allowed
     */
    public static function fromBrowserLanguages(array $languages, ?array $allowed = null): ?string
    {
        foreach ($languages as $language) {
            $matched = self::matchLocaleToCountry((string) $language, $allowed);

            if ($matched !== null) {
                return $matched;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public static function browserLanguageCountryMap(): array
    {
        return self::LANGUAGE_COUNTRY_MAP;
    }

    /**
     * @param  list<string>|null  $only
     * @param  list<string>  $except
     * @return list<string>
     */
    public static function resolve(?array $only = null, array $except = []): array
    {
        $supported = array_flip(self::allCodes());
        $except = array_map(strtoupper(...), $except);

        if ($only !== null) {
            $codes = [];

            foreach ($only as $code) {
                $normalized = strtoupper((string) $code);

                if (strlen($normalized) !== 2 || ! isset($supported[$normalized])) {
                    continue;
                }

                if (in_array($normalized, $except, true)) {
                    continue;
                }

                $codes[] = $normalized;
            }

            sort($codes);

            return array_values(array_unique($codes));
        }

        $codes = array_values(array_filter(
            self::allCodes(),
            fn (string $code): bool => ! in_array($code, $except, true),
        ));

        sort($codes);

        return $codes;
    }

    /**
     * @param  list<array{code: string, name: string, dial_code: string|null, flag_url: string}>  $countries
     * @return list<array{code: string, name: string, dial_code: string|null, flag_url: string}>
     */
    public static function sortWithPreferredFirst(array $countries, ?string $preferredCode): array
    {
        if ($preferredCode === null) {
            return $countries;
        }

        $preferredCode = strtoupper($preferredCode);
        $preferred = null;
        $rest = [];

        foreach ($countries as $country) {
            if ($country['code'] === $preferredCode) {
                $preferred = $country;

                continue;
            }

            $rest[] = $country;
        }

        if ($preferred === null) {
            return $countries;
        }

        return [$preferred, ...$rest];
    }

    /**
     * @param  list<string>|null  $only
     * @param  list<string>  $except
     * @return list<array{code: string, name: string, dial_code: string|null, flag_url: string}>
     */
    public static function metadata(?array $only = null, array $except = []): array
    {
        $locale = app()->getLocale();

        if (! isset(self::$metadataCache[$locale])) {
            self::$metadataCache[$locale] = [];
        }

        $resolved = self::resolve($only, $except);
        $results = [];

        foreach ($resolved as $code) {
            if (! isset(self::$metadataCache[$locale][$code])) {
                self::$metadataCache[$locale][$code] = [
                    'code' => $code,
                    'name' => self::name($code),
                    'dial_code' => self::dialCode($code),
                    'flag_url' => self::flagUrl($code),
                ];
            }

            $results[] = self::$metadataCache[$locale][$code];
        }

        return $results;
    }

    /**
     * @param  list<string>|null  $only
     * @param  list<string>  $except
     * @return array<string, array{label: string, image: string, description: string}>
     */
    public static function selectOptions(?array $only = null, array $except = []): array
    {
        $options = [];

        foreach (self::metadata($only, $except) as $country) {
            $options[$country['code']] = [
                'label' => $country['name'],
                'image' => $country['flag_url'],
                'description' => $country['dial_code'] ?? '',
            ];
        }

        return $options;
    }

    /**
     * @param  list<string>|null  $allowed
     */
    protected static function matchLocaleToCountry(string $locale, ?array $allowed = null): ?string
    {
        $allowedSet = array_flip(self::resolve($allowed));

        if (preg_match('/[-_](?<region>[A-Za-z]{2})$/', $locale, $matches)) {
            $region = strtoupper($matches['region']);

            if (isset($allowedSet[$region])) {
                return $region;
            }
        }

        $language = strtolower(substr($locale, 0, 2));
        $mapped = self::LANGUAGE_COUNTRY_MAP[$language] ?? null;

        if ($mapped !== null && isset($allowedSet[$mapped])) {
            return $mapped;
        }

        return null;
    }
}

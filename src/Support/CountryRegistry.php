<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class CountryRegistry
{
    public const POOL_ISO = 'iso';

    public const POOL_PHONE = 'phone';

    /**
     * @param  list<string>  $pools
     * @param  array<string, list<string>>  $filters
     * @param  list<string>  $extraLocales
     * @return array{locale: string, pools: array<string, array<string, array{c: string, n: string, d: string, f: string}>>, filters?: array<string, list<string>>, locale_names?: array<string, array<string, array<string, string>>>}
     */
    public static function payload(array $pools, ?string $locale = null, array $filters = [], array $extraLocales = []): array
    {
        $locale ??= app()->getLocale();
        $pools = array_values(array_unique($pools));
        $payload = [
            'locale' => $locale,
            'pools' => [],
        ];

        foreach ($pools as $pool) {
            $payload['pools'][$pool] = self::poolMetadata($pool, $locale);
        }

        if ($filters !== []) {
            $payload['filters'] = $filters;
        }

        $extraLocales = array_values(array_unique(array_filter(
            $extraLocales,
            static fn (string $extraLocale): bool => $extraLocale !== '' && $extraLocale !== $locale,
        )));

        if ($extraLocales !== []) {
            $payload['locale_names'] = [];

            foreach ($extraLocales as $extraLocale) {
                $payload['locale_names'][$extraLocale] = [];

                foreach ($pools as $pool) {
                    $payload['locale_names'][$extraLocale][$pool] = self::poolNames($pool, $extraLocale);
                }
            }
        }

        return $payload;
    }

    /**
     * @return array<string, array{c: string, n: string, d: string, f: string}>
     */
    public static function poolMetadata(string $pool, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return match ($pool) {
            self::POOL_ISO => self::isoPoolMetadata($locale),
            self::POOL_PHONE => self::phonePoolMetadata($locale),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function poolNames(string $pool, string $locale): array
    {
        $names = [];

        foreach (self::poolMetadata($pool, $locale) as $code => $country) {
            $names[$code] = $country['n'];
        }

        return $names;
    }

    /**
     * @param  array{c: string, n: string, d: string, f: string}  $country
     * @return array{code: string, name: string, dial_code: string|null, flag_url: string}
     */
    public static function expand(array $country): array
    {
        $dialCode = $country['d'] ?? '';

        return [
            'code' => $country['c'],
            'name' => $country['n'],
            'dial_code' => $dialCode !== '' ? $dialCode : null,
            'flag_url' => $country['f'],
        ];
    }

    /**
     * @param  array{code: string, name: string, dial_code: string|null, flag_url: string}  $country
     * @return array{c: string, n: string, d: string, f: string}
     */
    public static function compact(array $country): array
    {
        return [
            'c' => $country['code'],
            'n' => $country['name'],
            'd' => $country['dial_code'] ?? '',
            'f' => $country['flag_url'],
        ];
    }

    /**
     * @return array<string, array{c: string, n: string, d: string, f: string}>
     */
    protected static function isoPoolMetadata(string $locale): array
    {
        $map = [];

        foreach (Countries::metadata(locale: $locale) as $country) {
            $map[$country['code']] = self::compact($country);
        }

        return $map;
    }

    /**
     * @return array<string, array{c: string, n: string, d: string, f: string}>
     */
    protected static function phonePoolMetadata(string $locale): array
    {
        $map = [];

        foreach (PhoneCountries::metadata(locale: $locale) as $country) {
            $map[$country['code']] = self::compact([
                'code' => $country['code'],
                'name' => $country['name'],
                'dial_code' => $country['dial_code'],
                'flag_url' => $country['flag_url'],
            ]);
        }

        return $map;
    }
}

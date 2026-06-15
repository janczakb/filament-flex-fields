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
     * @return array{locale: string, pools: array<string, array<string, array{c: string, n: string, d: string, f: string}>>, filters?: array<string, list<string>>}
     */
    public static function payload(array $pools, ?string $locale = null, array $filters = []): array
    {
        $locale ??= app()->getLocale();
        $payload = [
            'locale' => $locale,
            'pools' => [],
        ];

        foreach (array_values(array_unique($pools)) as $pool) {
            $payload['pools'][$pool] = self::poolMetadata($pool);
        }

        if ($filters !== []) {
            $payload['filters'] = $filters;
        }

        return $payload;
    }

    /**
     * @return array<string, array{c: string, n: string, d: string, f: string}>
     */
    public static function poolMetadata(string $pool): array
    {
        return match ($pool) {
            self::POOL_ISO => self::isoPoolMetadata(),
            self::POOL_PHONE => self::phonePoolMetadata(),
            default => [],
        };
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
    protected static function isoPoolMetadata(): array
    {
        $map = [];

        foreach (Countries::metadata() as $country) {
            $map[$country['code']] = self::compact($country);
        }

        return $map;
    }

    /**
     * @return array<string, array{c: string, n: string, d: string, f: string}>
     */
    protected static function phonePoolMetadata(): array
    {
        $map = [];

        foreach (PhoneCountries::metadata() as $country) {
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

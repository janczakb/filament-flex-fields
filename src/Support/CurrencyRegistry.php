<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class CurrencyRegistry
{
    public const POOL_ISO = 'iso';

    /**
     * Inline `@js` metadata stays lean below this size; larger whitelists
     * share a deferred registry template (CountryRegistry pattern).
     */
    public const INLINE_METADATA_MAX = 12;

    /**
     * @param  list<string>  $pools
     * @param  array<string, list<string>>  $filters
     * @return array{locale: string, pools: array<string, array<string, array{s: string, n: string, d: int, l: string}>>, filters?: array<string, list<string>>}
     */
    public static function payload(array $pools, ?string $locale = null, array $filters = []): array
    {
        $locale ??= app()->getLocale();
        $pools = array_values(array_unique($pools));
        $payload = [
            'locale' => $locale,
            'pools' => [],
        ];

        foreach ($pools as $pool) {
            $payload['pools'][$pool] = self::poolMetadata($pool);
        }

        if ($filters !== []) {
            $payload['filters'] = $filters;
        }

        return $payload;
    }

    /**
     * @return array<string, array{s: string, n: string, d: int, l: string}>
     */
    public static function poolMetadata(string $pool): array
    {
        return match ($pool) {
            self::POOL_ISO => self::isoPoolMetadata(),
            default => [],
        };
    }

    /**
     * @param  array{s: string, n: string, d: int, l: string}  $currency
     * @return array{code: string, symbol: string, name: string, decimals: int, locale: string}
     */
    public static function expand(string $code, array $currency): array
    {
        return [
            'code' => $code,
            'symbol' => $currency['s'],
            'name' => $currency['n'],
            'decimals' => $currency['d'],
            'locale' => $currency['l'],
        ];
    }

    /**
     * @return array<string, array{s: string, n: string, d: int, l: string}>
     */
    protected static function isoPoolMetadata(): array
    {
        $map = [];

        foreach (CurrencyCountries::metadata() as $currency) {
            $map[$currency['code']] = [
                's' => $currency['symbol'],
                'n' => $currency['name'],
                'd' => $currency['decimals'],
                'l' => $currency['locale'],
            ];
        }

        return $map;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class TimezoneRegistry
{
    public const POOL_IANA = 'iana';

    /**
     * @param  list<string>  $pools
     * @param  array<string, list<string>>  $filters
     * @param  list<string>  $extraLocales
     * @return array{locale: string, pools: array<string, array<string, array{l: string, o: string, r: string}>>, filters?: array<string, list<string>>, locale_names?: array<string, array<string, array<string, string>>>}
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
     * @return array<string, array{l: string, o: string, r: string}>
     */
    public static function poolMetadata(string $pool, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return match ($pool) {
            self::POOL_IANA => self::ianaPoolMetadata($locale),
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function poolNames(string $pool, string $locale): array
    {
        $names = [];

        foreach (self::poolMetadata($pool, $locale) as $id => $timezone) {
            $names[$id] = $timezone['l'];
        }

        return $names;
    }

    /**
     * @param  array{l: string, o: string, r: string}  $timezone
     * @return array{id: string, label: string, offset: string, region: string}
     */
    public static function expand(string $id, array $timezone, ?string $label = null): array
    {
        return [
            'id' => $id,
            'label' => $label ?? $timezone['l'],
            'offset' => $timezone['o'],
            'region' => $timezone['r'],
        ];
    }

    /**
     * Compact id → [label, offset] map for blocking browser-timezone SSR boot.
     *
     * @param  array<string, array{l: string, o: string, r: string}>  $pool
     * @return array<string, array{0: string, 1: string}>
     */
    public static function bootCatalogFromPool(array $pool): array
    {
        $catalog = [];

        foreach ($pool as $id => $timezone) {
            $catalog[$id] = [$timezone['l'], $timezone['o']];
        }

        return $catalog;
    }

    /**
     * @return array<string, array{l: string, o: string, r: string}>
     */
    protected static function ianaPoolMetadata(string $locale): array
    {
        $map = [];

        foreach (Timezones::metadata(locale: $locale) as $timezone) {
            $map[$timezone['id']] = [
                'l' => $timezone['label'],
                'o' => $timezone['offset'],
                'r' => $timezone['region'],
            ];
        }

        return $map;
    }
}

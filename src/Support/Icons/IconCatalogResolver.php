<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Icons;

use BladeUI\Icons\Factory;
use BladeUI\Icons\IconsManifest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IconCatalogResolver
{
    public const int DEFAULT_PER_PAGE = 64;

    public const int MAX_PER_PAGE = 96;

    public function __construct(
        private Factory $factory,
        private IconsManifest $manifest,
    ) {}

    /**
     * @param  array<string, array{prefix: string, label: string, icons: list<string>}>  $catalog
     * @param  list<string>  $whitelist
     * @param  list<string>  $exclude
     */
    public function indexFor(
        array $catalog,
        array $whitelist = [],
        array $exclude = [],
        ?int $limitPerSet = null,
    ): IconCatalogIndex {
        $fingerprint = md5(json_encode([
            array_keys($catalog),
            $whitelist,
            $exclude,
            $limitPerSet,
        ], JSON_THROW_ON_ERROR));

        $cacheKey = 'fff.icon-index.'.$fingerprint;
        $ttlDays = (int) config('filament-flex-fields.ui.icon_picker_index_cache_days', 7);

        return Cache::remember($cacheKey, now()->addDays(max(1, $ttlDays)), function () use ($catalog, $whitelist, $exclude, $limitPerSet): IconCatalogIndex {
            return $this->buildIndex($catalog, $whitelist, $exclude, $limitPerSet);
        });
    }

    /**
     * @param  list<string>  $setNames
     * @return array<string, array{prefix: string, label: string, icons: list<string>}>
     */
    public function buildCatalogFromManifest(array $setNames): array
    {
        $setNames = array_values(array_unique($setNames));
        sort($setNames);

        if ($setNames === []) {
            return [];
        }

        $bundledManifest = $this->readBundledManifest();

        if ($bundledManifest !== null) {
            return array_intersect_key($bundledManifest, array_flip($setNames));
        }

        return $this->buildCatalogFromBladeIcons($setNames);
    }

    /**
     * @return array<string, array{prefix: string, label: string, icons: list<string>}>|null
     */
    protected function readBundledManifest(): ?array
    {
        if (! config('filament-flex-fields.ui.icon_picker_use_bundled_manifest', true)) {
            return null;
        }

        $path = dirname(__DIR__, 3).'/resources/dist/icon-catalog-manifest.json';

        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  list<string>  $setNames
     * @return array<string, array{prefix: string, label: string, icons: list<string>}>
     */
    protected function buildCatalogFromBladeIcons(array $setNames): array
    {
        $sets = $this->factory->all();
        $filteredSets = array_intersect_key($sets, array_flip($setNames));
        $manifest = $this->manifest->getManifest($filteredSets);
        $catalog = [];

        foreach ($setNames as $setName) {
            if (! isset($sets[$setName], $manifest[$setName])) {
                continue;
            }

            $prefix = (string) ($sets[$setName]['prefix'] ?? $setName);
            $icons = [];

            foreach ($manifest[$setName] as $pathIcons) {
                if (! is_array($pathIcons)) {
                    continue;
                }

                foreach ($pathIcons as $icon) {
                    if (! is_string($icon) || $icon === '') {
                        continue;
                    }

                    $icons[] = $this->fullIconName($prefix, $icon);
                }
            }

            $icons = array_values(array_unique($icons));
            sort($icons, SORT_NATURAL | SORT_FLAG_CASE);

            $catalog[$setName] = [
                'prefix' => $prefix,
                'label' => $this->formatSetLabel($setName, $prefix),
                'icons' => $icons,
            ];
        }

        return $catalog;
    }

    /**
     * @param  array<string, array{prefix: string, label: string, icons: list<string>}>  $catalog
     * @param  list<string>  $whitelist
     * @param  list<string>  $exclude
     */
    protected function buildIndex(
        array $catalog,
        array $whitelist,
        array $exclude,
        ?int $limitPerSet,
    ): IconCatalogIndex {
        $excludeLookup = array_fill_keys(array_map(
            static fn (string $icon): string => mb_strtolower(trim($icon)),
            $exclude,
        ), true);

        $entries = [];
        $allowedLookup = [];
        $setSummaries = [];

        if ($whitelist !== []) {
            foreach ($whitelist as $icon) {
                $icon = trim($icon);

                if ($icon === '' || isset($excludeLookup[mb_strtolower($icon)])) {
                    continue;
                }

                $setKey = $this->resolveIconSetKey($catalog, $icon);

                if ($setKey === null) {
                    continue;
                }

                $label = $this->formatIconLabel($icon);
                $entries[] = [
                    'name' => $icon,
                    'label' => $label,
                    'set' => $setKey,
                    'nameLower' => mb_strtolower($icon),
                    'labelLower' => mb_strtolower($label),
                ];
                $allowedLookup[$icon] = true;
            }

            usort($entries, static fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']));

            foreach ($catalog as $key => $setData) {
                $count = count(array_filter(
                    $entries,
                    static fn (array $entry): bool => $entry['set'] === $key,
                ));

                if ($count === 0) {
                    continue;
                }

                $setSummaries[] = [
                    'key' => $key,
                    'prefix' => $setData['prefix'],
                    'label' => $setData['label'],
                    'count' => $count,
                ];
            }

            return new IconCatalogIndex(
                $entries,
                $this->partitionEntriesBySet($entries),
                $allowedLookup,
                $setSummaries,
            );
        }

        foreach ($catalog as $setKey => $setData) {
            $setIcons = $setData['icons'];

            if ($limitPerSet !== null) {
                $setIcons = array_slice($setIcons, 0, max(0, $limitPerSet));
            }

            $count = 0;

            foreach ($setIcons as $icon) {
                if (isset($excludeLookup[mb_strtolower($icon)])) {
                    continue;
                }

                $label = $this->formatIconLabel($icon);
                $entries[] = [
                    'name' => $icon,
                    'label' => $label,
                    'set' => $setKey,
                    'nameLower' => mb_strtolower($icon),
                    'labelLower' => mb_strtolower($label),
                ];
                $allowedLookup[$icon] = true;
                $count++;
            }

            if ($count > 0) {
                $setSummaries[] = [
                    'key' => $setKey,
                    'prefix' => $setData['prefix'],
                    'label' => $setData['label'],
                    'count' => $count,
                ];
            }
        }

        return new IconCatalogIndex(
            $entries,
            $this->partitionEntriesBySet($entries),
            $allowedLookup,
            $setSummaries,
        );
    }

    /**
     * @param  list<array{name: string, label: string, set: string, nameLower: string, labelLower: string}>  $entries
     * @return array<string, list<array{name: string, label: string, set: string, nameLower: string, labelLower: string}>>
     */
    protected function partitionEntriesBySet(array $entries): array
    {
        $partitioned = [];

        foreach ($entries as $entry) {
            $partitioned[$entry['set']][] = $entry;
        }

        return $partitioned;
    }

    /**
     * @param  array<string, array{prefix: string, label: string, icons: list<string>}>  $catalog
     */
    protected function resolveIconSetKey(array $catalog, string $icon): ?string
    {
        foreach ($catalog as $setKey => $setData) {
            if (str_starts_with($icon, $setData['prefix'].'-')) {
                return $setKey;
            }
        }

        return null;
    }

    /**
     * @param  list<string>|string|null  $requested
     * @return list<string>
     */
    public function resolveSetNames(array|string|null $requested = null): array
    {
        $allSets = $this->factory->all();

        if ($requested === null) {
            return array_keys($allSets);
        }

        if (is_string($requested)) {
            $requested = [$requested];
        }

        $resolved = [];

        foreach ($requested as $value) {
            $value = trim($value);

            if ($value === '') {
                continue;
            }

            if (isset($allSets[$value])) {
                $resolved[] = $value;

                continue;
            }

            foreach ($allSets as $name => $config) {
                if (($config['prefix'] ?? '') === $value) {
                    $resolved[] = $name;
                }
            }
        }

        return array_values(array_unique($resolved));
    }

    /**
     * @param  list<string>  $setNames
     * @return array<string, array{prefix: string, label: string, icons: list<string>}>
     */
    public function catalogFor(array $setNames): array
    {
        $setNames = array_values(array_unique($setNames));
        sort($setNames);

        if ($setNames === []) {
            return [];
        }

        $cacheKey = 'fff.icon-catalog.'.md5(implode('|', $setNames));
        $ttlDays = (int) config('filament-flex-fields.ui.icon_picker_catalog_cache_days', 7);

        return Cache::remember($cacheKey, now()->addDays(max(1, $ttlDays)), function () use ($setNames): array {
            return $this->buildCatalogFromManifest($setNames);
        });
    }

    /**
     * @param  array<string, array{prefix: string, label: string, icons: list<string>}>  $catalog
     * @param  list<string>  $whitelist
     * @param  list<string>  $exclude
     * @return array{
     *     icons: list<array{name: string, label: string}>,
     *     total: int,
     *     page: int,
     *     perPage: int,
     *     hasMore: bool,
     *     sets: list<array{key: string, prefix: string, label: string, count: int}>
     * }
     */
    public function search(
        array $catalog,
        ?string $query = null,
        ?string $set = null,
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        array $whitelist = [],
        array $exclude = [],
        ?int $limitPerSet = null,
        bool $includeSetSummaries = false,
    ): array {
        return $this->indexFor($catalog, $whitelist, $exclude, $limitPerSet)
            ->search($query, $set, $page, $perPage, $includeSetSummaries);
    }

    public function fullIconName(string $prefix, string $icon): string
    {
        return $prefix.'-'.str_replace('.', '-', $icon);
    }

    /**
     * @param  array<string, array{prefix: string, label: string, icons: list<string>}>  $catalog
     * @param  list<string>  $whitelist
     * @param  list<string>  $exclude
     * @return list<string>
     */
    public function collectIcons(
        array $catalog,
        ?string $set = null,
        array $whitelist = [],
        array $exclude = [],
        ?int $limitPerSet = null,
    ): array {
        $excludeLookup = array_fill_keys(array_map(
            static fn (string $icon): string => mb_strtolower(trim($icon)),
            $exclude,
        ), true);

        if ($whitelist !== []) {
            $allowed = [];

            foreach ($whitelist as $icon) {
                $icon = trim($icon);

                if ($icon === '') {
                    continue;
                }

                $normalized = mb_strtolower($icon);

                if (isset($excludeLookup[$normalized])) {
                    continue;
                }

                if ($set !== null && ! $this->iconMatchesSet($catalog, $set, $icon)) {
                    continue;
                }

                $allowed[] = $icon;
            }

            sort($allowed, SORT_NATURAL | SORT_FLAG_CASE);

            return array_values(array_unique($allowed));
        }

        $icons = [];

        foreach ($catalog as $setKey => $setData) {
            if ($set !== null && $setKey !== $set && ($setData['prefix'] ?? '') !== $set) {
                continue;
            }

            $setIcons = $setData['icons'];

            if ($limitPerSet !== null) {
                $setIcons = array_slice($setIcons, 0, max(0, $limitPerSet));
            }

            foreach ($setIcons as $icon) {
                if (isset($excludeLookup[mb_strtolower($icon)])) {
                    continue;
                }

                $icons[] = $icon;
            }
        }

        sort($icons, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values(array_unique($icons));
    }

    /**
     * @param  array<string, array{prefix: string, label: string, icons: list<string>}>  $catalog
     */
    protected function iconMatchesSet(array $catalog, string $set, string $icon): bool
    {
        if (isset($catalog[$set])) {
            return str_starts_with($icon, $catalog[$set]['prefix'].'-');
        }

        foreach ($catalog as $setData) {
            if (($setData['prefix'] ?? '') === $set && str_starts_with($icon, $set.'-')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, array{prefix: string, label: string, icons: list<string>}>  $catalog
     * @param  list<string>  $whitelist
     * @param  list<string>  $exclude
     * @return list<array{key: string, prefix: string, label: string, count: int}>
     */
    public function summarizeSets(
        array $catalog,
        array $whitelist = [],
        array $exclude = [],
        ?int $limitPerSet = null,
    ): array {
        return $this->indexFor($catalog, $whitelist, $exclude, $limitPerSet)->setSummaries();
    }

    public function formatIconLabel(string $icon): string
    {
        $icon = trim($icon);

        if ($icon === '') {
            return '';
        }

        $name = str($icon)->after('-')->replace(['.', '-'], ' ')->trim();

        if ($name->isEmpty()) {
            return Str::headline($icon);
        }

        return Str::headline((string) $name);
    }

    protected function formatSetLabel(string $setName, string $prefix): string
    {
        return Str::headline(str_replace(['-', '_'], ' ', $prefix !== '' ? $prefix : $setName));
    }
}

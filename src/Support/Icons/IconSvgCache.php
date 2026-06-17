<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Icons;

use Illuminate\Support\Facades\Cache;

class IconSvgCache
{
    /**
     * @param  list<string>  $iconNames
     * @param  callable(list<string>): array<string, string>  $renderMissing
     * @return array<string, string>
     */
    public function rememberMany(array $iconNames, callable $renderMissing): array
    {
        $iconNames = array_values(array_unique(array_filter(
            array_map(static fn (mixed $icon): string => trim((string) $icon), $iconNames),
            static fn (string $icon): bool => $icon !== '',
        )));

        if ($iconNames === []) {
            return [];
        }

        $resolved = [];
        $missing = [];
        $keyMap = [];

        foreach ($iconNames as $icon) {
            $keyMap[$this->cacheKey($icon)] = $icon;
        }

        $cachedByKey = Cache::many(array_keys($keyMap));

        foreach ($keyMap as $cacheKey => $icon) {
            $cached = $cachedByKey[$cacheKey] ?? null;

            if (is_string($cached) && $cached !== '') {
                $resolved[$icon] = $cached;

                continue;
            }

            $missing[] = $icon;
        }

        if ($missing !== []) {
            $rendered = $renderMissing($missing);
            $ttl = $this->ttl();

            foreach ($rendered as $icon => $html) {
                if (! is_string($html) || $html === '') {
                    continue;
                }

                Cache::put($this->cacheKey($icon), $html, $ttl);
                $resolved[$icon] = $html;
            }
        }

        return $resolved;
    }

    protected function cacheKey(string $icon): string
    {
        return 'fff.icon-svg.'.md5(mb_strtolower(trim($icon)));
    }

    protected function ttl(): \DateTimeInterface|\DateInterval|int|null
    {
        $days = (int) config('filament-flex-fields.ui.icon_picker_svg_cache_days', 30);

        return now()->addDays(max(1, $days));
    }
}

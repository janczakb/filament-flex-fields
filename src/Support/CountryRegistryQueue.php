<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Illuminate\Support\Facades\View;

class CountryRegistryQueue
{
    /** @var array<string, true> */
    protected array $pools = [];

    /** @var array<string, list<string>> */
    protected array $filters = [];

    protected bool $scriptRendered = false;

    public function queuePool(string $pool): bool
    {
        if (isset($this->pools[$pool])) {
            return false;
        }

        $this->pools[$pool] = true;
        $this->scriptRendered = false;

        return true;
    }

    /**
     * @return list<string>
     */
    public function queuedPools(): array
    {
        return array_keys($this->pools);
    }

    /**
     * @return array<string, list<string>>
     */
    public function registeredFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param  list<string>  $codes
     */
    public function queueCountryFilter(array $codes): string
    {
        $normalized = array_values(array_unique(array_map(
            static fn (string $code): string => strtoupper($code),
            $codes,
        )));

        sort($normalized);

        $key = substr(hash('xxh128', implode(',', $normalized)), 0, 12);

        if (! isset($this->filters[$key])) {
            $this->filters[$key] = $normalized;
            $this->scriptRendered = false;
        }

        return $key;
    }

    public function renderScriptMarkup(): string
    {
        if ($this->scriptRendered || $this->pools === []) {
            return '';
        }

        $this->scriptRendered = true;

        return View::make('filament-flex-fields::partials.country-registry-data', [
            'pools' => $this->queuedPools(),
            'filters' => $this->registeredFilters(),
        ])->render();
    }

    public function clear(): void
    {
        $this->pools = [];
        $this->filters = [];
        $this->scriptRendered = false;
    }

    public static function enqueue(string $pool): bool
    {
        return app(self::class)->queuePool($pool);
    }

    /**
     * @return list<string>
     */
    public static function pools(): array
    {
        return app(self::class)->queuedPools();
    }

    public static function registerCountryFilter(array $codes): string
    {
        return app(self::class)->queueCountryFilter($codes);
    }

    public static function renderScriptOnce(): string
    {
        return app(self::class)->renderScriptMarkup();
    }

    public static function reset(): void
    {
        app(self::class)->clear();
    }
}

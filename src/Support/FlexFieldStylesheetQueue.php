<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class FlexFieldStylesheetQueue
{
    use FlexFieldAssetQueue;

    /**
     * Queue a component and its declared dependencies, returning only stylesheets
     * that have not been registered yet during the current request.
     *
     * @return list<string>
     */
    public function queueFor(string $component): array
    {
        $pending = [];

        foreach (FlexFieldAssets::stylesheetsFor($component) as $stylesheet) {
            if ($this->queue($stylesheet)) {
                $pending[] = $stylesheet;
            }
        }

        return $pending;
    }

    public function hasComponent(string $component): bool
    {
        return $this->hasKey($component);
    }

    /**
     * @return list<string>
     */
    public function enqueuedStylesheets(): array
    {
        return $this->registeredKeys();
    }

    /**
     * @return list<string>
     */
    public static function enqueueFor(string $component): array
    {
        return app(self::class)->queueFor($component);
    }

    public static function enqueue(string $component): bool
    {
        return app(self::class)->queue($component);
    }

    public static function has(string $component): bool
    {
        return app(self::class)->hasComponent($component);
    }

    public static function reset(): void
    {
        app(self::class)->clear();
    }

    /**
     * @return list<string>
     */
    public static function registered(): array
    {
        return app(self::class)->enqueuedStylesheets();
    }

    /**
     * @return list<string>
     */
    public static function pending(): array
    {
        return app(self::class)->pendingRegistered();
    }

    /**
     * @param  list<string>  $stylesheets
     */
    public static function markStylesheetsEmitted(array $stylesheets): void
    {
        app(self::class)->markEmitted($stylesheets);
    }
}

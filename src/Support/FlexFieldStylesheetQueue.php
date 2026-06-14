<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class FlexFieldStylesheetQueue
{
    /** @var array<string, true> */
    protected array $enqueued = [];

    /**
     * Queue a component and its declared dependencies, returning only stylesheets
     * that have not been output yet during the current request.
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

    public function queue(string $component): bool
    {
        if (isset($this->enqueued[$component])) {
            return false;
        }

        $this->enqueued[$component] = true;

        return true;
    }

    public function hasComponent(string $component): bool
    {
        return isset($this->enqueued[$component]);
    }

    public function clear(): void
    {
        $this->enqueued = [];
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
}

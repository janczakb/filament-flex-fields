<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class FlexFieldAlpineQueue
{
    /** @var array<string, true> */
    protected array $preloaded = [];

    /**
     * Queue shared Alpine chunks for a component, returning only chunks
     * that have not been preloaded yet during the current request.
     *
     * @return list<string>
     */
    public function queueChunksFor(string $component): array
    {
        $pending = [];

        foreach (FlexFieldAssets::alpineChunksFor($component) as $chunk) {
            if ($this->queue($chunk)) {
                $pending[] = $chunk;
            }
        }

        return $pending;
    }

    public function queue(string $chunk): bool
    {
        if (isset($this->preloaded[$chunk])) {
            return false;
        }

        $this->preloaded[$chunk] = true;

        return true;
    }

    public function hasChunk(string $chunk): bool
    {
        return isset($this->preloaded[$chunk]);
    }

    public function clear(): void
    {
        $this->preloaded = [];
    }

    /**
     * @return list<string>
     */
    public static function enqueueChunksFor(string $component): array
    {
        return app(self::class)->queueChunksFor($component);
    }

    public static function enqueue(string $chunk): bool
    {
        return app(self::class)->queue($chunk);
    }

    public static function has(string $chunk): bool
    {
        return app(self::class)->hasChunk($chunk);
    }

    public static function reset(): void
    {
        app(self::class)->clear();
    }
}

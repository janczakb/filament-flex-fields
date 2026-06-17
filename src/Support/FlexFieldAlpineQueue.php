<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

class FlexFieldAlpineQueue
{
    use FlexFieldAssetQueue;

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

    public function hasChunk(string $chunk): bool
    {
        return $this->hasKey($chunk);
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

    /**
     * @return list<string>
     */
    public static function pending(): array
    {
        return app(self::class)->pendingRegistered();
    }

    /**
     * @param  list<string>  $chunks
     */
    public static function markChunksEmitted(array $chunks): void
    {
        app(self::class)->markEmitted($chunks);
    }
}

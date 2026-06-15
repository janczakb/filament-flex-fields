<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Closure;

final class RatingColumnRenderCache
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function remember(string $key, Closure $callback): string
    {
        return self::$cache[$key] ??= $callback();
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    /**
     * @return array<string, string>
     */
    public static function entries(): array
    {
        return self::$cache;
    }
}

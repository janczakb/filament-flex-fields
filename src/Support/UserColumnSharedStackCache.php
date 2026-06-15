<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Closure;

final class UserColumnSharedStackCache
{
    /** @var array<string, mixed> */
    private static array $cache = [];

    public static function remember(string $key, Closure $callback): mixed
    {
        return self::$cache[$key] ??= $callback();
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function entries(): array
    {
        return self::$cache;
    }
}

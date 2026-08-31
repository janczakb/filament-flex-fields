<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support\Intelligence;

/**
 * Per-user remembered defaults and tenant-level default maps (in-memory foundation).
 */
final class SmartDefaults
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $userMemory = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $tenantDefaults = [];

    public static function remember(string $userKey, string $fieldKey, mixed $value): void
    {
        self::$userMemory[$userKey][$fieldKey] = $value;
    }

    public static function recall(string $userKey, string $fieldKey): mixed
    {
        return self::$userMemory[$userKey][$fieldKey] ?? null;
    }

    /**
     * @param  array<string, mixed>  $map
     */
    public static function tenantDefaults(string|int $tenantId, array $map): void
    {
        self::$tenantDefaults[(string) $tenantId] = $map;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getTenantDefaults(string|int $tenantId): array
    {
        return self::$tenantDefaults[(string) $tenantId] ?? [];
    }

    public static function clearUser(string $userKey): void
    {
        unset(self::$userMemory[$userKey]);
    }

    /**
     * @internal test helper
     */
    public static function reset(): void
    {
        self::$userMemory = [];
        self::$tenantDefaults = [];
    }
}

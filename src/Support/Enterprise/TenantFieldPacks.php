<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Enterprise;

final class TenantFieldPacks
{
    /** @var array<string, list<string>> */
    private static array $packs = [];

    /**
     * @param  list<string>  $fieldTypeKeys
     */
    public static function registerPack(string $tenantId, array $fieldTypeKeys): void
    {
        self::$packs[$tenantId] = array_values(array_unique($fieldTypeKeys));
    }

    /**
     * @return list<string>
     */
    public static function packFor(string $tenantId): array
    {
        return self::$packs[$tenantId] ?? [];
    }

    public static function clear(): void
    {
        self::$packs = [];
    }
}

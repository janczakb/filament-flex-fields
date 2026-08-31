<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Enterprise;

final class FieldRbacMatrix
{
    public const string ABILITY_VIEW = 'view';

    public const string ABILITY_EDIT = 'edit';

    public const string ABILITY_CONFIGURE = 'configure';

    /** @var array<string, array<string, array<string, bool>>> */
    private static array $matrix = [];

    public static function can(string $userKey, string $ability, string $fieldType): bool
    {
        if (! in_array($ability, [self::ABILITY_VIEW, self::ABILITY_EDIT, self::ABILITY_CONFIGURE], true)) {
            return true;
        }

        $entry = self::$matrix[$userKey][$fieldType][$ability] ?? null;

        if ($entry === null) {
            return true;
        }

        return $entry;
    }

    public static function grant(string $userKey, string $ability, string $fieldType): void
    {
        self::$matrix[$userKey][$fieldType][$ability] = true;
    }

    public static function deny(string $userKey, string $ability, string $fieldType): void
    {
        self::$matrix[$userKey][$fieldType][$ability] = false;
    }

    public static function reset(?string $userKey = null): void
    {
        if ($userKey === null) {
            self::$matrix = [];

            return;
        }

        unset(self::$matrix[$userKey]);
    }
}

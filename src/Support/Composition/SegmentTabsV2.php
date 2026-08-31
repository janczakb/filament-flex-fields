<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Composition;

/**
 * SegmentTabs v2 helpers for tab persistence (localStorage / URL query string).
 */
final class SegmentTabsV2
{
    private const PERSIST_PREFIX = 'fff-segment-tabs';

    public static function persistKey(string $tabsId): string
    {
        $tabsId = trim($tabsId);

        if ($tabsId === '') {
            return self::PERSIST_PREFIX;
        }

        return self::PERSIST_PREFIX.'::'.$tabsId;
    }

    public static function parsePersisted(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^[\w\-\:\.]+$/u', $value)) {
            return null;
        }

        return $value;
    }
}

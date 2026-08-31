<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support\Select;

final class HeadlessSelectFeatureFlags
{
    public static function useHeadlessEngine(): bool
    {
        // Headless combobox is always on at runtime; config keys remain for docs/tests only.
        return true;
    }

    public static function isFieldEligible(object $field): bool
    {
        return ! self::wouldUseNativeSelect($field);
    }

    private static function wouldUseNativeSelect(object $field): bool
    {
        if (! method_exists($field, 'isSearchable')
            || ! method_exists($field, 'isMultiple')
            || ! method_exists($field, 'isNative')) {
            return false;
        }

        $isHtmlAllowed = method_exists($field, 'isHtmlAllowed') && $field->isHtmlAllowed();

        return ! ($field->isSearchable() || $field->isMultiple() || $isHtmlAllowed) && $field->isNative();
    }
}

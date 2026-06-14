<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Filament\Forms\Components\Field;

/**
 * Evaluates locale tab state for badges and default active tab selection.
 */
final class TranslatableTabState
{
    public static function tabHasAnyFieldValue(SegmentTab $tab, callable $get): bool
    {
        foreach ($tab->getChildSchema()->getComponents() as $component) {
            if (! $component instanceof Field) {
                continue;
            }

            $statePath = $component->getStatePath(isAbsolute: false) ?? $component->getName();

            if (filled($get($statePath))) {
                return true;
            }

            if (filled(TranslatableHydrator::resolveRenderedState($component))) {
                return true;
            }
        }

        return false;
    }

    public static function resolveActiveTabWithValue(SegmentTabs $component, callable $get): int
    {
        $index = 1;

        foreach ($component->getVisibleTabs() as $tab) {
            if (self::tabHasAnyFieldValue($tab, $get)) {
                return $index;
            }

            $index++;
        }

        return 1;
    }
}

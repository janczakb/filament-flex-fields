<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns;

/**
 * Thin aliases kept only to ease migration from third-party translatable tab packages.
 * Prefer the first-class preset methods on TranslatableFields.
 */
trait HasTranslatableMigrationAliases
{
    public function addDirectionByLocale(): static
    {
        return $this->directionByLocale();
    }

    public function addEmptyBadgeWhenAllFieldsAreEmpty(?string $emptyLabel = null): static
    {
        return $this->emptyBadgeWhenAllFieldsAreEmpty($emptyLabel);
    }

    public function addSetActiveTabThatHasValue(): static
    {
        return $this->activeTabWithValue();
    }
}

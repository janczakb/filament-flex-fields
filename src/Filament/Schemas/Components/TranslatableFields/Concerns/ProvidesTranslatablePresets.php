<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns;

use Bjanczak\FilamentFlexFields\Enums\TranslatableDirectionScope;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableLocaleDirection;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableTabState;
use Filament\Forms\Components\Field;

trait ProvidesTranslatablePresets
{
    public function directionByLocale(TranslatableDirectionScope $scope = TranslatableDirectionScope::Auto): static
    {
        return $this->modifyFieldsUsing(function (Field $field, string $locale) use ($scope): void {
            TranslatableLocaleDirection::applyToField($field, $locale, $scope);
        });
    }

    public function emptyBadgeWhenAllFieldsAreEmpty(?string $emptyLabel = null): static
    {
        $emptyLabel ??= (string) config('filament-flex-fields.translatable.empty_badge_label', 'empty');

        return $this->modifyTabsUsing(function (TranslatableTab $tab) use ($emptyLabel): void {
            $tab
                ->badgeColor(function (TranslatableTab $component, callable $get): ?string {
                    return TranslatableTabState::tabHasAnyFieldValue($component, $get) ? null : 'warning';
                })
                ->badge(function (TranslatableTab $component, callable $get) use ($emptyLabel): ?string {
                    return TranslatableTabState::tabHasAnyFieldValue($component, $get) ? null : $emptyLabel;
                });
        });
    }

    public function activeTabWithValue(): static
    {
        return $this->activeTab(function (callable $get, TranslatableFields $component): int {
            return TranslatableTabState::resolveActiveTabWithValue($component, $get);
        });
    }

    /**
     * Recommended defaults for production forms.
     */
    public function withRecommendedDefaults(?string $emptyBadgeLabel = null): static
    {
        return $this
            ->directionByLocale()
            ->emptyBadgeWhenAllFieldsAreEmpty($emptyBadgeLabel)
            ->activeTabWithValue();
    }

    /**
     * Wrap tab panels in a bordered card (off by default — fields render flush).
     */
    public function borderedPanels(bool $condition = true): static
    {
        if ($condition) {
            $this->extraAttributes(['class' => 'fff-translatable-fields--bordered'], merge: true);
        }

        return $this;
    }
}

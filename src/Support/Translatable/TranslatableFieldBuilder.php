<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Closure;
use Filament\Forms\Components\Field;

/**
 * Backwards-compatible facade delegating to focused translatable services.
 */
final class TranslatableFieldBuilder
{
    public static function cloneFieldForLocale(Field $template, string $locale, bool $spatieTranslatable = false): Field
    {
        $tab = TranslatableTab::make($locale)->locale($locale);

        return TranslatableFieldFactory::make(
            template: $template,
            locale: $locale,
            tab: $tab,
            spatieTranslatable: $spatieTranslatable,
        );
    }

    public static function configureFieldHydration(Field $field, string $attribute, string $locale): void
    {
        TranslatableHydrator::applyToField($field, $attribute, $locale);
    }

    public static function tabHasAnyFieldValue($tab, callable $get): bool
    {
        return TranslatableTabState::tabHasAnyFieldValue($tab, $get);
    }

    public static function resolveActiveTabWithValue($component, callable $get): int
    {
        return TranslatableTabState::resolveActiveTabWithValue($component, $get);
    }

    /**
     * @param  array<string, string>  $locales
     * @param  list<Field>  $templates
     * @param  list<Closure>  $modifyTabsUsing
     * @param  list<Closure>  $modifyFieldsUsing
     * @return list<TranslatableTab>
     */
    public static function buildLocaleTabs(
        array $locales,
        array $templates,
        array $modifyTabsUsing = [],
        array $modifyFieldsUsing = [],
        bool $spatieTranslatable = false,
    ): array {
        $tabs = TranslatableTabFactory::make(
            locales: $locales,
            templates: $templates,
            spatieTranslatable: $spatieTranslatable,
        );

        foreach ($tabs as $tab) {
            $locale = $tab->getLocale();

            foreach ($modifyTabsUsing as $modifier) {
                $tab->evaluate($modifier, ['locale' => $locale, 'tab' => $tab]);
            }

            foreach ($tab->getChildSchema()->getComponents() as $field) {
                if (! $field instanceof Field) {
                    continue;
                }

                foreach ($modifyFieldsUsing as $modifier) {
                    $field->evaluate($modifier, ['locale' => $locale, 'tab' => $tab]);
                }
            }
        }

        return $tabs;
    }
}

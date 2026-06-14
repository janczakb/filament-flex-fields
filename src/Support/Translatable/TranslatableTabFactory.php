<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Closure;
use Filament\Forms\Components\Field;

/**
 * Builds locale tabs from field templates without applying user modifiers.
 */
final class TranslatableTabFactory
{
    /**
     * @param  array<string, string>  $locales
     * @param  list<Field>  $templates
     * @param  Closure(Field, string, TranslatableTab): Field|null  $localeFieldUsing
     * @param  Closure(Field): string|null  $storageAttributeUsing
     * @return list<TranslatableTab>
     */
    public static function make(
        array $locales,
        array $templates,
        bool $spatieTranslatable = false,
        ?Closure $localeFieldUsing = null,
        ?Closure $storageAttributeUsing = null,
    ): array {
        $tabs = [];

        foreach ($locales as $locale => $label) {
            $tab = TranslatableTab::make($label)->locale($locale);
            $fields = [];

            foreach ($templates as $template) {
                $fields[] = TranslatableFieldFactory::make(
                    template: $template,
                    locale: $locale,
                    tab: $tab,
                    spatieTranslatable: $spatieTranslatable,
                    localeFieldUsing: $localeFieldUsing,
                    storageAttributeUsing: $storageAttributeUsing,
                );
            }

            $tab->schema($fields);
            $tabs[] = $tab;
        }

        return $tabs;
    }
}

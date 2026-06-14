<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Closure;
use Filament\Forms\Components\Field;

/**
 * Clones template fields into per-locale form fields.
 */
final class TranslatableFieldFactory
{
    /**
     * @param  Closure(Field, string, TranslatableTab): Field|null  $localeFieldUsing
     * @param  Closure(Field): string|null  $storageAttributeUsing
     */
    public static function make(
        Field $template,
        string $locale,
        TranslatableTab $tab,
        bool $spatieTranslatable = false,
        ?Closure $localeFieldUsing = null,
        ?Closure $storageAttributeUsing = null,
    ): Field {
        if ($localeFieldUsing instanceof Closure) {
            $field = $template->evaluate($localeFieldUsing, [
                'template' => $template,
                'locale' => $locale,
                'tab' => $tab,
            ]);

            if ($field instanceof Field) {
                return $field;
            }
        }

        $localeStatePath = TranslatableAttributePath::localeStatePath($template, $locale);
        $storageAttribute = $storageAttributeUsing instanceof Closure
            ? (string) $template->evaluate($storageAttributeUsing, ['template' => $template, 'locale' => $locale])
            : TranslatableAttributePath::storageAttribute($template);

        $field = $template->getClone()
            ->name($localeStatePath)
            ->label($template->getLabel())
            ->statePath($localeStatePath);

        TranslatableHydrator::applyToField($field, $storageAttribute, $locale);

        if ($spatieTranslatable) {
            $field->dehydrateStateUsing(function (mixed $state): ?string {
                if (! is_string($state)) {
                    return null;
                }

                $trimmed = trim($state);

                return $trimmed === '' ? null : $trimmed;
            });
        }

        return $field;
    }
}

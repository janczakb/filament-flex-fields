<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Filament\Forms\Components\Field;
use Illuminate\Support\Str;

/**
 * Hydrates locale field state from JSON columns, Spatie translatable attributes, or nested form fill.
 */
final class TranslatableHydrator
{
    public static function resolveRenderedState(Field $field): mixed
    {
        $state = $field->getState();

        if (filled($state)) {
            return $state;
        }

        $livewire = $field->getLivewire();

        if ($livewire === null) {
            return null;
        }

        $absoluteStatePath = $field->getStatePath(isAbsolute: true);

        if (is_string($absoluteStatePath)) {
            $fromLivewire = data_get($livewire, $absoluteStatePath);

            if (filled($fromLivewire)) {
                return $fromLivewire;
            }

            if (str_contains($absoluteStatePath, '.')) {
                $locale = (string) str($absoluteStatePath)->afterLast('.');
                $parentPath = substr($absoluteStatePath, 0, -1 - strlen($locale));
                $translations = TranslatableTitle::normalizeHydratedState(data_get($livewire, $parentPath));

                if ($translations !== null) {
                    return $translations[$locale] ?? null;
                }
            }
        }

        $relativeStatePath = $field->getStatePath(isAbsolute: false);

        if (is_string($relativeStatePath) && str_contains($relativeStatePath, '.')) {
            $locale = (string) str($relativeStatePath)->afterLast('.');
            $storageAttribute = Str::beforeLast($relativeStatePath, ".{$locale}");

            return self::resolveLocaleState($field, $storageAttribute, $locale);
        }

        return null;
    }

    public static function applyToField(Field $field, string $storageAttribute, string $locale): void
    {
        $field
            ->default(fn (Field $component): mixed => self::resolveLocaleState($component, $storageAttribute, $locale))
            ->afterStateHydrated(function (Field $component, mixed $state, mixed $record) use ($storageAttribute, $locale): void {
                if (filled($state)) {
                    return;
                }

                if ($record) {
                    self::hydrateFromRecord($component, $record, $storageAttribute, $locale);
                }

                if (filled($component->getState())) {
                    return;
                }

                self::hydrateFromFormState($component, $storageAttribute, $locale);
            });
    }

    public static function resolveLocaleState(Field $component, string $storageAttribute, string $locale): mixed
    {
        $fromParent = self::resolveFromParentAttribute($component, $storageAttribute, $locale);

        if (filled($fromParent)) {
            return $fromParent;
        }

        return self::resolveFromLivewireParent($component, $locale);
    }

    public static function hydrateFromFormState(Field $component, string $storageAttribute, string $locale): void
    {
        $resolved = self::resolveLocaleState($component, $storageAttribute, $locale);

        if (filled($resolved)) {
            $component->state($resolved);
        }
    }

    public static function hydrateFromRecord(Field $component, mixed $record, string $storageAttribute, string $locale): void
    {
        if (SpatieTranslatableIntegration::modelUsesTranslatable($record, $storageAttribute)) {
            $translation = $record->getTranslation($storageAttribute, $locale, false);
            $component->state(filled($translation) ? (string) $translation : null);

            return;
        }

        $attributeValue = $record->getAttributes()[$storageAttribute] ?? null;
        $translations = TranslatableTitle::normalizeHydratedState($attributeValue);

        if ($translations === null) {
            return;
        }

        $component->state($translations[$locale] ?? null);
    }

    protected static function resolveFromParentAttribute(Field $component, string $storageAttribute, string $locale): mixed
    {
        $livewire = $component->getLivewire();

        if ($livewire === null) {
            return null;
        }

        $parentPath = $component->resolveRelativeStatePath($storageAttribute, isAbsolute: true);
        $translations = TranslatableTitle::normalizeHydratedState(data_get($livewire, $parentPath));

        if ($translations === null) {
            return null;
        }

        return $translations[$locale] ?? null;
    }

    protected static function resolveFromLivewireParent(Field $component, string $locale): mixed
    {
        $livewire = $component->getLivewire();

        if ($livewire === null) {
            return null;
        }

        $absoluteStatePath = $component->getStatePath(isAbsolute: true);

        if (! is_string($absoluteStatePath) || ! str_ends_with($absoluteStatePath, ".{$locale}")) {
            return null;
        }

        $parentPath = substr($absoluteStatePath, 0, -1 - strlen($locale));
        $translations = TranslatableTitle::normalizeHydratedState(data_get($livewire, $parentPath));

        if ($translations === null) {
            return null;
        }

        return $translations[$locale] ?? null;
    }
}

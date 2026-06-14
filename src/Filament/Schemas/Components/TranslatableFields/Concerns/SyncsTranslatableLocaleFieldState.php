<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableHydrator;
use Filament\Forms\Components\Field;
use Illuminate\Support\Str;

trait SyncsTranslatableLocaleFieldState
{
    public function fillStateWithNull(): void
    {
        parent::fillStateWithNull();

        $this->syncLocaleFieldStatesFromParent();
    }

    protected function syncLocaleFieldStatesFromParent(): void
    {
        foreach (parent::getChildSchema()?->getComponents() ?? [] as $tab) {
            if (! $tab instanceof TranslatableTab) {
                continue;
            }

            $locale = $tab->getLocale();

            foreach ($tab->getChildSchema()?->getComponents() ?? [] as $field) {
                if (! $field instanceof Field) {
                    continue;
                }

                $resolved = TranslatableHydrator::resolveRenderedState($field);

                if (filled($resolved)) {
                    if (blank($field->getState())) {
                        $field->state($resolved);
                    }

                    continue;
                }

                $relativeStatePath = $field->getStatePath(isAbsolute: false);

                if (! is_string($relativeStatePath) || ! str_ends_with($relativeStatePath, ".{$locale}")) {
                    continue;
                }

                $storageAttribute = Str::beforeLast($relativeStatePath, ".{$locale}");

                TranslatableHydrator::hydrateFromFormState($field, $storageAttribute, $locale);
            }
        }
    }
}

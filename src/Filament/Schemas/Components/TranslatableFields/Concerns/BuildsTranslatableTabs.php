<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableTabFactory;
use Filament\Forms\Components\Field;
use Filament\Schemas\Schema;
use RuntimeException;

trait BuildsTranslatableTabs
{
    /**
     * @return list<TranslatableTab>
     */
    public function buildTranslatableTabs(): array
    {
        /** @var list<Field> $templates */
        $templates = $this->evaluate($this->translatableFieldTemplates);
        $locales = $this->getLocales();

        if ($locales === []) {
            throw new RuntimeException('TranslatableFields requires at least one locale. Configure locales via ->locales() or config(filament-flex-fields.translatable.locales).');
        }

        foreach ($templates as $template) {
            if (! $template instanceof Field) {
                throw new RuntimeException('TranslatableFields schema only supports '.Field::class.' instances.');
            }
        }

        return TranslatableTabFactory::make(
            locales: $locales,
            templates: $templates,
            spatieTranslatable: $this->shouldUseSpatieTranslatable(),
            localeFieldUsing: $this->translatableLocaleFieldUsing,
            storageAttributeUsing: $this->translatableStorageAttributeUsing,
        );
    }

    public function getChildSchema($key = null): ?Schema
    {
        $this->syncLocaleFieldStatesFromParent();

        $schema = parent::getChildSchema($key);

        if ($schema === null) {
            return null;
        }

        foreach ($schema->getComponents() as $tab) {
            if (! $tab instanceof TranslatableTab) {
                continue;
            }

            $locale = $tab->getLocale();

            foreach ($this->getTranslatableTabModifiers() as $modifier) {
                $tab->evaluate($modifier, [
                    'locale' => $locale,
                    'tab' => $tab,
                ]);
            }

            foreach ($tab->getChildSchema()->getComponents() as $field) {
                if (! $field instanceof Field) {
                    continue;
                }

                foreach ($this->getTranslatableFieldModifiers() as $modifier) {
                    $field->evaluate($modifier, [
                        'locale' => $locale,
                        'tab' => $tab,
                    ]);
                }
            }
        }

        return $schema;
    }
}

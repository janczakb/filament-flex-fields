<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Translatable;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Closure;
use Filament\Forms\Components\Field;

final class RegistersTranslatableFieldMacros
{
    public static function boot(): void
    {
        Field::macro('translatableFields', function (
            array|Closure|null $locales = null,
            ?Closure $modifyTabsUsing = null,
            ?Closure $modifyFieldsUsing = null,
        ): TranslatableFields {
            /** @var Field $this */
            $component = TranslatableFields::make($this->getLabel())
                ->schema([$this]);

            if ($locales !== null) {
                $component->locales($locales);
            }

            if ($modifyTabsUsing instanceof Closure) {
                $component->modifyTabsUsing($modifyTabsUsing);
            }

            if ($modifyFieldsUsing instanceof Closure) {
                $component->modifyFieldsUsing($modifyFieldsUsing);
            }

            return $component;
        });

        Field::macro('translatableTabs', function (
            array|Closure|null $locales = null,
            ?Closure $modifyTabsUsing = null,
            ?Closure $modifyFieldsUsing = null,
        ): TranslatableFields {
            /** @var Field $this */
            return $this->translatableFields($locales, $modifyTabsUsing, $modifyFieldsUsing);
        });
    }
}

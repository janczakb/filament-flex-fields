<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns;

use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableLocales;
use Closure;

trait ConfiguresTranslatableLocales
{
    /**
     * @var array<string, string>|list<string>|Closure|null
     */
    protected array|Closure|null $translatableLocales = null;

    /**
     * @var array<string, string>|Closure|null
     */
    protected array|Closure|null $translatableLocaleLabels = null;

    /**
     * @param  array<string, string>|list<string>|Closure  $locales
     */
    public function locales(array|Closure $locales): static
    {
        $this->translatableLocales = $locales;

        return $this;
    }

    /**
     * @param  array<string, string>|Closure  $localeLabels
     */
    public function localesLabels(array|Closure $localeLabels): static
    {
        $this->translatableLocaleLabels = $localeLabels;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getLocales(): array
    {
        return TranslatableLocales::resolve($this->translatableLocales, $this->translatableLocaleLabels);
    }
}

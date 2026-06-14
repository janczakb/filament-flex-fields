<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableTitle;
use Closure;
use Illuminate\Support\Str;

trait InteractsWithSlugTranslatableTitle
{
    protected bool|Closure $translatableTitle = false;

    /**
     * @var array<string, string>|list<string>|Closure|null
     */
    protected array|Closure|null $titleLocales = null;

    protected string|Closure|null $slugSourceLocale = null;

    protected bool|Closure $spatieTranslatable = false;

    protected string|Closure|null $translatableTitleFieldName = null;

    public function translatableTitle(bool|Closure $condition = true): static
    {
        $this->translatableTitle = $condition;

        return $this;
    }

    /**
     * @param  array<string, string>|list<string>|Closure  $locales
     */
    public function titleLocales(array|Closure $locales): static
    {
        $this->titleLocales = $locales;
        $this->translatableTitle(true);

        return $this;
    }

    public function slugSourceLocale(string|Closure $locale): static
    {
        $this->slugSourceLocale = $locale;

        return $this;
    }

    public function spatieTranslatable(bool|Closure $condition = true): static
    {
        $this->spatieTranslatable = $condition;

        return $this;
    }

    public function translatableTitleField(string|Closure $fieldName): static
    {
        $this->translatableTitleFieldName = $fieldName;

        return $this;
    }

    public function usesTranslatableTitle(): bool
    {
        if (! (bool) $this->evaluate($this->translatableTitle)) {
            return false;
        }

        return $this->getTitleLocales() !== [];
    }

    /**
     * @return array<string, string>
     */
    public function getTitleLocales(): array
    {
        return TranslatableTitle::resolveLocales($this->titleLocales);
    }

    public function getSlugSourceLocale(): string
    {
        return TranslatableTitle::resolveSlugSourceLocale(
            $this->evaluate($this->slugSourceLocale),
            $this->getTitleLocales(),
        );
    }

    public function getTranslatableTitleFieldName(): string
    {
        $configured = $this->evaluate($this->translatableTitleFieldName);

        if (filled($configured)) {
            return (string) $configured;
        }

        $source = $this->evaluate($this->source);

        if (is_string($source) && str_contains($source, '.')) {
            return Str::beforeLast($source, '.');
        }

        return is_string($source) && filled($source)
            ? $source
            : (string) config('filament-flex-fields.slug.field_title', 'title');
    }

    public function shouldUseSpatieTranslatable(): bool
    {
        return (bool) $this->evaluate($this->spatieTranslatable);
    }

    public function getSourceStatePath(): ?string
    {
        if ($this->usesTranslatableTitle()) {
            return $this->resolveSourcePath(
                TranslatableTitle::sourcePath(
                    $this->getTranslatableTitleFieldName(),
                    $this->getSlugSourceLocale(),
                ),
            );
        }

        $source = $this->evaluate($this->source);

        if (blank($source)) {
            return null;
        }

        return $this->resolveSourcePath((string) $source);
    }
}

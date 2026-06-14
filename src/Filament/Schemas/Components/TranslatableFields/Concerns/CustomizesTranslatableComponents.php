<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Closure;
use Filament\Forms\Components\Field;

trait CustomizesTranslatableComponents
{
    /**
     * @var array<Field>|Closure
     */
    protected array|Closure $translatableFieldTemplates = [];

    /**
     * @var list<Closure(TranslatableTab, string): void>
     */
    protected array $translatableTabModifiers = [];

    /**
     * @var list<Closure(Field, string): void>
     */
    protected array $translatableFieldModifiers = [];

    protected bool|Closure $translatableSpatieEnabled = false;

    /**
     * @var Closure(Field, string, TranslatableTab): Field|null|null
     */
    protected ?Closure $translatableLocaleFieldUsing = null;

    /**
     * @var Closure(Field, string): string|null|null
     */
    protected ?Closure $translatableStorageAttributeUsing = null;

    /**
     * @param  array<Field>|Closure  $schema
     */
    public function schema(array|Closure $schema): static
    {
        $this->translatableFieldTemplates = $schema;

        return $this;
    }

    public function modifyTabsUsing(Closure $closure, bool $merge = true): static
    {
        if ($merge) {
            $this->translatableTabModifiers[] = $closure;
        } else {
            $this->translatableTabModifiers = [$closure];
        }

        return $this;
    }

    public function modifyFieldsUsing(Closure $closure, bool $merge = true): static
    {
        if ($merge) {
            $this->translatableFieldModifiers[] = $closure;
        } else {
            $this->translatableFieldModifiers = [$closure];
        }

        return $this;
    }

    public function spatieTranslatable(bool|Closure $condition = true): static
    {
        $this->translatableSpatieEnabled = $condition;

        return $this;
    }

    /**
     * Replace the default locale-field cloning strategy.
     *
     * @param  Closure(Field $template, string $locale, TranslatableTab $tab): Field|null  $callback
     */
    public function localeFieldUsing(Closure $callback): static
    {
        $this->translatableLocaleFieldUsing = $callback;

        return $this;
    }

    /**
     * Resolve the Eloquent attribute used for hydration (defaults to the template field name).
     *
     * @param  Closure(Field $template, string $locale): string  $callback
     */
    public function storageAttributeUsing(Closure $callback): static
    {
        $this->translatableStorageAttributeUsing = $callback;

        return $this;
    }

    public function shouldUseSpatieTranslatable(): bool
    {
        return (bool) $this->evaluate($this->translatableSpatieEnabled);
    }

    /**
     * @return list<Closure(TranslatableTab, string): void>
     */
    public function getTranslatableTabModifiers(): array
    {
        return $this->translatableTabModifiers;
    }

    /**
     * @return list<Closure(Field, string): void>
     */
    public function getTranslatableFieldModifiers(): array
    {
        return $this->translatableFieldModifiers;
    }
}

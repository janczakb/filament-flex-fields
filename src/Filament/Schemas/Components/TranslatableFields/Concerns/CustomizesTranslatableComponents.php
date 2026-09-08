<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\Concerns;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Schema;

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
     * Compatible with Filament\Schemas\Components\Component::schema()
     * (`Schema|Closure|array` since filamentphp/filament#19867).
     *
     * @param  array<Field>|Schema|Closure  $components
     */
    public function schema(Schema|array|Closure $components): static
    {
        if ($components instanceof Schema) {
            /** @var array<Field> $resolved */
            $resolved = $components->getComponents();
            $this->translatableFieldTemplates = $resolved;
        } else {
            $this->translatableFieldTemplates = $components;
        }

        // Bypass parent::schema(), so invalidate Filament child-schema caches
        // explicitly (required for Filament 5.8+ deferred / cached hierarchies).
        $this->clearCachedChildSchemas();

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
        $this->clearCachedChildSchemas();

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
        $this->clearCachedChildSchemas();

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
        $this->clearCachedChildSchemas();

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

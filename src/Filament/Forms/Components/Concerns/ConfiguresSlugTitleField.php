<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Str;

trait ConfiguresSlugTitleField
{
    protected string|Closure|null $source = null;

    protected bool|Closure $sourceIsLive = true;

    protected ?Field $titleField = null;

    protected ?Closure $titleAfterStateUpdated = null;

    protected ?Closure $slugAfterStateUpdated = null;

    protected ?Closure $titleFieldWrapper = null;

    protected bool|Closure $titleReadOnly = false;

    protected bool|Closure $slugReadOnly = false;

    public function source(string|Closure|null $statePath): static
    {
        $this->source = $statePath;

        return $this;
    }

    public function sourceLive(bool|Closure $condition = true): static
    {
        $this->sourceIsLive = $condition;

        return $this;
    }

    public function titleField(Field $field): static
    {
        $this->titleField = $field;

        if ($this->source === null) {
            $this->source($field->getName());
        }

        return $this;
    }

    public function titleFieldWrapper(?Closure $wrapper): static
    {
        $this->titleFieldWrapper = $wrapper;

        return $this;
    }

    public function titleAfterStateUpdated(?Closure $callback): static
    {
        $this->titleAfterStateUpdated = $callback;

        return $this;
    }

    public function slugAfterStateUpdated(?Closure $callback): static
    {
        $this->slugAfterStateUpdated = $callback;

        return $this;
    }

    public function titleReadOnly(bool|Closure $condition = true): static
    {
        $this->titleReadOnly = $condition;

        return $this;
    }

    public function slugReadOnly(bool|Closure $condition = true): static
    {
        $this->slugReadOnly = $condition;

        return $this;
    }

    public function isSourceLive(): bool
    {
        return (bool) $this->evaluate($this->sourceIsLive);
    }

    public function getConfiguredTitleField(): ?Field
    {
        if ($this->titleField === null) {
            return null;
        }

        $path = $this->resolveOwnStatePath();
        $parent = str_contains($path, '.') ? Str::beforeLast($path, '.') : null;
        $titleName = $this->titleField->getName();
        $titlePath = filled($parent) ? "{$parent}.{$titleName}" : $titleName;

        $field = $this->titleField
            ->statePath($titlePath)
            ->live();

        if ($this->isTitleReadOnly()) {
            $field->readOnly();
        }

        if ($this->titleAfterStateUpdated instanceof Closure) {
            $callback = $this->titleAfterStateUpdated;
            $field->afterStateUpdated(function (mixed $state, Field $component) use ($callback): void {
                $component->evaluate($callback, ['state' => $state]);
            });
        }

        if ($this->isRootMounted()) {
            $field->container($this->getContainer());
        }

        if ($this->titleFieldWrapper instanceof Closure) {
            return $this->evaluate($this->titleFieldWrapper, ['field' => $field]) ?? $field;
        }

        return $field;
    }

    public function isTitleReadOnly(): bool
    {
        return (bool) $this->evaluate($this->titleReadOnly);
    }

    public function isSlugReadOnly(): bool
    {
        return $this->isReadOnly() || (bool) $this->evaluate($this->slugReadOnly);
    }
}

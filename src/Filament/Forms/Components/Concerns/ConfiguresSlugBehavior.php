<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Bjanczak\FilamentFlexFields\Support\Slug\SlugGenerator;
use Closure;
use Illuminate\Support\Str;

trait ConfiguresSlugBehavior
{
    protected string|Closure|null $autoUpdateDisabledField = null;

    protected string|Closure|null $inlineEditPendingField = null;

    protected bool|Closure $autoGenerate = true;

    protected bool|Closure $preserveSlugOnEdit = true;

    protected bool|Closure $inlineEditing = true;

    protected bool|Closure $allowHomepageSlug = false;

    protected int|Closure $generationDebounce = 400;

    protected string|Closure|null $slugPattern = null;

    /**
     * @var array<int, string|Closure>|Closure
     */
    protected array|Closure $slugRules = [];

    protected string|Closure $variant = 'primary';

    public function autoUpdateDisabledField(string|Closure|null $field): static
    {
        $this->autoUpdateDisabledField = $field;

        return $this;
    }

    public function inlineEditPendingField(string|Closure|null $field): static
    {
        $this->inlineEditPendingField = $field;

        return $this;
    }

    public function autoGenerate(bool|Closure $condition = true): static
    {
        $this->autoGenerate = $condition;

        return $this;
    }

    public function preserveSlugOnEdit(bool|Closure $condition = true): static
    {
        $this->preserveSlugOnEdit = $condition;

        return $this;
    }

    public function inlineEditing(bool|Closure $condition = true): static
    {
        $this->inlineEditing = $condition;

        return $this;
    }

    public function allowHomepageSlug(bool|Closure $condition = true): static
    {
        $this->allowHomepageSlug = $condition;

        return $this;
    }

    public function generationDebounce(int|Closure $milliseconds = 400): static
    {
        $this->generationDebounce = $milliseconds;

        return $this;
    }

    public function slugPattern(string|Closure $pattern): static
    {
        $this->slugPattern = $pattern;

        return $this;
    }

    public function regex(string|Closure|null $pattern): static
    {
        if ($pattern !== null) {
            $this->slugPattern = $pattern;
        }

        return $this;
    }

    /**
     * @param  array<int, string|Closure>|Closure  $rules
     */
    public function slugRules(array|Closure $rules): static
    {
        $this->slugRules = $rules;

        $resolved = is_array($rules) ? $rules : [];

        if (in_array('required', $resolved, true)) {
            $this->required();
        }

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getAutoUpdateDisabledStatePath(): ?string
    {
        $field = $this->evaluate($this->autoUpdateDisabledField);

        if (blank($field)) {
            return null;
        }

        $field = (string) $field;

        if (str_contains($field, '.')) {
            return $field;
        }

        $path = $this->resolveOwnStatePath();
        $parent = str_contains($path, '.') ? Str::beforeLast($path, '.') : null;

        return filled($parent) ? "{$parent}.{$field}" : $field;
    }

    public function getInlineEditPendingFieldName(): string
    {
        $field = $this->evaluate($this->inlineEditPendingField);

        if (filled($field)) {
            return (string) $field;
        }

        return $this->getName().'_inline_edit_pending';
    }

    public function getInlineEditPendingStatePath(): ?string
    {
        if (! $this->shouldUseInlineEditing()) {
            return null;
        }

        $field = $this->getInlineEditPendingFieldName();

        if (str_contains($field, '.')) {
            return $field;
        }

        $path = $this->resolveOwnStatePath();
        $parent = str_contains($path, '.') ? Str::beforeLast($path, '.') : null;

        return filled($parent) ? "{$parent}.{$field}" : $field;
    }

    public function shouldAutoGenerate(): bool
    {
        return (bool) $this->evaluate($this->autoGenerate);
    }

    public function shouldPreserveSlugOnEdit(): bool
    {
        return (bool) $this->evaluate($this->preserveSlugOnEdit);
    }

    public function shouldUseInlineEditing(): bool
    {
        return (bool) $this->evaluate($this->inlineEditing);
    }

    public function getInitialAutoSyncDisabled(): bool
    {
        return $this->shouldPreserveSlugOnEdit() && filled($this->getRecordSlug());
    }

    public function allowsHomepageSlug(): bool
    {
        return (bool) $this->evaluate($this->allowHomepageSlug);
    }

    public function getDebounceMilliseconds(): int
    {
        return max(0, (int) $this->evaluate($this->generationDebounce));
    }

    public function getRegex(): string
    {
        $pattern = $this->evaluate($this->slugPattern);

        if (filled($pattern)) {
            return (string) $pattern;
        }

        return SlugGenerator::patternForSeparator(
            $this->getSeparator(),
            $this->allowsHomepageSlug(),
        );
    }

    public function getVariant(): string
    {
        return (string) $this->evaluate($this->variant);
    }
}

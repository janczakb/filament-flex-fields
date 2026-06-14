<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\InteractsWithTagSearch;
use Closure;
use Filament\Forms\Components\TagsInput;
use InvalidArgumentException;

class TagsField extends TagsInput
{
    use HasControlSize;
    use HasFieldFocusOutline;
    use InteractsWithTagSearch;

    protected string $view = 'filament-flex-fields::forms.components.tags-field';

    protected string|Closure $variant = 'primary';

    protected int|Closure|null $maxTags = null;

    protected bool|Closure $suggestionsOnly = false;

    protected bool|Closure $duplicateInsensitive = false;

    protected bool|Closure $showTagCount = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->size('md');
        $this->splitKeys(['Tab']);
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function maxTags(int|Closure|null $max): static
    {
        $this->maxTags = $max;

        return $this;
    }

    public function suggestionsOnly(bool|Closure $condition = true): static
    {
        $this->suggestionsOnly = $condition;

        return $this;
    }

    public function duplicateInsensitive(bool|Closure $condition = true): static
    {
        $this->duplicateInsensitive = $condition;

        return $this;
    }

    public function showTagCount(bool|Closure $condition = true): static
    {
        $this->showTagCount = $condition;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat', 'soft'], true)) {
            throw new InvalidArgumentException("Tags field variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function getMaxTags(): ?int
    {
        $max = $this->evaluate($this->maxTags);

        return is_int($max) ? $max : null;
    }

    public function isSuggestionsOnly(): bool
    {
        return (bool) $this->evaluate($this->suggestionsOnly);
    }

    public function isDuplicateInsensitive(): bool
    {
        return (bool) $this->evaluate($this->duplicateInsensitive);
    }

    public function shouldShowTagCount(): bool
    {
        return (bool) $this->evaluate($this->showTagCount);
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-tags-field',
            'fff-flex-text-input',
            'fff-tags-field--'.$this->getSize(),
            'fff-flex-text-input--'.$this->getSize(),
            'fff-tags-field--'.$this->getVariant(),
            'fff-flex-text-input--'.$this->getVariant(),
            'fi-color-'.($this->getColor() ?? 'primary'),
        ];
    }

    public function getTagDisplayLabel(string $tag): string
    {
        return ($this->getTagPrefix() ?? '').$tag.($this->getTagSuffix() ?? '');
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Closure;

trait ConfiguresUserSelectSearch
{
    protected int|Closure $maxVisibleAvatars = 5;

    protected int|Closure $minSearchLength = 2;

    protected int|Closure $defaultSuggestionsLimit = 5;

    protected ?Closure $applySearchUsing = null;

    public function maxVisibleAvatars(int|Closure $limit): static
    {
        $this->maxVisibleAvatars = $limit;

        return $this;
    }

    public function defaultSuggestionsLimit(int|Closure $limit): static
    {
        $this->defaultSuggestionsLimit = $limit;

        return $this;
    }

    public function minSearchLength(int|Closure $length): static
    {
        $this->minSearchLength = $length;

        return $this;
    }

    public function applySearchUsing(?Closure $callback): static
    {
        $this->applySearchUsing = $callback;

        return $this;
    }

    public function getMaxVisibleAvatars(): int
    {
        return max(1, (int) $this->evaluate($this->maxVisibleAvatars));
    }

    public function getMinSearchLength(): int
    {
        return max(0, (int) $this->evaluate($this->minSearchLength));
    }

    public function getDefaultSuggestionsLimit(): int
    {
        $limit = (int) $this->evaluate($this->defaultSuggestionsLimit);

        return max(1, $limit);
    }
}

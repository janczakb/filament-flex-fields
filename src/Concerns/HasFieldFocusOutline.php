<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Closure;

trait HasFieldFocusOutline
{
    protected bool|Closure|null $focusOutline = null;

    public function focusOutline(bool|Closure $condition = true): static
    {
        $this->focusOutline = $condition;

        return $this;
    }

    public function shouldShowFocusOutline(): bool
    {
        if ($this->focusOutline === null) {
            return $this->defaultFocusOutline();
        }

        return (bool) $this->evaluate($this->focusOutline);
    }

    protected function defaultFocusOutline(): bool
    {
        return false;
    }
}

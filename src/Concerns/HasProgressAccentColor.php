<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Bjanczak\FilamentFlexFields\Support\ProgressColor;
use Closure;

trait HasProgressAccentColor
{
    protected string|Closure|null $color = 'primary';

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getResolvedColor(): string
    {
        return ProgressColor::normalize((string) ($this->evaluate($this->color) ?? 'primary'));
    }

    public function getColorToken(): ?string
    {
        $resolved = $this->getResolvedColor();

        return ProgressColor::isSemantic($resolved) ? $resolved : null;
    }

    public function usesCustomAccentColor(): bool
    {
        return $this->getColorToken() === null;
    }

    public function getAccentCssColor(): ?string
    {
        return $this->usesCustomAccentColor() ? $this->getResolvedColor() : null;
    }
}

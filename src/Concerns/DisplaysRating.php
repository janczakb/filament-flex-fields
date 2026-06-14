<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use BackedEnum;
use Closure;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

trait DisplaysRating
{
    protected int|Closure $max = 5;

    protected string|Closure|null $color = 'warning';

    protected string|BackedEnum|Htmlable|Closure|null $icon = null;

    protected bool|Closure $shouldShowValue = true;

    public function max(int|Closure $max): static
    {
        if ($max instanceof Closure) {
            $this->max = $max;

            return $this;
        }

        if ($max < 1) {
            throw new InvalidArgumentException('Rating max must be at least 1.');
        }

        $this->max = $max;

        return $this;
    }

    public function stars(int|Closure $count): static
    {
        return $this->max($count);
    }

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function icon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function showValue(bool|Closure $condition = true): static
    {
        $this->shouldShowValue = $condition;

        return $this;
    }

    public function getMax(): int
    {
        $max = (int) $this->evaluate($this->max);

        if ($max < 1) {
            throw new InvalidArgumentException('Rating max must be at least 1.');
        }

        return $max;
    }

    public function getColor(): string
    {
        $color = $this->evaluate($this->color);

        return filled($color) ? (string) $color : 'warning';
    }

    public function getIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->icon);

        if ($icon instanceof Htmlable) {
            return $icon;
        }

        if ($icon instanceof BackedEnum) {
            return $icon;
        }

        if (filled($icon)) {
            return (string) $icon;
        }

        return Heroicon::Star;
    }

    public function shouldShowValue(): bool
    {
        return (bool) $this->evaluate($this->shouldShowValue);
    }
}

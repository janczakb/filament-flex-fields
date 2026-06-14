<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Tables\Columns;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\CalculatesRatingFill;
use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Closure;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;

class RatingColumn extends TextColumn
{
    use CalculatesRatingFill;

    protected int|Closure $ratingMax = 5;

    protected string|Closure|null $ratingDisplayColor = 'warning';

    protected string|BackedEnum|Htmlable|Closure|null $ratingDisplayIcon = null;

    protected bool|Closure $shouldShowRatingValue = true;

    protected string|ControlSize|Closure $ratingDisplaySize = 'md';

    protected function setUp(): void
    {
        parent::setUp();

        $this->html();

        $this->formatStateUsing(fn (mixed $state, RatingColumn $column): string => $column->formatRatingDisplay($state));
    }

    public function stars(int|Closure $count): static
    {
        if ($count instanceof Closure) {
            $this->ratingMax = $count;

            return $this;
        }

        if ($count < 1) {
            throw new InvalidArgumentException('Rating max must be at least 1.');
        }

        $this->ratingMax = $count;

        return $this;
    }

    public function ratingColor(string|Closure|null $color): static
    {
        $this->ratingDisplayColor = $color;

        return $this;
    }

    public function ratingIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->ratingDisplayIcon = $icon;

        return $this;
    }

    public function showValue(bool|Closure $condition = true): static
    {
        $this->shouldShowRatingValue = $condition;

        return $this;
    }

    public function ratingSize(string|ControlSize|Closure $size): static
    {
        $this->ratingDisplaySize = $size;

        return $this;
    }

    public function getMax(): int
    {
        $max = (int) $this->evaluate($this->ratingMax);

        if ($max < 1) {
            throw new InvalidArgumentException('Rating max must be at least 1.');
        }

        return $max;
    }

    public function getRatingColor(): string
    {
        $color = $this->evaluate($this->ratingDisplayColor);

        return filled($color) ? (string) $color : 'warning';
    }

    public function getRatingIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->ratingDisplayIcon);

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
        return (bool) $this->evaluate($this->shouldShowRatingValue);
    }

    public function getRatingDisplaySize(): string
    {
        $size = $this->evaluate($this->ratingDisplaySize);

        if ($size instanceof ControlSize) {
            return $size->value;
        }

        return (string) $size;
    }

    public function formatRatingDisplay(mixed $state): string
    {
        $value = $this->normalizeRatingFromState($state);

        if ($value === null) {
            return '';
        }

        /** @var View $view */
        $view = view('filament-flex-fields::tables.columns.rating-column', [
            'value' => $value,
            'max' => $this->getMax(),
            'size' => $this->getRatingDisplaySize(),
            'color' => $this->getRatingColor(),
            'icon' => $this->getRatingIcon(),
            'items' => $this->getItemIndexes(),
            'showValue' => $this->shouldShowValue(),
            'fillPercentageFor' => fn (int $index): float => $this->getFillPercentageForValue($value, $index),
        ]);

        return $view->render();
    }

    public function normalizeRatingFromState(mixed $state): ?float
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (! is_numeric($state)) {
            return null;
        }

        $value = (float) $state;

        if ($value < 0) {
            return null;
        }

        return min($value, (float) $this->getMax());
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Tables\Columns;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Bjanczak\FilamentFlexFields\Support\ProgressColumnRenderCache;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\View\View;

class ProgressColumn extends TextColumn
{
    protected string|ControlSize|Closure $progressDisplaySize = 'md';

    protected string|Closure|null $progressDisplayColor = 'primary';

    protected bool|Closure $shouldShowProgressValue = true;

    protected function setUp(): void
    {
        parent::setUp();

        FlexFieldStylesheetQueue::enqueueFor('progress-column');

        $this->html();

        $this->formatStateUsing(fn (mixed $state, ProgressColumn $column): string => $column->formatProgressDisplay($state));
    }

    public function progressSize(string|ControlSize|Closure $size): static
    {
        $this->progressDisplaySize = $size;

        return $this;
    }

    public function progressColor(string|Closure|null $color): static
    {
        $this->progressDisplayColor = $color;

        return $this;
    }

    public function showValue(bool|Closure $condition = true): static
    {
        $this->shouldShowProgressValue = $condition;

        return $this;
    }

    public function getProgressDisplaySize(): string
    {
        $size = $this->evaluate($this->progressDisplaySize);

        if ($size instanceof ControlSize) {
            return $size->value;
        }

        return (string) $size;
    }

    public function getProgressColor(): string
    {
        $color = $this->evaluate($this->progressDisplayColor);

        return filled($color) ? (string) $color : 'primary';
    }

    public function shouldShowValue(): bool
    {
        return (bool) $this->evaluate($this->shouldShowProgressValue);
    }

    /**
     * @return array{percentage: float, label: string|null}|null
     */
    public function normalizeProgressFromState(mixed $state): ?array
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_array($state)) {
            $value = $state['value'] ?? $state['percentage'] ?? null;
            $max = (float) ($state['max'] ?? 100);
            $label = isset($state['label']) ? (string) $state['label'] : null;

            if (! is_numeric($value) || $max <= 0) {
                return null;
            }

            $percentage = max(0, min(100, ((float) $value / $max) * 100));

            return [
                'percentage' => $percentage,
                'label' => $label,
            ];
        }

        if (! is_numeric($state)) {
            return null;
        }

        return [
            'percentage' => max(0, min(100, (float) $state)),
            'label' => null,
        ];
    }

    public function formatProgressDisplay(mixed $state): string
    {
        $normalized = $this->normalizeProgressFromState($state);

        if ($normalized === null) {
            return '';
        }

        $cacheKey = hash('xxh128', json_encode([
            'column' => $this->getName(),
            'percentage' => $normalized['percentage'],
            'label' => $normalized['label'],
            'size' => $this->getProgressDisplaySize(),
            'color' => $this->getProgressColor(),
            'showValue' => $this->shouldShowValue(),
        ], JSON_THROW_ON_ERROR));

        return ProgressColumnRenderCache::remember($cacheKey, function () use ($normalized): string {
            /** @var View $view */
            $view = view('filament-flex-fields::tables.columns.progress-column', [
                'percentage' => $normalized['percentage'],
                'label' => $normalized['label'],
                'size' => $this->getProgressDisplaySize(),
                'color' => $this->getProgressColor(),
                'showValue' => $this->shouldShowValue(),
            ]);

            return $view->render();
        });
    }
}

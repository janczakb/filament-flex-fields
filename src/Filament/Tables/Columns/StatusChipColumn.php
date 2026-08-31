<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Tables\Columns;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Bjanczak\FilamentFlexFields\Support\StatusChipColumnRenderCache;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\View\View;

class StatusChipColumn extends TextColumn
{
    protected string|ControlSize|Closure $chipDisplaySize = 'md';

    protected string|Closure|null $chipDisplayColor = null;

    protected function setUp(): void
    {
        parent::setUp();

        FlexFieldStylesheetQueue::enqueueFor('status-chip-column');

        $this->html();

        $this->formatStateUsing(fn (mixed $state, StatusChipColumn $column): string => $column->formatChipDisplay($state));
    }

    public function chipSize(string|ControlSize|Closure $size): static
    {
        $this->chipDisplaySize = $size;

        return $this;
    }

    public function chipColor(string|Closure|null $color): static
    {
        $this->chipDisplayColor = $color;

        return $this;
    }

    public function getChipDisplaySize(): string
    {
        $size = $this->evaluate($this->chipDisplaySize);

        if ($size instanceof ControlSize) {
            return $size->value;
        }

        return (string) $size;
    }

    public function getChipDisplayColor(): ?string
    {
        $color = $this->evaluate($this->chipDisplayColor);

        if ($color === null || $color === '') {
            return null;
        }

        return (string) $color;
    }

    /**
     * @return array{label: string, color: string|null}|null
     */
    public function normalizeChipFromState(mixed $state): ?array
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_array($state)) {
            $label = trim((string) ($state['label'] ?? $state['name'] ?? ''));

            if ($label === '') {
                return null;
            }

            $color = $state['color'] ?? null;

            return [
                'label' => $label,
                'color' => filled($color) ? (string) $color : null,
            ];
        }

        if (! is_string($state)) {
            return null;
        }

        $label = trim($state);

        return $label !== '' ? ['label' => $label, 'color' => null] : null;
    }

    public function formatChipDisplay(mixed $state): string
    {
        $chip = $this->normalizeChipFromState($state);

        if ($chip === null) {
            return '';
        }

        $color = $chip['color'] ?? $this->getChipDisplayColor();

        $cacheKey = hash('xxh128', json_encode([
            'column' => $this->getName(),
            'label' => $chip['label'],
            'color' => $color,
            'size' => $this->getChipDisplaySize(),
        ], JSON_THROW_ON_ERROR));

        return StatusChipColumnRenderCache::remember($cacheKey, function () use ($chip, $color): string {
            /** @var View $view */
            $view = view('filament-flex-fields::tables.columns.status-chip-column', [
                'label' => $chip['label'],
                'size' => $this->getChipDisplaySize(),
                'color' => $color,
            ]);

            return $view->render();
        });
    }
}

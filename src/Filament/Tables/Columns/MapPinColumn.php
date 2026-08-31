<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Tables\Columns;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\MapPinColumnRenderCache;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\View\View;

class MapPinColumn extends TextColumn
{
    protected string|ControlSize|Closure $pinDisplaySize = 'md';

    protected bool|Closure $shouldShowLabel = true;

    protected string|Closure|null $pinIcon = null;

    protected function setUp(): void
    {
        parent::setUp();

        FlexFieldStylesheetQueue::enqueueFor('map-pin-column');

        $this->html();

        $this->formatStateUsing(fn (mixed $state, MapPinColumn $column): string => $column->formatMapPinDisplay($state));
    }

    public function pinSize(string|ControlSize|Closure $size): static
    {
        $this->pinDisplaySize = $size;

        return $this;
    }

    public function showLabel(bool|Closure $condition = true): static
    {
        $this->shouldShowLabel = $condition;

        return $this;
    }

    public function pinIcon(string|Closure|null $icon): static
    {
        $this->pinIcon = $icon;

        return $this;
    }

    public function getPinDisplaySize(): string
    {
        $size = $this->evaluate($this->pinDisplaySize);

        if ($size instanceof ControlSize) {
            return $size->value;
        }

        return (string) $size;
    }

    public function shouldShowLabel(): bool
    {
        return (bool) $this->evaluate($this->shouldShowLabel);
    }

    public function getPinIcon(): string
    {
        $icon = $this->evaluate($this->pinIcon);

        return filled($icon) ? (string) $icon : GravityIcon::MapPin;
    }

    /**
     * @return array{label: string, coordinates: string|null}|null
     */
    public function normalizeLocationFromState(mixed $state): ?array
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_array($state)) {
            $label = trim((string) ($state['label'] ?? $state['address'] ?? $state['name'] ?? ''));

            if ($label === '' && isset($state['lat'], $state['lng'])) {
                $label = sprintf('%s, %s', $state['lat'], $state['lng']);
            }

            if ($label === '') {
                return null;
            }

            $coordinates = null;

            if (isset($state['lat'], $state['lng']) && is_numeric($state['lat']) && is_numeric($state['lng'])) {
                $coordinates = sprintf('%s,%s', $state['lat'], $state['lng']);
            }

            return [
                'label' => $label,
                'coordinates' => $coordinates,
            ];
        }

        if (! is_string($state)) {
            return null;
        }

        $label = trim($state);

        return $label !== '' ? ['label' => $label, 'coordinates' => null] : null;
    }

    public function formatMapPinDisplay(mixed $state): string
    {
        $location = $this->normalizeLocationFromState($state);

        if ($location === null) {
            return '';
        }

        $cacheKey = hash('xxh128', json_encode([
            'column' => $this->getName(),
            'label' => $location['label'],
            'coordinates' => $location['coordinates'],
            'size' => $this->getPinDisplaySize(),
            'icon' => $this->getPinIcon(),
            'showLabel' => $this->shouldShowLabel(),
        ], JSON_THROW_ON_ERROR));

        return MapPinColumnRenderCache::remember($cacheKey, function () use ($location): string {
            /** @var View $view */
            $view = view('filament-flex-fields::tables.columns.map-pin-column', [
                'label' => $location['label'],
                'coordinates' => $location['coordinates'],
                'size' => $this->getPinDisplaySize(),
                'icon' => $this->getPinIcon(),
                'showLabel' => $this->shouldShowLabel(),
            ]);

            return $view->render();
        });
    }
}

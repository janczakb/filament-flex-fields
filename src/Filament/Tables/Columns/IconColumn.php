<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Tables\Columns;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Bjanczak\FilamentFlexFields\Support\IconColumnRenderCache;
use Bjanczak\FilamentFlexFields\Support\Icons\IconCatalogResolver;
use Bjanczak\FilamentFlexFields\Support\Icons\IconSvgCache;
use Closure;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

class IconColumn extends TextColumn
{
    protected string|ControlSize|Closure $iconDisplaySize = 'md';

    /**
     * @var string|array<int | string, string | int>|Closure|null
     */
    protected string|array|Closure|null $iconDisplayColor = null;

    protected bool|Closure $shouldShowLabel = false;

    protected bool|Closure $shouldShowName = false;

    protected ?Closure $resolveLabelUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        FlexFieldStylesheetQueue::enqueueFor('icon-column');

        $this->html();

        $this->formatStateUsing(fn (mixed $state, IconColumn $column): string => $column->formatIconDisplay($state));
    }

    public function iconSize(string|ControlSize|Closure $size): static
    {
        $this->iconDisplaySize = $size;

        return $this;
    }

    /**
     * @param  string|array<int | string, string | int>|Closure|null  $color
     */
    public function iconColor(string|array|Closure|null $color): static
    {
        $this->iconDisplayColor = $color;

        return $this;
    }

    public function showLabel(bool|Closure $condition = true): static
    {
        $this->shouldShowLabel = $condition;

        return $this;
    }

    public function showName(bool|Closure $condition = true): static
    {
        $this->shouldShowName = $condition;

        return $this;
    }

    public function labelUsing(?Closure $callback): static
    {
        $this->resolveLabelUsing = $callback;

        return $this;
    }

    public function getIconDisplaySize(): string
    {
        $size = $this->evaluate($this->iconDisplaySize);

        if ($size instanceof ControlSize) {
            return $size->value;
        }

        return (string) $size;
    }

    /**
     * @return string|array<int | string, string | int>|null
     */
    public function getIconDisplayColor(): string|array|null
    {
        $color = $this->evaluate($this->iconDisplayColor);

        if ($color === null || $color === '') {
            return null;
        }

        if (is_array($color)) {
            return $color;
        }

        return (string) $color;
    }

    public function shouldShowLabel(): bool
    {
        return (bool) $this->evaluate($this->shouldShowLabel);
    }

    public function shouldShowName(): bool
    {
        return (bool) $this->evaluate($this->shouldShowName);
    }

    public function normalizeIconFromState(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (! is_string($state)) {
            return null;
        }

        $icon = trim($state);

        return $icon !== '' ? $icon : null;
    }

    public function formatIconDisplay(mixed $state): string
    {
        $icon = $this->normalizeIconFromState($state);

        if ($icon === null) {
            return '';
        }

        $cacheKey = $this->renderCacheKey($icon);

        return IconColumnRenderCache::remember($cacheKey, function () use ($icon): string {
            /** @var View $view */
            $view = view('filament-flex-fields::tables.columns.icon-column', [
                'icon' => $icon,
                'iconHtml' => $this->renderIconHtml($icon),
                'size' => $this->getIconDisplaySize(),
                'color' => $this->getIconDisplayColor(),
                'label' => $this->resolveIconLabel($icon),
                'showLabel' => $this->shouldShowLabel(),
                'showName' => $this->shouldShowName(),
            ]);

            return $view->render();
        });
    }

    public function renderIconHtml(string $icon): string
    {
        $cache = app(IconSvgCache::class);

        $cached = $cache->rememberMany(
            [$icon],
            fn (array $missing): array => $this->renderFreshIconHtmlBatch($missing),
        );

        return $cached[$icon] ?? '';
    }

    /**
     * @param  list<string>  $icons
     * @return array<string, string>
     */
    protected function renderFreshIconHtmlBatch(array $icons): array
    {
        $rendered = [];

        foreach ($icons as $icon) {
            $html = \Filament\Support\generate_icon_html($icon, size: $this->filamentIconSize());

            if ($html instanceof Htmlable) {
                $content = $html->toHtml();

                if ($content !== '') {
                    $rendered[$icon] = $content;
                }
            }
        }

        return $rendered;
    }

    public function resolveIconLabel(string $icon): string
    {
        if ($this->resolveLabelUsing !== null) {
            $label = $this->evaluate($this->resolveLabelUsing, [
                'icon' => $icon,
            ]);

            if (is_string($label)) {
                return trim($label);
            }
        }

        return app(IconCatalogResolver::class)->formatIconLabel($icon);
    }

    protected function filamentIconSize(): IconSize
    {
        return match ($this->getIconDisplaySize()) {
            'sm' => IconSize::Small,
            'lg' => IconSize::Large,
            default => IconSize::Medium,
        };
    }

    protected function renderCacheKey(string $icon): string
    {
        return hash('xxh128', json_encode([
            'column' => $this->getName(),
            'icon' => $icon,
            'size' => $this->getIconDisplaySize(),
            'color' => $this->getIconDisplayColor(),
            'showLabel' => $this->shouldShowLabel(),
            'showName' => $this->shouldShowName(),
            'label' => $this->resolveIconLabel($icon),
        ], JSON_THROW_ON_ERROR));
    }
}

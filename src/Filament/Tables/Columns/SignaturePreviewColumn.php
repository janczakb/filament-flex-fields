<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Tables\Columns;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Bjanczak\FilamentFlexFields\Support\SignaturePreviewColumnRenderCache;
use Bjanczak\FilamentFlexFields\Support\SignatureSvg;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

class SignaturePreviewColumn extends TextColumn
{
    protected string|ControlSize|Closure $previewDisplaySize = 'md';

    protected bool|Closure $shouldShowEmptyPlaceholder = true;

    protected function setUp(): void
    {
        parent::setUp();

        FlexFieldStylesheetQueue::enqueueFor('signature-preview-column');

        $this->html();

        $this->formatStateUsing(fn (mixed $state, SignaturePreviewColumn $column): string => $column->formatSignaturePreview($state));
    }

    public function previewSize(string|ControlSize|Closure $size): static
    {
        $this->previewDisplaySize = $size;

        return $this;
    }

    public function showEmptyPlaceholder(bool|Closure $condition = true): static
    {
        $this->shouldShowEmptyPlaceholder = $condition;

        return $this;
    }

    public function getPreviewDisplaySize(): string
    {
        $size = $this->evaluate($this->previewDisplaySize);

        if ($size instanceof ControlSize) {
            return $size->value;
        }

        return (string) $size;
    }

    public function shouldShowEmptyPlaceholder(): bool
    {
        return (bool) $this->evaluate($this->shouldShowEmptyPlaceholder);
    }

    public function normalizeSignatureFromState(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_array($state)) {
            $svg = $state['svg'] ?? $state['signature'] ?? null;

            if (! is_string($svg)) {
                return null;
            }

            return SignatureSvg::normalize($svg);
        }

        if (! is_string($state)) {
            return null;
        }

        return SignatureSvg::normalize($state);
    }

    public function formatSignaturePreview(mixed $state): string
    {
        $svg = $this->normalizeSignatureFromState($state);

        if ($svg === null || SignatureSvg::isEmpty($svg)) {
            return $this->shouldShowEmptyPlaceholder()
                ? $this->renderEmptyPlaceholder()
                : '';
        }

        $cacheKey = hash('xxh128', json_encode([
            'column' => $this->getName(),
            'svg' => $svg,
            'size' => $this->getPreviewDisplaySize(),
        ], JSON_THROW_ON_ERROR));

        return SignaturePreviewColumnRenderCache::remember($cacheKey, function () use ($svg): string {
            /** @var View $view */
            $view = view('filament-flex-fields::tables.columns.signature-preview-column', [
                'svg' => new HtmlString($svg),
                'size' => $this->getPreviewDisplaySize(),
            ]);

            return $view->render();
        });
    }

    protected function renderEmptyPlaceholder(): string
    {
        /** @var View $view */
        $view = view('filament-flex-fields::tables.columns.signature-preview-column', [
            'svg' => null,
            'size' => $this->getPreviewDisplaySize(),
        ]);

        return $view->render();
    }
}

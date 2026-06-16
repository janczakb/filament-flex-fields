<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\UrlMeta\UrlMetaScraper;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class LinkPreviewField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasPlaceholder;

    protected string $view = 'filament-flex-fields::forms.components.link-preview-field';

    protected string|Closure $variant = 'primary';

    protected bool|Closure $previewEnabled = true;

    protected int|Closure $previewDebounce = 500;

    protected int|Closure $previewMinUrlLength = 10;

    protected int|Closure $previewMinSkeletonMs = 500;

    /** @var 'horizontal'|'vertical'|'card'|Closure */
    protected string|Closure $previewLayout = 'horizontal';

    protected bool|Closure $resolveInitialPreviewOnServer = true;

    protected bool|Closure $showVisitLink = true;

    protected string|BackedEnum|Htmlable|Closure|null $visitIcon = null;

    protected string|Closure $visitLabel = 'Visit link';

    protected string|Closure|null $prefix = null;

    protected string|Closure|null $suffix = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->placeholder(__('filament-flex-fields::default.link_preview.placeholder'));
        $this->visitLabel(__('filament-flex-fields::default.link_preview.visit'));
        $this->visitIcon(GravityIcon::Paperclip);

        $this->rule('nullable');
        $this->rule('url');

        $this->afterStateHydrated(function (LinkPreviewField $component, mixed $state): void {
            if (is_string($state)) {
                $component->state(trim($state) === '' ? null : trim($state));
            }
        });

        $this->dehydrateStateUsing(function (LinkPreviewField $component, mixed $state): ?string {
            if (! is_string($state)) {
                return null;
            }

            $trimmed = trim($state);

            return $trimmed === '' ? null : $trimmed;
        });
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'soft', 'flat', 'ghost'], true)) {
            throw new InvalidArgumentException("Invalid LinkPreviewField variant [{$variant}].");
        }

        return $variant;
    }

    public function preview(bool|Closure $condition = true): static
    {
        $this->previewEnabled = $condition;

        return $this;
    }

    public function isPreviewEnabled(): bool
    {
        return (bool) $this->evaluate($this->previewEnabled);
    }

    public function previewDebounce(int|Closure $milliseconds): static
    {
        $this->previewDebounce = $milliseconds;

        return $this;
    }

    public function getPreviewDebounce(): int
    {
        return max(0, (int) $this->evaluate($this->previewDebounce));
    }

    public function previewMinUrlLength(int|Closure $length): static
    {
        $this->previewMinUrlLength = $length;

        return $this;
    }

    public function getPreviewMinUrlLength(): int
    {
        return max(4, (int) $this->evaluate($this->previewMinUrlLength));
    }

    public function previewMinSkeletonMs(int|Closure $milliseconds): static
    {
        $this->previewMinSkeletonMs = $milliseconds;

        return $this;
    }

    public function getPreviewMinSkeletonMs(): int
    {
        return max(0, (int) $this->evaluate($this->previewMinSkeletonMs));
    }

    /**
     * @param  'horizontal'|'vertical'|'card'|Closure  $layout
     */
    public function previewLayout(string|Closure $layout): static
    {
        $this->previewLayout = $layout;

        return $this;
    }

    /**
     * @return 'horizontal'|'vertical'|'card'
     */
    public function getPreviewLayout(): string
    {
        $layout = $this->evaluate($this->previewLayout);

        if (! in_array($layout, ['horizontal', 'vertical', 'card'], true)) {
            throw new InvalidArgumentException("Invalid LinkPreviewField preview layout [{$layout}].");
        }

        return $layout;
    }

    public function resolveInitialPreviewOnServer(bool|Closure $condition = true): static
    {
        $this->resolveInitialPreviewOnServer = $condition;

        return $this;
    }

    public function shouldResolveInitialPreviewOnServer(): bool
    {
        return (bool) $this->evaluate($this->resolveInitialPreviewOnServer);
    }

    public function showVisitLink(bool|Closure $condition = true): static
    {
        $this->showVisitLink = $condition;

        return $this;
    }

    public function shouldShowVisitLink(): bool
    {
        return (bool) $this->evaluate($this->showVisitLink);
    }

    public function visitIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->visitIcon = $icon;

        return $this;
    }

    public function getVisitIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->evaluate($this->visitIcon);
    }

    public function visitLabel(string|Closure $label): static
    {
        $this->visitLabel = $label;

        return $this;
    }

    public function getVisitLabel(): string
    {
        return $this->evaluate($this->visitLabel);
    }

    public function prefix(string|Closure|null $label): static
    {
        $this->prefix = $label;

        return $this;
    }

    public function getPrefix(): ?string
    {
        $prefix = $this->evaluate($this->prefix);

        return is_string($prefix) && $prefix !== '' ? $prefix : null;
    }

    public function suffix(string|Closure|null $label): static
    {
        $this->suffix = $label;

        return $this;
    }

    public function getSuffix(): ?string
    {
        $suffix = $this->evaluate($this->suffix);

        return is_string($suffix) && $suffix !== '' ? $suffix : null;
    }

    public function getScrapeUrl(): string
    {
        return route('filament-flex-fields.url-meta.scrape', absolute: false);
    }

    /**
     * @return array{title?: string, description?: string, image?: string}|null
     */
    public function resolveInitialPreview(?string $url): ?array
    {
        if (! $this->isPreviewEnabled() || ! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        $scraper = app(UrlMetaScraper::class);

        if (! $scraper->isScrapableUrl($url)) {
            return null;
        }

        $preview = $scraper->scrape($url);

        return $preview === [] ? null : $preview;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAlpineConfiguration(): array
    {
        return [
            'scrapeUrl' => $this->getScrapeUrl(),
            'previewEnabled' => $this->isPreviewEnabled(),
            'previewDebounce' => $this->getPreviewDebounce(),
            'previewMinUrlLength' => $this->getPreviewMinUrlLength(),
            'previewMinSkeletonMs' => $this->getPreviewMinSkeletonMs(),
            'previewLayout' => $this->getPreviewLayout(),
            'showVisitLink' => $this->shouldShowVisitLink(),
            'prefix' => $this->getPrefix(),
            'labels' => [
                'error' => __('filament-flex-fields::default.link_preview.error'),
                'visit' => $this->getVisitLabel(),
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-link-preview-field' => true,
            'fff-link-preview-field--'.$this->getSize() => true,
            'fff-link-preview-field--'.$this->getVariant() => true,
            'fff-link-preview-field--layout-'.$this->getPreviewLayout() => true,
        ];
    }
}

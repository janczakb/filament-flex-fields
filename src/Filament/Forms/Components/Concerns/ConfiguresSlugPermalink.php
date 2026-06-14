<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Bjanczak\FilamentFlexFields\Support\Slug\SlugGenerator;
use Bjanczak\FilamentFlexFields\Support\Slug\SpatieSlugIntegration;
use Closure;

trait ConfiguresSlugPermalink
{
    protected string|Closure|null $urlHost = null;

    protected string|Closure|null $urlPath = null;

    protected bool|Closure $urlHostVisible = true;

    protected bool|Closure $urlPathVisible = true;

    protected bool|Closure $permalinkPreview = true;

    protected string|Closure|null $visitUrl = null;

    protected string|Closure|null $visitLinkLabel = null;

    protected string|Closure|null $permalinkLabel = null;

    protected string|Closure|null $slugLabelPostfix = null;

    public function urlHost(string|Closure|null $host): static
    {
        $this->urlHost = $host;

        return $this;
    }

    public function urlPath(string|Closure|null $path): static
    {
        $this->urlPath = $path;

        return $this;
    }

    public function urlHostVisible(bool|Closure $condition = true): static
    {
        $this->urlHostVisible = $condition;

        return $this;
    }

    public function urlPathVisible(bool|Closure $condition = true): static
    {
        $this->urlPathVisible = $condition;

        return $this;
    }

    public function permalinkPreview(bool|Closure $condition = true): static
    {
        $this->permalinkPreview = $condition;

        return $this;
    }

    public function permalinkLabel(string|Closure|null $label): static
    {
        $this->permalinkLabel = $label;

        return $this;
    }

    public function visitUrl(string|Closure|null $url): static
    {
        $this->visitUrl = $url;

        return $this;
    }

    public function visitRoute(string|Closure|null $route): static
    {
        return $this->visitUrl($route);
    }

    public function visitLinkLabel(string|Closure|null $label): static
    {
        $this->visitLinkLabel = $label;

        return $this;
    }

    public function slugLabelPostfix(string|Closure|null $postfix): static
    {
        $this->slugLabelPostfix = $postfix;

        return $this;
    }

    public function getPermalinkLabel(): string
    {
        $label = $this->evaluate($this->permalinkLabel);

        return filled($label)
            ? (string) $label
            : __('filament-flex-fields::default.slug.permalink');
    }

    public function getVisitLinkLabel(): string
    {
        $label = $this->evaluate($this->visitLinkLabel);

        return filled($label)
            ? (string) $label
            : __('filament-flex-fields::default.slug.visit');
    }

    public function getUrlHost(): ?string
    {
        $host = $this->evaluate($this->urlHost);

        return filled($host) ? rtrim((string) $host, '/') : null;
    }

    public function getUrlPath(): ?string
    {
        $path = $this->evaluate($this->urlPath);

        if (blank($path)) {
            return null;
        }

        $path = (string) $path;

        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return rtrim($path, '/').'/';
    }

    public function getDisplayUrlHost(): ?string
    {
        $host = $this->getUrlHost();

        if ($host === null) {
            return null;
        }

        $displayHost = preg_replace('#^https?://#i', '', $host);

        return filled($displayHost) ? $displayHost : $host;
    }

    public function hasUrlContext(): bool
    {
        return filled($this->getUrlHost())
            || filled($this->getUrlPath())
            || filled($this->getSlugLabelPostfix());
    }

    public function isUrlHostVisible(): bool
    {
        return (bool) $this->evaluate($this->urlHostVisible);
    }

    public function isUrlPathVisible(): bool
    {
        return (bool) $this->evaluate($this->urlPathVisible);
    }

    public function hasPermalinkPreview(): bool
    {
        return (bool) $this->evaluate($this->permalinkPreview);
    }

    public function canVisitLink(): bool
    {
        if (! $this->shouldShowVisitLink()) {
            return false;
        }

        return $this->getOperation() !== 'create';
    }

    public function getSlugLabelPostfix(): ?string
    {
        $postfix = $this->evaluate($this->slugLabelPostfix);

        return filled($postfix) ? (string) $postfix : null;
    }

    public function usesSelfHealingPermalink(): bool
    {
        if (! SpatieSlugIntegration::isAvailable()) {
            return false;
        }

        $record = $this->resolveRecord();

        if ($record === null) {
            return false;
        }

        return SpatieSlugIntegration::usesSelfHealingUrls($record, $this->getSpatieSlugField());
    }

    public function getPermalinkRecordKey(): int|string|null
    {
        return $this->resolveRecord()?->getKey();
    }

    public function getSelfHealingSeparator(): string
    {
        if (! SpatieSlugIntegration::isAvailable()) {
            return $this->getSeparator();
        }

        $record = $this->resolveRecord();

        if ($record === null) {
            return $this->getSeparator();
        }

        return SpatieSlugIntegration::getSelfHealingSeparator($record, $this->getSpatieSlugField());
    }

    public function resolvePermalinkSlugForUrl(string $slug): string
    {
        $slug = $this->normalizeSlug($slug);
        $record = $this->resolveRecord();

        if ($record === null || ! SpatieSlugIntegration::isAvailable()) {
            return $slug;
        }

        return SpatieSlugIntegration::buildSelfHealingRouteKey($slug, $record, $this->getSpatieSlugField());
    }

    public function getFullPermalinkUrl(?string $slug = null): ?string
    {
        if (blank($slug)) {
            return null;
        }

        $normalizedSlug = $this->normalizeSlug($slug);

        if (blank($normalizedSlug) && ! SlugGenerator::isHomepage($normalizedSlug)) {
            return null;
        }

        $permalinkSlug = $this->resolvePermalinkSlugForUrl($normalizedSlug);

        $visitUrl = $this->evaluate($this->visitUrl, [
            'slug' => $normalizedSlug,
            'routeKey' => $permalinkSlug,
            'record' => $this->resolveRecord(),
        ]);

        if (filled($visitUrl)) {
            return (string) $visitUrl;
        }

        $host = $this->getUrlHost();
        $path = $this->getUrlPath();
        $postfix = $this->getSlugLabelPostfix();

        if ($host === null && $path === null && $postfix === null) {
            return null;
        }

        $slugSegment = SlugGenerator::permalinkSlugSegment($permalinkSlug, $path, $this->allowsHomepageSlug());

        return ($host ?? '').($path ?? '').$slugSegment.($postfix ?? '');
    }
}

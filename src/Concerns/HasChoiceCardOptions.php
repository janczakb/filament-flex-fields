<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize as HasControlSizeConcern;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasChoiceCardOptions
{
    use HasControlSizeConcern;

    /**
     * @var array<string | int, string | array<string, mixed>> | Closure
     */
    protected array|Closure $options = [];

    /**
     * @var array<string|int, array{
     *     label: string,
     *     description: ?string,
     *     price: ?string,
     *     price_suffix: ?string,
     *     meta: ?string,
     *     icon: ?string,
     *     badge: ?string,
     *     badge_color: string,
     *     disabled: bool,
     * }>|null
     */
    protected ?array $normalizedOptionsCache = null;

    /**
     * @var array<string | int> | Closure
     */
    protected array|Closure $disabledOptions = [];

    protected string|Closure $layout = 'stack';

    protected int|array|Closure $gridColumns = 1;

    protected string|Closure|null $indicator = null;

    protected string|Closure $variant = 'default';

    protected string|Closure|null $color = null;

    protected bool|Closure $isRippleEnabled = false;

    /**
     * @param  array<string | int, string | array<string, mixed>> | Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  array<string | int> | Closure  $keys
     */
    public function disabledOptions(array|Closure $keys): static
    {
        $this->disabledOptions = $keys;

        return $this;
    }

    public function layout(string|Closure $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function gridColumns(int|array|Closure $columns): static
    {
        $this->gridColumns = $columns;

        return $this;
    }

    public function indicator(string|Closure|null $indicator): static
    {
        $this->indicator = $indicator;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function ripple(bool|Closure $condition = true): static
    {
        $this->isRippleEnabled = $condition;

        return $this;
    }

    /**
     * @return array<int, string|int>
     */
    public function getOptionKeys(): array
    {
        return array_keys($this->getNormalizedOptions());
    }

    /**
     * @return array<string|int, array{
     *     label: string,
     *     description: ?string,
     *     price: ?string,
     *     price_suffix: ?string,
     *     meta: ?string,
     *     icon: ?string,
     *     badge: ?string,
     *     badge_color: string,
     *     disabled: bool,
     * }>
     */
    public function getNormalizedOptions(): array
    {
        if ($this->normalizedOptionsCache !== null) {
            return $this->normalizedOptionsCache;
        }

        $disabledOptions = collect($this->getDisabledOptions())->map(fn ($key): string => (string) $key);
        $normalized = [];

        foreach ($this->evaluate($this->options) as $value => $option) {
            $key = is_int($value) ? (string) $value : (string) $value;

            if (is_string($option)) {
                $normalized[$key] = $this->normalizeOptionArray($key, [
                    'label' => $option,
                ], $disabledOptions);

                continue;
            }

            if (is_array($option)) {
                $normalized[$key] = $this->normalizeOptionArray($key, $option, $disabledOptions);
            }
        }

        return $this->normalizedOptionsCache = $normalized;
    }

    /**
     * @param  array<string, mixed>  $option
     * @return array{
     *     label: string,
     *     description: ?string,
     *     price: ?string,
     *     price_suffix: ?string,
     *     meta: ?string,
     *     icon: ?string,
     *     badge: ?string,
     *     badge_color: string,
     *     disabled: bool,
     * }
     */
    protected function normalizeOptionArray(string $key, array $option, Collection $disabledOptions): array
    {
        $price = $option['price'] ?? $option['value'] ?? null;
        $priceSuffix = $option['price_suffix'] ?? $option['suffix'] ?? null;

        return [
            'label' => (string) ($option['label'] ?? $key),
            'description' => filled($option['description'] ?? null) ? (string) $option['description'] : null,
            'price' => filled($price) ? (string) $price : null,
            'price_suffix' => filled($priceSuffix) ? (string) $priceSuffix : null,
            'meta' => filled($option['meta'] ?? null) ? (string) $option['meta'] : null,
            'icon' => filled($option['icon'] ?? null) ? (string) $option['icon'] : null,
            'badge' => filled($option['badge'] ?? null) ? (string) $option['badge'] : null,
            'badge_color' => (string) ($option['badge_color'] ?? 'success'),
            'disabled' => (bool) ($option['disabled'] ?? false) || $disabledOptions->contains($key),
        ];
    }

    /**
     * @return array<string | int>
     */
    public function getDisabledOptions(): array
    {
        return Arr::wrap($this->evaluate($this->disabledOptions));
    }

    public function getLayout(): string
    {
        return $this->evaluate($this->layout);
    }

    /**
     * @return array{default: int, sm: int, md: int, lg: int}
     */
    public function getGridColumnConfig(): array
    {
        $columns = $this->evaluate($this->gridColumns);

        if (is_int($columns)) {
            return [
                'default' => $columns,
                'sm' => $columns,
                'md' => $columns,
                'lg' => $columns,
            ];
        }

        if (! is_array($columns)) {
            return [
                'default' => 1,
                'sm' => 1,
                'md' => 1,
                'lg' => 1,
            ];
        }

        $default = max(1, min(4, (int) ($columns['default'] ?? 1)));
        $sm = max(1, min(4, (int) ($columns['sm'] ?? $default)));
        $md = max(1, min(4, (int) ($columns['md'] ?? $sm)));
        $lg = max(1, min(4, (int) ($columns['lg'] ?? $md)));

        return [
            'default' => $default,
            'sm' => $sm,
            'md' => $md,
            'lg' => $lg,
        ];
    }

    public function getVariant(): string
    {
        return $this->evaluate($this->variant);
    }

    public function getColor(): string
    {
        $color = $this->evaluate($this->color);

        if (filled($color)) {
            return (string) $color;
        }

        return 'primary';
    }

    public function isRippleEnabled(): bool
    {
        return (bool) $this->evaluate($this->isRippleEnabled);
    }

    public function isOptionDisabled(string|int $key): bool
    {
        return $this->getNormalizedOptions()[(string) $key]['disabled'] ?? true;
    }

    /**
     * @return array<string, string>
     */
    public function getChoiceCardSizeStyles(): array
    {
        return match ($this->getSize()) {
            'sm' => [
                '--fff-choice-cards-gap' => '0.375rem',
                '--fff-choice-cards-p' => '0.625rem',
                '--fff-choice-cards-radius' => '0.5rem',
                '--fff-choice-cards-indicator-inset' => '0.625rem',
                '--fff-choice-cards-indicator-size' => '0.875rem',
                '--fff-choice-cards-indicator-check-size' => '1rem',
                '--fff-choice-cards-indicator-icon-size' => '0.5rem',
                '--fff-choice-cards-content-pr' => '1rem',
                '--fff-choice-cards-featured-content-pr' => '1.5rem',
                '--fff-choice-cards-label-size' => '0.8125rem',
                '--fff-choice-cards-body-size' => '0.6875rem',
                '--fff-choice-cards-price-size' => '0.8125rem',
                '--fff-choice-cards-featured-price-size' => '1.125rem',
                '--fff-choice-cards-icon-box-size' => '1.375rem',
                '--fff-choice-cards-icon-size' => '0.75rem',
            ],
            'lg' => [
                '--fff-choice-cards-gap' => '1rem',
                '--fff-choice-cards-p' => '1.5rem',
                '--fff-choice-cards-radius' => '1rem',
                '--fff-choice-cards-indicator-inset' => '1.5rem',
                '--fff-choice-cards-indicator-size' => '1.25rem',
                '--fff-choice-cards-indicator-check-size' => '1.625rem',
                '--fff-choice-cards-indicator-icon-size' => '1rem',
                '--fff-choice-cards-content-pr' => '2.25rem',
                '--fff-choice-cards-featured-content-pr' => '3rem',
                '--fff-choice-cards-label-size' => '1.125rem',
                '--fff-choice-cards-body-size' => '0.9375rem',
                '--fff-choice-cards-price-size' => '1.125rem',
                '--fff-choice-cards-featured-price-size' => '2rem',
                '--fff-choice-cards-icon-box-size' => '2.75rem',
                '--fff-choice-cards-icon-size' => '1.25rem',
            ],
            default => [
                '--fff-choice-cards-gap' => '0.75rem',
                '--fff-choice-cards-p' => '1rem',
                '--fff-choice-cards-radius' => '0.75rem',
                '--fff-choice-cards-indicator-inset' => '1rem',
                '--fff-choice-cards-indicator-size' => '1rem',
                '--fff-choice-cards-indicator-check-size' => '1.25rem',
                '--fff-choice-cards-indicator-icon-size' => '0.75rem',
                '--fff-choice-cards-content-pr' => '1.5rem',
                '--fff-choice-cards-featured-content-pr' => '2.25rem',
                '--fff-choice-cards-label-size' => '0.9375rem',
                '--fff-choice-cards-body-size' => '0.8125rem',
                '--fff-choice-cards-price-size' => '0.9375rem',
                '--fff-choice-cards-featured-price-size' => '1.5rem',
                '--fff-choice-cards-icon-box-size' => '2rem',
                '--fff-choice-cards-icon-size' => '1rem',
            ],
        };
    }
}

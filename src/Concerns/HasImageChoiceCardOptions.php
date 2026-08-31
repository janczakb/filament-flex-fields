<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize as HasControlSizeConcern;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasImageChoiceCardOptions
{
    use HasControlSizeConcern;
    use HasFieldRounding;

    /**
     * @var array<string | int, string | array<string, mixed>> | Closure
     */
    protected array|Closure $options = [];

    /**
     * @var array<string|int, array{
     *     label: string,
     *     image: ?string,
     *     alt: string,
     *     disabled: bool,
     * }>|null
     */
    protected ?array $normalizedOptionsCache = null;

    /**
     * @var array<string | int> | Closure
     */
    protected array|Closure $disabledOptions = [];

    protected int|array|Closure $gridColumns = 4;

    protected string|Closure|null $indicator = null;

    protected string|Closure $variant = 'default';

    protected string|Closure|null $color = null;

    protected bool|Closure $isRippleEnabled = false;

    protected string|Closure $imageAspectRatio = '3/4';

    protected string|Closure $imageFit = 'cover';

    /**
     * @param  array<string | int, string | array<string, mixed>> | Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;
        $this->normalizedOptionsCache = null;

        return $this;
    }

    /**
     * @param  array<string | int> | Closure  $keys
     */
    public function disabledOptions(array|Closure $keys): static
    {
        $this->disabledOptions = $keys;
        $this->normalizedOptionsCache = null;

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

    public function imageAspectRatio(string|Closure $ratio): static
    {
        $this->imageAspectRatio = $ratio;

        return $this;
    }

    public function imageFit(string|Closure $fit): static
    {
        $this->imageFit = $fit;

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
     *     image: ?string,
     *     alt: string,
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
            $key = (string) $value;

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
     *     image: ?string,
     *     alt: string,
     *     disabled: bool,
     * }
     */
    protected function normalizeOptionArray(string $key, array $option, Collection $disabledOptions): array
    {
        $label = (string) ($option['label'] ?? $key);
        $image = filled($option['image'] ?? null) ? (string) $option['image'] : null;
        $alt = filled($option['alt'] ?? null) ? (string) $option['alt'] : $label;

        return [
            'label' => $label,
            'image' => $image,
            'alt' => $alt,
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

    /**
     * @return array{default: int, sm: int, md: int, lg: int, xl: int}
     */
    public function getGridColumnConfig(): array
    {
        $columns = $this->evaluate($this->gridColumns);

        if (is_int($columns)) {
            $columns = max(1, min(6, $columns));

            return [
                'default' => $columns,
                'sm' => $columns,
                'md' => $columns,
                'lg' => $columns,
                'xl' => $columns,
            ];
        }

        if (! is_array($columns)) {
            return [
                'default' => 1,
                'sm' => 1,
                'md' => 1,
                'lg' => 1,
                'xl' => 1,
            ];
        }

        $default = max(1, min(6, (int) ($columns['default'] ?? 1)));
        $sm = max(1, min(6, (int) ($columns['sm'] ?? $default)));
        $md = max(1, min(6, (int) ($columns['md'] ?? $sm)));
        $lg = max(1, min(6, (int) ($columns['lg'] ?? $md)));
        $xl = max(1, min(6, (int) ($columns['xl'] ?? $lg)));

        return [
            'default' => $default,
            'sm' => $sm,
            'md' => $md,
            'lg' => $lg,
            'xl' => $xl,
        ];
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        return in_array($variant, ['default', 'overlay'], true) ? $variant : 'default';
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

    public function getImageAspectRatio(): string
    {
        $ratio = (string) $this->evaluate($this->imageAspectRatio);

        return filled($ratio) ? $ratio : '3/4';
    }

    public function getImageFit(): string
    {
        $fit = (string) $this->evaluate($this->imageFit);

        return in_array($fit, ['cover', 'contain', 'fill'], true) ? $fit : 'cover';
    }

    public function isOptionDisabled(string|int $key): bool
    {
        return $this->getNormalizedOptions()[(string) $key]['disabled'] ?? true;
    }

    /**
     * @return array<string, string>
     */
    public function getImageChoiceCardSizeStyles(): array
    {
        return match ($this->getSize()) {
            'sm' => [
                '--fff-image-choice-cards-gap' => '0.5rem',
                '--fff-image-choice-cards-footer-p' => '0.5rem 0.625rem',
                '--fff-image-choice-cards-footer-band' => '2.25rem',
                '--fff-image-choice-cards-label-size' => '0.75rem',
                '--fff-image-choice-cards-indicator-size' => '1rem',
                '--fff-image-choice-cards-indicator-icon-size' => '0.625rem',
            ],
            'lg' => [
                '--fff-image-choice-cards-gap' => '1rem',
                '--fff-image-choice-cards-footer-p' => '0.875rem 1rem',
                '--fff-image-choice-cards-footer-band' => '3.25rem',
                '--fff-image-choice-cards-label-size' => '1rem',
                '--fff-image-choice-cards-indicator-size' => '1.375rem',
                '--fff-image-choice-cards-indicator-icon-size' => '0.875rem',
            ],
            default => [
                '--fff-image-choice-cards-gap' => '0.75rem',
                '--fff-image-choice-cards-footer-p' => '0.625rem 0.75rem',
                '--fff-image-choice-cards-footer-band' => '2.75rem',
                '--fff-image-choice-cards-label-size' => '0.875rem',
                '--fff-image-choice-cards-indicator-size' => '1.125rem',
                '--fff-image-choice-cards-indicator-icon-size' => '0.75rem',
            ],
        };
    }
}

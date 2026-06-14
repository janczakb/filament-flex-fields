<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasChecklistOptions
{
    use HasControlSize;

    /**
     * @var array<string | int, string | array<string, mixed>> | Closure
     */
    protected array|Closure $options = [];

    /**
     * @var array<string | int, string> | Closure
     */
    protected array|Closure $icons = [];

    /**
     * @var array<string | int, string> | Closure
     */
    protected array|Closure $descriptions = [];

    /**
     * @var array<string | int> | Closure
     */
    protected array|Closure $disabledOptions = [];

    /**
     * @param  array<string | int, string | array<string, mixed>> | Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param  array<string | int, string> | Closure  $icons
     */
    public function icons(array|Closure $icons): static
    {
        $this->icons = $icons;

        return $this;
    }

    /**
     * @param  array<string | int, string> | Closure  $descriptions
     */
    public function descriptions(array|Closure $descriptions): static
    {
        $this->descriptions = $descriptions;

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
     *     icon: ?string,
     *     disabled: bool,
     * }>
     */
    public function getNormalizedOptions(): array
    {
        $disabledOptions = collect($this->getDisabledOptions())->map(fn ($key): string => (string) $key);
        $icons = collect($this->evaluate($this->icons))->mapWithKeys(
            fn (mixed $icon, string|int $key): array => [(string) $key => (string) $icon],
        );
        $descriptions = collect($this->evaluate($this->descriptions))->mapWithKeys(
            fn (mixed $description, string|int $key): array => [(string) $key => (string) $description],
        );
        $normalized = [];

        foreach ($this->evaluate($this->options) as $value => $option) {
            $key = is_int($value) ? (string) $value : (string) $value;

            if (is_string($option)) {
                $normalized[$key] = $this->normalizeOptionArray($key, [
                    'label' => $option,
                ], $disabledOptions, $icons, $descriptions);

                continue;
            }

            if (is_array($option)) {
                $normalized[$key] = $this->normalizeOptionArray($key, $option, $disabledOptions, $icons, $descriptions);
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $option
     * @param  Collection<int|string, string>  $icons
     * @param  Collection<int|string, string>  $descriptions
     * @return array{
     *     label: string,
     *     description: ?string,
     *     icon: ?string,
     *     disabled: bool,
     * }
     */
    protected function normalizeOptionArray(
        string $key,
        array $option,
        Collection $disabledOptions,
        Collection $icons,
        Collection $descriptions,
    ): array {
        $icon = $option['icon'] ?? $icons->get($key);
        $description = $option['description'] ?? $option['desc'] ?? $descriptions->get($key);

        return [
            'label' => (string) ($option['label'] ?? $key),
            'description' => filled($description) ? (string) $description : null,
            'icon' => filled($icon) ? (string) $icon : null,
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

    public function isOptionDisabled(string|int $key): bool
    {
        return $this->getNormalizedOptions()[(string) $key]['disabled'] ?? true;
    }

    /**
     * @return array<string, string>
     */
    public function getChecklistSizeStyles(): array
    {
        return match ($this->getSize()) {
            'sm' => [
                '--fff-flex-checklist-radius' => '0.625rem',
                '--fff-flex-checklist-row-py' => '0.5rem',
                '--fff-flex-checklist-row-px' => '0.625rem',
                '--fff-flex-checklist-gap' => '0.625rem',
                '--fff-flex-checklist-label-size' => '0.8125rem',
                '--fff-flex-checklist-body-size' => '0.6875rem',
                '--fff-flex-checklist-icon-size' => '0.9375rem',
                '--fff-flex-checklist-icon-box-size' => '1.5rem',
                '--fff-flex-checklist-indicator-size' => '0.875rem',
                '--fff-flex-checklist-indicator-icon-size' => '0.5rem',
                '--fff-flex-checklist-lock-size' => '0.875rem',
            ],
            'lg' => [
                '--fff-flex-checklist-radius' => '1rem',
                '--fff-flex-checklist-row-py' => '0.875rem',
                '--fff-flex-checklist-row-px' => '1rem',
                '--fff-flex-checklist-gap' => '1rem',
                '--fff-flex-checklist-label-size' => '1rem',
                '--fff-flex-checklist-body-size' => '0.875rem',
                '--fff-flex-checklist-icon-size' => '1.125rem',
                '--fff-flex-checklist-icon-box-size' => '2.25rem',
                '--fff-flex-checklist-indicator-size' => '1.25rem',
                '--fff-flex-checklist-indicator-icon-size' => '1rem',
                '--fff-flex-checklist-lock-size' => '1.125rem',
            ],
            default => [
                '--fff-flex-checklist-radius' => '0.75rem',
                '--fff-flex-checklist-row-py' => '0.625rem',
                '--fff-flex-checklist-row-px' => '0.75rem',
                '--fff-flex-checklist-gap' => '0.75rem',
                '--fff-flex-checklist-label-size' => '0.875rem',
                '--fff-flex-checklist-body-size' => '0.75rem',
                '--fff-flex-checklist-icon-size' => '1rem',
                '--fff-flex-checklist-icon-box-size' => '1.75rem',
                '--fff-flex-checklist-indicator-size' => '1rem',
                '--fff-flex-checklist-indicator-icon-size' => '0.75rem',
                '--fff-flex-checklist-lock-size' => '1rem',
            ],
        };
    }
}

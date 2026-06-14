<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\StateCasts\PriceRangeFieldStateCast;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use InvalidArgumentException;

class PriceRangeField extends Field
{
    use HasControlSize;

    protected string $view = 'filament-flex-fields::forms.components.price-range-field';

    protected int|float|Closure $min = 0;

    protected int|float|Closure $max = 1000;

    protected int|float|Closure $step = 1;

    protected bool|Closure $isInteger = true;

    protected int|Closure|null $decimalPlaces = null;

    protected string|Closure|null $prefix = null;

    protected string|Closure $variant = 'primary';

    protected bool|Closure $showInputs = true;

    protected string|Closure|null $minInputLabel = null;

    protected string|Closure|null $maxInputLabel = null;

    /**
     * @var list<int | float> | Closure
     */
    protected array|Closure $histogram = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(function (PriceRangeField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! is_array($value)) {
                    $fail(__('validation.array', ['attribute' => $attribute]));

                    return;
                }

                $min = $value['min'] ?? null;
                $max = $value['max'] ?? null;

                if (! is_numeric($min) || ! is_numeric($max)) {
                    $fail(__('filament-flex-fields::default.validation.price_range.invalid'));

                    return;
                }

                $min = (float) $min;
                $max = (float) $max;

                if ($min < $component->getMin() || $max > $component->getMax()) {
                    $fail(__('filament-flex-fields::default.validation.price_range.out_of_bounds'));

                    return;
                }

                if ($min > $max) {
                    $fail(__('filament-flex-fields::default.validation.price_range.min_greater_than_max'));
                }
            };
        });
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            app(PriceRangeFieldStateCast::class, ['field' => $this]),
        ];
    }

    public function min(int|float|Closure $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function max(int|float|Closure $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function step(int|float|Closure $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function integer(bool|Closure $condition = true): static
    {
        $this->isInteger = $condition;

        return $this;
    }

    public function decimalPlaces(int|Closure|null $places): static
    {
        $this->decimalPlaces = $places;

        return $this;
    }

    public function prefix(string|Closure|null $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function withoutPrefix(): static
    {
        return $this->prefix(null);
    }

    public function hasPrefix(): bool
    {
        return filled($this->getPrefix());
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function showInputs(bool|Closure $condition = true): static
    {
        $this->showInputs = $condition;

        return $this;
    }

    public function minInputLabel(string|Closure|null $label): static
    {
        $this->minInputLabel = $label;

        return $this;
    }

    public function maxInputLabel(string|Closure|null $label): static
    {
        $this->maxInputLabel = $label;

        return $this;
    }

    /**
     * @param  list<int | float> | Closure  $heights
     */
    public function histogram(array|Closure $heights): static
    {
        $this->histogram = $heights;

        return $this;
    }

    public function getMin(): int|float
    {
        return $this->evaluate($this->min);
    }

    public function getMax(): int|float
    {
        return $this->evaluate($this->max);
    }

    public function getStep(): int|float
    {
        return $this->evaluate($this->step);
    }

    public function isInteger(): bool
    {
        return (bool) $this->evaluate($this->isInteger);
    }

    public function getDecimalPlaces(): ?int
    {
        $places = $this->evaluate($this->decimalPlaces);

        return $places === null ? null : (int) $places;
    }

    public function getPrefix(): ?string
    {
        return $this->evaluate($this->prefix);
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        $variant = match ($variant) {
            'bordered' => 'primary',
            'faded' => 'secondary',
            default => $variant,
        };

        if (! in_array($variant, ['primary', 'secondary', 'flat'], true)) {
            throw new InvalidArgumentException("Price range variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function shouldShowInputs(): bool
    {
        return (bool) $this->evaluate($this->showInputs);
    }

    public function getMinInputLabel(): string
    {
        $label = $this->evaluate($this->minInputLabel);

        return filled($label)
            ? (string) $label
            : __('filament-flex-fields::default.price_range.min');
    }

    public function getMaxInputLabel(): string
    {
        $label = $this->evaluate($this->maxInputLabel);

        return filled($label)
            ? (string) $label
            : __('filament-flex-fields::default.price_range.max');
    }

    /**
     * @return list<float>
     */
    public function getHistogram(): array
    {
        $heights = $this->evaluate($this->histogram);

        if ($heights === []) {
            return $this->defaultHistogram();
        }

        return collect($heights)
            ->map(fn ($height): float => max(8.0, min(100.0, (float) $height)))
            ->values()
            ->all();
    }

    /**
     * @return list<float>
     */
    public function defaultHistogram(): array
    {
        return [
            30, 74, 85, 36, 98, 86, 30, 30, 55, 55, 40, 80, 95, 96, 63, 64, 68, 30, 47, 54,
            76, 30, 30, 30, 83, 30, 50, 45, 93, 56, 95, 30,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{min: int|float, max: int|float}
     */
    public function normalizeState(array $state): array
    {
        $minBound = $this->getMin();
        $maxBound = $this->getMax();

        $min = $this->normalizeValue($state['min'] ?? $minBound, $minBound, $maxBound);
        $max = $this->normalizeValue($state['max'] ?? $maxBound, $minBound, $maxBound);

        if ($min > $max) {
            $max = $min;
        }

        return [
            'min' => $min,
            'max' => $max,
        ];
    }

    protected function normalizeValue(mixed $value, int|float $minBound, int|float $maxBound): int|float
    {
        $numeric = is_numeric($value) ? (float) $value : (float) $minBound;
        $clamped = max($minBound, min($maxBound, $numeric));
        $stepped = round($clamped / $this->getStep()) * $this->getStep();

        if ($this->isInteger()) {
            return (int) round($stepped);
        }

        $places = $this->getDecimalPlaces();

        return $places === null ? $stepped : round($stepped, $places);
    }

    /**
     * @return array<string, string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-price-range-field',
            'fff-price-range-field--'.$this->getSize(),
            'fff-price-range-field--'.$this->getVariant(),
        ];
    }
}

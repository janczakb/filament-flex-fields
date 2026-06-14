<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Forms\Components\Contracts\CanHaveNumericState;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\NumberStateCast;
use Illuminate\Contracts\Support\Htmlable;

class NumberStepper extends Field implements CanHaveNumericState
{
    use HasControlSize;

    protected string $view = 'filament-flex-fields::forms.components.number-stepper';

    /**
     * @var scalar | Closure | null
     */
    protected $minValue = null;

    /**
     * @var scalar | Closure | null
     */
    protected $maxValue = null;

    protected int|float|Closure $step = 1;

    protected bool|Closure $isInteger = true;

    protected bool|Closure $isNullable = false;

    protected string|Closure $variant = 'default';

    protected string|Closure|null $displayPrefix = null;

    protected string|Closure|null $displaySuffix = null;

    protected string|Closure|null $nullLabel = null;

    protected string|BackedEnum|Htmlable|Closure|null $decrementIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $incrementIcon = null;

    protected bool|Closure $isReversed = false;

    protected int|Closure|null $decimalPlaces = null;

    protected bool|Closure $isWheelAnimated = true;

    /**
     * @param  scalar | Closure | null  $value
     */
    public function minValue($value): static
    {
        $this->minValue = $value;

        $this->rule(static function (NumberStepper $component): string {
            $value = $component->getMinValue();

            return "min:{$value}";
        }, static fn (NumberStepper $component): bool => filled($component->getMinValue()) && ! $component->isNullable());

        return $this;
    }

    /**
     * @param  scalar | Closure | null  $value
     */
    public function maxValue($value): static
    {
        $this->maxValue = $value;

        $this->rule(static function (NumberStepper $component): string {
            $value = $component->getMaxValue();

            return "max:{$value}";
        }, static fn (NumberStepper $component): bool => filled($component->getMaxValue()));

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

        $this->rule('integer', $condition);

        return $this;
    }

    public function nullable(bool|Closure $condition = true): static
    {
        $this->isNullable = $condition;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function prefix(string|Closure|null $prefix): static
    {
        $this->displayPrefix = $prefix;

        return $this;
    }

    public function suffix(string|Closure|null $suffix): static
    {
        $this->displaySuffix = $suffix;

        return $this;
    }

    public function nullLabel(string|Closure|null $label): static
    {
        $this->nullLabel = $label;

        return $this;
    }

    public function decrementIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->decrementIcon = $icon;

        return $this;
    }

    public function incrementIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->incrementIcon = $icon;

        return $this;
    }

    /**
     * @param  array{decrement?: string|BackedEnum|Htmlable|null, increment?: string|BackedEnum|Htmlable|null} | Closure  $icons
     */
    public function icons(array|Closure $icons): static
    {
        $icons = $this->evaluate($icons);

        if (array_key_exists('decrement', $icons)) {
            $this->decrementIcon = $icons['decrement'];
        }

        if (array_key_exists('increment', $icons)) {
            $this->incrementIcon = $icons['increment'];
        }

        return $this;
    }

    public function reversed(bool|Closure $condition = true): static
    {
        $this->isReversed = $condition;

        return $this;
    }

    public function decimalPlaces(int|Closure|null $places): static
    {
        $this->decimalPlaces = $places;

        return $this;
    }

    public function wheelAnimated(bool|Closure $condition = true): static
    {
        $this->isWheelAnimated = $condition;

        return $this;
    }

    /**
     * @return scalar | null
     */
    public function getMinValue(): mixed
    {
        return $this->evaluate($this->minValue);
    }

    /**
     * @return scalar | null
     */
    public function getMaxValue(): mixed
    {
        return $this->evaluate($this->maxValue);
    }

    public function getStep(): int|float
    {
        return $this->evaluate($this->step);
    }

    public function isInteger(): bool
    {
        return (bool) $this->evaluate($this->isInteger);
    }

    public function isNullable(): bool
    {
        return (bool) $this->evaluate($this->isNullable);
    }

    public function getVariant(): string
    {
        return $this->evaluate($this->variant);
    }

    public function getDisplayPrefix(): ?string
    {
        return $this->evaluate($this->displayPrefix);
    }

    public function getDisplaySuffix(): ?string
    {
        return $this->evaluate($this->displaySuffix);
    }

    public function getNullLabel(): ?string
    {
        return $this->evaluate($this->nullLabel);
    }

    public function getDefaultDecrementIcon(): string|BackedEnum|Htmlable
    {
        $icon = config('filament-flex-fields.ui.number_stepper_decrement_icon');

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return GravityIcon::Minus;
    }

    public function getDefaultIncrementIcon(): string|BackedEnum|Htmlable
    {
        $icon = config('filament-flex-fields.ui.number_stepper_increment_icon');

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return GravityIcon::Plus;
    }

    public function getDecrementIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->decrementIcon);

        return $icon ?? $this->getDefaultDecrementIcon();
    }

    public function getIncrementIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->incrementIcon);

        return $icon ?? $this->getDefaultIncrementIcon();
    }

    public function isReversed(): bool
    {
        return (bool) $this->evaluate($this->isReversed);
    }

    public function getDecimalPlaces(): ?int
    {
        $places = $this->evaluate($this->decimalPlaces);

        return $places === null ? null : (int) $places;
    }

    public function isWheelAnimated(): bool
    {
        return (bool) $this->evaluate($this->isWheelAnimated);
    }

    public function getWidthAnchorText(): string
    {
        $mainAnchor = $this->getWidthAnchorMainText();
        $candidates = [$mainAnchor];

        $suffix = $this->getDisplaySuffix();

        if (filled($suffix)) {
            $candidates[] = $mainAnchor."\u{00a0}".$suffix;
        }

        if ($this->isNullable()) {
            $candidates[] = $this->getNullLabel() ?? '—';
        }

        return $this->pickWidestString($candidates);
    }

    public function getWidthAnchorMainText(): string
    {
        return $this->formatBucketedDisplay(88.0);
    }

    public function formatBucketedDisplay(float $value): string
    {
        $prefix = $this->getDisplayPrefix() ?? '';
        $decimalPlaces = $this->getDecimalPlaces();

        $intPart = (string) (int) floor(abs($value));
        $digitCount = strlen($intPart);
        $bucketSize = $digitCount <= 2 ? 2 : $digitCount;
        $paddedInt = str_repeat('8', $bucketSize);

        if ($decimalPlaces !== null) {
            $formatted = $paddedInt.'.'.str_repeat('8', $decimalPlaces);
        } else {
            $formatted = $paddedInt;
        }

        return $prefix.$formatted;
    }

    public function hasDisplayValue(mixed $value): bool
    {
        return $this->normalizeDisplayValue($value) !== null;
    }

    public function formatDisplayValue(mixed $value): ?string
    {
        $main = $this->formatDisplayMain($value);

        if ($main === null) {
            return null;
        }

        $suffix = $this->getDisplaySuffix();

        if (filled($suffix)) {
            return $main."\u{00a0}".$suffix;
        }

        return $main;
    }

    public function formatDisplayMain(mixed $value): ?string
    {
        $numeric = $this->normalizeDisplayValue($value);

        if ($numeric === null) {
            return null;
        }

        if ($this->isInteger()) {
            $numeric = (int) round($numeric);
        }

        $decimalPlaces = $this->getDecimalPlaces();

        $formatted = $decimalPlaces === null
            ? (string) $numeric
            : number_format((float) $numeric, $decimalPlaces, '.', '');

        $prefix = $this->getDisplayPrefix();

        if (filled($prefix)) {
            $formatted = $prefix.$formatted;
        }

        return $formatted;
    }

    public function getInitialSizerText(mixed $state): string
    {
        $candidates = [$this->getWidthAnchorText()];

        if ($this->hasDisplayValue($state)) {
            $numeric = $this->normalizeDisplayValue($state);

            if ($numeric !== null) {
                $bucketedMain = $this->formatBucketedDisplay($numeric);
                $suffix = $this->getDisplaySuffix();

                $candidates[] = filled($suffix)
                    ? $bucketedMain."\u{00a0}".$suffix
                    : $bucketedMain;
            }

            $displayValue = $this->formatDisplayValue($state);

            if ($displayValue !== null) {
                $candidates[] = $displayValue;
            }
        } elseif ($this->isNullable()) {
            $candidates[] = $this->getNullLabel() ?? '—';
        }

        return $this->pickWidestString($candidates);
    }

    protected function normalizeDisplayValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param  array<int, string>  $candidates
     */
    protected function pickWidestString(array $candidates): string
    {
        $widest = '';

        foreach ($candidates as $candidate) {
            if (mb_strlen($candidate) > mb_strlen($widest)) {
                $widest = $candidate;
            }
        }

        return $widest;
    }

    public function isNumeric(): bool
    {
        return true;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            app(NumberStateCast::class, ['isNullable' => $this->isNullable()]),
        ];
    }
}

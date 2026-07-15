<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldRounding;
use Bjanczak\FilamentFlexFields\Concerns\HasNumericInputOptions;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Contracts\CanHaveNumericState;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\NumberStateCast;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class CalculatorField extends Field implements CanHaveNumericState
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasFieldRounding;
    use HasNumericInputOptions;
    use HasPlaceholder;

    protected string $view = 'filament-flex-fields::forms.components.calculator-field';

    protected string|Closure $variant = 'primary';

    /**
     * @var scalar | Closure | null
     */
    protected $minValue = null;

    /**
     * @var scalar | Closure | null
     */
    protected $maxValue = null;

    protected int|float|Closure $step = 1;

    protected bool|Closure $isInteger = false;

    protected int|Closure|null $decimalPlaces = null;

    protected string|BackedEnum|Htmlable|Closure|null $calculatorIcon = null;

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat', 'soft'], true)) {
            throw new InvalidArgumentException("Calculator field variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            app(NumberStateCast::class, ['field' => $this]),
        ];
    }

    /**
     * @param  scalar | Closure | null  $value
     */
    public function minValue($value): static
    {
        $this->minValue = $value;

        $this->rule(static function (CalculatorField $component): string {
            $value = $component->getMinValue();

            return "min:{$value}";
        }, static fn (CalculatorField $component): bool => filled($component->getMinValue()));

        return $this;
    }

    /**
     * @param  scalar | Closure | null  $value
     */
    public function maxValue($value): static
    {
        $this->maxValue = $value;

        $this->rule(static function (CalculatorField $component): string {
            $value = $component->getMaxValue();

            return "max:{$value}";
        }, static fn (CalculatorField $component): bool => filled($component->getMaxValue()));

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

    public function decimalPlaces(int|Closure|null $places): static
    {
        $this->decimalPlaces = $places;

        return $this;
    }

    public function calculatorIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->calculatorIcon = $icon;

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

    public function isNumeric(): bool
    {
        return true;
    }

    public function getDecimalPlaces(): ?int
    {
        $places = $this->evaluate($this->decimalPlaces);

        return is_int($places) ? $places : null;
    }

    public function getCalculatorIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->calculatorIcon);

        if (filled($icon)) {
            return $icon;
        }

        return GravityIcon::make('calculator');
    }

    public function getCalculatorFieldId(): string
    {
        try {
            $statePath = $this->getStatePath();

            if (filled($statePath)) {
                return $statePath;
            }
        } catch (\Throwable) {
            //
        }

        $name = $this->getName();

        if (filled($name)) {
            return $name;
        }

        return spl_object_hash($this);
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-calculator-field',
            'fff-flex-text-input-field',
            'fff-calculator-field--'.$this->getSize(),
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-rounding-'.$this->getRounding(),
            'fff-calculator-field--'.$this->getVariant(),
            'fff-flex-text-input-field--'.$this->getVariant(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Closure;
use Filament\Forms\Components\Contracts\CanHaveNumericState;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\NumberStateCast;

class TrackSlider extends Field implements CanHaveNumericState
{
    use HasControlSize;

    protected string $view = 'filament-flex-fields::forms.components.track-slider';

    protected int|float|Closure $min = 0;

    protected int|float|Closure $max = 100;

    protected int|float|Closure $step = 1;

    protected bool|Closure $isInteger = true;

    protected bool|Closure $showOutput = true;

    protected string|Closure|null $displaySuffix = null;

    protected string|Closure $variant = 'default';

    protected int|Closure|null $decimalPlaces = null;

    protected string|Closure|null $trackLabel = null;

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

    public function showOutput(bool|Closure $condition = true): static
    {
        $this->showOutput = $condition;

        return $this;
    }

    public function suffix(string|Closure|null $suffix): static
    {
        $this->displaySuffix = $suffix;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function decimalPlaces(int|Closure|null $places): static
    {
        $this->decimalPlaces = $places;

        return $this;
    }

    public function trackLabel(string|Closure|null $label): static
    {
        $this->trackLabel = $label;

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

    public function shouldShowOutput(): bool
    {
        return (bool) $this->evaluate($this->showOutput);
    }

    public function getDisplaySuffix(): ?string
    {
        return $this->evaluate($this->displaySuffix);
    }

    public function getVariant(): string
    {
        return $this->evaluate($this->variant);
    }

    public function getDecimalPlaces(): ?int
    {
        $places = $this->evaluate($this->decimalPlaces);

        return $places === null ? null : (int) $places;
    }

    public function getTrackLabel(): ?string
    {
        return $this->evaluate($this->trackLabel);
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
            app(NumberStateCast::class, ['isNullable' => false]),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Closure;
use Filament\Forms\Components\Slider;
use Filament\Forms\Components\Slider\Enums\PipsMode;

class FlexSlider extends Slider
{
    use HasControlSize;

    private const MAX_STEP_DOTS = 11;

    protected string $view = 'filament-flex-fields::forms.components.flex-slider';

    protected bool|Closure $showValue = false;

    protected string|Closure|null $displayPrefix = null;

    protected string|Closure|null $displaySuffix = null;

    protected string|Closure $variant = 'default';

    protected string|Closure|null $trackLabel = null;

    protected bool|Closure $hideThumbUntilInteraction = false;

    protected string|Closure $valuePosition = 'end';

    protected bool|Closure $autoFill = false;

    protected string|Closure|null $color = 'primary';

    protected string|Closure|null $fillColor = null;

    protected bool|Closure $showStepDots = false;

    public function showValue(bool|Closure $condition = true): static
    {
        $this->showValue = $condition;

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

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function trackLabel(string|Closure|null $label): static
    {
        $this->trackLabel = $label;

        return $this;
    }

    public function hideThumbUntilInteraction(bool|Closure $condition = true): static
    {
        $this->hideThumbUntilInteraction = $condition;

        return $this;
    }

    public function valuePosition(string|Closure $position): static
    {
        $this->valuePosition = $position;

        return $this;
    }

    public function autoFill(bool|Closure $condition = true): static
    {
        $this->autoFill = $condition;

        return $this;
    }

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function fillColor(string|Closure|null $color): static
    {
        $this->fillColor = $color;

        return $this;
    }

    public function showStepDots(bool|Closure $condition = true): static
    {
        $this->showStepDots = $condition;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->evaluate($this->color);
    }

    public function getFillColor(): ?string
    {
        return $this->evaluate($this->fillColor);
    }

    public function shouldShowValue(): bool
    {
        return (bool) $this->evaluate($this->showValue);
    }

    public function getDisplayPrefix(): ?string
    {
        return $this->evaluate($this->displayPrefix);
    }

    public function getDisplaySuffix(): ?string
    {
        return $this->evaluate($this->displaySuffix);
    }

    public function getVariant(): string
    {
        return $this->evaluate($this->variant);
    }

    public function getTrackLabel(): ?string
    {
        return $this->evaluate($this->trackLabel);
    }

    public function shouldHideThumbUntilInteraction(): bool
    {
        return (bool) $this->evaluate($this->hideThumbUntilInteraction);
    }

    public function getValuePosition(): string
    {
        return $this->evaluate($this->valuePosition);
    }

    public function shouldAutoFill(): bool
    {
        return (bool) $this->evaluate($this->autoFill);
    }

    public function getMinValueWithPadding(): int|float
    {
        if ($this->getRangePadding() !== null) {
            return parent::getMinValueWithPadding();
        }

        return $this->getMinValue();
    }

    public function getMaxValueWithPadding(): int|float
    {
        if ($this->getRangePadding() !== null) {
            return parent::getMaxValueWithPadding();
        }

        return $this->getMaxValue();
    }

    /**
     * @return array<int, float>
     */
    public function getNormalizedStateValues(): array
    {
        $state = $this->resolveStateForChrome();

        if (is_array($state)) {
            return array_map(
                fn (mixed $value): float => $this->normalizeNumeric((float) $value),
                $state,
            );
        }

        if ($state === null) {
            return [$this->normalizeNumeric((float) $this->getMinValue())];
        }

        return [$this->normalizeNumeric((float) $state)];
    }

    public function isRangeState(): bool
    {
        return is_array($this->resolveStateForChrome());
    }

    public function valueToPercent(float|int $value): float
    {
        $range = $this->getMaxValue() - $this->getMinValue();

        if ($range <= 0) {
            return 0;
        }

        return min(100, max(0, (((float) $value - $this->getMinValue()) / $range) * 100));
    }

    public function valueToRatio(float|int $value): float
    {
        $range = (float) $this->getMaxValue() - (float) $this->getMinValue();

        if ($range <= 0) {
            return 0.0;
        }

        $normalized = $this->normalizeNumeric((float) $value);

        return max(0, min(1, ($normalized - (float) $this->getMinValue()) / $range));
    }

    /**
     * @return array<int, float>
     */
    public function getInitialValueRatios(): array
    {
        return array_map(
            fn (float $value): float => $this->valueToRatio($value),
            $this->getNormalizedStateValues(),
        );
    }

    public function formatRatio(float $ratio): string
    {
        return rtrim(rtrim(sprintf('%.6F', max(0, min(1, $ratio))), '0'), '.');
    }

    public function thumbChromeVariables(float $value): string
    {
        return '--fff-flex-slider-value-ratio: '.$this->formatRatio($this->valueToRatio($value)).';';
    }

    public function shouldShowStepDots(): bool
    {
        if (! (bool) $this->evaluate($this->showStepDots)) {
            return false;
        }

        if ($this->isVertical()) {
            return false;
        }

        $step = $this->getStep();

        if (! filled($step) || (float) $step <= 0) {
            return false;
        }

        $range = (float) $this->getMaxValue() - (float) $this->getMinValue();

        if ($range <= 0) {
            return false;
        }

        $count = (int) round($range / (float) $step) + 1;

        return $count >= 2 && $count <= 101;
    }

    /**
     * @return array<int, float>
     */
    public function getStepDotRatios(): array
    {
        if (! $this->shouldShowStepDots()) {
            return [];
        }

        $step = (float) $this->getStep();
        $min = (float) $this->getMinValue();
        $max = (float) $this->getMaxValue();
        $ratios = [];

        for ($value = $min; $value <= $max + ($step / 2); $value += $step) {
            $normalized = min($max, max($min, $this->normalizeNumeric($value)));
            $ratios[] = $this->valueToRatio($normalized);

            if ($normalized >= $max) {
                break;
            }
        }

        return $this->limitStepDotRatios(array_values(array_unique(array_map(
            fn (float $ratio): float => round($ratio, 6),
            $ratios,
        ))));
    }

    /**
     * @param  array<int, float>  $ratios
     * @return array<int, float>
     */
    protected function limitStepDotRatios(array $ratios): array
    {
        if (count($ratios) <= self::MAX_STEP_DOTS) {
            return $ratios;
        }

        $sampled = [];
        $lastIndex = count($ratios) - 1;

        for ($index = 0; $index < self::MAX_STEP_DOTS; $index++) {
            $sourceIndex = (int) round($index * $lastIndex / (self::MAX_STEP_DOTS - 1));
            $sampled[] = $ratios[$sourceIndex];
        }

        return array_values(array_unique($sampled));
    }

    public function stepDotStyle(float $ratio): string
    {
        return '--fff-flex-slider-step-ratio: '.$this->formatRatio($ratio).';';
    }

    public function shouldRenderServerPips(): bool
    {
        if ($this->isVertical() || ! filled($this->getPipsMode())) {
            return false;
        }

        return in_array($this->getPipsMode(), [
            PipsMode::Steps,
            PipsMode::Positions,
            PipsMode::Count,
        ], true);
    }

    /**
     * @return array<int, array{ratio: float, label: ?string, size: string}>
     */
    public function getServerRenderedPips(): array
    {
        if (! $this->shouldRenderServerPips()) {
            return [];
        }

        return match ($this->getPipsMode()) {
            PipsMode::Steps => $this->buildStepsModePips(),
            PipsMode::Positions => $this->buildPositionsModePips(),
            PipsMode::Count => $this->buildCountModePips(),
            default => [],
        };
    }

    /**
     * @return array<int, array{ratio: float, label: ?string, size: string}>
     */
    protected function buildStepsModePips(): array
    {
        $step = (float) $this->getStep();

        if ($step <= 0) {
            return [];
        }

        $min = (float) $this->getMinValue();
        $max = (float) $this->getMaxValue();
        $density = max(1, $this->getPipsDensity() ?? 10);
        $positionPercents = [];

        for ($value = $min; $value <= $max + ($step / 2); $value += $step) {
            $normalized = min($max, max($min, $this->normalizeNumeric($value)));
            $positionPercents[] = $this->valueToRatio($normalized) * 100;

            if ($normalized >= $max) {
                break;
            }
        }

        return $this->buildPipsFromLabeledPoints($positionPercents);
    }

    /**
     * @return array<int, array{ratio: float, label: ?string, size: string}>
     */
    protected function buildPositionsModePips(): array
    {
        $values = $this->getPipsValues();

        if (! is_array($values) || $values === []) {
            return [];
        }

        $positionPercents = array_map(
            fn (int|float $value): float => (float) $value,
            $values,
        );

        sort($positionPercents);

        return $this->buildPipsFromLabeledPoints($positionPercents);
    }

    /**
     * @return array<int, array{ratio: float, label: ?string, size: string}>
     */
    protected function buildCountModePips(): array
    {
        $count = $this->getPipsValues();

        if (! is_numeric($count) || (int) $count < 2) {
            return [];
        }

        $interval = (int) $count - 1;
        $spread = 100 / $interval;
        $positionPercents = [];

        for ($index = 0; $index < $interval; $index++) {
            $positionPercents[] = $index * $spread;
        }

        $positionPercents[] = 100.0;

        return $this->buildPipsFromLabeledPoints($positionPercents);
    }

    /**
     * @param  array<int, float>  $positionPercents
     * @return array<int, array{ratio: float, label: ?string, size: string}>
     */
    protected function buildPipsFromLabeledPoints(array $positionPercents): array
    {
        $density = max(1, $this->getPipsDensity() ?? 10);
        $min = (float) $this->getMinValue();
        $max = (float) $this->getMaxValue();
        $pipsByKey = [];
        $previousRatio = null;

        foreach ($positionPercents as $positionPercent) {
            $value = $this->positionPercentToValue($positionPercent);
            $ratio = $this->valueToRatio($value);

            if ($previousRatio !== null) {
                $this->addDensityPipsBetween($previousRatio, $ratio, $density, $pipsByKey);
            }

            $this->addLabeledPip($ratio, $value, $min, $max, $pipsByKey);
            $previousRatio = $ratio;
        }

        return $this->sortPips(array_values($pipsByKey));
    }

    /**
     * @param  array<string, array{ratio: float, label: ?string, size: string}>  $pipsByKey
     */
    protected function addDensityPipsBetween(float $previousRatio, float $ratio, int $density, array &$pipsByKey): void
    {
        $previousPercent = $previousRatio * 100;
        $nextPercent = $ratio * 100;
        $percentDifference = $nextPercent - $previousPercent;

        if ($percentDifference <= 0) {
            return;
        }

        $steps = $percentDifference / $density;
        $realSteps = (int) round($steps);

        if ($realSteps <= 0) {
            return;
        }

        $stepSize = $percentDifference / $realSteps;

        for ($index = 1; $index <= $realSteps; $index++) {
            $percent = $previousPercent + ($index * $stepSize);
            $intermediateRatio = round($percent / 100, 6);

            if (abs($intermediateRatio - $ratio) < 0.000001) {
                continue;
            }

            $key = $this->pipKey($intermediateRatio);

            if (isset($pipsByKey[$key])) {
                continue;
            }

            $pipsByKey[$key] = [
                'ratio' => $intermediateRatio,
                'label' => null,
                'size' => 'normal',
            ];
        }
    }

    /**
     * @param  array<string, array{ratio: float, label: ?string, size: string}>  $pipsByKey
     */
    protected function addLabeledPip(float $ratio, float $value, float $min, float $max, array &$pipsByKey): void
    {
        $pipsByKey[$this->pipKey($ratio)] = [
            'ratio' => $ratio,
            'label' => $this->formatPipLabel($value),
            'size' => $this->resolvePipMarkerSize($value, $min, $max),
        ];
    }

    protected function positionPercentToValue(float $positionPercent): float
    {
        $min = (float) $this->getMinValue();
        $max = (float) $this->getMaxValue();
        $value = $min + ($positionPercent / 100) * ($max - $min);

        return $this->normalizeNumeric($value);
    }

    protected function pipKey(float $ratio): string
    {
        return $this->formatRatio($ratio);
    }

    /**
     * @param  array<int, array{ratio: float, label: ?string, size: string}>  $pips
     * @return array<int, array{ratio: float, label: ?string, size: string}>
     */
    protected function sortPips(array $pips): array
    {
        usort(
            $pips,
            fn (array $left, array $right): int => $left['ratio'] <=> $right['ratio'],
        );

        return $pips;
    }

    public function pipStyle(float $ratio): string
    {
        return '--fff-flex-slider-pip-ratio: '.$this->formatRatio($ratio).';';
    }

    protected function formatPipLabel(float $value): string
    {
        return $this->formatDisplayValue($value);
    }

    protected function resolvePipMarkerSize(float $value, float $min, float $max): string
    {
        if ($value <= $min + 0.0001 || $value >= $max - 0.0001) {
            return 'large';
        }

        return 'sub';
    }

    public function resolveFillSegmentType(float $startRatio, int $handleCount): string
    {
        if ($handleCount === 1 && $startRatio <= 0.0001) {
            return 'from-min';
        }

        return 'between';
    }

    /**
     * @param  array{type: string, startRatio: float, endRatio: float}  $segment
     */
    public function fillSegmentClass(array $segment): string
    {
        return $segment['type'] === 'between'
            ? 'fff-flex-slider__fill--between'
            : 'fff-flex-slider__fill--from-min';
    }

    /**
     * @param  array{type: string, startRatio: float, endRatio: float}  $segment
     */
    public function fillSegmentModifierClass(array $segment): string
    {
        return $segment['type'] === 'between'
            ? 'fff-flex-slider__fill--between'
            : 'fff-flex-slider__fill--from-min';
    }

    /**
     * @param  array{type: string, startRatio: float, endRatio: float}  $segment
     */
    public function fillSegmentVariables(array $segment): string
    {
        if ($segment['type'] === 'between') {
            return sprintf(
                '--fff-flex-slider-fill-start: %s; --fff-flex-slider-fill-end: %s;',
                $this->formatRatio($segment['startRatio']),
                $this->formatRatio($segment['endRatio']),
            );
        }

        return '--fff-flex-slider-value-ratio: '.$this->formatRatio($segment['endRatio']).';';
    }

    /**
     * @return array<int, bool>|false
     */
    public function resolveConnectForChrome(): array|false
    {
        $fillTrack = $this->getFillTrack();

        if ($fillTrack !== null) {
            return $fillTrack;
        }

        if (! $this->shouldAutoFill()) {
            return false;
        }

        $handleCount = count($this->getNormalizedStateValues());

        if ($handleCount === 1) {
            return [true, false];
        }

        return array_map(
            fn (int $index): bool => $index > 0 && $index < $handleCount,
            range(0, $handleCount),
        );
    }

    /**
     * @return array<int, array{type: string, startRatio: float, endRatio: float}>
     */
    public function getInitialFillSegments(): array
    {
        $connect = $this->resolveConnectForChrome();

        if ($connect === false) {
            return [];
        }

        $flags = is_array($connect) ? $connect : [$connect];
        $ratios = $this->getInitialValueRatios();
        $handleCount = count($ratios);
        $stops = array_merge([0.0], $ratios, [1.0]);
        $segments = [];

        for ($index = 0; $index < count($flags); $index++) {
            if (! $flags[$index]) {
                continue;
            }

            $segments[] = [
                'type' => $this->resolveFillSegmentType($stops[$index], $handleCount),
                'startRatio' => $stops[$index],
                'endRatio' => $stops[$index + 1],
            ];
        }

        return $segments;
    }

    public function formatDisplayValue(float|int|null $value = null): string
    {
        if ($value === null) {
            $state = $this->resolveStateForChrome();

            if (is_array($state)) {
                return collect($state)
                    ->map(fn (mixed $item): string => $this->formatDisplayValue((float) $item))
                    ->implode(' – ');
            }

            $value = $state ?? $this->getMinValue();
        }

        $numeric = $this->normalizeNumeric((float) $value);

        if ($this->getDecimalPlaces() !== null) {
            $formatted = number_format($numeric, $this->getDecimalPlaces(), '.', '');
        } elseif ($this->stepDecimalCount() === 0) {
            $formatted = (string) (int) round($numeric);
        } else {
            $formatted = rtrim(
                rtrim(number_format($numeric, $this->stepDecimalCount(), '.', ''), '0'),
                '.',
            );
        }

        if ($prefix = $this->getDisplayPrefix()) {
            $formatted = "{$prefix}{$formatted}";
        }

        if ($suffix = $this->getDisplaySuffix()) {
            $formatted = "{$formatted}{$suffix}";
        }

        return $formatted;
    }

    public function normalizeNumeric(float|int $value): float
    {
        $numeric = (float) $value;

        if ($this->getDecimalPlaces() !== null) {
            return round($numeric, $this->getDecimalPlaces());
        }

        $step = $this->getStep();

        if (filled($step) && (float) $step > 0) {
            $step = (float) $step;
            $stepped = round($numeric / $step) * $step;

            return round($stepped, $this->stepDecimalCount());
        }

        return $numeric;
    }

    protected function stepDecimalCount(): int
    {
        $step = $this->getStep();

        if (! filled($step)) {
            return 0;
        }

        $stepString = rtrim(rtrim(sprintf('%.10F', (float) $step), '0'), '.');

        if (! str_contains($stepString, '.')) {
            return 0;
        }

        return strlen(explode('.', $stepString)[1]);
    }

    protected function resolveStateForChrome(): mixed
    {
        try {
            $state = $this->getState();
        } catch (\Throwable) {
            $state = null;
        }

        if ($state === null || ($state === '' && ! is_array($state))) {
            return $this->getDefaultState();
        }

        return $state;
    }
}

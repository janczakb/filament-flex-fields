<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\AnimatesProgressFill;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasProgressAccentColor;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\ProgressColor;
use Closure;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class ProgressCircle extends Component
{
    use AnimatesProgressFill;
    use HasControlSize;
    use HasProgressAccentColor;

    protected const SEMICIRCLE_ARC_SPAN = 210.0;

    protected ?string $resolvedSvgInstanceId = null;

    protected string $view = 'filament-flex-fields::schemas.components.progress-circle';

    protected float|int|Closure|null $value = null;

    protected float|int|Closure $max = 100;

    protected string|Closure|null $displayValue = null;

    protected string|Closure|null $fraction = null;

    protected string|Closure|null $label = null;

    protected string|Closure $variant = 'circle';

    protected float|int|Closure $gapAngle = 0;

    protected bool|Closure $isPaused = false;

    protected string|BackedEnum|Htmlable|Closure|null $pausedIcon = null;

    protected string|Closure|null $gradientStroke = null;

    protected string|Closure|null $gradientFrom = null;

    protected string|Closure|null $gradientTo = null;

    protected string|Closure|null $trackGradientFrom = null;

    protected string|Closure|null $trackGradientTo = null;

    protected string|Closure $contentLayout = 'center';

    protected bool|Closure $hasShell = false;

    protected string|Closure|null $heading = null;

    protected string|Closure|null $description = null;

    protected string|Closure|null $footer = null;

    public static function make(): static
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->gap(false);
        $this->columns(1);
    }

    public function value(float|int|Closure|null $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function max(float|int|Closure $max): static
    {
        $this->max = $max;

        return $this;
    }

    public function displayValue(string|Closure|null $value): static
    {
        $this->displayValue = $value;

        return $this;
    }

    public function fraction(string|Closure|null $fraction): static
    {
        $this->fraction = $fraction;

        return $this;
    }

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function gapAngle(float|int|Closure $degrees): static
    {
        $this->gapAngle = $degrees;

        return $this;
    }

    public function paused(bool|Closure $condition = true): static
    {
        $this->isPaused = $condition;

        return $this;
    }

    public function pausedIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->pausedIcon = $icon;

        return $this;
    }

    public function gradientStroke(string|Closure|null $gradient): static
    {
        $this->gradientStroke = $gradient;

        return $this;
    }

    public function gradientFrom(string|Closure|null $color): static
    {
        $this->gradientFrom = $color;

        return $this;
    }

    public function gradientTo(string|Closure|null $color): static
    {
        $this->gradientTo = $color;

        return $this;
    }

    public function trackGradientFrom(string|Closure|null $color): static
    {
        $this->trackGradientFrom = $color;

        return $this;
    }

    public function trackGradientTo(string|Closure|null $color): static
    {
        $this->trackGradientTo = $color;

        return $this;
    }

    public function contentLayout(string|Closure $layout): static
    {
        $this->contentLayout = $layout;

        return $this;
    }

    public function shell(bool|Closure $condition = true): static
    {
        $this->hasShell = $condition;

        return $this;
    }

    public function heading(string|Closure|null $heading): static
    {
        $this->heading = $heading;

        return $this;
    }

    public function description(string|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function footer(string|Closure|null $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    public function getValue(): float
    {
        $value = $this->evaluate($this->value);

        if ($value === null) {
            return 0.0;
        }

        return (float) $value;
    }

    public function getMax(): float
    {
        $max = (float) $this->evaluate($this->max);

        if ($max <= 0) {
            throw new InvalidArgumentException('Progress circle max must be greater than 0.');
        }

        return $max;
    }

    public function getDisplayValue(): ?string
    {
        $value = $this->evaluate($this->displayValue);

        if (filled($value)) {
            return (string) $value;
        }

        return null;
    }

    public function getFraction(): ?string
    {
        $fraction = $this->evaluate($this->fraction);

        return filled($fraction) ? (string) $fraction : null;
    }

    public function getLabel(): ?string
    {
        $label = $this->evaluate($this->label);

        return filled($label) ? (string) $label : null;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['circle', 'semicircle'], true)) {
            throw new InvalidArgumentException("Progress circle variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function getColor(): string
    {
        return $this->getResolvedColor();
    }

    public function getGapAngle(): float
    {
        $gapAngle = (float) $this->evaluate($this->gapAngle);

        if ($gapAngle < 0 || $gapAngle >= 360) {
            throw new InvalidArgumentException('Progress circle gap angle must be between 0 and 359.');
        }

        return $gapAngle;
    }

    public function isPaused(): bool
    {
        return (bool) $this->evaluate($this->isPaused);
    }

    public function getPausedIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->pausedIcon);

        if ($icon instanceof Htmlable) {
            return $icon;
        }

        if ($icon instanceof BackedEnum) {
            return $icon;
        }

        if (filled($icon)) {
            return (string) $icon;
        }

        return GravityIcon::PauseFill;
    }

    public function hasGradientStroke(): bool
    {
        return filled($this->getGradientStroke()) || (filled($this->getGradientFrom()) && filled($this->getGradientTo()));
    }

    public function getGradientStroke(): ?string
    {
        $gradient = $this->evaluate($this->gradientStroke);

        return filled($gradient) ? (string) $gradient : null;
    }

    public function getGradientFrom(): ?string
    {
        $color = $this->evaluate($this->gradientFrom);

        return filled($color) ? ProgressColor::normalize((string) $color) : null;
    }

    public function getGradientTo(): ?string
    {
        $color = $this->evaluate($this->gradientTo);

        return filled($color) ? ProgressColor::normalize((string) $color) : null;
    }

    public function hasTrackGradientStroke(): bool
    {
        return $this->usesExplicitTrackGradient();
    }

    public function usesExplicitTrackGradient(): bool
    {
        return filled($this->evaluate($this->trackGradientFrom))
            && filled($this->evaluate($this->trackGradientTo));
    }

    public function getTrackGradientFrom(): ?string
    {
        $color = $this->evaluate($this->trackGradientFrom);

        return filled($color) ? ProgressColor::normalize((string) $color) : null;
    }

    public function getTrackGradientTo(): ?string
    {
        $color = $this->evaluate($this->trackGradientTo);

        return filled($color) ? ProgressColor::normalize((string) $color) : null;
    }

    public function getContentLayout(): string
    {
        $layout = (string) $this->evaluate($this->contentLayout);

        if (! in_array($layout, ['center', 'left', 'right', 'above'], true)) {
            throw new InvalidArgumentException("Progress circle content layout [{$layout}] is not supported.");
        }

        return $layout;
    }

    public function hasShell(): bool
    {
        return (bool) $this->evaluate($this->hasShell);
    }

    public function getHeading(): ?string
    {
        $heading = $this->evaluate($this->heading);

        return filled($heading) ? (string) $heading : null;
    }

    public function getDescription(): ?string
    {
        $description = $this->evaluate($this->description);

        return filled($description) ? (string) $description : null;
    }

    public function getFooter(): ?string
    {
        $footer = $this->evaluate($this->footer);

        return filled($footer) ? (string) $footer : null;
    }

    public function hasCardChrome(): bool
    {
        return $this->hasShell()
            || filled($this->getHeading())
            || filled($this->getDescription())
            || filled($this->getFooter());
    }

    public function getProgressRatio(): float
    {
        return max(0.0, min(1.0, $this->getValue() / $this->getMax()));
    }

    public function getPercentage(): int
    {
        return (int) round($this->getProgressRatio() * 100);
    }

    public function getFormattedValue(): string
    {
        $displayValue = $this->getDisplayValue();

        if ($displayValue !== null) {
            return $displayValue;
        }

        return $this->getPercentage().'%';
    }

    public function hasCenterContent(): bool
    {
        return $this->isPaused()
            || filled($this->getDisplayValue())
            || filled($this->getFraction())
            || filled($this->getLabel())
            || ! $this->isPaused();
    }

    public function hasGapArc(): bool
    {
        return $this->getVariant() === 'circle' && $this->getGapAngle() > 0;
    }

    public function hasBelowLabel(): bool
    {
        return $this->getVariant() === 'semicircle'
            && $this->getContentLayout() === 'center'
            && filled($this->getLabel());
    }

    /**
     * @return array{
     *     radius: float,
     *     strokeWidth: float,
     *     centerX: float,
     *     centerY: float,
     *     circumference: float,
     *     arcLength: float,
     *     progressLength: float,
     *     gapLength: float,
     *     rotation: float,
     *     viewBox: string,
     *     gradientId: string,
     *     trackGradientId: string,
     *     gradientX1: float,
     *     gradientY1: float,
     *     gradientX2: float,
     *     gradientY2: float,
     *     viewBoxHeight: float,
     *     semicircleFloorInsetPercent: float,
     * }
     */
    public function getSvgMetrics(): array
    {
        $radius = 42.0;
        $strokeWidth = 8.0;
        $circumference = 2 * M_PI * $radius;
        $variant = $this->getVariant();
        $gapAngle = $this->getGapAngle();
        $viewBoxHeight = 100.0;
        $semicircleFloorInsetPercent = 0.0;

        if ($variant === 'semicircle') {
            $totalAngle = max(1.0, self::SEMICIRCLE_ARC_SPAN - $gapAngle);
            $rotation = 270.0 - ($totalAngle / 2.0);
            $centerX = 50.0;
            $centerY = 46.0;
            $endpointY = $centerY + ($radius * sin(deg2rad($rotation)));
            $viewBoxHeight = ceil($endpointY + ($strokeWidth / 2) + 2);
            $viewBox = '0 0 100 '.$viewBoxHeight;
            $semicircleFloorInsetPercent = round((($viewBoxHeight - $endpointY) / $viewBoxHeight) * 100, 3);
            $gradientX1 = 8.0;
            $gradientY1 = 62.0;
            $gradientX2 = 92.0;
            $gradientY2 = 18.0;
        } else {
            $totalAngle = max(1.0, 360.0 - $gapAngle);
            $rotation = 90.0 + ($gapAngle / 2.0);
            $centerX = 50.0;
            $centerY = 50.0;
            $viewBox = '0 0 100 100';
            $gradientX1 = 8.0;
            $gradientY1 = 88.0;
            $gradientX2 = 92.0;
            $gradientY2 = 12.0;
        }

        $arcLength = $circumference * ($totalAngle / 360.0);
        $progressLength = min($arcLength, $arcLength * $this->getProgressRatio());
        $gapLength = max(0.0, $circumference - $arcLength);

        return [
            'radius' => $radius,
            'strokeWidth' => $strokeWidth,
            'centerX' => $centerX,
            'centerY' => $centerY,
            'circumference' => $circumference,
            'arcLength' => $arcLength,
            'progressLength' => $progressLength,
            'gapLength' => $gapLength,
            'rotation' => $rotation,
            'viewBox' => $viewBox,
            'gradientId' => 'fff-progress-circle-gradient-'.$this->resolveSvgInstanceId(),
            'trackGradientId' => 'fff-progress-circle-track-gradient-'.$this->resolveSvgInstanceId(),
            'gradientX1' => $gradientX1,
            'gradientY1' => $gradientY1,
            'gradientX2' => $gradientX2,
            'gradientY2' => $gradientY2,
            'viewBoxHeight' => $viewBoxHeight,
            'semicircleFloorInsetPercent' => $semicircleFloorInsetPercent,
        ];
    }

    protected function resolveSvgInstanceId(): string
    {
        if ($this->resolvedSvgInstanceId !== null) {
            return $this->resolvedSvgInstanceId;
        }

        $id = $this->getId();

        if (filled($id)) {
            return $this->resolvedSvgInstanceId = (string) $id;
        }

        $key = $this->getKey(isAbsolute: true);

        if (filled($key)) {
            return $this->resolvedSvgInstanceId = substr(md5((string) $key), 0, 12);
        }

        return $this->resolvedSvgInstanceId = substr(md5(spl_object_hash($this)), 0, 12);
    }
}

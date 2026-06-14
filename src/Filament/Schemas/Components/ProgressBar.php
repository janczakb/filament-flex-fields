<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\AnimatesProgressFill;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasProgressAccentColor;
use Bjanczak\FilamentFlexFields\Support\ProgressColor;
use Closure;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class ProgressBar extends Component
{
    use AnimatesProgressFill;
    use HasControlSize;
    use HasProgressAccentColor;

    protected string $view = 'filament-flex-fields::schemas.components.progress-bar';

    protected float|int|Closure|null $value = null;

    protected float|int|Closure $max = 100;

    protected string|Closure|null $label = null;

    protected string|Closure|null $displayValue = null;

    protected bool|Closure $shouldShowValue = true;

    protected bool|Closure $isIndeterminate = false;

    /**
     * @var array<int, array<string, mixed>>|Closure|null
     */
    protected array|Closure|null $segments = null;

    protected int|Closure|null $activeSegment = null;

    protected string|BackedEnum|Htmlable|Closure|null $startMarker = null;

    protected string|BackedEnum|Htmlable|Closure|null $endMarker = null;

    protected string|BackedEnum|Htmlable|Closure|null $currentMarker = null;

    protected float|Closure $activeSegmentProgress = 0.62;

    protected bool|Closure|null $shouldShowSegmentThumb = null;

    protected string|Closure $remainingTrackStyle = 'solid';

    protected string|Closure $variant = 'track';

    protected int|Closure|null $pillCount = null;

    protected string|Closure|null $gradientFrom = null;

    protected string|Closure|null $gradientTo = null;

    protected bool|Closure $hasShell = false;

    protected string|Closure|null $description = null;

    protected string|Closure|null $footer = null;

    protected bool|Closure $shouldShowValueBadge = false;

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

    public function label(string|Closure|null $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function displayValue(string|Closure|null $value): static
    {
        $this->displayValue = $value;

        return $this;
    }

    public function showValue(bool|Closure $condition = true): static
    {
        $this->shouldShowValue = $condition;

        return $this;
    }

    public function indeterminate(bool|Closure $condition = true): static
    {
        $this->isIndeterminate = $condition;

        return $this;
    }

    /**
     * @param  array<int, array<string, mixed>>|Closure|null  $segments
     */
    public function segments(array|Closure|null $segments): static
    {
        $this->segments = $segments;

        return $this;
    }

    public function activeSegment(int|Closure|null $index): static
    {
        $this->activeSegment = $index;

        return $this;
    }

    public function startMarker(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->startMarker = $icon;

        return $this;
    }

    public function endMarker(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->endMarker = $icon;

        return $this;
    }

    public function currentMarker(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->currentMarker = $icon;

        return $this;
    }

    public function activeSegmentProgress(float|Closure $progress): static
    {
        $this->activeSegmentProgress = $progress;

        return $this;
    }

    public function segmentThumb(bool|Closure $condition = true): static
    {
        $this->shouldShowSegmentThumb = $condition;

        return $this;
    }

    public function remainingTrackStyle(string|Closure $style): static
    {
        $this->remainingTrackStyle = $style;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function pillCount(int|Closure $count): static
    {
        $this->pillCount = $count;

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

    public function shell(bool|Closure $condition = true): static
    {
        $this->hasShell = $condition;

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

    public function valueBadge(bool|Closure $condition = true): static
    {
        $this->shouldShowValueBadge = $condition;

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
            throw new InvalidArgumentException('Progress bar max must be greater than 0.');
        }

        return $max;
    }

    public function getLabel(): ?string
    {
        $label = $this->evaluate($this->label);

        return filled($label) ? (string) $label : null;
    }

    public function getDisplayValue(): ?string
    {
        $value = $this->evaluate($this->displayValue);

        if (filled($value)) {
            return (string) $value;
        }

        return null;
    }

    public function shouldShowValue(): bool
    {
        return (bool) $this->evaluate($this->shouldShowValue);
    }

    public function getColor(): string
    {
        return $this->getResolvedColor();
    }

    public function isIndeterminate(): bool
    {
        return (bool) $this->evaluate($this->isIndeterminate);
    }

    public function hasSegments(): bool
    {
        return filled($this->getNormalizedSegments());
    }

    public function hasSegmentLabels(): bool
    {
        return collect($this->getNormalizedSegments())
            ->contains(fn (array $segment): bool => filled($segment['label'] ?? null)
                || filled($segment['description'] ?? null)
                || filled($segment['icon'] ?? null));
    }

    public function hasSegmentIcons(): bool
    {
        return collect($this->getNormalizedSegments())
            ->contains(fn (array $segment): bool => filled($segment['icon'] ?? null));
    }

    /**
     * @return list<array{label: ?string, description: ?string, icon: string|BackedEnum|Htmlable|null, state: string}>
     */
    public function getNormalizedSegments(): array
    {
        $segments = $this->evaluate($this->segments);

        if (! is_array($segments) || $segments === []) {
            return [];
        }

        $activeIndex = $this->resolveActiveSegmentIndex(count($segments));
        $normalized = [];

        foreach (array_values($segments) as $index => $segment) {
            if (is_string($segment)) {
                $normalized[] = [
                    'label' => $segment,
                    'description' => null,
                    'icon' => null,
                    'state' => $this->resolveSegmentState($index, $activeIndex),
                ];

                continue;
            }

            if (! is_array($segment)) {
                continue;
            }

            $state = $segment['state'] ?? $this->resolveSegmentState($index, $activeIndex);

            if (! in_array($state, ['complete', 'active', 'pending'], true)) {
                throw new InvalidArgumentException("Progress bar segment state [{$state}] is not supported.");
            }

            $normalized[] = [
                'label' => filled($segment['label'] ?? null) ? (string) $segment['label'] : null,
                'description' => filled($segment['description'] ?? null) ? (string) $segment['description'] : null,
                'icon' => $segment['icon'] ?? null,
                'state' => $state,
            ];
        }

        return $normalized;
    }

    protected function resolveActiveSegmentIndex(int $segmentCount): int
    {
        $activeSegment = $this->evaluate($this->activeSegment);

        if ($activeSegment !== null) {
            return max(0, min($segmentCount - 1, (int) $activeSegment));
        }

        if ($this->isIndeterminate()) {
            return 0;
        }

        $ratio = $this->getProgressRatio();
        $index = (int) floor($ratio * $segmentCount);

        return max(0, min($segmentCount - 1, $index));
    }

    protected function resolveSegmentState(int $index, int $activeIndex): string
    {
        if ($index < $activeIndex) {
            return 'complete';
        }

        if ($index === $activeIndex) {
            return 'active';
        }

        return 'pending';
    }

    public function getActiveSegmentIndex(): int
    {
        return $this->resolveActiveSegmentIndex(count($this->getNormalizedSegments()));
    }

    public function getProgressRatio(): float
    {
        if ($this->isIndeterminate()) {
            return 0.0;
        }

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

    public function hasHeader(): bool
    {
        if ($this->hasCardChrome()) {
            return false;
        }

        return filled($this->getLabel()) || ($this->shouldShowValue() && ! $this->isIndeterminate() && ! $this->hasSegments() && ! $this->isPillsVariant());
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['track', 'pills'], true)) {
            throw new InvalidArgumentException("Progress bar variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function isPillsVariant(): bool
    {
        return $this->getVariant() === 'pills' && ! $this->hasSegments();
    }

    public function usesAutoPillCount(): bool
    {
        return $this->evaluate($this->pillCount) === null;
    }

    public function getPillCount(): ?int
    {
        $count = $this->evaluate($this->pillCount);

        if ($count === null) {
            return null;
        }

        $count = (int) $count;

        if ($count < 1) {
            throw new InvalidArgumentException('Progress bar pill count must be at least 1.');
        }

        return min($count, 100);
    }

    public function getActivePillCount(): int
    {
        if ($this->isIndeterminate() || $this->usesAutoPillCount()) {
            return 0;
        }

        $count = $this->getPillCount();
        $active = (int) round($this->getProgressRatio() * $count);

        return max(0, min($count, $active));
    }

    public function getGradientFrom(): string
    {
        $from = $this->evaluate($this->gradientFrom);

        if (filled($from)) {
            return ProgressColor::normalize((string) $from);
        }

        return match ($this->getColorToken() ?? 'primary') {
            'success' => 'rgb(34 197 94)',
            'warning' => 'rgb(245 158 11)',
            'danger' => 'rgb(239 68 68)',
            default => 'rgb(239 68 68)',
        };
    }

    public function getGradientTo(): string
    {
        $to = $this->evaluate($this->gradientTo);

        if (filled($to)) {
            return ProgressColor::normalize((string) $to);
        }

        return match ($this->getColorToken() ?? 'primary') {
            'success' => 'rgb(74 222 128)',
            'warning' => 'rgb(251 191 36)',
            'danger' => 'rgb(245 158 11)',
            default => 'rgb(245 158 11)',
        };
    }

    public function getPillColorForIndex(int $index): ?string
    {
        if ($this->usesAutoPillCount()) {
            return null;
        }

        $activePillCount = $this->getActivePillCount();

        if ($index >= $activePillCount) {
            return null;
        }

        if ($activePillCount === 1) {
            return $this->getGradientFrom();
        }

        return $this->interpolateColor(
            $this->getGradientFrom(),
            $this->getGradientTo(),
            $index / ($activePillCount - 1),
        );
    }

    protected function interpolateColor(string $from, string $to, float $ratio): string
    {
        $fromChannels = ProgressColor::parseRgbChannels($from);
        $toChannels = ProgressColor::parseRgbChannels($to);
        $ratio = max(0.0, min(1.0, $ratio));

        $red = (int) round($fromChannels[0] + ($toChannels[0] - $fromChannels[0]) * $ratio);
        $green = (int) round($fromChannels[1] + ($toChannels[1] - $fromChannels[1]) * $ratio);
        $blue = (int) round($fromChannels[2] + ($toChannels[2] - $fromChannels[2]) * $ratio);

        return ProgressColor::toRgbString($red, $green, $blue);
    }

    public function hasShell(): bool
    {
        return (bool) $this->evaluate($this->hasShell);
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

    public function shouldShowValueBadge(): bool
    {
        return (bool) $this->evaluate($this->shouldShowValueBadge);
    }

    public function hasCardChrome(): bool
    {
        return $this->hasShell();
    }

    public function hasCardHeader(): bool
    {
        return filled($this->getLabel()) || ($this->shouldShowValue() && $this->shouldShowValueBadge() && ! $this->isIndeterminate());
    }

    public function getStartMarker(): string|BackedEnum|Htmlable|null
    {
        return $this->evaluate($this->startMarker);
    }

    public function getEndMarker(): string|BackedEnum|Htmlable|null
    {
        return $this->evaluate($this->endMarker);
    }

    public function getCurrentMarker(): string|BackedEnum|Htmlable|null
    {
        return $this->evaluate($this->currentMarker);
    }

    public function hasStartMarker(): bool
    {
        return filled($this->getStartMarker());
    }

    public function hasEndMarker(): bool
    {
        return filled($this->getEndMarker());
    }

    public function hasCurrentMarker(): bool
    {
        return filled($this->getCurrentMarker());
    }

    public function hasTrackMarkers(): bool
    {
        return $this->hasStartMarker() || $this->hasEndMarker() || $this->hasCurrentMarker();
    }

    public function getActiveSegmentProgress(): float
    {
        $progress = (float) $this->evaluate($this->activeSegmentProgress);

        return max(0.0, min(1.0, $progress));
    }

    public function shouldShowSegmentThumb(): bool
    {
        $condition = $this->evaluate($this->shouldShowSegmentThumb);

        if ($condition !== null) {
            return (bool) $condition;
        }

        return $this->hasSegments();
    }

    public function getRemainingTrackStyle(): string
    {
        $style = (string) $this->evaluate($this->remainingTrackStyle);

        if (! in_array($style, ['solid', 'dashed'], true)) {
            throw new InvalidArgumentException("Progress bar remaining track style [{$style}] is not supported.");
        }

        return $style;
    }

    public function getSegmentFillPercentage(): float
    {
        $segments = $this->getNormalizedSegments();
        $count = count($segments);

        if ($count === 0) {
            return 0.0;
        }

        $activeIndex = $this->getActiveSegmentIndex();
        $activeProgress = $this->getActiveSegmentProgress();

        return (($activeIndex + $activeProgress) / $count) * 100;
    }

    public function getSegmentFillWidthForIndex(int $index): float
    {
        $segments = $this->getNormalizedSegments();

        if (! isset($segments[$index])) {
            return 0.0;
        }

        return match ($segments[$index]['state']) {
            'complete' => 100.0,
            'active' => $this->getActiveSegmentProgress() * 100,
            default => 0.0,
        };
    }

    public function getSegmentThumbPosition(): float
    {
        return $this->getSegmentFillPercentage();
    }
}

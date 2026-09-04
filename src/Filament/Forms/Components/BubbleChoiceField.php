<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldRounding;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\OptionsArrayStateCast;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class BubbleChoiceField extends Field
{
    use HasControlSize;
    use HasFieldRounding;

    protected string $view = 'filament-flex-fields::forms.components.bubble-choice-field';

    /**
     * @var array<string | int, string | array<string, mixed>> | Closure
     */
    protected array|Closure $options = [];

    /**
     * @var array<string, array{label: string, image: ?string, color: ?string, selectedColor: ?string, disabled: bool}>|null
     */
    protected ?array $normalizedOptionsCache = null;

    /**
     * @var array<string | int> | Closure
     */
    protected array|Closure $disabledOptions = [];

    protected string|Closure $variant = 'soft';

    protected string|Closure $arenaHeight = '500px';

    protected string|Closure $selectedShape = 'scallop';

    protected string|Closure|null $bubbleColor = null;

    protected string|Closure|null $selectedBubbleColor = null;

    protected string|Closure|null $arenaColor = null;

    protected int|float|Closure $bubbleSize = 160;

    protected int|float|Closure $minSize = 25;

    protected int|float|Closure $gutter = 8;

    protected int|Closure $numCols = 6;

    protected int|float|Closure $fringeWidth = 160;

    protected int|float|Closure $yRadius = 130;

    protected int|float|Closure $xRadius = 220;

    protected int|float|Closure $cornerRadius = 50;

    protected bool|Closure $compact = true;

    protected int|float|Closure $gravitation = 5;

    protected bool|Closure $provideProps = true;

    protected int|Closure|null $minItems = null;

    protected int|Closure|null $maxItems = null;

    protected int|Closure|null $exactItems = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (BubbleChoiceField $component, mixed $state): void {
            $component->state($component->normalizeState(is_array($state) ? $state : []));
        });

        $this->dehydrateStateUsing(fn (BubbleChoiceField $component, mixed $state): array => $component->normalizeState(is_array($state) ? $state : []));

        $this->rule(function (BubbleChoiceField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! is_array($value)) {
                    $fail(__('validation.array', ['attribute' => $attribute]));

                    return;
                }

                $allowedKeys = array_keys($component->getNormalizedOptions());

                foreach ($value as $item) {
                    if (! in_array((string) $item, $allowedKeys, true)) {
                        $fail(__('filament-flex-fields::default.validation.bubble_choice.invalid_option'));

                        return;
                    }
                }

                $count = count($value);

                if ($exact = $component->getExactItems()) {
                    if ($count !== $exact) {
                        $fail(__('filament-flex-fields::default.validation.bubble_choice.exact', ['count' => $exact]));

                        return;
                    }
                }

                $min = $component->getMinItems();

                if ($component->isRequired() && $min === null && $component->getExactItems() === null) {
                    $min = 1;
                }

                if ($min !== null && $count < $min) {
                    $fail(__('filament-flex-fields::default.validation.bubble_choice.min', ['count' => $min]));

                    return;
                }

                if ($max = $component->getMaxItems()) {
                    if ($count > $max) {
                        $fail(__('filament-flex-fields::default.validation.bubble_choice.max', ['count' => $max]));
                    }
                }
            };
        });
    }

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

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function arenaHeight(string|Closure $height): static
    {
        $this->arenaHeight = $height;

        return $this;
    }

    public function selectedShape(string|Closure $shape): static
    {
        $this->selectedShape = $shape;

        return $this;
    }

    public function bubbleColor(string|Closure|null $color): static
    {
        $this->bubbleColor = $color;

        return $this;
    }

    public function selectedBubbleColor(string|Closure|null $color): static
    {
        $this->selectedBubbleColor = $color;

        return $this;
    }

    public function arenaColor(string|Closure|null $color): static
    {
        $this->arenaColor = $color;

        return $this;
    }

    public function bubbleSize(int|float|Closure $size): static
    {
        $this->bubbleSize = $size;

        return $this;
    }

    public function minSize(int|float|Closure $size): static
    {
        $this->minSize = $size;

        return $this;
    }

    public function gutter(int|float|Closure $gutter): static
    {
        $this->gutter = $gutter;

        return $this;
    }

    public function numCols(int|Closure $cols): static
    {
        $this->numCols = $cols;

        return $this;
    }

    public function fringeWidth(int|float|Closure $width): static
    {
        $this->fringeWidth = $width;

        return $this;
    }

    public function yRadius(int|float|Closure $radius): static
    {
        $this->yRadius = $radius;

        return $this;
    }

    public function xRadius(int|float|Closure $radius): static
    {
        $this->xRadius = $radius;

        return $this;
    }

    public function cornerRadius(int|float|Closure $radius): static
    {
        $this->cornerRadius = $radius;

        return $this;
    }

    public function compact(bool|Closure $condition = true): static
    {
        $this->compact = $condition;

        return $this;
    }

    public function gravitation(int|float|Closure $strength): static
    {
        $this->gravitation = $strength;

        return $this;
    }

    public function provideProps(bool|Closure $condition = true): static
    {
        $this->provideProps = $condition;

        return $this;
    }

    /**
     * @param  array{
     *     size?: int|float,
     *     minSize?: int|float,
     *     gutter?: int|float,
     *     numCols?: int,
     *     fringeWidth?: int|float,
     *     yRadius?: int|float,
     *     xRadius?: int|float,
     *     cornerRadius?: int|float,
     *     compact?: bool,
     *     gravitation?: int|float,
     *     provideProps?: bool
     * }|Closure  $options
     */
    public function layoutOptions(array|Closure $options): static
    {
        $resolved = Arr::wrap($this->evaluate($options));

        if (array_key_exists('size', $resolved)) {
            $this->bubbleSize($resolved['size']);
        }

        if (array_key_exists('minSize', $resolved)) {
            $this->minSize($resolved['minSize']);
        }

        if (array_key_exists('gutter', $resolved)) {
            $this->gutter($resolved['gutter']);
        }

        if (array_key_exists('numCols', $resolved)) {
            $this->numCols((int) $resolved['numCols']);
        }

        if (array_key_exists('fringeWidth', $resolved)) {
            $this->fringeWidth($resolved['fringeWidth']);
        }

        if (array_key_exists('yRadius', $resolved)) {
            $this->yRadius($resolved['yRadius']);
        }

        if (array_key_exists('xRadius', $resolved)) {
            $this->xRadius($resolved['xRadius']);
        }

        if (array_key_exists('cornerRadius', $resolved)) {
            $this->cornerRadius($resolved['cornerRadius']);
        }

        if (array_key_exists('compact', $resolved)) {
            $this->compact((bool) $resolved['compact']);
        }

        if (array_key_exists('gravitation', $resolved)) {
            $this->gravitation($resolved['gravitation']);
        }

        if (array_key_exists('provideProps', $resolved)) {
            $this->provideProps((bool) $resolved['provideProps']);
        }

        return $this;
    }

    public function minItems(int|Closure|null $count): static
    {
        $this->minItems = $count;

        return $this;
    }

    public function maxItems(int|Closure|null $count): static
    {
        $this->maxItems = $count;

        return $this;
    }

    public function exactItems(int|Closure|null $count): static
    {
        $this->exactItems = $count;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['soft', 'solid', 'outline'], true)) {
            throw new InvalidArgumentException("Bubble choice variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function getArenaHeight(): string
    {
        return (string) $this->evaluate($this->arenaHeight);
    }

    public function getSelectedShape(): string
    {
        $shape = (string) $this->evaluate($this->selectedShape);

        if (! in_array($shape, ['scallop', 'circle', 'grow'], true)) {
            throw new InvalidArgumentException("Bubble choice selected shape [{$shape}] is not supported.");
        }

        return $shape;
    }

    public function getBubbleColor(): ?string
    {
        $color = $this->evaluate($this->bubbleColor);

        return filled($color) ? (string) $color : null;
    }

    public function getSelectedBubbleColor(): ?string
    {
        $color = $this->evaluate($this->selectedBubbleColor);

        return filled($color) ? (string) $color : null;
    }

    public function getArenaColor(): ?string
    {
        $color = $this->evaluate($this->arenaColor);

        return filled($color) ? (string) $color : null;
    }

    /**
     * @return array{
     *     size: float,
     *     minSize: float,
     *     gutter: float,
     *     provideProps: bool,
     *     numCols: int,
     *     fringeWidth: float,
     *     yRadius: float,
     *     xRadius: float,
     *     cornerRadius: float,
     *     compact: bool,
     *     gravitation: float
     * }
     */
    public function getLayoutOptionsForJs(): array
    {
        return [
            'size' => (float) $this->evaluate($this->bubbleSize),
            'minSize' => (float) $this->evaluate($this->minSize),
            'gutter' => (float) $this->evaluate($this->gutter),
            'provideProps' => (bool) $this->evaluate($this->provideProps),
            'numCols' => max(1, (int) $this->evaluate($this->numCols)),
            'fringeWidth' => (float) $this->evaluate($this->fringeWidth),
            'yRadius' => (float) $this->evaluate($this->yRadius),
            'xRadius' => (float) $this->evaluate($this->xRadius),
            'cornerRadius' => (float) $this->evaluate($this->cornerRadius),
            'compact' => (bool) $this->evaluate($this->compact),
            'gravitation' => (float) $this->evaluate($this->gravitation),
        ];
    }

    public function getMinItems(): ?int
    {
        $count = $this->evaluate($this->minItems);

        return $count === null ? null : (int) $count;
    }

    public function getMaxItems(): ?int
    {
        $count = $this->evaluate($this->maxItems);

        return $count === null ? null : (int) $count;
    }

    public function getExactItems(): ?int
    {
        $count = $this->evaluate($this->exactItems);

        return $count === null ? null : (int) $count;
    }

    /**
     * @return array<string, array{label: string, description: ?string, image: ?string, imageMode: string, color: ?string, selectedColor: ?string, disabled: bool}>
     */
    public function getNormalizedOptions(): array
    {
        if ($this->normalizedOptionsCache !== null) {
            return $this->normalizedOptionsCache;
        }

        $disabledOptions = collect($this->getDisabledOptions())->map(fn ($key): string => (string) $key);
        $normalized = [];

        foreach (Arr::wrap($this->evaluate($this->options)) as $key => $option) {
            $key = (string) $key;

            if (is_string($option)) {
                $normalized[$key] = [
                    'label' => $option,
                    'description' => null,
                    'image' => null,
                    'imageMode' => 'background',
                    'color' => null,
                    'selectedColor' => null,
                    'disabled' => $disabledOptions->contains($key),
                ];

                continue;
            }

            if (! is_array($option)) {
                continue;
            }

            $imageMode = (string) ($option['imageMode'] ?? $option['image_mode'] ?? 'background');

            if (! in_array($imageMode, ['background', 'icon'], true)) {
                $imageMode = 'background';
            }

            $normalized[$key] = [
                'label' => (string) ($option['label'] ?? $key),
                'description' => filled($option['description'] ?? $option['desc'] ?? null)
                    ? (string) ($option['description'] ?? $option['desc'])
                    : null,
                'image' => filled($option['image'] ?? null) ? (string) $option['image'] : null,
                'imageMode' => $imageMode,
                'color' => filled($option['color'] ?? null) ? (string) $option['color'] : null,
                'selectedColor' => filled($option['selectedColor'] ?? $option['selected_color'] ?? null)
                    ? (string) ($option['selectedColor'] ?? $option['selected_color'])
                    : null,
                'disabled' => (bool) ($option['disabled'] ?? false) || $disabledOptions->contains($key),
            ];
        }

        return $this->normalizedOptionsCache = $normalized;
    }

    /**
     * @return list<array{value: string, label: string, description: ?string, image: ?string, imageMode: string, color: ?string, selectedColor: ?string, disabled: bool}>
     */
    public function getOptionsForJs(): array
    {
        $options = [];

        foreach ($this->getNormalizedOptions() as $value => $option) {
            $options[] = [
                'value' => $value,
                'label' => $option['label'],
                'description' => $option['description'],
                'image' => $option['image'],
                'imageMode' => $option['imageMode'],
                'color' => $option['color'],
                'selectedColor' => $option['selectedColor'],
                'disabled' => $option['disabled'],
            ];
        }

        return $options;
    }

    /**
     * @return array<string | int>
     */
    public function getDisabledOptions(): array
    {
        return Arr::wrap($this->evaluate($this->disabledOptions));
    }

    /**
     * @param  array<int|string, mixed>  $state
     * @return list<string>
     */
    public function normalizeState(array $state): array
    {
        $allowedKeys = array_keys($this->getNormalizedOptions());
        $normalized = [];

        foreach ($state as $value) {
            $key = (string) $value;

            if (! in_array($key, $allowedKeys, true)) {
                continue;
            }

            if ($this->getNormalizedOptions()[$key]['disabled'] ?? false) {
                continue;
            }

            if (in_array($key, $normalized, true)) {
                continue;
            }

            $normalized[] = $key;
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-bubble-choice-field',
            'fff-bubble-choice-field--'.$this->getSize(),
            'fff-rounding-'.$this->getRounding(),
            'fff-bubble-choice-field--'.$this->getVariant(),
            'fff-bubble-choice-field--shape-'.$this->getSelectedShape(),
        ];
    }

    public function getStateCast(): ?StateCast
    {
        return app(OptionsArrayStateCast::class, ['isNullable' => false]);
    }
}

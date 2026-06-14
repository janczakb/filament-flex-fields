<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Field;
use InvalidArgumentException;

class FlexColorPickerField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;

    public const string LAYOUT_ADVANCED = 'advanced';

    public const string LAYOUT_GRID = 'grid';

    public const string FORMAT_HEX = 'hex';

    public const string FORMAT_RGB = 'rgb';

    public const string FORMAT_HSL = 'hsl';

    public const string FORMAT_RGBA = 'rgba';

    protected string $view = 'filament-flex-fields::forms.components.flex-color-picker-field';

    protected string|Closure $layout = self::LAYOUT_ADVANCED;

    protected string|Closure $variant = 'primary';

    protected string|Closure $format = self::FORMAT_HEX;

    protected bool|Closure $alphaEnabled = false;

    protected bool|Closure $eyedropperEnabled = true;

    protected int|Closure $gridColumns = 17;

    protected int|Closure $gridRows = 11;

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $gridColors = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);

        $this->rule(function (FlexColorPickerField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if ($value === null || $value === '') {
                    if ($component->isRequired()) {
                        $fail(__('validation.required', ['attribute' => $component->getLabel()]));
                    }

                    return;
                }

                if (! is_string($value)) {
                    $fail(__('filament-flex-fields::default.validation.flex_color_picker.invalid'));

                    return;
                }

                if (! $component->isValidColorString($value)) {
                    $fail(__('filament-flex-fields::default.validation.flex_color_picker.invalid'));
                }
            };
        });
    }

    public function layout(string|Closure $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat'], true)) {
            throw new InvalidArgumentException("Flex color picker variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function hex(): static
    {
        return $this->format(self::FORMAT_HEX);
    }

    public function rgb(): static
    {
        return $this->format(self::FORMAT_RGB);
    }

    public function hsl(): static
    {
        return $this->format(self::FORMAT_HSL);
    }

    public function rgba(): static
    {
        return $this->format(self::FORMAT_RGBA);
    }

    public function format(string|Closure $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function alpha(bool|Closure $enabled = true): static
    {
        $this->alphaEnabled = $enabled;

        return $this;
    }

    public function eyedropper(bool|Closure $enabled = true): static
    {
        $this->eyedropperEnabled = $enabled;

        return $this;
    }

    public function gridColumns(int|Closure $columns): static
    {
        $this->gridColumns = $columns;

        return $this;
    }

    public function gridRows(int|Closure $rows): static
    {
        $this->gridRows = $rows;

        return $this;
    }

    /**
     * @param  list<string>|Closure|null  $colors
     */
    public function gridColors(array|Closure|null $colors): static
    {
        $this->gridColors = $colors;

        return $this;
    }

    public function getLayout(): string
    {
        $layout = (string) $this->evaluate($this->layout);

        if (! in_array($layout, [self::LAYOUT_ADVANCED, self::LAYOUT_GRID], true)) {
            throw new InvalidArgumentException("Flex color picker layout [{$layout}] is not supported.");
        }

        return $layout;
    }

    public function getFormat(): string
    {
        $format = (string) $this->evaluate($this->format);

        if (! in_array($format, [self::FORMAT_HEX, self::FORMAT_RGB, self::FORMAT_HSL, self::FORMAT_RGBA], true)) {
            throw new InvalidArgumentException("Flex color picker format [{$format}] is not supported.");
        }

        if ($format === self::FORMAT_RGBA && ! $this->isAlphaEnabled()) {
            return self::FORMAT_RGB;
        }

        return $format;
    }

    public function isAlphaEnabled(): bool
    {
        return (bool) $this->evaluate($this->alphaEnabled);
    }

    public function isEyedropperEnabled(): bool
    {
        return (bool) $this->evaluate($this->eyedropperEnabled);
    }

    public function getGridColumns(): int
    {
        return max(1, (int) $this->evaluate($this->gridColumns));
    }

    public function getGridRows(): int
    {
        return max(1, (int) $this->evaluate($this->gridRows));
    }

    /**
     * @return list<string>|null
     */
    public function getGridColors(): ?array
    {
        $colors = $this->evaluate($this->gridColors);

        if ($colors === null) {
            return null;
        }

        return array_values(array_map(
            fn (mixed $color): string => strtoupper((string) $color),
            $colors,
        ));
    }

    public function isValidColorString(?string $value): bool
    {
        $input = trim((string) $value);

        if ($input === '') {
            return true;
        }

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $input) === 1) {
            return true;
        }

        if (preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*([\d.]+))?\s*\)$/', $input, $matches) === 1) {
            return $this->channelsAreValid((int) $matches[1], (int) $matches[2], (int) $matches[3])
                && (! isset($matches[4]) || $this->alphaIsValid((float) $matches[4]));
        }

        if (preg_match('/^hsla?\(\s*([\d.]+)\s*,\s*([\d.]+)%\s*,\s*([\d.]+)%(?:\s*,\s*([\d.]+))?\s*\)$/', $input, $matches) === 1) {
            $hue = (float) $matches[1];
            $saturation = (float) $matches[2];
            $lightness = (float) $matches[3];

            return $hue >= 0
                && $hue <= 360
                && $saturation >= 0
                && $saturation <= 100
                && $lightness >= 0
                && $lightness <= 100
                && (! isset($matches[4]) || $this->alphaIsValid((float) $matches[4]));
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-flex-color-picker-field',
            'fff-flex-text-input-field',
            'fff-flex-color-picker-field--'.$this->getSize(),
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-flex-color-picker-field--'.$this->getVariant(),
            'fff-flex-text-input-field--'.$this->getVariant(),
        ];
    }

    protected function channelsAreValid(int $red, int $green, int $blue): bool
    {
        return $red >= 0 && $red <= 255
            && $green >= 0 && $green <= 255
            && $blue >= 0 && $blue <= 255;
    }

    protected function alphaIsValid(float $alpha): bool
    {
        return $alpha >= 0 && $alpha <= 1;
    }
}

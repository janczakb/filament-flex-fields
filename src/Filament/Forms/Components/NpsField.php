<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldRounding;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class NpsField extends Field
{
    use HasControlSize;
    use HasFieldRounding;

    protected string $view = 'filament-flex-fields::forms.components.nps-field';

    /**
     * @var array<string|int, string>|Closure|null
     */
    protected array|Closure|null $options = null;

    protected string|Closure|null $minLabel = null;

    protected string|Closure|null $maxLabel = null;

    protected bool|Closure $isColorCoded = false;

    protected string|Closure $variant = 'pills';

    /**
     * @var array<string|int, string>|Closure|null
     */
    protected array|Closure|null $emojiImages = null;

    /**
     * @var array<string|int>|Closure
     */
    protected array|Closure $disabledOptions = [];

    /**
     * @var array<string|int, string>|Closure
     */
    protected array|Closure $icons = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);

        $this->options(array_combine(range(0, 10), range(0, 10)));

        $this->rules([
            'nullable',
            fn (NpsField $component): In => Rule::in($component->getOptionKeys()),
        ]);
    }

    /**
     * Set the available options.
     *
     * @param  array<string|int, string>|Closure  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<string|int, string>
     */
    public function getOptions(): array
    {
        return $this->evaluate($this->options) ?? [];
    }

    /**
     * @return list<string|int>
     */
    public function getOptionKeys(): array
    {
        return array_keys($this->getOptions());
    }

    /**
     * @param  array<string|int>|Closure  $keys
     */
    public function disabledOptions(array|Closure $keys): static
    {
        $this->disabledOptions = $keys;

        return $this;
    }

    /**
     * @return array<string|int>
     */
    public function getDisabledOptions(): array
    {
        return Arr::wrap($this->evaluate($this->disabledOptions));
    }

    public function isOptionDisabled(string|int $key): bool
    {
        foreach ($this->getDisabledOptions() as $disabledKey) {
            if ((string) $disabledKey === (string) $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the label for the minimum (leftmost) value.
     */
    public function minLabel(string|Closure|null $label): static
    {
        $this->minLabel = $label;

        return $this;
    }

    public function getMinLabel(): ?string
    {
        return $this->evaluate($this->minLabel);
    }

    /**
     * Set the label for the maximum (rightmost) value.
     */
    public function maxLabel(string|Closure|null $label): static
    {
        $this->maxLabel = $label;

        return $this;
    }

    public function getMaxLabel(): ?string
    {
        return $this->evaluate($this->maxLabel);
    }

    /**
     * Enable NPS color coding (0-6 red, 7-8 yellow/gray, 9-10 green).
     */
    public function colorCoded(bool|Closure $condition = true): static
    {
        $this->isColorCoded = $condition;

        return $this;
    }

    public function isColorCoded(): bool
    {
        return (bool) $this->evaluate($this->isColorCoded);
    }

    /**
     * @var array<string, string|array<mixed>>|Closure|null
     */
    protected array|Closure|null $colors = null;

    /**
     * @var array<string, string|array<mixed>>|Closure|null
     */
    protected array|Closure|null $textColors = null;

    /**
     * Set custom background colors for options.
     * E.g. ['danger' => [0, 1, 2], '#ff0000' => [3, 4]]
     */
    public function colors(array|Closure|null $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    public function getColors(): array
    {
        return $this->evaluate($this->colors) ?? [];
    }

    /**
     * Set custom active text colors for options.
     * E.g. ['white' => [0, 1, 2], '#ffffff' => [3, 4]]
     */
    public function textColors(array|Closure|null $textColors): static
    {
        $this->textColors = $textColors;

        return $this;
    }

    public function getTextColors(): array
    {
        return $this->evaluate($this->textColors) ?? [];
    }

    public function getOptionColor(mixed $value): ?string
    {
        $colors = $this->getColors();

        foreach ($colors as $color => $condition) {
            if (is_array($condition)) {
                if (in_array($value, $condition)) {
                    return $color;
                }
            } elseif ($condition instanceof Closure) {
                if ($this->evaluate($condition, ['value' => $value])) {
                    return $color;
                }
            } elseif ($condition === $value) {
                return $color;
            }
        }

        // Fallback to legacy colorCoded behavior
        if (empty($colors) && $this->isColorCoded()) {
            if ($this->isDetractor($value)) {
                return 'danger';
            }
            if ($this->isPassive($value)) {
                return 'warning';
            }
            if ($this->isPromoter($value)) {
                return 'success';
            }
        }

        return null;
    }

    public function getOptionTextColor(mixed $value): ?string
    {
        $textColors = $this->getTextColors();

        foreach ($textColors as $color => $condition) {
            if (is_array($condition)) {
                if (in_array($value, $condition)) {
                    return $color;
                }
            } elseif ($condition instanceof Closure) {
                if ($this->evaluate($condition, ['value' => $value])) {
                    return $color;
                }
            } elseif ($condition === $value) {
                return $color;
            }
        }

        // Fallback to legacy colorCoded behavior
        if (empty($textColors) && $this->isColorCoded()) {
            if ($this->isDetractor($value)) {
                return '#ffffff';
            }
            if ($this->isPassive($value)) {
                return 'gray-900';
            }
            if ($this->isPromoter($value)) {
                return '#ffffff';
            }
        }

        return null;
    }

    /**
     * Check if a given key is a detractor (0-6).
     */
    public function isDetractor(mixed $key): bool
    {
        if (! is_numeric($key)) {
            return false;
        }

        return (int) $key >= 0 && (int) $key <= 6;
    }

    /**
     * Check if a given key is passive (7-8).
     */
    public function isPassive(mixed $key): bool
    {
        if (! is_numeric($key)) {
            return false;
        }

        return (int) $key === 7 || (int) $key === 8;
    }

    /**
     * Check if a given key is a promoter (9-10).
     */
    public function isPromoter(mixed $key): bool
    {
        if (! is_numeric($key)) {
            return false;
        }

        return (int) $key === 9 || (int) $key === 10;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        return $this->evaluate($this->variant) ?? 'pills';
    }

    /**
     * @param  array<string|int, string>|Closure  $icons
     */
    public function icons(array|Closure $icons): static
    {
        $this->icons = $icons;

        return $this;
    }

    /**
     * @return array<string|int, string>
     */
    public function getIcons(): array
    {
        return $this->evaluate($this->icons) ?? [];
    }

    public function getOptionIcon(mixed $value): ?string
    {
        $icons = $this->getIcons();

        foreach ([$value, (string) $value] as $key) {
            if (array_key_exists($key, $icons) && filled($icons[$key])) {
                return (string) $icons[$key];
            }
        }

        return null;
    }

    /**
     * Set explicit image URLs or paths for emoji variant options.
     * E.g. [0 => 'path/to/awful.webp', 1 => 'path/to/bad.webp', ...]
     *
     * @param  array<string|int, string>|Closure|null  $images
     */
    public function emojiImages(array|Closure|null $images): static
    {
        $this->emojiImages = $images;

        return $this;
    }

    public function getEmojiImages(): array
    {
        return $this->evaluate($this->emojiImages) ?? [];
    }

    public function getEmojiImage(mixed $value): ?string
    {
        $images = $this->getEmojiImages();

        if (array_key_exists($value, $images)) {
            return $images[$value];
        }

        if (in_array($value, [0, 1, 2, 3, 4], true)) {
            return FlexFieldAssets::assetUrl('nps-field/emojis/'.$value.'.webp');
        }

        return null;
    }

    public function getOptionTone(mixed $value): ?string
    {
        if (! $this->isColorCoded()) {
            return null;
        }

        if ($this->isDetractor($value)) {
            return 'detractor';
        }

        if ($this->isPassive($value)) {
            return 'passive';
        }

        if ($this->isPromoter($value)) {
            return 'promoter';
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getOptionStyleVariables(mixed $value): array
    {
        if ($this->isColorCoded()) {
            return [];
        }

        $background = $this->resolveColorValue($this->getOptionColor($value));
        $text = $this->resolveColorValue($this->getOptionTextColor($value));

        $variables = [];

        if ($background !== null) {
            $variables['--fff-nps-selected-bg'] = $background;
        }

        if ($text !== null) {
            $variables['--fff-nps-selected-color'] = $text;
        }

        return $variables;
    }

    public function resolveColorValue(?string $color): ?string
    {
        if ($color === null || $color === '') {
            return null;
        }

        if (str_starts_with($color, '#') || str_starts_with($color, 'rgb')) {
            return $color;
        }

        if ($color === 'gray-900') {
            return 'var(--fi-color-gray-900, #18181b)';
        }

        $fallbacks = [
            'danger' => '#ef4444',
            'warning' => '#eab308',
            'success' => '#22c55e',
            'primary' => '#6366f1',
            'gray' => '#6b7280',
        ];

        $fallback = $fallbacks[$color] ?? 'inherit';

        return "var(--fi-color-{$color}-500, {$fallback})";
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class ColorSwatchField extends Field
{
    use HasControlSize;

    protected string $view = 'filament-flex-fields::forms.components.color-swatch-field';

    /**
     * @var array<string, string> | Closure
     */
    protected array|Closure $colors = [];

    protected string|Closure|null $sectionLabel = null;

    protected string|BackedEnum|Htmlable|Closure|null $sectionIcon = null;

    protected bool|array|Closure $tooltips = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules([
            'nullable',
            fn (ColorSwatchField $component): In => Rule::in(array_keys($component->getColors())),
        ]);
    }

    /**
     * @param  array<string, string> | Closure  $colors
     */
    public function colors(array|Closure $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    public function sectionLabel(string|Closure|null $label): static
    {
        $this->sectionLabel = $label;

        return $this;
    }

    public function sectionIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->sectionIcon = $icon;

        return $this;
    }

    /**
     * @param  array<string, string> | bool | Closure  $tooltips
     */
    public function tooltips(bool|array|Closure $tooltips = true): static
    {
        $this->tooltips = $tooltips;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getColors(): array
    {
        return $this->evaluate($this->colors);
    }

    public function getSectionLabel(): ?string
    {
        $label = $this->evaluate($this->sectionLabel);

        return filled($label) ? (string) $label : null;
    }

    public function getDefaultSectionIcon(): string|BackedEnum|Htmlable
    {
        $icon = config('filament-flex-fields.ui.color_swatch_section_icon');

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return GravityIcon::Palette;
    }

    public function getSectionIcon(): string|BackedEnum|Htmlable|null
    {
        $icon = $this->evaluate($this->sectionIcon);

        if (filled($icon)) {
            return $icon;
        }

        if (filled($this->getSectionLabel())) {
            return $this->getDefaultSectionIcon();
        }

        return null;
    }

    public function hasTooltips(): bool
    {
        $tooltips = $this->evaluate($this->tooltips);

        if (is_array($tooltips)) {
            return $tooltips !== [];
        }

        return (bool) $tooltips;
    }

    public function getColorLabel(string $key): string
    {
        $tooltips = $this->evaluate($this->tooltips);

        if (is_array($tooltips) && array_key_exists($key, $tooltips)) {
            return (string) $tooltips[$key];
        }

        return str($key)->replace(['_', '-'], ' ')->title()->toString();
    }

    public function isLightSwatch(string $hex): bool
    {
        $normalized = strtolower(trim($hex));

        return in_array($normalized, ['#fff', '#ffffff', 'white', 'rgb(255, 255, 255)', 'rgb(255 255 255)'], true);
    }
}

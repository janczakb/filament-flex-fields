<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Illuminate\Contracts\Support\Htmlable;

trait HasSelectFieldIcons
{
    protected string|BackedEnum|Htmlable|Closure|null $chevronIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $clearIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $selectedOptionCheckIcon = null;

    public function chevronIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->chevronIcon = $icon;

        return $this;
    }

    public function clearIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->clearIcon = $icon;

        return $this;
    }

    public function selectedOptionCheckIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->selectedOptionCheckIcon = $icon;

        return $this;
    }

    public function getDefaultChevronIcon(): string|BackedEnum|Htmlable
    {
        $icon = config('filament-flex-fields.ui.select_chevron_icon');

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return GravityIcon::CircleChevronDown;
    }

    public function getDefaultClearIcon(): string|BackedEnum|Htmlable
    {
        $icon = config('filament-flex-fields.ui.select_clear_icon');

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return GravityIcon::CircleXmark;
    }

    public function getChevronIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->chevronIcon);

        return $icon ?? $this->getDefaultChevronIcon();
    }

    public function getClearIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->clearIcon);

        return $icon ?? $this->getDefaultClearIcon();
    }

    public function getDefaultSelectedOptionCheckIcon(): string|BackedEnum|Htmlable
    {
        $icon = config('filament-flex-fields.ui.select_selected_option_check_icon');

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return GravityIcon::Check;
    }

    public function getSelectedOptionCheckIcon(): string|BackedEnum|Htmlable
    {
        $icon = $this->evaluate($this->selectedOptionCheckIcon);

        return $icon ?? $this->getDefaultSelectedOptionCheckIcon();
    }
}

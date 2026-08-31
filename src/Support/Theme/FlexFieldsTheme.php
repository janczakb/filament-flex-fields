<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support\Theme;

use Bjanczak\FilamentFlexFields\Enums\Density;

class FlexFieldsTheme
{
    protected Density $density;

    /**
     * @var array<string, mixed>
     */
    protected array $theme = [];

    public function __construct()
    {
        $this->density = Density::default();
    }

    public function density(): Density
    {
        return $this->density;
    }

    public function setDensity(Density|string $density): static
    {
        $this->density = Density::fromMixed($density);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function theme(): array
    {
        return $this->theme;
    }

    /**
     * @param  array<string, mixed>  $theme
     */
    public function setTheme(array $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $theme
     */
    public function mergeTheme(array $theme): static
    {
        $this->theme = array_replace($this->theme, $theme);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function toHtmlAttributes(): array
    {
        $attributes = [
            'data-fff-density' => $this->density->value,
        ];

        if ($this->theme !== []) {
            $attributes['data-fff-theme'] = json_encode($this->theme, JSON_THROW_ON_ERROR);
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function toCssVariables(): array
    {
        $variables = [];

        foreach ($this->theme as $key => $value) {
            if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
                continue;
            }

            $variableName = $this->resolveThemeVariableName((string) $key);

            if ($variableName === null) {
                continue;
            }

            $variables[$variableName] = (string) $value;
        }

        return $variables;
    }

    protected function resolveThemeVariableName(string $key): ?string
    {
        if (str_starts_with($key, '--fff-')) {
            return $key;
        }

        if (str_starts_with($key, 'fff_')) {
            return '--'.$key;
        }

        return match ($key) {
            'primary', 'primary_color' => '--fff-field-focus-border',
            'radius', 'field_radius' => '--fff-global-radius',
            'menu_radius' => '--fff-global-menu-radius',
            'field_bg' => '--fff-field-bg',
            'field_border' => '--fff-field-border',
            'field_focus_ring' => '--fff-field-focus-ring',
            'field_focus_ring_width' => '--fff-field-focus-ring-width',
            default => null,
        };
    }
}

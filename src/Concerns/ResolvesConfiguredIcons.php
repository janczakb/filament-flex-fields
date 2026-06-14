<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Closure;
use Illuminate\Contracts\Support\Htmlable;

trait ResolvesConfiguredIcons
{
    protected function resolveConfiguredIcon(string $configKey, string|BackedEnum|Htmlable $fallback): string|BackedEnum|Htmlable
    {
        $icon = FlexFieldsConfig::getUiDefault($configKey);

        if (is_string($icon) && filled($icon)) {
            return $icon;
        }

        return $fallback;
    }

    protected function resolveFieldIcon(string|BackedEnum|Htmlable|Closure|null $icon, string $configKey, string|BackedEnum|Htmlable $fallback): string|BackedEnum|Htmlable
    {
        $resolved = $this->evaluate($icon);

        return $resolved ?? $this->resolveConfiguredIcon($configKey, $fallback);
    }
}

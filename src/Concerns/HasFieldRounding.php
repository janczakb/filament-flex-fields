<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Closure;

trait HasFieldRounding
{
    protected string|Closure|null $rounding = null;

    public function rounding(string|Closure|null $rounding): static
    {
        $this->rounding = $rounding;

        return $this;
    }

    public function getRounding(): string
    {
        $rounding = $this->evaluate($this->rounding);

        if (filled($rounding)) {
            return (string) $rounding;
        }

        $default = FlexFieldsConfig::getUiDefault('field_rounding', 'md');

        return $default === 'default' ? 'md' : (string) $default;
    }
}

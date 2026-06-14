<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Closure;

trait HasControlSize
{
    protected string|ControlSize|Closure $size = 'md';

    public function size(string|ControlSize|Closure $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): string
    {
        $size = $this->evaluate($this->size);

        if ($size instanceof ControlSize) {
            return $size->value;
        }

        return (string) $size;
    }
}

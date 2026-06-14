<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Closure;
use InvalidArgumentException;

trait AnimatesProgressFill
{
    protected bool|Closure $shouldAnimateFill = true;

    protected int|Closure $animationDuration = 240;

    public function animated(bool|Closure $condition = true): static
    {
        $this->shouldAnimateFill = $condition;

        return $this;
    }

    public function animationDuration(int|Closure $milliseconds): static
    {
        $this->animationDuration = $milliseconds;

        return $this;
    }

    public function shouldAnimateFill(): bool
    {
        return (bool) $this->evaluate($this->shouldAnimateFill);
    }

    public function getAnimationDuration(): int
    {
        $duration = (int) $this->evaluate($this->animationDuration);

        if ($duration < 0 || $duration > 5000) {
            throw new InvalidArgumentException('Progress fill animation duration must be between 0 and 5000 milliseconds.');
        }

        return $duration;
    }
}

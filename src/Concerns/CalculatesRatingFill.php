<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

trait CalculatesRatingFill
{
    /**
     * @return list<int>
     */
    public function getItemIndexes(): array
    {
        return range(1, $this->getMax());
    }

    public function getFillPercentageForValue(float|int|null $value, int $index): float
    {
        if ($value === null) {
            return 0;
        }

        return max(0, min(1, ((float) $value) - ($index - 1)));
    }
}

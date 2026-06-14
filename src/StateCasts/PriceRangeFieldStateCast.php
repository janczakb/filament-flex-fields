<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\StateCasts;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;

class PriceRangeFieldStateCast implements StateCast
{
    public function __construct(
        protected PriceRangeField $field,
    ) {}

    /**
     * @return array{min: int|float, max: int|float}
     */
    public function get(mixed $state): array
    {
        return $this->field->normalizeState(is_array($state) ? $state : []);
    }

    /**
     * @return array{min: int|float, max: int|float}
     */
    public function set(mixed $state): array
    {
        return $this->field->normalizeState(is_array($state) ? $state : []);
    }
}

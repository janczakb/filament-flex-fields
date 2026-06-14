<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\StateCasts;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;

class CurrencyFieldStateCast implements StateCast
{
    public function __construct(
        protected CurrencyField $field,
    ) {}

    public function get(mixed $state): int|array|null
    {
        return $this->field->normalizeState($state);
    }

    public function set(mixed $state): int|array|null
    {
        return $this->field->normalizeState($state);
    }
}

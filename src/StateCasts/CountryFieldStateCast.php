<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\StateCasts;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;

class CountryFieldStateCast implements StateCast
{
    public function __construct(
        protected CountryField $field,
    ) {}

    public function get(mixed $state): ?string
    {
        return $this->field->normalizeState($state);
    }

    public function set(mixed $state): ?string
    {
        $normalized = $this->field->normalizeState($state);

        if ($this->field->shouldUseBrowserLocaleDefault() && blank($normalized)) {
            $detected = $this->field->getBrowserLocaleCountryCode();

            if ($detected !== null) {
                return $detected;
            }
        }

        return $normalized;
    }
}

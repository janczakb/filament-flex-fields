<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\StateCasts;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;

class PhoneFieldStateCast implements StateCast
{
    public function __construct(
        protected PhoneField $field,
    ) {}

    /**
     * @return array{country: string, national: string, e164: string}
     */
    public function get(mixed $state): array
    {
        return $this->field->normalizeState($state);
    }

    /**
     * @return array{country: string, national: string, e164: string}
     */
    public function set(mixed $state): array
    {
        $normalized = $this->field->normalizeState($state);

        if ($this->field->shouldUseBrowserLocaleDefault()) {
            $detected = $this->field->getBrowserLocaleCountryCode();

            if ($detected !== null && blank($normalized['national'])) {
                $normalized['country'] = $detected;
            }
        }

        return $normalized;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum MonthDisplay: string
{
    case Numeric = 'numeric';
    case Short = 'short';
    case Long = 'long';

    public function isTextual(): bool
    {
        return $this !== self::Numeric;
    }
}

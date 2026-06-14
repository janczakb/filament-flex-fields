<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum ControlSize: string
{
    case Sm = 'sm';
    case Md = 'md';
    case Lg = 'lg';

    public static function default(): self
    {
        return self::Md;
    }
}

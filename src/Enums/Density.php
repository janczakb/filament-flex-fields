<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Enums;

enum Density: string
{
    case Compact = 'compact';
    case Comfortable = 'comfortable';
    case Spacious = 'spacious';

    public static function default(): self
    {
        return self::Comfortable;
    }

    public static function fromMixed(Density|string $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::from($value);
    }
}

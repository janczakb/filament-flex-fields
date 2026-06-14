<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum DateTimeGranularity: string
{
    case Day = 'day';
    case Hour = 'hour';
    case Minute = 'minute';
    case Second = 'second';
}

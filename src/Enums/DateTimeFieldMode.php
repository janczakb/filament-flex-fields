<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum DateTimeFieldMode: string
{
    case Date = 'date';
    case Time = 'time';
    case DateTime = 'dateTime';
    case DateRange = 'dateRange';
    case Duration = 'duration';
    case TimeRange = 'timeRange';
    case Month = 'month';
    case Year = 'year';
}

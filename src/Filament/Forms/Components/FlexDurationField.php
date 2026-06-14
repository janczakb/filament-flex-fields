<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

class FlexDurationField extends FlexDateTimeField
{
    public function getMode(): DateTimeFieldMode
    {
        return DateTimeFieldMode::Duration;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->showCalendar = false;
        $this->showCalendarButton = false;
        $this->hourCycle = 24;
        $this->granularity = DateTimeGranularity::Minute;
        $this->hideTimeZone = true;
    }
}

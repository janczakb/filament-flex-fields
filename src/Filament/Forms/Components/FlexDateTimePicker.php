<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;

class FlexDateTimePicker extends FlexDateTimeField
{
    public function getMode(): DateTimeFieldMode
    {
        return DateTimeFieldMode::DateTime;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->showCalendar = true;
        $this->showCalendarButton = true;
    }
}

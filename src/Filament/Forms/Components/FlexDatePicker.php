<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;

class FlexDatePicker extends FlexDateTimeField
{
    public function getMode(): DateTimeFieldMode
    {
        return DateTimeFieldMode::Date;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->showCalendar = true;
        $this->showCalendarButton = true;
    }
}

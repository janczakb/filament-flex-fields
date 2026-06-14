<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;

class FlexDateField extends FlexDateTimeField
{
    public function getMode(): DateTimeFieldMode
    {
        return DateTimeFieldMode::Date;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->showCalendar = false;
        $this->showCalendarButton = false;
    }
}

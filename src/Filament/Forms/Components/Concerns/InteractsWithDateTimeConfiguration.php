<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;
use Bjanczak\FilamentFlexFields\Enums\MonthDisplay;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeFieldValue;
use Carbon\CarbonInterface;
use Closure;
use InvalidArgumentException;

trait InteractsWithDateTimeConfiguration
{
    use HasControlSize;
    use HasFieldFocusOutline;

    protected string|Closure $variant = 'primary';

    protected DateTimeGranularity|string|Closure $granularity = DateTimeGranularity::Day;

    protected string|Closure|null $locale = null;

    protected string|Closure|null $timeZone = null;

    protected int|Closure $hourCycle = 24;

    protected string|Closure|null $displayFormat = null;

    protected string|Closure|null $storageFormat = null;

    protected bool|Closure $forceLeadingZeros = true;

    protected string|CarbonInterface|Closure|null $minValue = null;

    protected string|CarbonInterface|Closure|null $maxValue = null;

    protected ?Closure $isDateUnavailable = null;

    protected string|Closure $rangeSeparator = ' - ';

    protected bool|Closure $allowSameDay = true;

    protected bool|Closure $highlightToday = true;

    protected bool|Closure $showCalendar = false;

    protected bool|Closure $showCalendarButton = false;

    protected bool|Closure $closeOnSelect = true;

    protected int|Closure $firstDayOfWeek = 0;

    protected bool|Closure $hideTimeZone = false;

    protected bool|Closure $hideTimeSection = false;

    protected bool|Closure $showSeconds = false;

    protected bool|Closure $showYearSegment = true;

    protected MonthDisplay|string|Closure $monthDisplay = MonthDisplay::Numeric;

    abstract public function getMode(): DateTimeFieldMode;

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function granularity(DateTimeGranularity|string|Closure $granularity): static
    {
        $this->granularity = $granularity;

        return $this;
    }

    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function timeZone(string|Closure|null $timeZone): static
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    public function hourCycle(int|Closure $hourCycle): static
    {
        $this->hourCycle = $hourCycle;

        return $this;
    }

    public function displayFormat(string|Closure|null $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    public function storageFormat(string|Closure|null $format): static
    {
        $this->storageFormat = $format;

        return $this;
    }

    public function forceLeadingZeros(bool|Closure $condition = true): static
    {
        $this->forceLeadingZeros = $condition;

        return $this;
    }

    public function minValue(string|CarbonInterface|Closure|null $value): static
    {
        $this->minValue = $value;

        return $this;
    }

    public function minDate(string|CarbonInterface|Closure|null $value): static
    {
        return $this->minValue($value);
    }

    public function maxValue(string|CarbonInterface|Closure|null $value): static
    {
        $this->maxValue = $value;

        return $this;
    }

    public function maxDate(string|CarbonInterface|Closure|null $value): static
    {
        return $this->maxValue($value);
    }

    public function isDateUnavailable(Closure $callback): static
    {
        $this->isDateUnavailable = $callback;

        return $this;
    }

    public function rangeSeparator(string|Closure $separator): static
    {
        $this->rangeSeparator = $separator;

        return $this;
    }

    public function allowSameDay(bool|Closure $condition = true): static
    {
        $this->allowSameDay = $condition;

        return $this;
    }

    public function highlightToday(bool|Closure $condition = true): static
    {
        $this->highlightToday = $condition;

        return $this;
    }

    public function showCalendarButton(bool|Closure $condition = true): static
    {
        $this->showCalendarButton = $condition;

        return $this;
    }

    public function closeOnSelect(bool|Closure $condition = true): static
    {
        $this->closeOnSelect = $condition;

        return $this;
    }

    public function firstDayOfWeek(int|Closure $day): static
    {
        $this->firstDayOfWeek = $day;

        return $this;
    }

    public function hideTimeZone(bool|Closure $condition = true): static
    {
        $this->hideTimeZone = $condition;

        return $this;
    }

    public function hideTimeSection(bool|Closure $condition = true): static
    {
        $this->hideTimeSection = $condition;

        return $this;
    }

    public function showSeconds(bool|Closure $condition = true): static
    {
        $this->showSeconds = $condition;

        return $this;
    }

    public function showYearSegment(bool|Closure $condition = true): static
    {
        $this->showYearSegment = $condition;

        return $this;
    }

    public function monthDisplay(MonthDisplay|string|Closure $display): static
    {
        $this->monthDisplay = $display;

        return $this;
    }

    public function withRecommendedDefaults(): static
    {
        return match ($this->getMode()) {
            DateTimeFieldMode::Date => $this
                ->granularity(DateTimeGranularity::Day)
                ->closeOnSelect()
                ->highlightToday(),
            DateTimeFieldMode::Time => $this
                ->granularity(DateTimeGranularity::Minute)
                ->hourCycle(12)
                ->hideTimeZone(),
            DateTimeFieldMode::DateTime => $this
                ->granularity(DateTimeGranularity::Minute)
                ->hourCycle(24)
                ->closeOnSelect(false),
            DateTimeFieldMode::DateRange => $this
                ->granularity(DateTimeGranularity::Day)
                ->allowSameDay()
                ->highlightToday()
                ->closeOnSelect(false),
            DateTimeFieldMode::Duration => $this
                ->granularity(DateTimeGranularity::Minute)
                ->hourCycle(24)
                ->hideTimeZone(),
            DateTimeFieldMode::TimeRange => $this
                ->granularity(DateTimeGranularity::Minute)
                ->hourCycle(24)
                ->hideTimeZone(),
            DateTimeFieldMode::Month => $this
                ->closeOnSelect()
                ->highlightToday(),
            DateTimeFieldMode::Year => $this
                ->closeOnSelect()
                ->highlightToday(),
        };
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat'], true)) {
            throw new InvalidArgumentException("Date/time field variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function getGranularity(): DateTimeGranularity
    {
        $granularity = $this->evaluate($this->granularity);

        if ($granularity instanceof DateTimeGranularity) {
            return $granularity;
        }

        return DateTimeGranularity::from((string) $granularity);
    }

    public function getLocale(): string
    {
        $locale = $this->evaluate($this->locale);

        if (filled($locale)) {
            return (string) $locale;
        }

        return app()->getLocale();
    }

    public function getTimeZone(): string
    {
        $timeZone = $this->evaluate($this->timeZone);

        if (filled($timeZone)) {
            return (string) $timeZone;
        }

        return (string) config('app.timezone', 'UTC');
    }

    public function getHourCycle(): int
    {
        return DateTimeFieldValue::assertHourCycle((int) $this->evaluate($this->hourCycle));
    }

    public function getDisplayFormat(): string
    {
        $configured = $this->evaluate($this->displayFormat);

        return DateTimeFieldValue::resolveDisplayFormat(
            $this->getMode(),
            $this->getGranularity(),
            $this->shouldShowSeconds(),
            $this->getHourCycle(),
            filled($configured) ? (string) $configured : null,
        );
    }

    public function getStorageFormat(): string
    {
        $configured = $this->evaluate($this->storageFormat);

        return DateTimeFieldValue::resolveStorageFormat(
            $this->getMode(),
            $this->getGranularity(),
            $this->shouldShowSeconds(),
            filled($configured) ? (string) $configured : null,
        );
    }

    public function shouldForceLeadingZeros(): bool
    {
        return (bool) $this->evaluate($this->forceLeadingZeros);
    }

    public function getMinValue(): string|CarbonInterface|Closure|null
    {
        return $this->minValue;
    }

    public function getMaxValue(): string|CarbonInterface|Closure|null
    {
        return $this->maxValue;
    }

    public function getIsDateUnavailableCallback(): ?Closure
    {
        return $this->isDateUnavailable;
    }

    public function getRangeSeparator(): string
    {
        return (string) $this->evaluate($this->rangeSeparator);
    }

    public function shouldAllowSameDay(): bool
    {
        return (bool) $this->evaluate($this->allowSameDay);
    }

    public function shouldHighlightToday(): bool
    {
        return (bool) $this->evaluate($this->highlightToday);
    }

    public function shouldShowCalendar(): bool
    {
        return (bool) $this->evaluate($this->showCalendar);
    }

    public function shouldShowCalendarButton(): bool
    {
        return (bool) $this->evaluate($this->showCalendarButton);
    }

    public function shouldCloseOnSelect(): bool
    {
        return (bool) $this->evaluate($this->closeOnSelect);
    }

    public function getFirstDayOfWeek(): int
    {
        $day = (int) $this->evaluate($this->firstDayOfWeek);

        return max(0, min(6, $day));
    }

    public function shouldHideTimeZone(): bool
    {
        return (bool) $this->evaluate($this->hideTimeZone);
    }

    public function shouldHideTimeSection(): bool
    {
        return (bool) $this->evaluate($this->hideTimeSection);
    }

    public function shouldShowSeconds(): bool
    {
        return (bool) $this->evaluate($this->showSeconds);
    }

    public function shouldShowYearSegment(): bool
    {
        return (bool) $this->evaluate($this->showYearSegment);
    }

    public function getMonthDisplay(): MonthDisplay
    {
        $display = $this->evaluate($this->monthDisplay);

        if ($display instanceof MonthDisplay) {
            return $display;
        }

        return MonthDisplay::from((string) $display);
    }

    protected function makeValueNormalizer(): DateTimeFieldValue
    {
        return new DateTimeFieldValue(
            $this->getMode(),
            $this->getGranularity(),
            $this->shouldShowSeconds(),
            $this->getStorageFormat(),
        );
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-date-time-field',
            'fff-flex-text-input-field',
            'fff-date-time-field--'.$this->getMode()->value,
            'fff-date-time-field--'.$this->getSize(),
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-date-time-field--'.$this->getVariant(),
            'fff-flex-text-input-field--'.$this->getVariant(),
        ];
    }
}

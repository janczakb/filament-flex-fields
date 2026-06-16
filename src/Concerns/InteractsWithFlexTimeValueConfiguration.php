<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeFieldValue;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeSegmentHydrator;
use Carbon\CarbonInterface;
use Closure;

trait InteractsWithFlexTimeValueConfiguration
{
    protected string|Closure|null $locale = null;

    protected int|Closure $hourCycle = 24;

    protected string|Closure|null $storageFormat = null;

    protected string|CarbonInterface|Closure|null $minValue = null;

    protected string|CarbonInterface|Closure|null $maxValue = null;

    public function locale(string|Closure|null $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function hourCycle(int|Closure $hourCycle): static
    {
        $this->hourCycle = $hourCycle;

        return $this;
    }

    public function storageFormat(string|Closure|null $format): static
    {
        $this->storageFormat = $format;

        return $this;
    }

    public function minValue(string|CarbonInterface|Closure|null $value): static
    {
        $this->minValue = $value;

        return $this;
    }

    public function maxValue(string|CarbonInterface|Closure|null $value): static
    {
        $this->maxValue = $value;

        return $this;
    }

    public function getLocale(): string
    {
        $locale = $this->evaluate($this->locale);

        if (filled($locale)) {
            return (string) $locale;
        }

        return app()->getLocale();
    }

    public function getHourCycle(): int
    {
        return DateTimeFieldValue::assertHourCycle((int) $this->evaluate($this->hourCycle));
    }

    public function getStorageFormat(): string
    {
        $configured = $this->evaluate($this->storageFormat);

        return DateTimeFieldValue::resolveStorageFormat(
            DateTimeFieldMode::Time,
            DateTimeGranularity::Minute,
            false,
            filled($configured) ? (string) $configured : null,
        );
    }

    public function getResolvedMinValue(): ?string
    {
        return $this->resolveBoundTimeValue($this->minValue);
    }

    public function getResolvedMaxValue(): ?string
    {
        return $this->resolveBoundTimeValue($this->maxValue);
    }

    protected function makeTimeValueNormalizer(): DateTimeFieldValue
    {
        return new DateTimeFieldValue(
            DateTimeFieldMode::Time,
            DateTimeGranularity::Minute,
            false,
            $this->getStorageFormat(),
        );
    }

    protected function resolveBoundTimeValue(string|CarbonInterface|Closure|null $value): ?string
    {
        $resolved = $this->evaluate($value);

        if ($resolved instanceof CarbonInterface) {
            $resolved = $resolved->format('H:i');
        }

        if (! is_string($resolved) || blank($resolved)) {
            return null;
        }

        return $this->makeTimeValueNormalizer()->normalizeSingle($resolved);
    }

    /**
     * @return array{
     *     hourCycle: int,
     *     minuteStep: int,
     *     minValue: string|null,
     *     maxValue: string|null,
     *     locale: string,
     *     hourPlaceholder: string,
     *     minutePlaceholder: string,
     * }
     */
    public function getAlpineConfiguration(): array
    {
        $locale = $this->getLocale();

        return [
            'hourCycle' => $this->getHourCycle(),
            'minuteStep' => $this->getMinuteStep(),
            'minValue' => $this->getResolvedMinValue(),
            'maxValue' => $this->getResolvedMaxValue(),
            'locale' => $locale,
            'hourPlaceholder' => DateTimeSegmentHydrator::segmentPlaceholder('hour', $locale),
            'minutePlaceholder' => DateTimeSegmentHydrator::segmentPlaceholder('minute', $locale),
        ];
    }
}

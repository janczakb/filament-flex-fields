<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\DateTime;

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class DateTimeFieldValue
{
    public function __construct(
        protected DateTimeFieldMode $mode,
        protected DateTimeGranularity $granularity,
        protected bool $showSeconds,
        protected string $storageFormat,
    ) {}

    public function normalize(mixed $state): string|array|null
    {
        if ($this->mode === DateTimeFieldMode::DateRange || $this->mode === DateTimeFieldMode::TimeRange) {
            return $this->normalizeRange($state);
        }

        return $this->normalizeSingle($state);
    }

    /**
     * @return array{start: string|null, end: string|null}|null
     */
    protected function normalizeRange(mixed $state): ?array
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_string($state)) {
            $parts = explode(' - ', $state, 2);

            return [
                'start' => $this->normalizeSingle($parts[0] ?? null),
                'end' => $this->normalizeSingle($parts[1] ?? null),
            ];
        }

        if (! is_array($state)) {
            return null;
        }

        return [
            'start' => $this->normalizeSingle($state['start'] ?? null),
            'end' => $this->normalizeSingle($state['end'] ?? null),
        ];
    }

    public function normalizeSingle(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        if ($state instanceof CarbonInterface) {
            return $state->format($this->storageFormat);
        }

        if (! is_string($state) && ! is_numeric($state)) {
            return null;
        }

        $value = trim((string) $state);

        if ($value === '') {
            return null;
        }

        try {
            $parsed = $this->parseValue($value);

            return $parsed?->format($this->storageFormat);
        } catch (\Throwable) {
            return null;
        }
    }

    public function parseValue(string $value): ?Carbon
    {
        if ($this->mode === DateTimeFieldMode::Time || $this->mode === DateTimeFieldMode::Duration || $this->mode === DateTimeFieldMode::TimeRange) {
            return $this->parseTimeValue($value);
        }

        if ($this->mode === DateTimeFieldMode::Month) {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        if ($this->mode === DateTimeFieldMode::Year) {
            return Carbon::createFromFormat('Y', $value)->startOfYear();
        }

        if ($this->mode === DateTimeFieldMode::Date || $this->granularity === DateTimeGranularity::Day) {
            return Carbon::parse($value)->startOfDay();
        }

        return Carbon::parse($value);
    }

    protected function parseTimeValue(string $value): Carbon
    {
        $formats = $this->showSeconds
            ? ['H:i:s', 'H:i', 'g:i:s A', 'g:i A']
            : ['H:i', 'H:i:s', 'g:i A', 'g:i:s A'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }
        }

        return Carbon::parse($value);
    }

    public function formatForDisplay(?string $value, string $displayFormat): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            $parsed = $this->parseValue($value);

            return $parsed?->format($displayFormat);
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * @return list<string>
     */
    public static function defaultStorageFormats(
        DateTimeFieldMode $mode,
        DateTimeGranularity $granularity,
        bool $showSeconds,
    ): array {
        return match ($mode) {
            DateTimeFieldMode::Date => ['Y-m-d'],
            DateTimeFieldMode::Month => ['Y-m'],
            DateTimeFieldMode::Year => ['Y'],
            DateTimeFieldMode::Time, DateTimeFieldMode::Duration, DateTimeFieldMode::TimeRange => $showSeconds ? ['H:i:s', 'H:i'] : ['H:i', 'H:i:s'],
            DateTimeFieldMode::DateTime => match ($granularity) {
                DateTimeGranularity::Day => ['Y-m-d'],
                DateTimeGranularity::Hour => ['Y-m-d\TH:00:00', 'Y-m-d H:00:00'],
                DateTimeGranularity::Minute => ['Y-m-d\TH:i:00', 'Y-m-d H:i:00'],
                DateTimeGranularity::Second => ['Y-m-d\TH:i:s', 'Y-m-d H:i:s'],
            },
            DateTimeFieldMode::DateRange => match ($granularity) {
                DateTimeGranularity::Day => ['Y-m-d'],
                DateTimeGranularity::Hour => ['Y-m-d\TH:00:00'],
                DateTimeGranularity::Minute => ['Y-m-d\TH:i:00'],
                DateTimeGranularity::Second => ['Y-m-d\TH:i:s'],
            },
        };
    }

    public static function resolveStorageFormat(
        DateTimeFieldMode $mode,
        DateTimeGranularity $granularity,
        bool $showSeconds,
        ?string $configured,
    ): string {
        if (filled($configured)) {
            return $configured;
        }

        return self::defaultStorageFormats($mode, $granularity, $showSeconds)[0];
    }

    public static function resolveDisplayFormat(
        DateTimeFieldMode $mode,
        DateTimeGranularity $granularity,
        bool $showSeconds,
        int $hourCycle,
        ?string $configured,
    ): string {
        if (filled($configured)) {
            return $configured;
        }

        return match ($mode) {
            DateTimeFieldMode::Date => 'm/d/Y',
            DateTimeFieldMode::Month => 'm/Y',
            DateTimeFieldMode::Year => 'Y',
            DateTimeFieldMode::Time, DateTimeFieldMode::Duration, DateTimeFieldMode::TimeRange => $hourCycle === 12
                ? ($showSeconds ? 'g:i:s A' : 'g:i A')
                : ($showSeconds ? 'H:i:s' : 'H:i'),
            DateTimeFieldMode::DateTime => match ($granularity) {
                DateTimeGranularity::Day => 'm/d/Y',
                DateTimeGranularity::Hour => $hourCycle === 12 ? 'm/d/Y g:i A' : 'm/d/Y H:i',
                DateTimeGranularity::Minute => $hourCycle === 12 ? 'm/d/Y g:i A' : 'm/d/Y H:i',
                DateTimeGranularity::Second => $hourCycle === 12 ? 'm/d/Y g:i:s A' : 'm/d/Y H:i:s',
            },
            DateTimeFieldMode::DateRange => match ($granularity) {
                DateTimeGranularity::Day => 'm/d/Y',
                DateTimeGranularity::Hour => $hourCycle === 12 ? 'm/d/Y g:i A' : 'm/d/Y H:i',
                DateTimeGranularity::Minute => $hourCycle === 12 ? 'm/d/Y g:i A' : 'm/d/Y H:i',
                DateTimeGranularity::Second => $hourCycle === 12 ? 'm/d/Y g:i:s A' : 'm/d/Y H:i:s',
            },
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function assertHourCycle(int $hourCycle): int
    {
        if (! in_array($hourCycle, [12, 24], true)) {
            throw new InvalidArgumentException("Hour cycle [{$hourCycle}] is not supported. Use 12 or 24.");
        }

        return $hourCycle;
    }
}

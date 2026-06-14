<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\DateTime;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;

class DateTimeConstraintResolver
{
    public function __construct(
        protected DateTimeFieldValue $valueNormalizer,
        protected mixed $minValue,
        protected mixed $maxValue,
        protected ?Closure $isDateUnavailable,
    ) {}

    public function resolveMin(): ?string
    {
        return $this->resolveBoundary($this->minValue);
    }

    public function resolveMax(): ?string
    {
        return $this->resolveBoundary($this->maxValue);
    }

    protected function resolveBoundary(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Closure) {
            $value = $value();
        }

        if ($value instanceof CarbonInterface) {
            return $this->valueNormalizer->normalizeSingle($value);
        }

        if (is_string($value) || is_numeric($value)) {
            return $this->valueNormalizer->normalizeSingle((string) $value);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function unavailableDatesBetween(?string $min, ?string $max, int $cap = 366): array
    {
        if (! $this->isDateUnavailable instanceof Closure) {
            return [];
        }

        if ($min === null && $max === null) {
            return [];
        }

        try {
            $start = Carbon::parse($min ?? $max)->startOfDay();
            $end = Carbon::parse($max ?? $min)->startOfDay();

            if ($start->greaterThan($end)) {
                [$start, $end] = [$end, $start];
            }

            $unavailable = [];
            $current = $start->copy();
            $days = 0;

            while ($current->lessThanOrEqualTo($end) && $days < $cap) {
                if ((bool) ($this->isDateUnavailable)($current)) {
                    $unavailable[] = $current->format('Y-m-d');
                }

                $current->addDay();
                $days++;
            }

            return $unavailable;
        } catch (\Throwable) {
            return [];
        }
    }

    public function isUnavailable(?string $value): bool
    {
        if ($value === null || ! $this->isDateUnavailable instanceof Closure) {
            return false;
        }

        try {
            $date = Carbon::parse($value)->startOfDay();

            return (bool) ($this->isDateUnavailable)($date);
        } catch (\Throwable) {
            return false;
        }
    }

    public function isBelowMin(?string $value): bool
    {
        $min = $this->resolveMin();

        if ($value === null || $min === null) {
            return false;
        }

        return $this->compare($value, $min) < 0;
    }

    public function isAboveMax(?string $value): bool
    {
        $max = $this->resolveMax();

        if ($value === null || $max === null) {
            return false;
        }

        return $this->compare($value, $max) > 0;
    }

    public function compareValues(string $left, string $right): int
    {
        return $this->compare($left, $right);
    }

    protected function compare(string $left, string $right): int
    {
        try {
            $leftDate = Carbon::parse($left);
            $rightDate = Carbon::parse($right);

            return $leftDate <=> $rightDate;
        } catch (\Throwable) {
            return strcmp($left, $right);
        }
    }
}

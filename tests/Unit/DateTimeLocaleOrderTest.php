<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;
use Bjanczak\FilamentFlexFields\Enums\MonthDisplay;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeConstraintResolver;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeFieldValue;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeLocaleOrder;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeMonthLabels;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeSegmentHydrator;
use Carbon\Carbon;

it('orders date segments day-first for european locales', function () {
    expect(DateTimeLocaleOrder::dateSegmentParts('pl_PL'))->toBe(['day', 'month', 'year'])
        ->and(DateTimeLocaleOrder::dateSegmentParts('en_GB'))->toBe(['day', 'month', 'year'])
        ->and(DateTimeLocaleOrder::dateSegmentParts('de_DE'))->toBe(['day', 'month', 'year'])
        ->and(DateTimeLocaleOrder::isDayFirst('pl_PL'))->toBeTrue();
});

it('orders date segments month-first for us locale', function () {
    expect(DateTimeLocaleOrder::dateSegmentParts('en_US'))->toBe(['month', 'day', 'year'])
        ->and(DateTimeLocaleOrder::isDayFirst('en_US'))->toBeFalse();
});

it('hydrates locale-aware segment parts in the hydrator', function () {
    $parts = DateTimeSegmentHydrator::segmentParts(
        DateTimeFieldMode::Date,
        DateTimeGranularity::Day,
        24,
        false,
        'pl_PL',
    );

    expect($parts)->toBe(['day', 'month', 'year'])
        ->and(DateTimeSegmentHydrator::separatorAfter('day', $parts, 'pl_PL'))->not->toBe('');
});

it('does not add a date separator between year and time segments', function () {
    $parts = DateTimeSegmentHydrator::segmentParts(
        DateTimeFieldMode::DateTime,
        DateTimeGranularity::Minute,
        12,
        false,
        'en_US',
    );

    expect(DateTimeSegmentHydrator::separatorAfter('year', $parts, 'en_US'))->toBe('');
});

it('returns month-only segment parts when year segment is hidden', function () {
    $parts = DateTimeSegmentHydrator::segmentParts(
        DateTimeFieldMode::Month,
        DateTimeGranularity::Day,
        24,
        false,
        'en_US',
        false,
    );

    expect($parts)->toBe(['month']);
});

it('precomputes unavailable dates for alpine config window', function () {
    $normalizer = new DateTimeFieldValue(
        DateTimeFieldMode::Date,
        DateTimeGranularity::Day,
        false,
        'Y-m-d',
    );

    $weekend = static fn (Carbon $date): bool => $date->isWeekend();

    $resolver = new DateTimeConstraintResolver(
        $normalizer,
        '2026-06-01',
        '2026-06-14',
        $weekend,
    );

    $unavailable = $resolver->unavailableDatesBetween('2026-06-01', '2026-06-14');

    expect($unavailable)->toContain('2026-06-06', '2026-06-07', '2026-06-13', '2026-06-14')
        ->and($unavailable)->not->toContain('2026-06-09');
});

it('precomputes unavailable dates around an anchor when min and max are missing', function () {
    $normalizer = new DateTimeFieldValue(
        DateTimeFieldMode::Date,
        DateTimeGranularity::Day,
        false,
        'Y-m-d',
    );

    $weekend = static fn (Carbon $date): bool => $date->isWeekend();

    $resolver = new DateTimeConstraintResolver(
        $normalizer,
        null,
        null,
        $weekend,
    );

    $unavailable = $resolver->unavailableDatesBetween(null, null, 2190, '2026-06-10');

    expect($unavailable)->toContain('2026-06-06', '2026-06-07', '2026-06-13', '2026-06-14')
        ->and($unavailable)->not->toContain('2026-06-10');
});

it('normalizes duration values as time strings', function () {
    $normalizer = new DateTimeFieldValue(
        DateTimeFieldMode::Duration,
        DateTimeGranularity::Minute,
        true,
        'H:i:s',
    );

    expect($normalizer->normalize('2:30:15'))->toBe('02:30:15')
        ->and($normalizer->normalize('02:30'))->toBe('02:30:00');
});

it('falls back when ICU cannot create a date formatter', function (): void {
    $previous = ini_set('intl.use_exceptions', '0');
    $locale = str_repeat('x', 300);
    try {
        expect(IntlDateFormatter::create($locale, IntlDateFormatter::SHORT, IntlDateFormatter::NONE))->toBeNull()
            ->and(DateTimeLocaleOrder::dateSegmentParts($locale))->toBe(['month', 'day', 'year'])
            ->and(DateTimeLocaleOrder::separatorAfter('month', ['month', 'day', 'year'], $locale))->toBe('/');
    } finally {
        ini_set('intl.use_exceptions', $previous);
    }
})->skip(! extension_loaded('intl'), 'ICU failure case requires intl');

it('keeps all month labels when ICU cannot create a date formatter', function (MonthDisplay $display, string $first): void {
    $previous = ini_set('intl.use_exceptions', '0');
    try {
        $labels = DateTimeMonthLabels::labels(str_repeat('x', 300), $display);
        expect($labels)->toHaveCount(12)->and($labels[0])->toBe($first)
            ->and(array_filter($labels, fn (string $label): bool => $label === ''))->toBe([]);
    } finally {
        ini_set('intl.use_exceptions', $previous);
    }
})->with([
    'long' => [MonthDisplay::Long, 'January'],
    'short' => [MonthDisplay::Short, 'Jan'],
])->skip(! extension_loaded('intl'), 'ICU failure case requires intl');

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeOs;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeStorageContract;
use Bjanczak\FilamentFlexFields\Support\DateTime\ScheduleV2;
use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleDays;
use Carbon\Carbon;

it('normalizes iso dates and datetimes via datetime os', function () {
    expect(DateTimeOs::normalizeDate('2026-06-15'))->toBe('2026-06-15')
        ->and(DateTimeOs::normalizeDate(Carbon::parse('2026-06-15')))->toBe('2026-06-15')
        ->and(DateTimeOs::normalizeDateTime('2026-06-15 14:30'))->toBe('2026-06-15T14:30:00')
        ->and(DateTimeOs::normalizeTime('9:5'))->toBe('09:05')
        ->and(DateTimeOs::normalizeTime('invalid'))->toBeNull();
});

it('documents canonical storage formats on the contract', function () {
    expect(DateTimeStorageContract::DATE)->toBe('Y-m-d')
        ->and(DateTimeStorageContract::TIME)->toBe('H:i')
        ->and(DateTimeStorageContract::SCHEDULE_TIME)->toBe('H:i');
});

it('resolves first day of week from locale', function () {
    expect(DateTimeOs::firstDayOfWeekForLocale('en_US'))->toBe(0)
        ->and(DateTimeOs::firstDayOfWeekForLocale('de_DE'))->toBe(1);
});

it('returns weekday business hours defaults', function () {
    $defaults = DateTimeOs::businessHoursDefaults('Europe/Warsaw');

    expect($defaults)->toHaveKey('timezone', 'Europe/Warsaw')
        ->and($defaults['days']['mon']['enabled'])->toBeTrue()
        ->and($defaults['days']['mon']['slots'])->toBe([['from' => '09:00', 'to' => '17:00']])
        ->and($defaults['days']['sat']['enabled'])->toBeFalse();
});

it('detects overlapping intervals on the same day', function () {
    expect(ScheduleV2::validateNoOverlap([
        ['from' => '09:00', 'to' => '12:00'],
        ['from' => '11:00', 'to' => '13:00'],
    ]))->toBeFalse()
        ->and(ScheduleV2::validateNoOverlap([
            ['from' => '09:00', 'to' => '12:00'],
            ['from' => '12:00', 'to' => '13:00'],
        ]))->toBeTrue();
});

it('supports overnight intervals without false overlap', function () {
    expect(ScheduleV2::validateNoOverlap([
        ['from' => '22:00', 'to' => '06:00', 'overnight' => true],
        ['from' => '07:00', 'to' => '08:00'],
    ]))->toBeTrue()
        ->and(ScheduleV2::validateNoOverlap([
            ['from' => '22:00', 'to' => '06:00', 'overnight' => true],
            ['from' => '23:30', 'to' => '23:45'],
        ]))->toBeFalse();
});

it('treats from >= to as overnight when overnight flag is omitted', function () {
    expect(ScheduleV2::validateNoOverlap([
        ['from' => '23:00', 'to' => '01:00'],
    ]))->toBeTrue()
        ->and(ScheduleV2::validateNoOverlap([
            ['from' => '23:00', 'to' => '01:00'],
            ['from' => '00:30', 'to' => '02:00'],
        ]))->toBeFalse();
});

it('copies one day schedule to another day', function () {
    $schedule = [
        'timezone' => 'UTC',
        'days' => [
            'mon' => [
                'enabled' => true,
                'slots' => [
                    ['from' => '09:00', 'to' => '12:00', 'type' => 'slot'],
                    ['from' => '13:00', 'to' => '17:00', 'type' => 'slot'],
                ],
            ],
            'tue' => [
                'enabled' => false,
                'slots' => [],
            ],
        ],
    ];

    $copied = ScheduleV2::copyDay($schedule, 'mon', 'tue');

    expect($copied['days']['tue'])->toBe([
        'enabled' => true,
        'slots' => [
            ['from' => '09:00', 'to' => '12:00', 'type' => 'slot'],
            ['from' => '13:00', 'to' => '17:00', 'type' => 'slot'],
        ],
    ])
        ->and($copied['days']['mon'])->toBe($schedule['days']['mon'])
        ->and($copied['timezone'])->toBe('UTC');
});

it('ignores invalid day keys when copying schedule days', function () {
    $schedule = [
        'days' => array_fill_keys(ScheduleDays::ALL, ['enabled' => false, 'slots' => []]),
    ];

    expect(ScheduleV2::copyDay($schedule, 'invalid', 'tue'))->toBe($schedule)
        ->and(ScheduleV2::copyDay($schedule, 'mon', 'invalid'))->toBe($schedule);
});

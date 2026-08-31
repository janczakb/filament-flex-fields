<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeOs;
use Bjanczak\FilamentFlexFields\Support\DateTime\ScheduleV2;
use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleChangeAuditor;
use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleExporter;
use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleIcalGenerator;
use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleNormalizer;
use Carbon\CarbonImmutable;

it('preserves overnight flag when normalizing schedule slots', function () {
    $normalizer = new ScheduleNormalizer;

    $day = $normalizer->normalizeDay([
        'enabled' => true,
        'slots' => [
            ['from' => '22:00', 'to' => '06:00', 'overnight' => true, 'type' => 'slot'],
        ],
    ]);

    expect($day['slots'][0])
        ->toHaveKeys(['from', 'to', 'type', 'overnight'])
        ->and($day['slots'][0]['overnight'])->toBeTrue();
});

it('exposes slotIsOvernight helper on ScheduleV2', function () {
    expect(ScheduleV2::slotIsOvernight(['from' => '22:00', 'to' => '06:00', 'overnight' => true]))->toBeTrue()
        ->and(ScheduleV2::slotIsOvernight(['from' => '22:00', 'to' => '06:00']))->toBeFalse();
});

it('defaults first day of week from locale when not explicitly configured', function () {
    $field = FlexDateField::make('starts_at')->locale('de_DE');

    expect($field->getFirstDayOfWeek())->toBe(DateTimeOs::firstDayOfWeekForLocale('de_DE'));
});

it('respects explicit first day of week configuration', function () {
    $field = FlexDateField::make('starts_at')->locale('de_DE')->firstDayOfWeek(0);

    expect($field->getFirstDayOfWeek())->toBe(0);
});

it('exports schedule to api payload', function () {
    $schedule = DateTimeOs::businessHoursDefaults('Europe/Warsaw');
    $api = (new ScheduleExporter)->toApi($schedule);

    expect($api)
        ->toHaveKeys(['timezone', 'days', 'opening_hours'])
        ->and($api['timezone'])->toBe('Europe/Warsaw')
        ->and($api['opening_hours'])->toHaveCount(7)
        ->and($api['opening_hours'][0])->toHaveKeys(['day', 'enabled', 'slots']);
});

it('builds opening hours summary with localized day labels', function () {
    $schedule = [
        'timezone' => 'UTC',
        'days' => [
            'mon' => [
                'enabled' => true,
                'slots' => [
                    ['from' => '22:00', 'to' => '06:00', 'overnight' => true, 'type' => 'slot'],
                ],
            ],
        ],
    ];

    $summary = (new ScheduleExporter)->toOpeningHoursSummary($schedule, ['mon']);

    expect($summary)->toHaveCount(1)
        ->and($summary[0]['periods'][0]['overnight'])->toBeTrue();
});

it('generates weekly ical events for enabled slots', function () {
    $schedule = [
        'timezone' => 'UTC',
        'days' => [
            'mon' => [
                'enabled' => true,
                'slots' => [
                    ['from' => '09:00', 'to' => '17:00', 'type' => 'slot'],
                ],
            ],
        ],
    ];

    $ical = (new ScheduleIcalGenerator)->generate(
        $schedule,
        'Store Hours',
        CarbonImmutable::parse('2026-06-01', 'UTC'),
        ['mon'],
    );

    expect($ical)
        ->toContain('BEGIN:VCALENDAR')
        ->toContain('BEGIN:VEVENT')
        ->toContain('RRULE:FREQ=WEEKLY;BYDAY=MO')
        ->toContain('DTSTART;TZID=UTC:')
        ->toContain('END:VCALENDAR');
});

it('audits schedule changes with human readable messages', function () {
    $before = DateTimeOs::businessHoursDefaults('UTC');
    $after = $before;
    $after['days']['sat']['enabled'] = true;
    $after['days']['sat']['slots'] = [['from' => '10:00', 'to' => '14:00', 'type' => 'slot']];

    $changes = (new ScheduleChangeAuditor)->diff($before, $after);
    $messages = (new ScheduleChangeAuditor)->describe($changes);

    expect($changes)->not->toBeEmpty()
        ->and(collect($changes)->pluck('type'))->toContain('day_enabled')
        ->and($messages[0])->toBeString()->not->toBe('');
});

it('normalizes datetime values across dst fixture contract', function () {
    $fixture = json_decode(
        file_get_contents(__DIR__.'/../fixtures/datetime-dst-contract.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    foreach ($fixture['cases'] as $case) {
        if ($case['expected_normalized'] === null) {
            expect(DateTimeOs::normalizeDateTime($case['local']))->toBeNull();

            continue;
        }

        expect(DateTimeOs::normalizeDateTime($case['local']))->toBe($case['expected_normalized']);
    }

    foreach ($fixture['schedule_overnight'] as $case) {
        expect(ScheduleV2::validateNoOverlap($case['slots']))->toBeTrue();

        foreach ($case['overlap_allowed_with'] as $allowed) {
            expect(ScheduleV2::validateNoOverlap([...$case['slots'], $allowed]))->toBeTrue();
        }

        foreach ($case['overlap_blocked_with'] as $blocked) {
            expect(ScheduleV2::validateNoOverlap([...$case['slots'], $blocked]))->toBeFalse();
        }
    }
});

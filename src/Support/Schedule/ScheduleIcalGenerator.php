<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schedule;

use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeOs;
use Carbon\CarbonImmutable;

final class ScheduleIcalGenerator
{
    private const ICAL_DAY_MAP = [
        'mon' => 'MO',
        'tue' => 'TU',
        'wed' => 'WE',
        'thu' => 'TH',
        'fri' => 'FR',
        'sat' => 'SA',
        'sun' => 'SU',
    ];

    /**
     * @param  array{timezone?: string, days: array<string, array{enabled: bool, slots: list<array<string, mixed>>}>}  $schedule
     * @param  list<string>  $days
     */
    public function generate(
        array $schedule,
        string $calendarName = 'Opening Hours',
        ?CarbonImmutable $anchorDate = null,
        array $days = ScheduleDays::ALL,
    ): string {
        $api = (new ScheduleExporter)->toApi($schedule, $days);
        $timezone = $api['timezone'];
        $anchorDate ??= CarbonImmutable::now($timezone)->startOfWeek();
        $uidDomain = 'filament-flex-fields.local';
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Filament Flex Fields//Schedule//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escapeText($calendarName),
            'X-WR-TIMEZONE:'.$this->escapeText($timezone),
        ];

        foreach ($api['opening_hours'] as $entry) {
            if (! ($entry['enabled'] ?? false)) {
                continue;
            }

            $day = $entry['day'];
            $icalDay = self::ICAL_DAY_MAP[$day] ?? null;

            if ($icalDay === null) {
                continue;
            }

            foreach ($entry['slots'] as $index => $slot) {
                if (! is_array($slot)) {
                    continue;
                }

                $from = DateTimeOs::normalizeTime($slot['from'] ?? null);
                $to = DateTimeOs::normalizeTime($slot['to'] ?? null);

                if ($from === null || $to === null) {
                    continue;
                }

                $eventDate = $this->resolveEventDate($anchorDate, $day, $timezone);
                $start = $eventDate->setTimeFromTimeString($from);
                $end = $eventDate->setTimeFromTimeString($to);

                if ((bool) ($slot['overnight'] ?? false) || $end->lessThanOrEqualTo($start)) {
                    $end = $end->addDay();
                }

                $uid = sha1("{$day}-{$from}-{$to}-{$index}@{$uidDomain}");
                $summary = ($slot['type'] ?? 'slot') === 'break' ? 'Break' : 'Open';

                $lines[] = 'BEGIN:VEVENT';
                $lines[] = 'UID:'.$uid;
                $lines[] = 'SUMMARY:'.$this->escapeText($summary);
                $lines[] = 'DTSTART;TZID='.$timezone.':'.$start->format('Ymd\THis');
                $lines[] = 'DTEND;TZID='.$timezone.':'.$end->format('Ymd\THis');
                $lines[] = 'RRULE:FREQ=WEEKLY;BYDAY='.$icalDay;
                $lines[] = 'END:VEVENT';
            }
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    private function resolveEventDate(CarbonImmutable $anchorDate, string $day, string $timezone): CarbonImmutable
    {
        $targetIndex = array_search($day, ScheduleDays::ALL, true);
        $anchorIndex = $anchorDate->dayOfWeekIso - 1;

        if ($targetIndex === false) {
            return $anchorDate->setTimezone($timezone);
        }

        $offset = $targetIndex - $anchorIndex;

        return $anchorDate->addDays($offset)->setTimezone($timezone);
    }

    private function escapeText(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\n"],
            ['\\\\', '\;', '\,', '\n'],
            $value,
        );
    }
}

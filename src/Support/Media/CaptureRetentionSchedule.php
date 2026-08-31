<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Illuminate\Console\Scheduling\Schedule;

final class CaptureRetentionSchedule
{
    public static function register(Schedule $schedule): void
    {
        if (! (bool) config('filament-flex-fields.media_capture.retention.schedule_enabled', true)) {
            return;
        }

        $expression = (string) config('filament-flex-fields.media_capture.retention.schedule', 'daily');

        $event = $schedule->command('flex-fields:prune-capture-media');

        match ($expression) {
            'hourly' => $event->hourly(),
            'weekly' => $event->weekly(),
            default => $event->daily(),
        };
    }
}

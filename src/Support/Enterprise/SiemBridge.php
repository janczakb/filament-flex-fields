<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Enterprise;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Forwards {@see ObservabilityHooks} events to a SIEM / log sink (M13).
 *
 * Drivers: `null` (off), `log` (Laravel log channel), or a custom callable
 * registered via {@see registerSink()}.
 */
final class SiemBridge
{
    public const string DRIVER_NULL = 'null';

    public const string DRIVER_LOG = 'log';

    /** @var (callable(string, array<string, mixed>): void)|null */
    private static $customSink = null;

    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        if (! ObservabilityHooks::enabled()) {
            return;
        }

        if (self::driver() === self::DRIVER_NULL && self::$customSink === null) {
            return;
        }

        foreach (ObservabilityHooks::listEvents() as $event) {
            ObservabilityHooks::on($event, static function (array $payload) use ($event): void {
                self::forward($event, $payload);
            });
        }
    }

    /**
     * @param  callable(string, array<string, mixed>): void|null  $sink
     */
    public static function registerSink(?callable $sink): void
    {
        self::$customSink = $sink;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function forward(string $event, array $payload): void
    {
        if (! ObservabilityHooks::enabled()) {
            return;
        }

        $envelope = [
            'source' => 'filament-flex-fields',
            'event' => $event,
            'payload' => $payload,
            'occurred_at' => now()->toIso8601String(),
        ];

        if (self::$customSink !== null) {
            try {
                (self::$customSink)($event, $envelope);
            } catch (Throwable) {
                // SIEM sinks must not break field UX.
            }

            return;
        }

        if (self::driver() !== self::DRIVER_LOG) {
            return;
        }

        try {
            Log::channel(self::channel())->info('fff.siem', $envelope);
        } catch (Throwable) {
            //
        }
    }

    public static function driver(): string
    {
        try {
            if (! function_exists('app') || ! app()->bound('config')) {
                return self::DRIVER_NULL;
            }

            $driver = (string) config('filament-flex-fields.enterprise.siem.driver', self::DRIVER_NULL);

            return in_array($driver, [self::DRIVER_NULL, self::DRIVER_LOG], true)
                ? $driver
                : self::DRIVER_NULL;
        } catch (Throwable) {
            return self::DRIVER_NULL;
        }
    }

    public static function channel(): string
    {
        try {
            return (string) config('filament-flex-fields.enterprise.siem.channel', 'stack');
        } catch (Throwable) {
            return 'stack';
        }
    }

    public static function clear(): void
    {
        self::$customSink = null;
        self::$booted = false;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Enterprise;

/**
 * In-process enterprise telemetry listeners for SelectField / uploads / overlays.
 *
 * Client-side surfaces that cannot reach PHP (overlay exclusive open) dispatch a
 * browser CustomEvent named {@see self::WINDOW_EVENT} with
 * `detail: { event: 'overlay.open'|'select.search'|..., ...payload }`.
 * PHP {@see emit()} / {@see record()} no-op when `enterprise.enabled` is false.
 */
final class ObservabilityHooks
{
    public const string EVENT_FIELD_MOUNT = 'field.mount';

    public const string EVENT_OVERLAY_OPEN = 'overlay.open';

    public const string EVENT_SELECT_SEARCH = 'select.search';

    public const string EVENT_UPLOAD_FAIL = 'upload.fail';

    public const string EVENT_UPLOAD_SUCCESS = 'upload.success';

    public const string EVENT_RETENTION_PRUNE = 'retention.prune';

    public const string EVENT_BARCODE_CAPTURE = 'barcode.capture';

    /**
     * Browser CustomEvent name for JS→listener observability (overlay.open, client select.search).
     */
    public const string WINDOW_EVENT = 'fff:observability';

    /** @var array<string, list<callable(array<string, mixed>): void>> */
    private static array $listeners = [];

    public static function enabled(): bool
    {
        return (bool) config('filament-flex-fields.enterprise.enabled', true);
    }

    /**
     * @param  callable(array<string, mixed>): void  $listener
     */
    public static function on(string $event, callable $listener): void
    {
        self::$listeners[$event][] = $listener;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function emit(string $event, array $payload = []): void
    {
        if (! self::enabled()) {
            return;
        }

        foreach (self::$listeners[$event] ?? [] as $listener) {
            $listener($payload);
        }
    }

    /**
     * Alias of {@see emit()} for call sites that prefer "record" wording.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function record(string $event, array $payload = []): void
    {
        self::emit($event, $payload);
    }

    /**
     * @return list<string>
     */
    public static function listEvents(): array
    {
        return [
            self::EVENT_FIELD_MOUNT,
            self::EVENT_OVERLAY_OPEN,
            self::EVENT_SELECT_SEARCH,
            self::EVENT_UPLOAD_FAIL,
            self::EVENT_UPLOAD_SUCCESS,
            self::EVENT_RETENTION_PRUNE,
            self::EVENT_BARCODE_CAPTURE,
        ];
    }

    public static function clear(): void
    {
        self::$listeners = [];
    }
}

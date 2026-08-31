<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureQuarantine;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class MediaCaptureOs implements MediaStorageContract
{
    /** @var (callable(string): bool|null)|null */
    private static $virusScanCallback = null;

    /** @var (callable(): string|null)|null */
    private static $legalSignerIdResolver = null;

    /** @var (callable(): string|null)|null */
    private static $documentHashResolver = null;

    /** @var (callable(string, string, array<string, mixed>): string|null)|null */
    private static $signedUploadUrlResolver = null;

    /** @var (callable(string): string|null)|null */
    private static $tokenizeCreditCardCallback = null;

    private static ?VoiceNoteTranscriptionInterface $transcriptionInterface = null;

    /**
     * @var array{
     *     uploads: array{enabled: bool, days: int|null},
     *     voice_notes: array{enabled: bool, days: int|null},
     *     signatures: array{enabled: bool, days: int|null},
     *     temp_captures: array{enabled: bool, days: int|null},
     * }|null
     */
    private static ?array $retentionPolicies = null;

    /**
     * @param  (callable(string $path): bool|null)|null  $callback
     */
    public static function registerVirusScanCallback(?callable $callback): void
    {
        self::$virusScanCallback = $callback;
    }

    /**
     * @return (callable(string $path): bool|null)|null
     */
    public static function virusScanCallback(): ?callable
    {
        return self::$virusScanCallback;
    }

    /**
     * @param  (callable(string $disk, string $path, array<string, mixed> $context): string|null)|null  $resolver
     */
    public static function registerSignedUploadUrlResolver(?callable $resolver): void
    {
        self::$signedUploadUrlResolver = $resolver;
    }

    /**
     * @return (callable(string $disk, string $path, array<string, mixed> $context): string|null)|null
     */
    public static function signedUploadUrlResolver(): ?callable
    {
        return self::$signedUploadUrlResolver;
    }

    /**
     * Tokenize primary account numbers via host-app callback — Flex Fields never persists raw PAN.
     *
     * @param  (callable(string $pan): string|null)|null  $callback
     */
    public static function registerTokenizeCreditCardCallback(?callable $callback): void
    {
        self::$tokenizeCreditCardCallback = $callback;
    }

    /**
     * @return (callable(string $pan): string|null)|null
     */
    public static function tokenizeCreditCardCallback(): ?callable
    {
        return self::$tokenizeCreditCardCallback;
    }

    public static function registerTranscriptionInterface(?VoiceNoteTranscriptionInterface $interface): void
    {
        self::$transcriptionInterface = $interface;
    }

    public static function transcriptionInterface(): ?VoiceNoteTranscriptionInterface
    {
        return self::$transcriptionInterface;
    }

    /**
     * Boot permissive dev defaults when virus scan is not required.
     * When {@see shouldRequireVirusScan()} is true, no callback is registered — uploads fail closed.
     */
    public static function bootSafeDefaults(): void
    {
        if (self::$virusScanCallback === null && ! self::shouldRequireVirusScan()) {
            self::registerVirusScanCallback(static fn (string $path): bool => true);
        }
    }

    public static function shouldRequireVirusScan(): bool
    {
        return (bool) config('filament-flex-fields.media_capture.require_virus_scan', false);
    }

    public static function shouldScanBeforePersist(): bool
    {
        return (bool) config('filament-flex-fields.media_capture.scan_before_persist', true);
    }

    public static function hasRegisteredVirusScanner(): bool
    {
        return self::virusScanCallback() !== null;
    }

    /**
     * @param  (callable(): string|null)|null  $resolver
     */
    public static function registerLegalSignerIdResolver(?callable $resolver): void
    {
        self::$legalSignerIdResolver = $resolver;
    }

    /**
     * @param  (callable(): string|null)|null  $resolver
     */
    public static function registerDocumentHashResolver(?callable $resolver): void
    {
        self::$documentHashResolver = $resolver;
    }

    public static function resolveLegalSignerId(): ?string
    {
        if (self::$legalSignerIdResolver !== null) {
            $resolved = (self::$legalSignerIdResolver)();

            if (is_string($resolved) && filled($resolved)) {
                return $resolved;
            }
        }

        $user = auth()->user();

        if ($user !== null) {
            $id = $user->getAuthIdentifier();

            return is_scalar($id) ? (string) $id : null;
        }

        return null;
    }

    public static function resolveDocumentHash(): ?string
    {
        if (self::$documentHashResolver === null) {
            return null;
        }

        $resolved = (self::$documentHashResolver)();

        return is_string($resolved) && filled($resolved) ? $resolved : null;
    }

    public static function passesVirusScanForTemporaryFile(TemporaryUploadedFile $file): bool
    {
        if (! self::shouldScanBeforePersist()) {
            return true;
        }

        try {
            if (! $file->exists()) {
                return ! self::shouldRequireVirusScan();
            }
        } catch (\Throwable) {
            return ! self::shouldRequireVirusScan();
        }

        $path = rescue(fn (): string => $file->getRealPath() ?: $file->path(), '', report: false);

        if ($path === '') {
            return ! self::shouldRequireVirusScan();
        }

        return self::passesVirusScan($path);
    }

    /**
     * Move or delete a rejected stored file. Returns quarantine path when configured.
     */
    public static function rejectStoredFile(string $diskName, string $storedPath): ?string
    {
        if (MediaCaptureQuarantine::isEnabled()) {
            return MediaCaptureQuarantine::quarantineFromDisk($diskName, $storedPath);
        }

        rescue(fn () => \Illuminate\Support\Facades\Storage::disk($diskName)->delete($storedPath), report: false);

        return null;
    }

    /**
     * Boot Media & Capture OS from package config (retention, transcription binding).
     *
     * @param  array<string, mixed>|null  $config
     */
    public static function bootFromConfig(?array $config = null): void
    {
        self::bootSafeDefaults();

        $config ??= config('filament-flex-fields.media_capture', []);

        if (! is_array($config)) {
            return;
        }

        if (isset($config['retention']) && is_array($config['retention'])) {
            self::$retentionPolicies = array_replace_recursive(
                self::retentionPolicyDefaults(),
                $config['retention'],
            );
        }

        self::bootSignedUrlResolverFromConfig($config);
        self::bootTranscriptionFromConfig($config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function bootSignedUrlResolverFromConfig(array $config): void
    {
        if (self::$signedUploadUrlResolver !== null) {
            return;
        }

        if (($config['auto_signed_urls'] ?? true) !== true) {
            return;
        }

        self::registerSignedUploadUrlResolver(new LaravelStorageSignedUrlResolver);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function bootTranscriptionFromConfig(array $config): void
    {
        if (self::$transcriptionInterface !== null) {
            return;
        }

        $transcriptionClass = $config['transcription'] ?? null;

        if (is_string($transcriptionClass) && filled($transcriptionClass) && class_exists($transcriptionClass)) {
            $instance = app($transcriptionClass);

            if ($instance instanceof VoiceNoteTranscriptionInterface) {
                self::registerTranscriptionInterface(self::wrapTranscriptionWithCircuitBreaker($instance, $config));

                return;
            }
        }

        self::registerTranscriptionInterface(
            self::wrapTranscriptionWithCircuitBreaker(new NullVoiceNoteTranscription, $config),
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function wrapTranscriptionWithCircuitBreaker(
        VoiceNoteTranscriptionInterface $provider,
        array $config,
    ): VoiceNoteTranscriptionInterface {
        $cb = $config['transcription_circuit_breaker'] ?? [];

        if (! is_array($cb) || ($cb['enabled'] ?? true) !== true) {
            return $provider;
        }

        return new CircuitBreakerTranscriptionProvider(
            $provider,
            (int) ($cb['failure_threshold'] ?? 3),
            (int) ($cb['open_seconds'] ?? 30),
        );
    }

    /**
     * Record a barcode capture event for observability / SIEM bridges.
     *
     * @param  array<string, mixed>  $context
     */
    public static function recordBarcodeCapture(BarcodeValue $barcode, string $field, array $context = []): void
    {
        \Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks::record(
            \Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks::EVENT_BARCODE_CAPTURE,
            array_merge([
                'field' => $field,
                'value' => $barcode->value,
                'format' => $barcode->format,
            ], $context),
        );
    }

    /**
     * @return array{
     *     uploads: array{enabled: bool, days: int|null},
     *     voice_notes: array{enabled: bool, days: int|null},
     *     signatures: array{enabled: bool, days: int|null},
     *     temp_captures: array{enabled: bool, days: int|null},
     * }
     */
    public static function retentionPolicies(): array
    {
        return self::$retentionPolicies ?? self::retentionPolicyDefaults();
    }

    public static function passesVirusScan(string $path): bool
    {
        $virusScan = self::virusScanCallback();

        if ($virusScan === null) {
            return ! self::shouldRequireVirusScan();
        }

        return $virusScan($path) !== false;
    }

    public static function shouldRequireCreditCardTokenization(): bool
    {
        return (bool) config('filament-flex-fields.media_capture.pci.require_tokenization', false);
    }

    public static function shouldNeverStorePan(): bool
    {
        return (bool) config('filament-flex-fields.media_capture.pci.never_store_pan', true);
    }

    /**
     * Attempt PCI tokenization for validation/dehydrate paths.
     */
    public static function tokenizePrimaryAccountNumber(string $pan): ?string
    {
        $tokenize = self::tokenizeCreditCardCallback();

        if ($tokenize === null || $pan === '') {
            return null;
        }

        $token = $tokenize($pan);

        return is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * Default media retention posture for capture fields (host apps may override centrally).
     *
     * @return array{
     *     uploads: array{enabled: bool, days: int|null},
     *     voice_notes: array{enabled: bool, days: int|null},
     *     signatures: array{enabled: bool, days: int|null},
     *     temp_captures: array{enabled: bool, days: int|null},
     * }
     */
    public static function retentionPolicyDefaults(): array
    {
        return [
            'uploads' => [
                'enabled' => false,
                'days' => null,
            ],
            'voice_notes' => [
                'enabled' => false,
                'days' => 365,
            ],
            'signatures' => [
                'enabled' => false,
                'days' => null,
            ],
            'temp_captures' => [
                'enabled' => true,
                'days' => 7,
            ],
        ];
    }

    /**
     * Reset runtime overrides (tests).
     */
    public static function resetRuntimeState(): void
    {
        self::$virusScanCallback = null;
        self::$signedUploadUrlResolver = null;
        self::$tokenizeCreditCardCallback = null;
        self::$transcriptionInterface = null;
        self::$retentionPolicies = null;
        self::$legalSignerIdResolver = null;
        self::$documentHashResolver = null;
    }
}

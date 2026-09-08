<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline;

use Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureQuarantine;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Drivers\DiskMediaDriver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Drivers\MediaDriver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Drivers\SpatieMediaDriver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\DiskMediaRef;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\MediaRef;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\SpatieMediaRef;
use Closure;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Shared media ingress control plane: normalize → pre-AV → driver → post-AV/quarantine → observability.
 */
final class MediaIngress
{
    public function __construct(
        private readonly MediaDriver $diskDriver = new DiskMediaDriver,
        private readonly MediaDriver $spatieDriver = new SpatieMediaDriver,
    ) {}

    /**
     * @param  (Closure(MediaRef, MediaContext): MediaRef|null)|null  $afterPersist
     */
    public function persist(
        MediaPayload $payload,
        MediaContext $context,
        ?Closure $afterPersist = null,
    ): ?MediaRef {
        $context = $context->correlationId !== null
            ? $context
            : $context->withCorrelationId((string) Str::uuid());

        if (! $this->passesPrePersistScan($payload, $context)) {
            return null;
        }

        try {
            $ref = $this->driverFor($context)->persist($payload, $context);
        } catch (RuntimeException $exception) {
            ObservabilityHooks::record(ObservabilityHooks::EVENT_UPLOAD_FAIL, [
                'field' => $context->field,
                'reason' => 'spatie_record_missing',
                'message' => $exception->getMessage(),
                'adapter' => $context->driver->value,
                'kind' => $context->kind->value,
                'correlation_id' => $context->correlationId,
            ]);

            throw $exception;
        } catch (Throwable $throwable) {
            ObservabilityHooks::record(ObservabilityHooks::EVENT_UPLOAD_FAIL, [
                'field' => $context->field,
                'reason' => 'persist_failed',
                'message' => $throwable->getMessage(),
                'adapter' => $context->driver->value,
                'kind' => $context->kind->value,
                'correlation_id' => $context->correlationId,
            ]);

            return null;
        }

        if ($ref === null) {
            ObservabilityHooks::record(ObservabilityHooks::EVENT_UPLOAD_FAIL, [
                'field' => $context->field,
                'reason' => 'persist_failed',
                'adapter' => $context->driver->value,
                'kind' => $context->kind->value,
                'correlation_id' => $context->correlationId,
            ]);

            return null;
        }

        if ($afterPersist !== null) {
            $mutated = $afterPersist($ref, $context);

            if ($mutated instanceof MediaRef) {
                $ref = $mutated;
            }
        }

        if (! $this->passesPostPersistScan($ref, $context)) {
            $this->reject($ref, $context);

            return null;
        }

        ObservabilityHooks::record(ObservabilityHooks::EVENT_UPLOAD_SUCCESS, [
            'field' => $context->field,
            'adapter' => $context->driver->value,
            'kind' => $context->kind->value,
            'correlation_id' => $context->correlationId,
            'path' => $ref->relativePath(),
            'uuid' => $ref->mediaUuid(),
        ]);

        return $ref;
    }

    /**
     * Run post-persist AV + quarantine for fields that persist outside the drivers
     * (e.g. Filament FileUpload save + image processing).
     */
    public function assertCleanStored(MediaRef $ref, MediaContext $context): bool
    {
        $context = $context->correlationId !== null
            ? $context
            : $context->withCorrelationId((string) Str::uuid());

        if ($this->passesPostPersistScan($ref, $context)) {
            ObservabilityHooks::record(ObservabilityHooks::EVENT_UPLOAD_SUCCESS, [
                'field' => $context->field,
                'adapter' => $context->driver->value,
                'kind' => $context->kind->value,
                'correlation_id' => $context->correlationId,
                'path' => $ref->relativePath(),
                'uuid' => $ref->mediaUuid(),
            ]);

            return true;
        }

        $this->reject($ref, $context);

        return false;
    }

    public function passesPrePersistScan(MediaPayload $payload, MediaContext $context): bool
    {
        if (! MediaCaptureOs::shouldScanBeforePersist()) {
            return true;
        }

        $path = $payload->scanPath();

        if ($path === null) {
            // No local path (stream/inline) — fail closed only when virus scan is required.
            if (MediaCaptureOs::shouldRequireVirusScan() && ! MediaCaptureOs::hasRegisteredVirusScanner()) {
                $this->recordVirusFail($context, 'pre_persist');

                return false;
            }

            // When a scanner exists but we only have a stream, materialize a temp file for scanning.
            if (MediaCaptureOs::hasRegisteredVirusScanner() || MediaCaptureOs::shouldRequireVirusScan()) {
                $temp = $this->materializeTempForScan($payload);

                if ($temp === null) {
                    $this->recordVirusFail($context, 'pre_persist');

                    return false;
                }

                try {
                    if (! MediaCaptureOs::passesVirusScan($temp)) {
                        $this->recordVirusFail($context, 'pre_persist');

                        return false;
                    }
                } finally {
                    @unlink($temp);
                }

                return true;
            }

            return true;
        }

        if (! MediaCaptureOs::passesVirusScan($path)) {
            $this->recordVirusFail($context, 'pre_persist');

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function resolveSignedUrl(string $disk, string $path, array $context = []): ?string
    {
        $resolver = MediaCaptureOs::signedUploadUrlResolver();

        if ($resolver === null) {
            return null;
        }

        $signed = $resolver($disk, $path, $context);

        return is_string($signed) && filled($signed) ? $signed : null;
    }

    public function reject(MediaRef $ref, MediaContext $context): void
    {
        if ($ref instanceof SpatieMediaRef) {
            $media = $ref->media();
            MediaCaptureQuarantine::quarantineSpatieMedia($media);

            if (method_exists($media, 'delete')) {
                rescue(fn () => $media->delete(), report: false);
            }

            return;
        }

        if ($ref instanceof DiskMediaRef) {
            MediaCaptureOs::rejectStoredFile($ref->disk, $ref->path);
        }
    }

    private function passesPostPersistScan(MediaRef $ref, MediaContext $context): bool
    {
        $scanPath = null;

        if ($ref instanceof SpatieMediaRef) {
            $media = $ref->media();

            if (method_exists($media, 'getPath')) {
                $scanPath = (string) $media->getPath();
            }
        } elseif ($ref->relativePath() !== null && $ref->diskName() !== null) {
            $absolute = rescue(
                function () use ($ref): string {
                    $disk = $ref->diskName();
                    $path = $ref->relativePath();

                    if ($disk === null || $path === null) {
                        return '';
                    }

                    return Storage::disk($disk)->path($path);
                },
                '',
                report: false,
            );
            $scanPath = is_string($absolute) && $absolute !== '' ? $absolute : $ref->relativePath();
        }

        if ($scanPath === null || $scanPath === '') {
            return ! MediaCaptureOs::shouldRequireVirusScan();
        }

        if (MediaCaptureOs::passesVirusScan($scanPath)) {
            return true;
        }

        $this->recordVirusFail($context, 'post_persist', [
            'path' => $ref->relativePath(),
            'uuid' => $ref->mediaUuid(),
        ]);

        return false;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function recordVirusFail(MediaContext $context, string $stage, array $extra = []): void
    {
        ObservabilityHooks::record(ObservabilityHooks::EVENT_UPLOAD_FAIL, array_merge([
            'field' => $context->field,
            'reason' => 'virus_scan',
            'stage' => $stage,
            'adapter' => $context->driver->value,
            'kind' => $context->kind->value,
            'correlation_id' => $context->correlationId,
        ], $extra));
    }

    private function driverFor(MediaContext $context): MediaDriver
    {
        return match ($context->driver) {
            MediaStorageDriver::Disk => $this->diskDriver,
            MediaStorageDriver::Spatie => $this->spatieDriver,
        };
    }

    private function materializeTempForScan(MediaPayload $payload): ?string
    {
        try {
            $stream = $payload->openReadableStream();
            $temp = tempnam(sys_get_temp_dir(), 'fff-av-');

            if ($temp === false) {
                return null;
            }

            $out = fopen($temp, 'wb');

            if (! is_resource($out)) {
                return null;
            }

            stream_copy_to_stream($stream, $out);
            fclose($out);

            if (is_resource($stream)) {
                fclose($stream);
            }

            return $temp;
        } catch (Throwable) {
            return null;
        }
    }
}

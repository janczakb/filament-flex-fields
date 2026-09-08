<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\FlexFileUpload\FlexFileUploadStorage;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Drivers\SpatieMediaDriver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaContext;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaKind;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaPayload;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaStorageDriver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\SpatieMediaRef;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

/**
 * @deprecated Public adapter kept for BC. New code should use {@see MediaIngress} / {@see FlexMedia}.
 * Internally delegates to stream-safe {@see SpatieMediaDriver}.
 */
final class SpatieMediaCaptureAdapter
{
    /**
     * Persist an upload through Spatie Media Library via Media Ingress (stream/path safe).
     */
    public static function saveUploadedFile(
        FlexSpatieMediaLibraryFileUpload $component,
        TemporaryUploadedFile $file,
        ?Model $record = null,
    ): ?string {
        $record ??= $component->getRecord();

        if (! $record instanceof Model) {
            return null;
        }

        try {
            if (! $file->exists()) {
                return null;
            }
        } catch (UnableToCheckFileExistence) {
            return null;
        }

        $payload = MediaPayload::fromTemporaryUploadedFile($file);

        // Test doubles / remote temps without a local path: fall back to stream from get() only
        // when no local path exists (never the preferred enterprise path).
        if (! $payload->hasLocalPath()) {
            $contents = rescue(fn (): string => (string) $file->get(), '', report: false);

            if ($contents === '') {
                return null;
            }

            $payload = MediaPayload::fromInlineString(
                $contents,
                rescue(fn (): string => (string) $file->getClientOriginalName(), 'upload.bin', report: false),
            );
        }

        $context = new MediaContext(
            kind: MediaKind::Upload,
            driver: MediaStorageDriver::Spatie,
            field: $component->getName(),
            record: $record,
            collection: $component->getCollection() ?? 'default',
            disk: $component->getDiskName(),
            filename: $component->getUploadedFileNameForStorage($file),
            mediaName: $component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            conversionsDisk: $component->getConversionsDisk(),
            customProperties: array_merge(
                $component->getCustomProperties(),
                self::enterpriseCustomProperties($component),
            ),
            manipulations: $component->getManipulations(),
            properties: $component->getProperties(),
            customHeaders: $component->getCustomHeaders(),
            responsiveImages: $component->hasResponsiveImages(),
        );

        try {
            $ref = FlexMedia::ingress()->persist($payload, $context);
        } catch (RuntimeException) {
            return null;
        }

        return $ref instanceof SpatieMediaRef ? $ref->uuid : null;
    }

    /**
     * Resolve a Spatie media UUID to the Filament upload payload, with optional signed URLs.
     *
     * @return array{name: string, size: int|string|null, type: string|null, url: string|null}|null
     */
    public static function resolveUploadedFilePayload(
        FlexSpatieMediaLibraryFileUpload $component,
        string $file,
    ): ?array {
        if (! $component->getRecord()) {
            return null;
        }

        $media = self::findMediaByUuid($component, $file);

        if ($media === null) {
            return null;
        }

        $url = null;

        if ($component->getVisibility() === 'private') {
            $conversion = $component->getConversion();

            try {
                $url = $media->getTemporaryUrl(
                    now()->addMinutes(5),
                    (filled($conversion) && $media->hasGeneratedConversion($conversion)) ? $conversion : '',
                );
            } catch (Throwable) {
                // Driver does not support temporary URLs.
            }
        }

        $conversion = $component->getConversion();

        if ($conversion && $media->hasGeneratedConversion($conversion)) {
            $url ??= $media->getUrl($conversion);
        }

        $url ??= $media->getUrl();

        if ($component->getVisibility() !== 'private') {
            $signedUrl = self::resolveSignedUrlForMedia($component, $media);

            if ($signedUrl !== null) {
                $url = $signedUrl;
            }
        }

        return [
            'name' => $media->getAttributeValue('name') ?? $media->getAttributeValue('file_name'),
            'size' => $media->getAttributeValue('size'),
            'type' => $media->getAttributeValue('mime_type'),
            'url' => $url,
        ];
    }

    public static function findMediaByUuid(FlexSpatieMediaLibraryFileUpload $component, string $uuid): ?object
    {
        $record = $component->getRecord();

        if (! $record instanceof Model) {
            return null;
        }

        $relation = $record->getRelationValue('media');

        if ($relation !== null) {
            $match = $relation->firstWhere('uuid', $uuid);

            if ($match !== null) {
                return $match;
            }
        }

        $mediaClass = 'Spatie\\MediaLibrary\\MediaCollections\\Models\\Media';

        if (! class_exists($mediaClass)) {
            return null;
        }

        return $mediaClass::query()->where('uuid', $uuid)->first();
    }

    /**
     * @return array<string, mixed>
     */
    public static function enterpriseCustomProperties(FlexSpatieMediaLibraryFileUpload $component): array
    {
        return [
            'flex_capture' => [
                'field' => $component->getName(),
                'collection' => $component->getCollection() ?? 'default',
            ],
        ];
    }

    /**
     * @param  list<string>  $directories
     * @return list<string>
     */
    public static function pruneDiskDirectories(
        string $diskName,
        array $directories,
        int $maxAgeDays,
        bool $dryRun = false,
    ): array {
        $disk = Storage::disk($diskName);
        $cutoff = Carbon::now()->subDays($maxAgeDays);
        $deleted = [];

        foreach ($directories as $directory) {
            $directory = trim($directory, '/');

            if ($directory === '') {
                continue;
            }

            $files = rescue(fn (): array => $disk->allFiles($directory), [], report: false);

            foreach ($files as $path) {
                $modified = rescue(fn (): int => (int) $disk->lastModified($path), 0, report: false);

                if ($modified <= 0 || Carbon::createFromTimestamp($modified)->greaterThan($cutoff)) {
                    continue;
                }

                if (! $dryRun) {
                    rescue(fn () => $disk->delete($path), report: false);
                }

                $deleted[] = $path;
            }
        }

        return $deleted;
    }

    /**
     * @return list<string>
     */
    public static function pruneSpatieMedia(
        int $maxAgeDays,
        ?string $collection = null,
        bool $dryRun = false,
        ?string $kind = null,
    ): array {
        $mediaClass = 'Spatie\\MediaLibrary\\MediaCollections\\Models\\Media';

        if (! class_exists($mediaClass)) {
            return [];
        }

        $cutoff = Carbon::now()->subDays($maxAgeDays);

        $query = $mediaClass::query()
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('custom_properties->flex_capture');

        if (filled($collection)) {
            $query->where('collection_name', $collection);
        }

        if (filled($kind)) {
            $query->where('custom_properties->flex_capture->kind', $kind);
        }

        $deleted = [];

        $query->each(function (object $media) use (&$deleted, $dryRun): void {
            if (! self::hasFlexCaptureStamp($media)) {
                return;
            }

            $uuid = (string) $media->getAttributeValue('uuid');
            $deleted[] = $uuid;

            if (! $dryRun) {
                rescue(fn () => $media->delete(), report: false);
            }
        });

        return $deleted;
    }

    public static function hasFlexCaptureStamp(object $media): bool
    {
        $properties = $media->getAttributeValue('custom_properties');

        if (is_string($properties)) {
            $properties = json_decode($properties, true);
        }

        if (! is_array($properties)) {
            return false;
        }

        $stamp = $properties['flex_capture'] ?? null;

        return is_array($stamp) && filled($stamp['field'] ?? null);
    }

    private static function resolveSignedUrlForMedia(FlexSpatieMediaLibraryFileUpload $component, object $media): ?string
    {
        if (! method_exists($media, 'getPathRelativeToRoot')) {
            return null;
        }

        /** @var FlexFileUploadStorage $component */
        return FlexMedia::signedUrl($component->getDiskName(), (string) $media->getPathRelativeToRoot(), [
            'visibility' => $component->getVisibility(),
            'field' => $component->getName(),
        ]) ?? $component->resolveMediaCaptureSignedUrl((string) $media->getPathRelativeToRoot());
    }
}

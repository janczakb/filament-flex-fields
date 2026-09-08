<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Support\Media\FlexMedia;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureTenantDiskResolver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaContext;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaKind;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaPayload;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaStorageDriver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\DiskMediaRef;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * @deprecated Prefer SignatureField::disk() / Media Ingress disk driver. Kept as internal store behind SignatureField.
 */
final class SignatureStorage
{
    public const string TOKEN_PREFIX = 'ffstage:';

    public static function store(string $svg, string $directory, ?string $diskName = null): string
    {
        $svg = trim($svg);

        if ($svg === '' || ! str_contains($svg, '<svg') || ! SignatureSvg::isValid($svg)) {
            throw new InvalidArgumentException('Invalid signature SVG.');
        }

        $svg = SignatureSvg::normalize($svg) ?? $svg;

        $context = new MediaContext(
            kind: MediaKind::Signature,
            driver: MediaStorageDriver::Disk,
            field: 'signature',
            disk: self::resolveDiskName($diskName),
            directory: trim($directory, '/'),
            filename: null,
            mediaName: 'signature',
        );

        $payload = MediaPayload::fromInlineString($svg, 'signature.svg', 'image/svg+xml');
        $ref = FlexMedia::persist($payload, $context);

        if (! $ref instanceof DiskMediaRef) {
            throw new InvalidArgumentException('Unable to persist signature SVG via Media Ingress.');
        }

        return self::TOKEN_PREFIX.$ref->path;
    }

    /**
     * Optional Spatie sink — copies SVG bytes into Media Library. Does not change SignatureField state.
     */
    public static function sinkToSpatie(
        string $svg,
        Model $record,
        string $collection = 'signatures',
        ?string $diskName = null,
        ?string $field = 'signature',
    ): ?string {
        $svg = trim($svg);

        if ($svg === '' || ! str_contains($svg, '<svg') || ! SignatureSvg::isValid($svg)) {
            return null;
        }

        $svg = SignatureSvg::normalize($svg) ?? $svg;

        $context = new MediaContext(
            kind: MediaKind::Signature,
            driver: MediaStorageDriver::Spatie,
            field: $field,
            record: $record,
            collection: $collection,
            disk: $diskName,
            filename: 'signature.svg',
            mediaName: 'signature',
            customProperties: [
                'flex_capture' => [
                    'field' => $field,
                    'collection' => $collection,
                    'kind' => MediaKind::Signature->value,
                    'sink' => true,
                ],
            ],
        );

        $payload = MediaPayload::fromInlineString($svg, 'signature.svg', 'image/svg+xml');
        $ref = FlexMedia::persist($payload, $context);

        return $ref?->mediaUuid();
    }

    public static function resolve(?string $state, ?string $diskName = null): ?string
    {
        if (! is_string($state) || trim($state) === '') {
            return null;
        }

        if (str_contains($state, '<svg')) {
            return $state;
        }

        $relativePath = self::relativePath($state);

        if ($relativePath === null) {
            return null;
        }

        $disk = Storage::disk(self::resolveDiskName($diskName));

        if (! $disk->exists($relativePath)) {
            return null;
        }

        $svg = $disk->get($relativePath);

        return is_string($svg) && str_contains($svg, '<svg') ? $svg : null;
    }

    public static function relativePath(string $state): ?string
    {
        if (str_starts_with($state, self::TOKEN_PREFIX)) {
            return substr($state, strlen(self::TOKEN_PREFIX));
        }

        if (str_ends_with(strtolower($state), '.svg') && ! str_contains($state, '<')) {
            return ltrim($state, '/');
        }

        return null;
    }

    private static function resolveDiskName(?string $diskName): string
    {
        if (is_string($diskName) && $diskName !== '') {
            return $diskName;
        }

        return MediaCaptureTenantDiskResolver::resolveDisk(null, [
            'adapter' => 'signature_storage',
            'kind' => MediaKind::Signature->value,
        ]);
    }
}

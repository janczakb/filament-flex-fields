<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class MediaCaptureQuarantine
{
    public static function quarantineFromDisk(string $sourceDisk, string $path): ?string
    {
        $quarantineDisk = self::diskName();

        if ($quarantineDisk === null) {
            return null;
        }

        $normalizedPath = ltrim($path, '/');
        $destination = self::destinationPath(basename($normalizedPath));

        $source = Storage::disk($sourceDisk);
        $target = Storage::disk($quarantineDisk);

        if (! $source->exists($normalizedPath)) {
            return null;
        }

        try {
            $read = $source->readStream($normalizedPath);

            if (! is_resource($read)) {
                return null;
            }

            try {
                $written = $target->writeStream($destination, $read, ['visibility' => 'private']);
            } finally {
                if (is_resource($read)) {
                    fclose($read);
                }
            }

            if ($written === false && ! $target->exists($destination)) {
                return null;
            }

            $source->delete($normalizedPath);

            return $destination;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Quarantine a local absolute path (e.g. Spatie media getPath()) without full-buffer get/put.
     */
    public static function quarantineLocalPath(string $absolutePath): ?string
    {
        $quarantineDisk = self::diskName();

        if ($quarantineDisk === null || ! is_file($absolutePath)) {
            return null;
        }

        $destination = self::destinationPath(basename($absolutePath));
        $target = Storage::disk($quarantineDisk);
        $read = fopen($absolutePath, 'rb');

        if (! is_resource($read)) {
            return null;
        }

        try {
            $written = $target->writeStream($destination, $read, ['visibility' => 'private']);
        } finally {
            if (is_resource($read)) {
                fclose($read);
            }
        }

        if ($written === false && ! $target->exists($destination)) {
            return null;
        }

        @unlink($absolutePath);

        return $destination;
    }

    /**
     * Spatie quarantine parity: copy media bytes to quarantine, then caller deletes the Media row.
     */
    public static function quarantineSpatieMedia(?object $media): ?string
    {
        if ($media === null) {
            return null;
        }

        if (method_exists($media, 'getPath')) {
            $absolute = (string) $media->getPath();

            if ($absolute !== '' && is_file($absolute)) {
                return self::quarantineLocalPath($absolute);
            }
        }

        if (method_exists($media, 'getPathRelativeToRoot') && method_exists($media, 'disk')) {
            $disk = (string) $media->disk;
            $path = (string) $media->getPathRelativeToRoot();

            if ($disk !== '' && $path !== '') {
                return self::quarantineFromDisk($disk, $path);
            }
        }

        $diskAttr = method_exists($media, 'getAttributeValue')
            ? $media->getAttributeValue('disk')
            : (property_exists($media, 'disk') ? $media->disk : null);
        $path = method_exists($media, 'getPathRelativeToRoot')
            ? (string) $media->getPathRelativeToRoot()
            : null;

        if (is_string($diskAttr) && filled($diskAttr) && is_string($path) && $path !== '') {
            return self::quarantineFromDisk($diskAttr, $path);
        }

        return null;
    }

    public static function diskName(): ?string
    {
        $disk = config('filament-flex-fields.media_capture.quarantine_disk');

        return is_string($disk) && filled($disk) ? $disk : null;
    }

    public static function isEnabled(): bool
    {
        return self::diskName() !== null;
    }

    private static function destinationPath(string $basename): string
    {
        return 'quarantine/'.now()->format('Y/m/d').'/'.Str::uuid()->toString().'-'.$basename;
    }
}

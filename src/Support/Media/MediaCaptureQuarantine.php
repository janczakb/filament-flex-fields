<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MediaCaptureQuarantine
{
    public static function quarantineFromDisk(string $sourceDisk, string $path): ?string
    {
        $quarantineDisk = self::diskName();

        if ($quarantineDisk === null) {
            return null;
        }

        $normalizedPath = ltrim($path, '/');
        $destination = 'quarantine/'.now()->format('Y/m/d').'/'.Str::uuid()->toString().'-'.basename($normalizedPath);

        $source = Storage::disk($sourceDisk);
        $target = Storage::disk($quarantineDisk);

        if (! $source->exists($normalizedPath)) {
            return null;
        }

        $contents = $source->get($normalizedPath);

        if (! is_string($contents)) {
            return null;
        }

        $target->put($destination, $contents, ['visibility' => 'private']);
        $source->delete($normalizedPath);

        return $destination;
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
}

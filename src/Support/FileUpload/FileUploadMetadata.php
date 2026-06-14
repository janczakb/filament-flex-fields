<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FileUpload;

use Illuminate\Contracts\Filesystem\Filesystem;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileUploadMetadata
{
    /**
     * @return array{
     *     original_name: string,
     *     mime: string|null,
     *     size_kb: float,
     *     width: int|null,
     *     height: int|null,
     * }
     */
    public static function fromTemporaryFile(TemporaryUploadedFile $file): array
    {
        $mime = $file->getMimeType();
        $sizeKb = round($file->getSize() / 1024, 2);
        $width = null;
        $height = null;

        if (is_string($mime) && str_starts_with($mime, 'image/')) {
            [$width, $height] = self::readImageDimensions($file->getRealPath());
        }

        return [
            'original_name' => $file->getClientOriginalName(),
            'mime' => $mime,
            'size_kb' => $sizeKb,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @return array{
     *     original_name: string,
     *     mime: string|null,
     *     size_kb: float,
     *     width: int|null,
     *     height: int|null,
     * }
     */
    public static function fromDiskPath(Filesystem $disk, string $path, ?string $originalName = null): array
    {
        $mime = rescue(fn (): ?string => $disk->mimeType($path), report: false);
        $sizeKb = round(((int) rescue(fn (): int => $disk->size($path), 0, report: false)) / 1024, 2);
        $width = null;
        $height = null;

        if (is_string($mime) && str_starts_with($mime, 'image/')) {
            $absolutePath = method_exists($disk, 'path') ? $disk->path($path) : null;

            if (is_string($absolutePath) && is_file($absolutePath)) {
                [$width, $height] = self::readImageDimensions($absolutePath);
            }
        }

        return [
            'original_name' => $originalName ?? basename($path),
            'mime' => $mime,
            'size_kb' => $sizeKb,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    protected static function readImageDimensions(?string $path): array
    {
        if (! is_string($path) || ! is_file($path)) {
            return [null, null];
        }

        $size = @getimagesize($path);

        if (! is_array($size)) {
            return [null, null];
        }

        return [
            isset($size[0]) ? (int) $size[0] : null,
            isset($size[1]) ? (int) $size[1] : null,
        ];
    }
}

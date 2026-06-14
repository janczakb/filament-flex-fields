<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FileUpload;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class FileUploadImageProcessor
{
    public function __construct(
        protected bool $optimizeImages = true,
        protected bool $optimizeImagesToWebp = false,
        protected ?int $maxImageWidth = null,
        protected ?int $maxImageHeight = null,
        protected bool $stripExif = true,
    ) {}

    public function process(Filesystem $disk, string $path): string
    {
        $absolutePath = method_exists($disk, 'path') ? $disk->path($path) : null;

        if (! is_string($absolutePath) || ! is_file($absolutePath)) {
            return $path;
        }

        $mime = @mime_content_type($absolutePath);

        if (! is_string($mime) || ! str_starts_with($mime, 'image/')) {
            return $path;
        }

        if (class_exists(ImageManager::class)) {
            return $this->processWithIntervention($disk, $path, $absolutePath);
        }

        return $this->processWithGd($disk, $path, $absolutePath, $mime);
    }

    protected function processWithIntervention(Filesystem $disk, string $path, string $absolutePath): string
    {
        /** @var class-string $managerClass */
        $managerClass = ImageManager::class;
        $manager = new $managerClass;
        $image = $manager->read($absolutePath);

        if ($this->maxImageWidth || $this->maxImageHeight) {
            $image->scale(
                width: $this->maxImageWidth,
                height: $this->maxImageHeight,
            );
        }

        if ($this->optimizeImagesToWebp && $this->supportsWebp()) {
            $newPath = $this->replaceExtension($path, 'webp');
            $newAbsolutePath = method_exists($disk, 'path') ? $disk->path($newPath) : null;

            if (is_string($newAbsolutePath)) {
                $image->toWebp(quality: 85)->save($newAbsolutePath);

                if ($newPath !== $path) {
                    $disk->delete($path);
                }

                return $newPath;
            }
        }

        $image->save($absolutePath);

        return $path;
    }

    protected function processWithGd(Filesystem $disk, string $path, string $absolutePath, string $mime): string
    {
        $resource = $this->createImageResource($absolutePath, $mime);

        if ($resource === null) {
            return $path;
        }

        $width = imagesx($resource);
        $height = imagesy($resource);

        [$targetWidth, $targetHeight] = $this->resolveTargetDimensions($width, $height);

        if ($targetWidth !== $width || $targetHeight !== $height) {
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);

            if ($resized !== false) {
                imagecopyresampled($resized, $resource, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
                imagedestroy($resource);
                $resource = $resized;
            }
        }

        if ($this->optimizeImagesToWebp && $this->supportsWebp()) {
            $newPath = $this->replaceExtension($path, 'webp');
            $newAbsolutePath = method_exists($disk, 'path') ? $disk->path($newPath) : null;

            if (is_string($newAbsolutePath)) {
                imagewebp($resource, $newAbsolutePath, 85);
                imagedestroy($resource);

                if ($newPath !== $path) {
                    $disk->delete($path);
                }

                return $newPath;
            }
        }

        $this->saveGdResource($resource, $absolutePath, $mime);
        imagedestroy($resource);

        return $path;
    }

    /**
     * @return resource|null
     */
    protected function createImageResource(string $absolutePath, string $mime)
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/gif' => @imagecreatefromgif($absolutePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            default => null,
        };
    }

    /**
     * @param  resource  $resource
     */
    protected function saveGdResource($resource, string $absolutePath, string $mime): void
    {
        match ($mime) {
            'image/jpeg', 'image/jpg' => imagejpeg($resource, $absolutePath, $this->optimizeImages ? 85 : 92),
            'image/png' => imagepng($resource, $absolutePath, $this->optimizeImages ? 6 : 3),
            'image/gif' => imagegif($resource, $absolutePath),
            'image/webp' => function_exists('imagewebp') ? imagewebp($resource, $absolutePath, 85) : null,
            default => null,
        };
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function resolveTargetDimensions(int $width, int $height): array
    {
        $maxWidth = $this->maxImageWidth;
        $maxHeight = $this->maxImageHeight;

        if (! $maxWidth && ! $maxHeight) {
            return [$width, $height];
        }

        $ratio = $width / max($height, 1);

        if ($maxWidth && $width > $maxWidth) {
            $width = $maxWidth;
            $height = (int) round($width / $ratio);
        }

        if ($maxHeight && $height > $maxHeight) {
            $height = $maxHeight;
            $width = (int) round($height * $ratio);
        }

        return [$width, $height];
    }

    protected function replaceExtension(string $path, string $extension): string
    {
        return Str::of($path)->beforeLast('.').'.'.$extension;
    }

    protected function supportsWebp(): bool
    {
        return function_exists('imagewebp');
    }
}

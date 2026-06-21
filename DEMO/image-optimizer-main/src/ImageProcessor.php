<?php

namespace DaniHidayatX\ImageOptimizer;

use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageProcessor
{
    public static function process($source, array $settings): string
    {
        $format = $settings['format'] ?? null;
        $resize = $settings['resize'] ?? null;
        $maxWidth = $settings['max_width'] ?? null;
        $maxHeight = $settings['max_height'] ?? null;
        $quality = $settings['quality'] ?? null;

        if ($format) {
            $quality = $quality ?? ($format === 'jpeg' || $format === 'jpg' ? 70 : null);
        }

        if (class_exists('Intervention\Image\ImageManagerStatic')) {
            return self::processV2($source, $format, $resize, $maxWidth, $maxHeight, $quality);
        }

        return self::processV3($source, $format, $resize, $maxWidth, $maxHeight, $quality);
    }

    protected static function processV2($source, $format, $resize, $maxWidth, $maxHeight, $quality): string
    {
        if ($source instanceof TemporaryUploadedFile) {
            $source = $source->getRealPath();
        }

        $image = ImageManagerStatic::make($source);

        $shouldResize = false;
        $imageWidth = null;
        $imageHeight = null;

        if ($maxWidth && $image->width() > $maxWidth) {
            $shouldResize = true;
            $imageWidth = $maxWidth;
        }

        if ($maxHeight && $image->height() > $maxHeight) {
            $shouldResize = true;
            $imageHeight = $maxHeight;
        }

        if ($resize) {
            $shouldResize = true;
            if ($image->height() > $image->width()) {
                $imageHeight = $image->height() - ($image->height() * ($resize / 100));
            } else {
                $imageWidth = $image->width() - ($image->width() * ($resize / 100));
            }
        }

        if ($shouldResize) {
            $image->resize($imageWidth, $imageHeight, function ($constraint) {
                $constraint->aspectRatio();
            });
        }

        if ($format) {
            return (string) $image->encode($format, $quality);
        }

        return (string) $image->encode();
    }

    protected static function processV3($source, $format, $resize, $maxWidth, $maxHeight, $quality): string
    {
        $driver = extension_loaded('imagick') && class_exists('\Intervention\Image\Drivers\Imagick\Driver')
            ? new Driver
            : new \Intervention\Image\Drivers\Gd\Driver;

        $manager = new ImageManager($driver);

        // Handle Livewire TemporaryUploadedFile for Intervention Image v3
        if ($source instanceof TemporaryUploadedFile) {
            $source = $source->getRealPath();
        }

        $image = $manager->read($source);

        $calcWidth = null;
        $calcHeight = null;

        if ($maxWidth && $image->width() > $maxWidth) {
            $calcWidth = $maxWidth;
        }

        if ($maxHeight && $image->height() > $maxHeight) {
            $calcHeight = $maxHeight;
        }

        if ($resize) {
            if ($image->height() > $image->width()) {
                $calcHeight = $image->height() - ($image->height() * ($resize / 100));
            } else {
                $calcWidth = $image->width() - ($image->width() * ($resize / 100));
            }
        }

        if ($calcWidth || $calcHeight) {
            $image->scale($calcWidth, $calcHeight);
        }

        if ($format) {
            $format = strtolower($format);

            if ($format === 'jpg') {
                $format = 'jpeg';
            }

            if ($format === 'webp') {
                return (string) ($quality ? $image->toWebp($quality) : $image->toWebp());
            }
            if ($format === 'jpeg') {
                return (string) ($quality ? $image->toJpeg($quality) : $image->toJpeg());
            }
            if ($format === 'png') {
                return (string) $image->toPng();
            }
            if ($format === 'gif') {
                return (string) $image->toGif();
            }
            if ($format === 'bmp') {
                return (string) $image->toBitmap();
            }

            return (string) $image->encode();
        }

        return (string) $image->encode();
    }
}

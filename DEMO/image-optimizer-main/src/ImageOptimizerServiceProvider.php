<?php

namespace DaniHidayatX\ImageOptimizer;

use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ImageOptimizerServiceProvider extends PackageServiceProvider
{
    public static string $name = 'image-optimizer';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        $this->registerMacros();
        $this->publishStubs();
    }

    protected function registerMacros(): void
    {
        // Optimization Settings Macros
        FileUpload::macro('optimize', function (string | Closure | null $format = 'webp', int | Closure | null $quality = null) {
            $this->imageOptimization = $this->imageOptimization ?? [];
            $this->imageOptimization['format'] = $format;
            $this->imageOptimization['quality'] = $quality;
            $this->ensureOptimizerHook();

            return $this;
        });

        FileUpload::macro('resize', function (int | Closure | null $percent = 50) {
            $this->imageOptimization = $this->imageOptimization ?? [];
            $this->imageOptimization['resize'] = $percent;
            $this->ensureOptimizerHook();

            return $this;
        });

        FileUpload::macro('maxImageWidth', function (int | Closure | null $width) {
            $this->imageOptimization = $this->imageOptimization ?? [];
            $this->imageOptimization['max_width'] = $width;
            $this->ensureOptimizerHook();

            return $this;
        });

        FileUpload::macro('maxImageHeight', function (int | Closure | null $height) {
            $this->imageOptimization = $this->imageOptimization ?? [];
            $this->imageOptimization['max_height'] = $height;
            $this->ensureOptimizerHook();

            return $this;
        });

        // Spatie Specific Macros (for compatibility)
        FileUpload::macro('mediaName', function (string | Closure | null $name) {
            $this->imageOptimization = $this->imageOptimization ?? [];
            $this->imageOptimization['media_name'] = $name;

            return $this;
        });

        FileUpload::macro('customHeaders', function (array | Closure | null $headers) {
            $this->imageOptimization = $this->imageOptimization ?? [];
            $this->imageOptimization['custom_headers'] = $headers;

            return $this;
        });

        // Hook Registration
        FileUpload::macro('ensureOptimizerHook', function () {
            if ($this->hasOptimizerHook ?? false) {
                return;
            }
            $this->hasOptimizerHook = true;

            $this->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file, ?Model $record = null) {
                if ($component->isSpatieComponent()) {
                    return $component->processAndStoreSpatie($file, $record);
                }

                return $component->processAndStoreImage($file);
            });
        });

        // Helper to check for Spatie component
        FileUpload::macro('isSpatieComponent', function () {
            return class_exists('\Filament\Forms\Components\SpatieMediaLibraryFileUpload') &&
                   $this instanceof SpatieMediaLibraryFileUpload;
        });

        // Standard FileUpload Logic
        FileUpload::macro('processAndStoreImage', function (TemporaryUploadedFile $file) {
            /** @var FileUpload $this */
            $settings = $this->imageOptimization ?? [];
            $format = $this->evaluate($settings['format'] ?? null);
            $resize = $this->evaluate($settings['resize'] ?? null);
            $maxWidth = $this->evaluate($settings['max_width'] ?? null);
            $maxHeight = $this->evaluate($settings['max_height'] ?? null);
            $quality = $this->evaluate($settings['quality'] ?? null);

            $filename = $this->getUploadedFileNameForStorage($file);

            $mime = $file->getMimeType();
            $isImage = str_contains((string) $mime, 'image');
            if (! $isImage) {
                $ext = strtolower($file->getClientOriginalExtension());
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg']);
            }

            if (
                $isImage &&
                ($format || $resize || $maxWidth || $maxHeight)
            ) {
                $compressedImage = ImageProcessor::process($file->get(), [
                    'format' => $format,
                    'resize' => $resize,
                    'max_width' => $maxWidth,
                    'max_height' => $maxHeight,
                    'quality' => $quality,
                ]);

                if ($format) {
                    // Update filename extension
                    $extension = strrpos($filename, '.');
                    if ($extension !== false) {
                        $filename = substr($filename, 0, $extension + 1) . $format;
                    } else {
                        $filename .= '.' . $format;
                    }
                }

                $path = ltrim($this->getDirectory() . '/' . $filename, '/');

                Storage::disk($this->getDiskName())->put(
                    $path,
                    $compressedImage
                );

                return $path;
            }

            return $this->storeUploadedFileToDisk($file);
        });

        // Spatie FileUpload Logic
        FileUpload::macro('processAndStoreSpatie', function (TemporaryUploadedFile $file, ?Model $record) {
            /** @var SpatieMediaLibraryFileUpload $this */
            if (! $record || ! method_exists($record, 'addMedia')) {
                return null;
            }

            $settings = $this->imageOptimization ?? [];
            $format = $this->evaluate($settings['format'] ?? null);
            $resize = $this->evaluate($settings['resize'] ?? null);
            $maxWidth = $this->evaluate($settings['max_width'] ?? null);
            $maxHeight = $this->evaluate($settings['max_height'] ?? null);
            $quality = $this->evaluate($settings['quality'] ?? null);

            $filename = $this->getUploadedFileNameForStorage($file);
            $content = $file->get();

            $mime = $file->getMimeType();
            $isImage = str_contains((string) $mime, 'image');
            if (! $isImage) {
                $ext = strtolower($file->getClientOriginalExtension());
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg']);
            }

            if (
                $isImage &&
                ($format || $resize || $maxWidth || $maxHeight)
            ) {
                $content = ImageProcessor::process($content, [
                    'format' => $format,
                    'resize' => $resize,
                    'max_width' => $maxWidth,
                    'max_height' => $maxHeight,
                    'quality' => $quality,
                ]);

                if ($format) {
                    // Update filename extension locally
                    $extension = strrpos($filename, '.');
                    if ($extension !== false) {
                        $filename = substr($filename, 0, $extension + 1) . $format;
                    } else {
                        $filename .= '.' . $format;
                    }
                }
            }

            // Create a temporary file for the optimized content
            $tempPath = tempnam(sys_get_temp_dir(), 'optimized-image-');
            file_put_contents($tempPath, $content);

            $mediaAdder = $record->addMedia($tempPath);

            // Apply Spatie Options
            if ($name = $this->evaluate($settings['media_name'] ?? null)) {
                $mediaAdder->usingName($name);
            } else {
                // Fallback to client original name if not set
                $mediaAdder->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            }

            if ($headers = $this->evaluate($settings['custom_headers'] ?? null)) {
                $mediaAdder->addCustomHeaders($headers);
            }

            // Standard Spatie component methods
            $mediaAdder->usingFileName($filename);

            // Note: Other Spatie options (properties, manipulations, etc.)

            if (method_exists($this, 'getCustomProperties')) {
                $mediaAdder->withCustomProperties($this->getCustomProperties($file));
            }
            if (method_exists($this, 'getManipulations')) {
                $mediaAdder->withManipulations($this->getManipulations());
            }
            if (method_exists($this, 'getConversionsDisk') && $disk = $this->getConversionsDisk()) {
                $mediaAdder->storingConversionsOnDisk($disk);
            }
            if (method_exists($this, 'hasResponsiveImages') && $this->hasResponsiveImages()) {
                $mediaAdder->withResponsiveImages();
            }

            try {
                $media = $mediaAdder->toMediaCollection($this->getCollection(), $this->getDiskName());
            } finally {
                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }

            return $media->getAttributeValue('uuid');
        });

        // Standard FileUpload storeFile logic (fallback)
        FileUpload::macro('storeUploadedFileToDisk', function (TemporaryUploadedFile $file) {
            /** @var FileUpload $this */
            $storeMethod = $this->getVisibility() === 'public' ? 'storePubliclyAs' : 'storeAs';

            if (
                $this->shouldMoveFiles() &&
                method_exists($file, 'getDisk') &&
                $this->getDiskName() === $file->getDisk()
            ) {
                $newPath = trim($this->getDirectory() . '/' . $this->getUploadedFileNameForStorage($file), '/');
                $this->getDisk()->move($file->path(), $newPath);

                return $newPath;
            }

            return $file->{$storeMethod}(
                $this->getDirectory(),
                $this->getUploadedFileNameForStorage($file),
                $this->getDiskName()
            );
        });
    }

    protected function publishStubs(): void
    {
        if (app()->runningInConsole()) {
            $filesystem = app(Filesystem::class);
            $stubsPath = __DIR__ . '/../stubs/';
            if ($filesystem->exists($stubsPath)) {
                foreach ($filesystem->files($stubsPath) as $file) {
                    $this->publishes([
                        $file->getRealPath() => base_path("stubs/image-optimizer/{$file->getFilename()}"),
                    ], 'image-optimizer-stubs');
                }
            }
        }
    }
}

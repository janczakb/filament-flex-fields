<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\FlexFileUpload;

use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadImageProcessor;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadMetadata;
use Bjanczak\FilamentFlexFields\Support\FileUpload\ScopedDirectoryResolver;
use Closure;
use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @mixin BaseFileUpload
 */
trait FlexFileUploadStorage
{
    protected bool|Closure $flexScopedDirectoryEnabled = false;

    protected string|Closure $flexScopedDirectoryPrefix = 'uploads';

    protected string|Closure|null $flexStoreMetadataIn = null;

    protected int|Closure|null $flexMaxTotalSizeKb = null;

    protected bool|Closure $flexDeleteFileOnRemove = false;

    protected bool|Closure $flexDeleteReplacedFiles = false;

    protected bool|Closure $flexPruneOrphanedOnSave = false;

    /**
     * @var (Closure(BaseFileUpload, string, int): void)|null
     */
    protected $flexDeletedStoredFileUsing = null;

    /**
     * @var list<string>
     */
    protected array $flexBaselineFilePaths = [];

    public function scopedDirectory(string|Closure $prefix = 'uploads'): static
    {
        $this->flexScopedDirectoryEnabled = true;
        $this->flexScopedDirectoryPrefix = $prefix;

        $this->directory(function (BaseFileUpload $component) use ($prefix): string {
            /** @var static $component */
            $resolvedPrefix = $component->evaluate($prefix);

            return ScopedDirectoryResolver::resolve(
                is_string($resolvedPrefix) ? $resolvedPrefix : 'uploads',
                $component->getRecord() instanceof Model ? $component->getRecord() : null,
                Auth::id(),
            );
        });

        return $this;
    }

    public function createFormStrategy(bool|Closure $condition = true): static
    {
        $this->preventFilePathTampering($condition, function (BaseFileUpload $component, string $file): bool {
            $directory = trim((string) $component->getDirectory(), '/');

            if ($directory === '') {
                return false;
            }

            $normalizedFile = ltrim($file, '/');

            return str_starts_with($normalizedFile, $directory.'/') || $normalizedFile === $directory;
        });

        return $this;
    }

    public function storeMetadataIn(string|Closure $statePath): static
    {
        $this->flexStoreMetadataIn = $statePath;

        return $this;
    }

    public function maxTotalSizeKb(int|Closure $kilobytes): static
    {
        $this->flexMaxTotalSizeKb = $kilobytes;

        $this->rule(static function (BaseFileUpload $component): Closure {
            /** @var static $component */
            $limit = $component->getMaxTotalSizeKb();

            return static function (string $attribute, mixed $value, Closure $fail) use ($component, $limit): void {
                if (blank($value) || $limit === null) {
                    return;
                }

                $totalKb = 0;

                foreach (Arr::wrap($value) as $file) {
                    if ($file instanceof TemporaryUploadedFile) {
                        $totalKb += $file->getSize() / 1024;

                        continue;
                    }

                    if (! is_string($file)) {
                        continue;
                    }

                    try {
                        if ($component->getDisk()->exists($file)) {
                            $totalKb += $component->getDisk()->size($file) / 1024;
                        }
                    } catch (\Throwable) {
                        //
                    }
                }

                if ($totalKb > $limit) {
                    $fail(__('filament-flex-fields::default.file_upload.validation.max_total_size', [
                        'max' => number_format($limit, 0),
                    ]));
                }
            };
        });

        return $this;
    }

    public function deleteFileOnRemove(bool|Closure $condition = true): static
    {
        $this->flexDeleteFileOnRemove = $condition;

        return $this;
    }

    public function deleteReplacedFiles(bool|Closure $condition = true): static
    {
        $this->flexDeleteReplacedFiles = $condition;

        return $this;
    }

    /**
     * WARNING: This method deletes all files in the current upload directory that are not part
     * of the current component's state upon saving.
     *
     * Do NOT use this with a shared static `directory()`, as it will delete files belonging
     * to other records. Always use `scopedDirectory()` or a dynamic directory closure when enabling this.
     */
    public function pruneOrphanedOnSave(bool|Closure $condition = true): static
    {
        $this->flexPruneOrphanedOnSave = $condition;

        return $this;
    }

    /**
     * Invoked after a stored path (and its thumb variant, if any) is deleted from disk.
     * The third argument is the number of bytes freed.
     *
     * @param  (Closure(BaseFileUpload, string, int): void)|null  $callback
     */
    public function deletedStoredFileUsing(?Closure $callback): static
    {
        $this->flexDeletedStoredFileUsing = $callback;

        return $this;
    }

    public function persistUploadedFileWithFlexProcessing(TemporaryUploadedFile $file): ?string
    {
        $storedPath = $this->saveUploadedFile($file);

        if ($storedPath === null) {
            return null;
        }

        if ($this->shouldOptimizeImages() || $this->shouldOptimizeImagesToWebp() || $this->getFlexMaxImageWidth() || $this->getFlexMaxImageHeight()) {
            $processor = new FileUploadImageProcessor(
                optimizeImages: $this->shouldOptimizeImages(),
                optimizeImagesToWebp: $this->shouldOptimizeImagesToWebp(),
                maxImageWidth: $this->getFlexMaxImageWidth(),
                maxImageHeight: $this->getFlexMaxImageHeight(),
                stripExif: $this->shouldStripExif(),
            );

            $storedPath = $processor->process($this->getDisk(), $storedPath);
        }

        $this->writeMetadataForStoredPath($storedPath, $file->getClientOriginalName(), $file);

        return $storedPath;
    }

    public function registerFlexFileUploadHooks(): void
    {
        $this->afterStateHydrated(function (BaseFileUpload $component): void {
            /** @var static $component */
            $component->hydrateFiles();
            $component->snapshotBaselineFilePaths();
        });

        $this->beforeStateDehydrated(function (BaseFileUpload $component): void {
            /** @var static $component */
            $component->saveUploadedFiles();
            $component->deleteReplacedFilesFromDisk();
            $component->pruneOrphanedFilesFromDirectory();
        }, true);

        $this->saveUploadedFileUsing(function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
            /** @var static $component */

            return $component->persistUploadedFileWithFlexProcessing($file);
        });

        $this->deleteUploadedFileUsing(function (BaseFileUpload $component, string $file): void {
            /** @var static $component */
            if (! $component->shouldDeleteFileOnRemove()) {
                return;
            }

            $component->deleteStoredPathWithVariants($file);
        });
    }

    /**
     * Nested repeater/builder uploads share the parent Eloquent record, but their
     * field name is not a model attribute. Filament's default implementation calls
     * getAttribute() eagerly and throws MissingAttributeException when
     * Model::preventAccessingMissingAttributes() is enabled.
     *
     * @return list<string>
     */
    public function getOriginalFilePaths(): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Model) {
            return [];
        }

        $attribute = $this->getName();

        if (! $record->hasAttribute($attribute)) {
            return [];
        }

        return array_values(array_filter(
            Arr::wrap($record->getOriginal($attribute, $record->getAttribute($attribute))),
            static fn (mixed $path): bool => is_string($path) && filled($path),
        ));
    }

    public function snapshotBaselineFilePaths(): void
    {
        $this->flexBaselineFilePaths = $this->normalizeFilePaths($this->getRawState());
    }

    /**
     * @return list<string>
     */
    public function getBaselineFilePaths(): array
    {
        return $this->flexBaselineFilePaths;
    }

    public function deleteReplacedFilesFromDisk(): void
    {
        if (! $this->shouldDeleteReplacedFiles()) {
            return;
        }

        $currentPaths = $this->normalizeFilePaths($this->getRawState());
        $removedPaths = array_diff($this->flexBaselineFilePaths, $currentPaths);

        foreach ($removedPaths as $path) {
            $this->deleteStoredPathWithVariants($path);
        }
    }

    public function pruneOrphanedFilesFromDirectory(): void
    {
        if (! $this->shouldPruneOrphanedOnSave()) {
            return;
        }

        $directory = trim((string) $this->getDirectory(), '/');

        if ($directory === '') {
            return;
        }

        $currentPaths = $this->normalizeFilePaths($this->getRawState());
        $keep = array_fill_keys($currentPaths, true);

        foreach ($currentPaths as $path) {
            $thumb = $this->thumbVariantPathFor($path);

            if ($thumb !== null) {
                $keep[$thumb] = true;
            }
        }

        $files = rescue(fn (): array => $this->getDisk()->allFiles($directory), [], report: false);

        foreach ($files as $file) {
            if (! isset($keep[$file])) {
                $this->deleteStoredPathWithVariants($file);
            }
        }
    }

    /**
     * Delete a stored upload and its `_thumb.webp` sibling (if present).
     *
     * @return int Bytes freed
     */
    public function deleteStoredPathWithVariants(string $path): int
    {
        $disk = $this->getDisk();
        $freed = 0;
        $targets = [$path];
        $thumb = $this->thumbVariantPathFor($path);

        if ($thumb !== null) {
            $targets[] = $thumb;
        }

        foreach (array_values(array_unique($targets)) as $target) {
            if (! $disk->exists($target)) {
                continue;
            }

            $freed += (int) rescue(fn (): int => (int) $disk->size($target), 0, report: false);
            rescue(fn () => $disk->delete($target), report: false);
        }

        if ($freed > 0 && $this->flexDeletedStoredFileUsing instanceof Closure) {
            $this->evaluate($this->flexDeletedStoredFileUsing, [
                'path' => $path,
                'bytes' => $freed,
                'bytesFreed' => $freed,
            ]);
        }

        return $freed;
    }

    protected function thumbVariantPathFor(string $path): ?string
    {
        if ($path === '' || str_ends_with($path, '_thumb.webp') || ! str_contains($path, '.')) {
            return null;
        }

        return \Illuminate\Support\Str::of($path)->beforeLast('.')->append('_thumb.webp')->toString();
    }

    public function writeMetadataForStoredPath(string $storedPath, ?string $originalName = null, ?TemporaryUploadedFile $temporaryFile = null): void
    {
        $metadataPath = $this->getStoreMetadataInPath();

        if (blank($metadataPath)) {
            return;
        }

        $metadata = $temporaryFile instanceof TemporaryUploadedFile
            ? FileUploadMetadata::fromTemporaryFile($temporaryFile)
            : FileUploadMetadata::fromDiskPath($this->getDisk(), $storedPath, $originalName);

        $set = $this->makeSetUtility();
        $get = $this->makeGetUtility();

        if ($this->isMultiple()) {
            $existing = $get($metadataPath) ?? [];
            $existing[$storedPath] = $metadata;
            $set($metadataPath, $existing);

            return;
        }

        $set($metadataPath, $metadata);
    }

    /**
     * @return list<string>
     */
    protected function normalizeFilePaths(mixed $state): array
    {
        return array_values(array_filter(array_map(
            static function (mixed $file): ?string {
                if (is_string($file) && filled($file)) {
                    return $file;
                }

                return null;
            },
            Arr::wrap($state),
        )));
    }

    public function getStoreMetadataInPath(): ?string
    {
        $path = $this->evaluate($this->flexStoreMetadataIn);

        return filled($path) ? (string) $path : null;
    }

    public function getMaxTotalSizeKb(): ?int
    {
        $value = $this->evaluate($this->flexMaxTotalSizeKb);

        return is_numeric($value) ? (int) $value : null;
    }

    public function shouldDeleteFileOnRemove(): bool
    {
        return (bool) $this->evaluate($this->flexDeleteFileOnRemove);
    }

    public function shouldDeleteReplacedFiles(): bool
    {
        return (bool) $this->evaluate($this->flexDeleteReplacedFiles);
    }

    public function shouldPruneOrphanedOnSave(): bool
    {
        return (bool) $this->evaluate($this->flexPruneOrphanedOnSave);
    }
}

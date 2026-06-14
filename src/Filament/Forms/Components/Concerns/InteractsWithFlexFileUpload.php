<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Bjanczak\FilamentFlexFields\Support\FileUpload\ExecutableExtensionGuard;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadImageProcessor;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadMetadata;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadMimePresets;
use Bjanczak\FilamentFlexFields\Support\FileUpload\ScopedDirectoryResolver;
use Closure;
use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @mixin BaseFileUpload
 */
trait InteractsWithFlexFileUpload
{
    protected string|Closure $flexFileUploadVariant = 'primary';

    protected bool|Closure $flexScopedDirectoryEnabled = false;

    protected string|Closure $flexScopedDirectoryPrefix = 'uploads';

    protected string|Closure|null $flexStoreMetadataIn = null;

    protected int|Closure|null $flexMaxTotalSizeKb = null;

    protected bool|Closure $flexRemainingSlotsLabel = false;

    protected int|Closure|null $flexMinImageWidth = null;

    protected int|Closure|null $flexMinImageHeight = null;

    protected int|Closure|null $flexMaxImageWidth = null;

    protected int|Closure|null $flexMaxImageHeight = null;

    protected bool|Closure $flexDeleteFileOnRemove = false;

    protected bool|Closure $flexDeleteReplacedFiles = false;

    protected bool|Closure $flexPruneOrphanedOnSave = false;

    protected bool|Closure $flexUploadSummary = false;

    protected string|Closure|null $flexEmptyStateHint = null;

    protected string|Closure|null $flexDropzoneLabel = null;

    protected bool|Closure $flexRequireReplaceConfirmation = false;

    protected bool|Closure $flexCompactList = false;

    protected bool|Closure $flexShowFileIcon = false;

    protected bool|Closure $flexOptimizeImages = false;

    protected bool|Closure $flexOptimizeImagesToWebp = false;

    protected bool|Closure $flexStripExif = true;

    protected bool|Closure $flexRejectExecutableFiles = false;

    /**
     * @var list<string>
     */
    protected array $flexBaselineFilePaths = [];

    /**
     * @var list<string>|null
     */
    protected ?array $flexAllowedExtensions = null;

    protected bool $flexRecommendedDefaultsApplied = false;

    public function withRecommendedDefaults(): static
    {
        return $this->applyRecommendedSecurityDefaults();
    }

    public function applyRecommendedSecurityDefaults(): static
    {
        $this->flexRecommendedDefaultsApplied = true;

        $this
            ->createFormStrategy()
            ->deleteFileOnRemove()
            ->deleteReplacedFiles()
            ->maxSize(5120)
            ->downloadable()
            ->openable()
            ->focusOutline();

        return $this;
    }

    public function documentsOnly(): static
    {
        return $this
            ->acceptedFileTypes(FileUploadMimePresets::documents())
            ->validationMessages([
                'mimetypes' => __('filament-flex-fields::default.file_upload.validation.documents_only'),
            ]);
    }

    public function imagesOnly(): static
    {
        return $this
            ->acceptedFileTypes(FileUploadMimePresets::images())
            ->validationMessages([
                'mimetypes' => __('filament-flex-fields::default.file_upload.validation.images_only'),
            ]);
    }

    public function spreadsheetsOnly(): static
    {
        return $this
            ->acceptedFileTypes(FileUploadMimePresets::spreadsheets())
            ->validationMessages([
                'mimetypes' => __('filament-flex-fields::default.file_upload.validation.spreadsheets_only'),
            ]);
    }

    /**
     * @param  list<string>  $extensions
     */
    public function allowedExtensions(array $extensions): static
    {
        $this->flexAllowedExtensions = array_values(array_filter(array_map(
            static fn (string $extension): string => strtolower(ltrim($extension, '.')),
            $extensions,
        )));

        $this->rule(static function (BaseFileUpload $component): Closure {
            /** @var static $component */
            $allowed = $component->getAllowedExtensions();

            return static function (string $attribute, mixed $value, Closure $fail) use ($allowed): void {
                if (blank($value)) {
                    return;
                }

                $filename = $value instanceof TemporaryUploadedFile
                    ? $value->getClientOriginalName()
                    : basename((string) $value);

                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (! in_array($extension, $allowed, true)) {
                    $fail(__('filament-flex-fields::default.file_upload.validation.extension_not_allowed', [
                        'extensions' => implode(', ', $allowed),
                    ]));
                }
            };
        });

        return $this;
    }

    public function rejectExecutableFiles(bool|Closure $condition = true): static
    {
        $this->flexRejectExecutableFiles = $condition;

        $this->rule(static function (BaseFileUpload $component): ?Closure {
            /** @var static $component */
            if (! $component->shouldRejectExecutableFiles()) {
                return null;
            }

            return ExecutableExtensionGuard::validationRule();
        });

        return $this;
    }

    public function scopedDirectory(string|Closure $prefix = 'uploads'): static
    {
        $this->flexScopedDirectoryEnabled = true;
        $this->flexScopedDirectoryPrefix = $prefix;

        $this->directory(function (BaseFileUpload $component) use ($prefix): string {
            /** @var static $component */
            $resolvedPrefix = $component->evaluate($prefix);

            return ScopedDirectoryResolver::resolve(
                is_string($resolvedPrefix) ? $resolvedPrefix : 'uploads',
                $component->getRecord(),
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

    public function remainingSlotsLabel(bool|Closure $condition = true): static
    {
        $this->flexRemainingSlotsLabel = $condition;

        return $this;
    }

    public function minImageDimensions(int $width, int $height): static
    {
        $this->flexMinImageWidth = $width;
        $this->flexMinImageHeight = $height;

        $this->registerImageDimensionRule();

        return $this;
    }

    public function maxImageDimensions(int $width, int $height): static
    {
        $this->flexMaxImageWidth = $width;
        $this->flexMaxImageHeight = $height;

        $this->registerImageDimensionRule();

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

    public function pruneOrphanedOnSave(bool|Closure $condition = true): static
    {
        $this->flexPruneOrphanedOnSave = $condition;

        return $this;
    }

    public function uploadSummary(bool|Closure $condition = true): static
    {
        $this->flexUploadSummary = $condition;

        return $this;
    }

    public function emptyStateHint(string|Closure $hint): static
    {
        $this->flexEmptyStateHint = $hint;

        return $this;
    }

    public function dropzoneLabel(string|Closure $label): static
    {
        $this->flexDropzoneLabel = $label;

        return $this;
    }

    public function requireReplaceConfirmation(bool|Closure $condition = true): static
    {
        $this->flexRequireReplaceConfirmation = $condition;

        return $this;
    }

    public function compactList(bool|Closure $condition = true): static
    {
        $this->flexCompactList = $condition;

        if ((bool) $this->evaluate($condition)) {
            $this->panelLayout('compact');
        }

        return $this;
    }

    public function showFileIcon(bool|Closure $condition = true): static
    {
        $this->flexShowFileIcon = $condition;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->flexFileUploadVariant = $variant;

        return $this;
    }

    public function optimizeImages(bool|Closure $condition = true): static
    {
        $this->flexOptimizeImages = $condition;

        return $this;
    }

    public function optimizeImagesToWebp(bool|Closure $condition = false): static
    {
        $this->flexOptimizeImagesToWebp = $condition;

        return $this;
    }

    public function maxImageWidth(int|Closure $width): static
    {
        $this->flexMaxImageWidth = $width;

        return $this;
    }

    public function maxImageHeight(int|Closure $height): static
    {
        $this->flexMaxImageHeight = $height;

        return $this;
    }

    public function stripExif(bool|Closure $condition = true): static
    {
        $this->flexStripExif = $condition;

        return $this;
    }

    public function registerFlexFileUploadHooks(): void
    {
        $this->afterStateHydrated(function (BaseFileUpload $component): void {
            /** @var static $component */
            $component->snapshotBaselineFilePaths();
        });

        $this->beforeStateDehydrated(function (BaseFileUpload $component): void {
            /** @var static $component */
            $component->deleteReplacedFilesFromDisk();
            $component->pruneOrphanedFilesFromDirectory();
        });

        $this->saveUploadedFileUsing(function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
            /** @var static $component */
            $storedPath = $component->saveUploadedFile($file);

            if ($storedPath === null) {
                return null;
            }

            if ($component->shouldOptimizeImages() || $component->shouldOptimizeImagesToWebp() || $component->getFlexMaxImageWidth() || $component->getFlexMaxImageHeight()) {
                $processor = new FileUploadImageProcessor(
                    optimizeImages: $component->shouldOptimizeImages(),
                    optimizeImagesToWebp: $component->shouldOptimizeImagesToWebp(),
                    maxImageWidth: $component->getFlexMaxImageWidth(),
                    maxImageHeight: $component->getFlexMaxImageHeight(),
                    stripExif: $component->shouldStripExif(),
                );

                $storedPath = $processor->process($component->getDisk(), $storedPath);
            }

            $component->writeMetadataForStoredPath($storedPath, $file->getClientOriginalName(), $file);

            return $storedPath;
        });

        $this->deleteUploadedFileUsing(function (BaseFileUpload $component, string $file): void {
            /** @var static $component */
            if (! $component->shouldDeleteFileOnRemove()) {
                return;
            }

            rescue(fn () => $component->getDisk()->delete($file), report: false);
        });
    }

    protected function registerImageDimensionRule(): void
    {
        $this->rule(static function (BaseFileUpload $component): ?Closure {
            /** @var static $component */
            $minWidth = $component->getFlexMinImageWidth();
            $minHeight = $component->getFlexMinImageHeight();
            $maxWidth = $component->getFlexMaxImageWidth();
            $maxHeight = $component->getFlexMaxImageHeight();

            if (! $minWidth && ! $minHeight && ! $maxWidth && ! $maxHeight) {
                return null;
            }

            $rule = Rule::dimensions();

            if ($minWidth) {
                $rule->minWidth($minWidth);
            }

            if ($minHeight) {
                $rule->minHeight($minHeight);
            }

            if ($maxWidth) {
                $rule->maxWidth($maxWidth);
            }

            if ($maxHeight) {
                $rule->maxHeight($maxHeight);
            }

            return static function (string $attribute, mixed $value, Closure $fail) use ($rule): void {
                if (blank($value)) {
                    return;
                }

                if (! validator(['file' => $value], ['file' => $rule])->passes()) {
                    $fail(__('filament-flex-fields::default.file_upload.validation.image_dimensions'));
                }
            };
        });
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
            rescue(fn () => $this->getDisk()->delete($path), report: false);
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

        $files = rescue(fn (): array => $this->getDisk()->allFiles($directory), [], report: false);

        foreach ($files as $file) {
            if (! in_array($file, $currentPaths, true)) {
                rescue(fn () => $this->getDisk()->delete($file), report: false);
            }
        }
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

    public function getVariant(): string
    {
        return (string) $this->evaluate($this->flexFileUploadVariant);
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

    public function shouldShowRemainingSlotsLabel(): bool
    {
        return (bool) $this->evaluate($this->flexRemainingSlotsLabel);
    }

    public function getRemainingSlotsLabel(): ?string
    {
        if (! $this->shouldShowRemainingSlotsLabel()) {
            return null;
        }

        $maxFiles = $this->getMaxFiles();

        if ($maxFiles === null) {
            return null;
        }

        $currentCount = count($this->normalizeFilePaths($this->getRawState()));
        $remaining = max($maxFiles - $currentCount, 0);

        return __('filament-flex-fields::default.file_upload.remaining_slots', [
            'remaining' => $remaining,
            'max' => $maxFiles,
        ]);
    }

    public function shouldShowUploadSummary(): bool
    {
        return (bool) $this->evaluate($this->flexUploadSummary);
    }

    public function getEmptyStateHint(): ?string
    {
        $hint = $this->evaluate($this->flexEmptyStateHint);

        return filled($hint) ? (string) $hint : null;
    }

    public function getDropzoneLabel(): ?string
    {
        $label = $this->evaluate($this->flexDropzoneLabel);

        return filled($label) ? (string) $label : null;
    }

    public function getEffectivePlaceholder(): ?string
    {
        return $this->getDropzoneLabel()
            ?? $this->getEmptyStateHint()
            ?? $this->getPlaceholder();
    }

    public function shouldRequireReplaceConfirmation(): bool
    {
        return (bool) $this->evaluate($this->flexRequireReplaceConfirmation);
    }

    public function shouldShowFileIcon(): bool
    {
        return (bool) $this->evaluate($this->flexShowFileIcon);
    }

    public function shouldUseCompactList(): bool
    {
        return (bool) $this->evaluate($this->flexCompactList);
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

    public function shouldOptimizeImages(): bool
    {
        return (bool) $this->evaluate($this->flexOptimizeImages);
    }

    public function shouldOptimizeImagesToWebp(): bool
    {
        return (bool) $this->evaluate($this->flexOptimizeImagesToWebp);
    }

    public function shouldStripExif(): bool
    {
        return (bool) $this->evaluate($this->flexStripExif);
    }

    public function shouldRejectExecutableFiles(): bool
    {
        return (bool) $this->evaluate($this->flexRejectExecutableFiles);
    }

    public function getFlexMinImageWidth(): ?int
    {
        $value = $this->evaluate($this->flexMinImageWidth);

        return is_numeric($value) ? (int) $value : null;
    }

    public function getFlexMinImageHeight(): ?int
    {
        $value = $this->evaluate($this->flexMinImageHeight);

        return is_numeric($value) ? (int) $value : null;
    }

    public function getFlexMaxImageWidth(): ?int
    {
        $value = $this->evaluate($this->flexMaxImageWidth);

        return is_numeric($value) ? (int) $value : null;
    }

    public function getFlexMaxImageHeight(): ?int
    {
        $value = $this->evaluate($this->flexMaxImageHeight);

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return list<string>
     */
    public function getAllowedExtensions(): array
    {
        return $this->flexAllowedExtensions ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFlexFileUploadAlpineConfiguration(): array
    {
        return [
            'showUploadSummary' => $this->shouldShowUploadSummary(),
            'requireReplaceConfirmation' => $this->shouldRequireReplaceConfirmation(),
            'replaceConfirmationMessage' => __('filament-flex-fields::default.file_upload.replace_confirmation'),
            'summaryTemplate' => __('filament-flex-fields::default.file_upload.summary'),
            'remainingSlotsLabel' => $this->getRemainingSlotsLabel(),
            'showFileIcon' => $this->shouldShowFileIcon(),
        ];
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-flex-file-upload',
            'fff-flex-file-upload--'.$this->getSize(),
            'fff-flex-file-upload--'.$this->getVariant(),
        ];

        if ($this->shouldShowFileIcon()) {
            $classes[] = 'fff-flex-file-upload--show-file-icon';
        }

        if ($this->shouldUseCompactList()) {
            $classes[] = 'fff-flex-file-upload--compact-list';
        }

        if ($this->shouldShowFocusOutline()) {
            $classes[] = 'has-focus-outline';
        }

        if ($this->isAvatar()) {
            $classes[] = 'fff-flex-file-upload--avatar';
        }

        return $classes;
    }
}

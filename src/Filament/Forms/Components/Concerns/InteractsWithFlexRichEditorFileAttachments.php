<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadImageProcessor;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadMimePresets;
use Bjanczak\FilamentFlexFields\Support\FileUpload\ScopedDirectoryResolver;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentIdResolver;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentPaths;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentPruner;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentVariantGenerator;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorImageVariant;
use Closure;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\FileAttachmentProviders\Contracts\FileAttachmentProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @mixin RichEditor
 */
trait InteractsWithFlexRichEditorFileAttachments
{
    protected bool|Closure $flexRichEditorOptimizeImages = false;

    protected bool|Closure $flexRichEditorOptimizeImagesToWebp = false;

    protected int|Closure|null $flexRichEditorMaxImageWidth = null;

    protected int|Closure|null $flexRichEditorMaxImageHeight = null;

    protected int|Closure|null $flexRichEditorMaxImageLongEdge = null;

    protected bool|Closure $flexRichEditorStripExif = true;

    /**
     * @var array<string, array<string, mixed>|RichEditorImageVariant>|Closure|null
     */
    protected array|Closure|null $flexRichEditorImageVariants = null;

    protected bool|Closure $flexRichEditorPruneOrphanedAttachments = true;

    public function imagesOnly(): static
    {
        return $this->fileAttachmentsAcceptedFileTypes(FileUploadMimePresets::images());
    }

    public function maxAttachmentSizeKb(int|Closure $size): static
    {
        return $this->fileAttachmentsMaxSize($size);
    }

    public function optimizeImages(bool|Closure $condition = true): static
    {
        $this->flexRichEditorOptimizeImages = $condition;

        return $this;
    }

    public function optimizeImagesToWebp(bool|Closure $condition = false): static
    {
        $this->flexRichEditorOptimizeImagesToWebp = $condition;

        return $this;
    }

    public function maxImageWidth(int|Closure $width): static
    {
        $this->flexRichEditorMaxImageWidth = $width;

        return $this;
    }

    public function maxImageHeight(int|Closure $height): static
    {
        $this->flexRichEditorMaxImageHeight = $height;

        return $this;
    }

    public function maxImageLongEdge(int|Closure $pixels): static
    {
        $this->flexRichEditorMaxImageLongEdge = $pixels;

        return $this;
    }

    public function stripExif(bool|Closure $condition = true): static
    {
        $this->flexRichEditorStripExif = $condition;

        return $this;
    }

    /**
     * @param  array<string, array<string, mixed>|RichEditorImageVariant>|Closure  $variants
     */
    public function imageVariants(array|Closure $variants): static
    {
        $this->flexRichEditorImageVariants = $variants;

        return $this;
    }

    public function pruneOrphanedAttachmentsOnSave(bool|Closure $condition = true): static
    {
        $this->flexRichEditorPruneOrphanedAttachments = $condition;

        return $this;
    }

    public function scopedAttachmentDirectory(string|Closure $prefix = 'rich-editor'): static
    {
        return $this->fileAttachmentsDirectory(function () use ($prefix): string {
            return ScopedDirectoryResolver::resolve(
                (string) $this->evaluate($prefix),
                $this->getRecord(),
                Auth::id(),
            );
        });
    }

    public function shouldOptimizeRichEditorImages(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorOptimizeImages);
    }

    public function shouldOptimizeRichEditorImagesToWebp(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorOptimizeImagesToWebp);
    }

    public function getFlexRichEditorMaxImageWidth(): ?int
    {
        $width = $this->evaluate($this->flexRichEditorMaxImageWidth);

        return is_int($width) ? $width : null;
    }

    public function getFlexRichEditorMaxImageHeight(): ?int
    {
        $height = $this->evaluate($this->flexRichEditorMaxImageHeight);

        return is_int($height) ? $height : null;
    }

    public function getFlexRichEditorMaxImageLongEdge(): ?int
    {
        $pixels = $this->evaluate($this->flexRichEditorMaxImageLongEdge);

        return is_int($pixels) ? $pixels : null;
    }

    public function shouldStripExifRichEditorAttachments(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorStripExif);
    }

    public function shouldPruneOrphanedRichEditorAttachments(): bool
    {
        return (bool) $this->evaluate($this->flexRichEditorPruneOrphanedAttachments);
    }

    /**
     * @return list<RichEditorImageVariant>
     */
    public function getFlexRichEditorImageVariants(): array
    {
        $variants = $this->evaluate($this->flexRichEditorImageVariants);

        if (! is_array($variants) || $variants === []) {
            return [];
        }

        return RichEditorImageVariant::normalizeCollection($variants);
    }

    public function shouldProcessRichEditorAttachmentImages(): bool
    {
        if ($this->getFlexRichEditorImageVariants() !== []) {
            return true;
        }

        return $this->shouldOptimizeRichEditorImages()
            || $this->shouldOptimizeRichEditorImagesToWebp()
            || $this->getFlexRichEditorMaxImageWidth()
            || $this->getFlexRichEditorMaxImageHeight()
            || $this->getFlexRichEditorMaxImageLongEdge();
    }

    public function usesRichEditorFileAttachmentProvider(): bool
    {
        return $this->getFileAttachmentProvider() !== null;
    }

    public function usesSpatieRichEditorFileAttachmentProvider(): bool
    {
        $provider = $this->getFileAttachmentProvider();

        return $provider !== null
            && str_contains($provider::class, 'SpatieMediaLibrary');
    }

    public function getFileAttachmentProvider(): ?FileAttachmentProvider
    {
        if (! isset($this->container)) {
            return null;
        }

        return parent::getFileAttachmentProvider();
    }

    public function registerFlexRichEditorFileAttachmentHooks(): void
    {
        $this->saveUploadedFileAttachmentUsing(function (TemporaryUploadedFile $file): mixed {
            /** @var static $this */

            return $this->persistFlexRichEditorFileAttachment($file);
        });

        $this->beforeStateDehydrated(function (): void {
            /** @var static $this */
            $this->pruneFlexRichEditorOrphanedAttachments();
        }, shouldUpdateValidatedStateAfter: true);
    }

    public function persistFlexRichEditorFileAttachment(TemporaryUploadedFile $file): mixed
    {
        $file = $this->prepareRichEditorAttachmentForProviderSave($file);

        if (filled($savedFile = $this->defaultSaveUploadedFileAttachment($file))) {
            $this->queueSpatieVariantConversions($savedFile);

            return $savedFile;
        }

        $path = $file->store($this->getFileAttachmentsDirectory(), $this->getFileAttachmentsDiskName());

        if ($this->getFileAttachmentsVisibility() === 'public') {
            rescue(fn () => $this->getFileAttachmentsDisk()->setVisibility($path, 'public'), report: false);
        }

        return $this->postProcessStoredRichEditorAttachment($path);
    }

    public function postProcessStoredRichEditorAttachment(string $path): string
    {
        $variants = $this->getFlexRichEditorImageVariants();

        if ($variants !== []) {
            $manifest = (new RichEditorAttachmentVariantGenerator)->generate(
                $this->getFileAttachmentsDisk(),
                $path,
                $variants,
            );

            return $manifest->master;
        }

        if ($this->shouldPostProcessRichEditorAttachmentOnDisk()) {
            return $this->makeRichEditorImageProcessor(allowWebp: true)->process(
                $this->getFileAttachmentsDisk(),
                $path,
            );
        }

        return $path;
    }

    public function pruneFlexRichEditorOrphanedAttachments(): void
    {
        if (! $this->shouldPruneOrphanedRichEditorAttachments()) {
            return;
        }

        if ($this->usesRichEditorFileAttachmentProvider()) {
            return;
        }

        $keptIds = RichEditorAttachmentIdResolver::fromContent($this->getState());
        $originalIds = $this->getOriginalFileAttachmentPaths();
        $removedIds = array_values(array_diff($originalIds, $keptIds));

        if ($this->shouldAggressivelyPruneScopedAttachmentDirectory()) {
            $removedIds = array_values(array_unique([
                ...$removedIds,
                ...$this->resolveUnreferencedAttachmentPathsInDirectory($keptIds),
            ]));
        }

        if ($removedIds === []) {
            return;
        }

        (new RichEditorAttachmentPruner)->deleteMastersWithVariants(
            $this->getFileAttachmentsDisk(),
            $removedIds,
        );
    }

    /**
     * @param  list<string>  $keptIds
     * @return list<string>
     */
    protected function resolveUnreferencedAttachmentPathsInDirectory(array $keptIds): array
    {
        $directory = trim((string) $this->getFileAttachmentsDirectory(), '/');

        if ($directory === '') {
            return [];
        }

        $keptPaths = [];

        foreach ($keptIds as $id) {
            $keptPaths[] = $id;
            $keptPaths[] = RichEditorAttachmentPaths::manifestPath($id);
        }

        $files = rescue(fn (): array => $this->getFileAttachmentsDisk()->allFiles($directory), [], report: false);
        $removed = [];

        foreach ($files as $file) {
            if (str_ends_with($file, '.flex-variants.json')) {
                continue;
            }

            if (str_contains($file, '__')) {
                $isVariantOfKeptMaster = collect($keptIds)->contains(
                    fn (string $keptId): bool => str_starts_with($file, (string) Str::of($keptId)->beforeLast('.')),
                );

                if ($isVariantOfKeptMaster) {
                    continue;
                }
            }

            if (! in_array($file, $keptPaths, true)) {
                $removed[] = $file;
            }
        }

        return array_values(array_unique($removed));
    }

    protected function shouldAggressivelyPruneScopedAttachmentDirectory(): bool
    {
        $directory = (string) $this->getFileAttachmentsDirectory();

        if ($directory === '') {
            return false;
        }

        if ($this->getRecord() !== null) {
            return true;
        }

        return str_contains($directory, '/drafts/');
    }

    protected function prepareRichEditorAttachmentForProviderSave(TemporaryUploadedFile $file): TemporaryUploadedFile
    {
        if (! $this->usesRichEditorFileAttachmentProvider() || ! $this->shouldPreProcessRichEditorAttachmentTemp()) {
            return $file;
        }

        $absolutePath = $file->getRealPath();

        if (! is_string($absolutePath) || ! is_file($absolutePath)) {
            return $file;
        }

        $processor = $this->resolvePreProcessRichEditorImageProcessor();
        $processor->processLocalPath($absolutePath);

        return $file;
    }

    protected function shouldPreProcessRichEditorAttachmentTemp(): bool
    {
        if ($this->getFlexRichEditorImageVariants() !== []) {
            return true;
        }

        return $this->shouldOptimizeRichEditorImages()
            || $this->getFlexRichEditorMaxImageWidth()
            || $this->getFlexRichEditorMaxImageHeight()
            || $this->getFlexRichEditorMaxImageLongEdge();
    }

    protected function shouldPostProcessRichEditorAttachmentOnDisk(): bool
    {
        if ($this->usesRichEditorFileAttachmentProvider()) {
            return false;
        }

        return $this->shouldProcessRichEditorAttachmentImages();
    }

    protected function resolvePreProcessRichEditorImageProcessor(): FileUploadImageProcessor
    {
        $masterVariant = collect($this->getFlexRichEditorImageVariants())
            ->first(fn (RichEditorImageVariant $variant): bool => $variant->master);

        if ($masterVariant instanceof RichEditorImageVariant) {
            return $masterVariant->toImageProcessor();
        }

        return $this->makeRichEditorImageProcessor(allowWebp: false);
    }

    protected function makeRichEditorImageProcessor(bool $allowWebp): FileUploadImageProcessor
    {
        return new FileUploadImageProcessor(
            optimizeImages: $this->shouldOptimizeRichEditorImages(),
            optimizeImagesToWebp: $allowWebp && $this->shouldOptimizeRichEditorImagesToWebp(),
            maxImageWidth: $this->getFlexRichEditorMaxImageWidth(),
            maxImageHeight: $this->getFlexRichEditorMaxImageHeight(),
            maxImageLongEdge: $this->getFlexRichEditorMaxImageLongEdge(),
            stripExif: $this->shouldStripExifRichEditorAttachments(),
        );
    }

    protected function queueSpatieVariantConversions(mixed $savedFile): void
    {
        if (! $this->usesSpatieRichEditorFileAttachmentProvider()) {
            return;
        }

        if ($this->getFlexRichEditorImageVariants() === []) {
            return;
        }

        if (! class_exists('Spatie\\MediaLibrary\\MediaCollections\\Models\\Media')) {
            return;
        }

        $mediaClass = 'Spatie\\MediaLibrary\\MediaCollections\\Models\\Media';

        $media = is_numeric($savedFile)
            ? $mediaClass::query()->find($savedFile)
            : $mediaClass::query()->where('uuid', (string) $savedFile)->first();

        if ($media === null) {
            return;
        }

        if (method_exists($media, 'refresh')) {
            $media->refresh();
        }
    }
}

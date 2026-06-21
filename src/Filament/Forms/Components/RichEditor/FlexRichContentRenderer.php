<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor;

use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentManifestRepository;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorImageVariant;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorResponsiveSrcsetBuilder;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorSpatieMediaRepository;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\RichEditor\TipTapExtensions\ImageExtension;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tiptap\Core\Extension;
use Tiptap\Editor;

class FlexRichContentRenderer extends RichContentRenderer
{
    protected bool $responsiveImages = false;

    protected bool $lazyImages = true;

    protected string $imageSizes = '100vw';

    /**
     * @var array<string, array<string, mixed>|RichEditorImageVariant>|list<RichEditorImageVariant>
     */
    protected array $imageVariants = [];

    /**
     * @var list<RichEditorImageVariant>|null
     */
    protected ?array $resolvedImageVariants = null;

    protected ?RichEditorAttachmentManifestRepository $attachmentManifestRepository = null;

    public function responsiveImages(bool $condition = true): static
    {
        $this->responsiveImages = $condition;

        return $this;
    }

    public function lazyImages(bool $condition = true): static
    {
        $this->lazyImages = $condition;

        return $this;
    }

    public function imageSizes(string $sizes): static
    {
        $this->imageSizes = $sizes;

        return $this;
    }

    /**
     * @param  array<string, array<string, mixed>|RichEditorImageVariant>|list<RichEditorImageVariant>  $variants
     */
    public function imageVariants(array $variants): static
    {
        $this->imageVariants = $variants;
        $this->resolvedImageVariants = null;

        return $this;
    }

    public function shouldUseResponsiveImages(): bool
    {
        return $this->responsiveImages;
    }

    public function shouldLazyLoadImages(): bool
    {
        return $this->lazyImages;
    }

    public function getImageSizes(): string
    {
        return $this->imageSizes;
    }

    /**
     * @return array<Extension>
     */
    public function getTipTapPhpExtensions(): array
    {
        $extensions = parent::getTipTapPhpExtensions();

        return array_map(
            fn (Extension $extension): Extension => $extension instanceof ImageExtension
                ? app(TipTapExtensions\FlexRichContentImageExtension::class)
                : $extension,
            $extensions,
        );
    }

    public function toHtml(): string
    {
        $unsafeHtml = $this->toUnsafeHtml();
        $placeholders = [];
        $protectedHtml = preg_replace_callback(
            '/<div[^>]*data-youtube-video[^>]*>\s*<iframe\b[^>]*>.*?<\/iframe>\s*<\/div>/is',
            function (array $matches) use (&$placeholders): string {
                $placeholder = '__FFF_YOUTUBE_EMBED_'.count($placeholders).'__';
                $placeholders[$placeholder] = $matches[0];

                return $placeholder;
            },
            $unsafeHtml,
        ) ?? $unsafeHtml;

        $sanitizedHtml = Str::sanitizeHtml($protectedHtml);

        foreach ($placeholders as $placeholder => $embedHtml) {
            $sanitizedHtml = str_replace($placeholder, $embedHtml, $sanitizedHtml);
        }

        return $sanitizedHtml;
    }

    protected function processFileAttachments(Editor $editor): void
    {
        $repository = $this->attachmentManifestRepository();
        $builder = new RichEditorResponsiveSrcsetBuilder;
        $diskName = $this->fileAttachmentsDiskName ?? config('filament.default_filesystem_disk');
        $disk = Storage::disk($diskName);
        $variants = $this->resolveImageVariants();
        $isSpatie = $this->usesSpatieFileAttachmentProvider();

        $editor->descendants(function (object &$node) use ($repository, $builder, $disk, $variants, $isSpatie): void {
            if ($node->type !== 'image') {
                return;
            }

            if (blank($node->attrs->id ?? null)) {
                return;
            }

            $attachmentId = (string) $node->attrs->id;
            $url = $this->getFileAttachmentUrl($attachmentId);

            if ($url === null) {
                return;
            }

            if (! $this->shouldUseResponsiveImages()) {
                $this->applyBasicImageAttributes($node, $url);

                return;
            }

            if ($isSpatie && $variants !== []) {
                $responsive = $this->resolveSpatieResponsiveAttributes($builder, $attachmentId, $url, $variants);

                if ($responsive !== null) {
                    $this->applyResponsiveImageAttributes($node, $url, $responsive);

                    return;
                }

                $this->applyBasicImageAttributes($node, $url);

                return;
            }

            $manifest = $repository->read($disk, $attachmentId);

            if ($manifest === null) {
                $this->applyBasicImageAttributes($node, $url);

                return;
            }

            $responsive = $builder->build(
                manifest: $manifest,
                urlResolver: fn (string $path): ?string => $this->getFileAttachmentUrl($path),
                sizes: $this->getImageSizes(),
                lazy: $this->shouldLazyLoadImages(),
            );

            $this->applyResponsiveImageAttributes($node, $url, $responsive);
        });
    }

    protected function usesSpatieFileAttachmentProvider(): bool
    {
        $provider = $this->getFileAttachmentProvider();

        return $provider !== null
            && str_contains($provider::class, 'SpatieMediaLibrary');
    }

    /**
     * @param  list<RichEditorImageVariant>  $variants
     * @return ?array{src: ?string, srcset: ?string, sizes: ?string, width: ?int}
     */
    protected function resolveSpatieResponsiveAttributes(
        RichEditorResponsiveSrcsetBuilder $builder,
        string $attachmentId,
        string $fallbackUrl,
        array $variants,
    ): ?array {
        $media = app(RichEditorSpatieMediaRepository::class)->findByUuid($attachmentId);

        if ($media === null) {
            return null;
        }

        return $builder->buildFromSpatieMedia(
            media: $media,
            variants: $variants,
            variantUrlResolver: fn (object $media, string $variant): ?string => $this->resolveSpatieVariantUrl($media, $variant),
            fallbackUrl: $fallbackUrl,
            sizes: $this->getImageSizes(),
        );
    }

    protected function resolveSpatieVariantUrl(object $media, string $variant): ?string
    {
        if (is_callable([$media, 'hasGeneratedConversion']) && $media->hasGeneratedConversion($variant)) {
            return $media->getUrl($variant);
        }

        return null;
    }

    /**
     * @return list<RichEditorImageVariant>
     */
    protected function resolveImageVariants(): array
    {
        if ($this->resolvedImageVariants !== null) {
            return $this->resolvedImageVariants;
        }

        if ($this->imageVariants === []) {
            return $this->resolvedImageVariants = [];
        }

        $first = reset($this->imageVariants);

        if ($first instanceof RichEditorImageVariant) {
            return $this->resolvedImageVariants = RichEditorImageVariant::ensureMasterVariant(array_values($this->imageVariants));
        }

        return $this->resolvedImageVariants = RichEditorImageVariant::normalizeCollection($this->imageVariants);
    }

    protected function attachmentManifestRepository(): RichEditorAttachmentManifestRepository
    {
        return $this->attachmentManifestRepository ??= new RichEditorAttachmentManifestRepository;
    }

    /**
     * @param  array{src: ?string, srcset: ?string, sizes: ?string, width: ?int}  $responsive
     */
    protected function applyResponsiveImageAttributes(object $node, string $fallbackUrl, array $responsive): void
    {
        $node->attrs->src = $responsive['src'] ?? $fallbackUrl;

        if (filled($responsive['srcset'] ?? null)) {
            $node->attrs->srcset = $responsive['srcset'];
        }

        if (filled($responsive['sizes'] ?? null)) {
            $node->attrs->sizes = $responsive['sizes'];
        }

        if (filled($responsive['width'] ?? null)) {
            $node->attrs->width = $responsive['width'];
        }

        if ($this->shouldLazyLoadImages()) {
            $node->attrs->loading = 'lazy';
            $node->attrs->decoding = 'async';
        }
    }

    protected function applyBasicImageAttributes(object $node, string $url): void
    {
        $node->attrs->src = $url;

        if ($this->shouldLazyLoadImages()) {
            $node->attrs->loading = 'lazy';
            $node->attrs->decoding = 'async';
        }
    }
}

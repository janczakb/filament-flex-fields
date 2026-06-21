<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichContentRenderer;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentManifest;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentVariantGenerator;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorImageVariant;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorResponsiveSrcsetBuilder;
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorSpatieMediaRepository;
use Bjanczak\FilamentFlexFields\Tests\Support\FakeRichEditorSpatieMediaRepository;
use Bjanczak\FilamentFlexFields\Tests\Support\SpatieMediaLibraryRichEditorTestFileAttachmentProvider;
use Illuminate\Support\Facades\Storage;

function createFlexRichContentRendererTestJpeg(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);

    ob_start();
    imagejpeg($image);
    $contents = ob_get_clean();
    imagedestroy($image);

    return $contents ?: '';
}

it('exposes content limit and enhancement toggles on flex rich editor', function () {
    $field = FlexRichEditor::make('body')
        ->minCharacters(10)
        ->maxCharacters(500)
        ->maxWords(100)
        ->limitBehavior('hard')
        ->readingTime()
        ->responsiveImages()
        ->lazyImages(false)
        ->altTextRequired()
        ->fullscreen()
        ->distractionFree()
        ->autosave(45, 'draft-key')
        ->pasteCleanup('aggressive')
        ->disabledTools(['strike']);

    expect($field->getMinCharacters())->toBe(10)
        ->and($field->getMaxCharacters())->toBe(500)
        ->and($field->getMaxWords())->toBe(100)
        ->and($field->getLimitBehavior())->toBe('hard')
        ->and($field->shouldShowReadingTime())->toBeTrue()
        ->and($field->shouldUseResponsiveRichEditorImages())->toBeTrue()
        ->and($field->shouldLazyLoadRichEditorImages())->toBeFalse()
        ->and($field->shouldRequireRichEditorAltText())->toBeTrue()
        ->and($field->shouldEnableRichEditorFullscreen())->toBeTrue()
        ->and($field->shouldEnableRichEditorDistractionFree())->toBeTrue()
        ->and($field->shouldAutosaveRichEditor())->toBeTrue()
        ->and($field->getRichEditorAutosaveIntervalSeconds())->toBe(45)
        ->and($field->getRichEditorAutosaveKey())->toBe('draft-key')
        ->and($field->getPasteCleanupMode())->toBe('aggressive')
        ->and($field->getDisabledRichEditorTools())->toBe(['strike'])
        ->and($field->shouldShowRichEditorFooter())->toBeTrue();
});

it('auto enables responsive images when image variants are configured', function () {
    $field = FlexRichEditor::make('body')
        ->imageVariants([
            'thumb' => ['max_long_edge' => 320, 'webp' => true],
        ]);

    expect($field->shouldUseResponsiveRichEditorImages())->toBeTrue();
});

it('registers toolbar role presets from config', function () {
    $buttons = FlexRichEditor::make('body')->getToolbarButtonsForRole('author');

    expect($buttons)->toBeArray()
        ->and(collect($buttons)->flatten()->all())->toContain('bold', 'attachFiles')
        ->not->toContain('clearContent', 'h1');
});

it('filters disabled tools from the resolved toolbar', function () {
    $field = FlexRichEditor::make('body')
        ->disabledTools(['strike', 'code']);

    $filtered = (new ReflectionClass($field))
        ->getMethod('filterDisabledRichEditorToolbarButtons')
        ->invoke($field, $field->getFlexDefaultToolbarButtons());

    $buttons = collect($filtered)->flatten()->all();

    expect($buttons)->not->toContain('strike', 'code');
});

it('adds flex length validation rules for characters and words', function () {
    $field = FlexRichEditor::make('body')
        ->maxCharacters(5)
        ->maxWords(2);

    $rules = $field->getLengthValidationRules();

    expect($rules)->not->toBeEmpty();
});

it('writes image widths into attachment manifests', function () {
    Storage::fake('public');

    $path = 'rich-editor/photo.jpg';
    Storage::disk('public')->put($path, createFlexRichContentRendererTestJpeg(800, 600));

    $manifest = (new RichEditorAttachmentVariantGenerator)->generate(
        Storage::disk('public'),
        $path,
        RichEditorImageVariant::normalizeCollection([
            'thumb' => ['max_long_edge' => 100, 'webp' => true],
            'large' => ['max_long_edge' => 400, 'master' => true],
        ]),
    );

    expect($manifest->widths)->toHaveKey('master')
        ->and($manifest->widths['master'])->toBeGreaterThan(0)
        ->and($manifest->widths)->toHaveKey('thumb');

    $payload = json_decode((string) Storage::disk('public')->get($manifest->manifestPath()), true);

    expect($payload)->toHaveKey('widths')
        ->and($payload['widths']['master'])->toBeInt();
});

it('builds responsive srcset descriptors from manifest widths', function () {
    $manifest = new RichEditorAttachmentManifest(
        master: 'rich-editor/photo-large.webp',
        variants: [
            'thumb' => 'rich-editor/photo__thumb.webp',
            'large' => 'rich-editor/photo__large.webp',
        ],
        widths: [
            'master' => 400,
            'thumb' => 100,
            'large' => 400,
        ],
    );

    $builder = new RichEditorResponsiveSrcsetBuilder;

    $result = $builder->build(
        manifest: $manifest,
        urlResolver: fn (string $path): string => "https://example.test/{$path}",
        sizes: '100vw',
        lazy: true,
    );

    expect($result['src'])->toBe('https://example.test/rich-editor/photo__large.webp')
        ->and($result['srcset'])->toContain('100w')
        ->and($result['srcset'])->toContain('400w')
        ->and($result['sizes'])->toBe('100vw');
});

it('builds responsive srcset descriptors from spatie media conversions', function () {
    $variants = RichEditorImageVariant::normalizeCollection([
        'thumb' => ['max_long_edge' => 320, 'webp' => true],
        'large' => ['max_long_edge' => 1200, 'master' => true],
    ]);

    $media = Mockery::mock();
    $media->shouldReceive('hasGeneratedConversion')
        ->with('thumb')
        ->andReturn(true);
    $media->shouldReceive('getUrl')
        ->with('thumb')
        ->andReturn('https://example.test/media/conversions/uuid-thumb.webp');

    $builder = new RichEditorResponsiveSrcsetBuilder;

    $result = $builder->buildFromSpatieMedia(
        media: $media,
        variants: $variants,
        variantUrlResolver: fn (object $media, string $variant): ?string => $media->getUrl($variant),
        fallbackUrl: 'https://example.test/media/uuid.jpg',
        sizes: '(max-width: 768px) 100vw, 50vw',
    );

    expect($result['src'])->toBe('https://example.test/media/uuid.jpg')
        ->and($result['srcset'])->toContain('320w')
        ->and($result['srcset'])->toContain('1200w')
        ->and($result['srcset'])->toContain('uuid-thumb.webp')
        ->and($result['sizes'])->toBe('(max-width: 768px) 100vw, 50vw')
        ->and($result['width'])->toBe(1200);
});

it('renders responsive spatie media images through flex rich content renderer', function () {
    $mediaUuid = '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d';

    $media = Mockery::mock();
    $media->shouldReceive('hasGeneratedConversion')
        ->with('thumb')
        ->andReturn(true);
    $media->shouldReceive('getUrl')
        ->with('thumb')
        ->andReturn('https://example.test/media/conversions/'.$mediaUuid.'-thumb.webp');

    app()->instance(
        RichEditorSpatieMediaRepository::class,
        (new FakeRichEditorSpatieMediaRepository)->seed($mediaUuid, $media),
    );

    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'image',
                        'attrs' => [
                            'id' => $mediaUuid,
                            'alt' => 'Spatie image',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $html = FlexRichContentRenderer::make($content)
        ->fileAttachmentProvider(new SpatieMediaLibraryRichEditorTestFileAttachmentProvider)
        ->responsiveImages()
        ->lazyImages()
        ->imageSizes('100vw')
        ->imageVariants(RichEditorImageVariant::normalizeCollection([
            'thumb' => ['max_long_edge' => 320, 'webp' => true],
            'large' => ['max_long_edge' => 1200, 'master' => true],
        ]))
        ->toHtml();

    expect($html)
        ->toContain('srcset=')
        ->toContain('sizes="100vw"')
        ->toContain('320w')
        ->toContain('1200w')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"');
});

it('falls back to a single spatie image url when conversions are unavailable', function () {
    $mediaUuid = '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6e';

    $media = Mockery::mock();
    $media->shouldReceive('hasGeneratedConversion')
        ->with('thumb')
        ->andReturn(false);

    app()->instance(
        RichEditorSpatieMediaRepository::class,
        (new FakeRichEditorSpatieMediaRepository)->seed($mediaUuid, $media),
    );

    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'image',
                        'attrs' => [
                            'id' => $mediaUuid,
                        ],
                    ],
                ],
            ],
        ],
    ];

    $html = FlexRichContentRenderer::make($content)
        ->fileAttachmentProvider(new SpatieMediaLibraryRichEditorTestFileAttachmentProvider)
        ->responsiveImages()
        ->imageVariants(RichEditorImageVariant::normalizeCollection([
            'thumb' => ['max_long_edge' => 320, 'webp' => true],
            'large' => ['max_long_edge' => 1200, 'master' => true],
        ]))
        ->toHtml();

    expect($html)
        ->toContain('src="https://example.test/media/'.$mediaUuid.'.jpg"')
        ->not->toContain('srcset=');
});
it('renders responsive lazy loaded images through flex rich content renderer', function () {
    Storage::fake('public');

    $master = 'rich-editor/photo-large.webp';
    Storage::disk('public')->put($master, createFlexRichContentRendererTestJpeg(400, 300));
    Storage::disk('public')->put('rich-editor/photo__thumb.webp', createFlexRichContentRendererTestJpeg(100, 75));

    Storage::disk('public')->put('rich-editor/photo-large.flex-variants.json', json_encode([
        'master' => $master,
        'variants' => [
            'thumb' => 'rich-editor/photo__thumb.webp',
        ],
        'widths' => [
            'master' => 400,
            'thumb' => 100,
        ],
    ]));

    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'image',
                        'attrs' => [
                            'id' => $master,
                            'alt' => 'Sample image',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $html = FlexRichContentRenderer::make($content)
        ->fileAttachmentsDisk('public')
        ->responsiveImages()
        ->lazyImages()
        ->imageSizes('100vw')
        ->toHtml();

    expect($html)
        ->toContain('srcset=')
        ->toContain('sizes="100vw"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"');
});

it('creates a flex rich content renderer from the field helper', function () {
    $field = FlexRichEditor::make('body')
        ->responsiveImages()
        ->lazyImages(false)
        ->imageSizes('(max-width: 768px) 100vw, 50vw')
        ->imageVariants([
            'thumb' => ['max_long_edge' => 320, 'webp' => true],
            'large' => ['max_long_edge' => 1200, 'master' => true],
        ]);

    $renderer = $field->makeFlexRichContentRenderer([
        'type' => 'doc',
        'content' => [],
    ])->fileAttachmentsDisk('public');

    expect($renderer)->toBeInstanceOf(FlexRichContentRenderer::class)
        ->and($renderer->shouldUseResponsiveImages())->toBeTrue()
        ->and($renderer->shouldLazyLoadImages())->toBeFalse()
        ->and($renderer->getImageSizes())->toBe('(max-width: 768px) 100vw, 50vw');
});

it('registers youtube plugin assets when enabled', function () {
    expect(
        Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins\FlexRichEditorYoutubePlugin::make(
            FlexRichEditor::make('body')->youtube(),
        )->getTipTapJsExtensions(),
    )->not->toBeEmpty()
        ->and(file_exists(__DIR__.'/../../resources/dist/support/flex-rich-editor-youtube-extension.js'))->toBeTrue();
});

it('registers block image plugin by default', function () {
    expect(
        Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins\FlexRichEditorBlockImagePlugin::make()->getTipTapJsExtensions(),
    )->not->toBeEmpty()
        ->and(file_exists(__DIR__.'/../../resources/dist/support/flex-rich-editor-block-image-extension.js'))->toBeTrue();
});

it('registers paste cleanup plugin lazily when enabled', function () {
    $field = FlexRichEditor::make('body')->pasteCleanup('aggressive');

    expect($field->shouldEnablePasteCleanup())->toBeTrue()
        ->and($field->getPasteCleanupMode())->toBe('aggressive')
        ->and(Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins\FlexRichEditorPasteCleanupPlugin::make('aggressive')->getTipTapJsExtensions())
        ->not->toBeEmpty();
});

it('renders flex rich editor blade with enhancement assets', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-rich-editor-field.blade.php');

    expect($blade)
        ->toContain('flex-rich-editor')
        ->toContain('flexRichEditorFormComponent')
        ->toContain('fff-rich-editor--fullscreen')
        ->toContain('fff-rich-editor__footer-stats--warning')
        ->toContain('fff-rich-editor__footer-autosave')
        ->toContain('autosaveStatus')
        ->not->toContain('x-effect="editorUpdatedAt; scheduleRichEditorChromeSync()"')
        ->toContain('aria-orientation="horizontal"')
        ->toContain('x-ref="toolbar"')
        ->toContain('role="textbox"')
        ->toContain('aria-live="polite"');
});

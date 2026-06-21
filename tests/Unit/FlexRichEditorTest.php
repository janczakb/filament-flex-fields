<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexToolbarButtonGroup;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichEditorTool;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\Plugins\FlexRichEditorYoutubePlugin;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexRichEditorPlayground;
use Bjanczak\FilamentFlexFields\Support\RichEditorGravityIcons;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

function createFlexRichEditorTestJpeg(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);

    ob_start();
    imagejpeg($image);
    $contents = ob_get_clean();
    imagedestroy($image);

    return $contents ?: '';
}

it('extends filament rich editor with flex defaults', function () {
    $field = FlexRichEditor::make('body');

    expect($field)
        ->toBeInstanceOf(FlexRichEditor::class)
        ->and($field->isJson())->toBeTrue()
        ->and($field->getFlexDefaultFloatingToolbars())->toHaveKey('paragraph')
        ->and($field->getFlexDefaultFloatingToolbars())->not->toHaveKey('image')
        ->and($field->getWrapperClasses())->toContain('fff-rich-editor-field--secondary')
        ->and($field->shouldShowJsonBadge())->toBeFalse();
});

it('enables chrome sync only when footer metrics or autosave are configured', function () {
    expect(FlexRichEditor::make('body')->shouldEnableRichEditorChromeSync())->toBeFalse()
        ->and(FlexRichEditor::make('body')->wordCount()->shouldEnableRichEditorChromeSync())->toBeTrue()
        ->and(FlexRichEditor::make('body')->autosave(30)->shouldEnableRichEditorChromeSync())->toBeTrue();
});

it('registers youtube toolbar tool without evaluating editor before initialization', function () {
    $field = FlexRichEditor::make('body')->youtube();

    $pluginTools = FlexRichEditorYoutubePlugin::make($field)->getEditorTools();

    expect($pluginTools)->toHaveCount(1)
        ->and($pluginTools[0]->getName())->toBe('youtube');

    $parentTool = RichEditorTool::make('youtube')->action(arguments: '{}');
    $flexTool = FlexRichEditorTool::fromParentTool($parentTool);

    $jsHandler = (new ReflectionProperty(RichEditorTool::class, 'jsHandler'))->getValue($flexTool);

    expect($jsHandler)->toBeInstanceOf(Closure::class);
});

it('exposes word count and json badge toggles', function () {
    $field = FlexRichEditor::make('body')
        ->wordCount()
        ->jsonBadge(false);

    expect($field->shouldShowWordCount())->toBeTrue()
        ->and($field->shouldShowJsonBadge())->toBeFalse()
        ->and($field->getJsonBadgeLabel())->toBe(__('filament-flex-fields::default.field_types.json'));
});

it('registers grouped toolbar buttons in default layout', function () {
    $groups = FlexRichEditor::make('body')->getFlexDefaultToolbarButtons();

    expect(collect($groups)->flatten()->all())->toContain('clearFormatting', 'clearContent')
        ->not->toContain('attachFiles', 'textColor', 'customBlocks', 'mergeTags')
        ->and($groups[2][0])->toBeInstanceOf(FlexToolbarButtonGroup::class)
        ->and($groups[3][0])->toBeInstanceOf(FlexToolbarButtonGroup::class)
        ->and($groups[5][0])->toBeInstanceOf(FlexToolbarButtonGroup::class);
});

it('exposes a full toolbar preset with attachments', function () {
    $groups = FlexRichEditor::make('body')->getFlexFullToolbarButtons();

    expect(collect($groups)->flatten()->all())->toContain('attachFiles')
        ->not->toContain('textColor', 'customBlocks', 'mergeTags');
});

it('registers gravity icons only for flex rich editor aliases', function () {
    RichEditorGravityIcons::register();

    $nativeBold = FilamentIcon::resolve('forms:components.rich-editor.toolbar.bold');
    $nativeBoldName = is_string($nativeBold) ? $nativeBold : (string) $nativeBold;

    expect(FilamentIcon::resolve('filament-flex-fields::flex-rich-editor.toolbar.clear-formatting'))->toBe('gravityui-eraser')
        ->and(FilamentIcon::resolve('filament-flex-fields::flex-rich-editor.toolbar.clear-content'))->toBe('gravityui-trash-bin')
        ->and($nativeBoldName)->not->toStartWith('gravityui-');
});

it('applies gravity icons to flex extra tools and maps flex toolbar icons', function () {
    $extraTools = FlexRichEditor::getFlexExtraTools();

    expect($extraTools[0]->getIcon())->toBe('gravityui-eraser')
        ->and($extraTools[1]->getIcon())->toBe('gravityui-trash-bin')
        ->and(RichEditorGravityIcons::iconForToolName('bold'))->toBe('gravityui-bold');
});

it('keeps native comparison extra tools without gravity icons', function () {
    $tools = FlexRichEditor::getNativeExtraTools();

    expect($tools)->toHaveCount(1)
        ->and($tools[0]->getName())->toBe('clearContent')
        ->and($tools[0]->getIcon())->toBeInstanceOf(Heroicon::class);
});

it('applies gravity icons to toolbar tools', function () {
    expect(RichEditorGravityIcons::iconForToolName('undo'))->toBe('gravityui-arrow-rotate-left')
        ->and(RichEditorGravityIcons::iconForToolName('redo'))->toBe('gravityui-arrow-rotate-right')
        ->and(RichEditorGravityIcons::iconForToolName('bold'))->toBe('gravityui-bold')
        ->and(RichEditorGravityIcons::iconForToolName('h1'))->toBe('gravityui-heading-1')
        ->and(RichEditorGravityIcons::iconForToolName('blockquote'))->toBe('gravityui-quote-close')
        ->and(RichEditorGravityIcons::iconForToolName('textColor'))->toBe('gravityui-palette')
        ->and(RichEditorGravityIcons::iconForToolName('attachFiles'))->toBe('gravityui-picture')
        ->and(RichEditorGravityIcons::iconForToolName('alignCenter'))->toBe('gravityui-bars-ascending-align-center');
});

it('uses the flex rich editor tooltip theme without filament arrow', function () {
    $toolSource = file_get_contents(__DIR__.'/../../src/Filament/Forms/Components/RichEditor/FlexRichEditorTool.php');
    $groupSource = file_get_contents(__DIR__.'/../../src/Filament/Forms/Components/RichEditor/FlexToolbarButtonGroup.php');

    expect($toolSource)
        ->toContain("theme: \\'fff-rich-editor\\'")
        ->toContain('arrow: false')
        ->not->toContain('$store.theme');

    expect($groupSource)
        ->toContain("theme: \\'fff-rich-editor\\'")
        ->toContain('arrow: false');
});

it('positions flex toolbar dropdown menus with fixed coordinates', function () {
    $source = file_get_contents(__DIR__.'/../../src/Filament/Forms/Components/RichEditor/FlexToolbarButtonGroup.php');

    expect($source)
        ->toContain('menuStyle')
        ->toContain(':style="menuStyle"')
        ->toContain('getBoundingClientRect()')
        ->toContain('position: fixed')
        ->toContain('requestAnimationFrame(reposition)')
        ->toContain('toolbar.addEventListener(\'scroll\'')
        ->toContain('fff-rich-editor__toolbar-dropdown-menu');
});

it('renders flex rich editor blade with filament alpine runtime', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-rich-editor-field.blade.php');

    expect($blade)
        ->toContain('fff-rich-editor')
        ->toContain('fi-fo-rich-editor')
        ->toContain('fi-fo-rich-editor-main')
        ->toContain('fi-fo-rich-editor-content')
        ->toContain('flexRichEditorFormComponent')
        ->toContain("getAlpineComponentSrc('flex-rich-editor'")
        ->toContain("getAlpineComponentSrc('rich-editor', 'filament/forms')")
        ->toContain('richEditorSrc:')
        ->toContain('flex-rich-editor-runtime-preload')
        ->toContain('x-intersect:enter.once.margin.300px')
        ->toContain('floatingToolbar::{{ $nodeName }}')
        ->toContain('fff-rich-editor__footer-stats')
        ->toContain('fff-rich-editor__bubble-menu')
        ->toContain('fff-rich-editor__image-overlay')
        ->toContain('fi-fo-rich-editor-dropdown-tool-trigger')
        ->toContain('fff-rich-editor__toolbar-separator')
        ->not->toContain('fff-rich-editor-toolbar-scroll')
        ->toContain('FormsIconAlias::')
        ->not->toContain('fi-fo-rich-editor-custom-blocks-ctn')
        ->not->toContain('fi-fo-rich-editor-merge-tags-list')
        ->not->toContain('<script')
        ->not->toContain('rich-editor-tooltips')
        ->not->toContain('richEditorFieldEnhancements')
        ->not->toContain('@tiptap/core');
});

it('declares rich editor field lazy stylesheet bundle', function () {
    expect(FlexFieldAssets::stylesheetsFor('rich-editor-field'))->toBe(['rich-editor-field']);
});

it('keeps the rich editor field bundle under the gzip budget', function () {
    $path = __DIR__.'/../../resources/dist/css/rich-editor-field.css';
    $gzipBytes = strlen((string) gzencode((string) file_get_contents($path), 9));
    $gzipKb = $gzipBytes / 1024;

    expect($gzipKb)->toBeLessThan(12);
});

it('keeps the flex rich editor javascript bundle under the gzip budget', function () {
    $path = __DIR__.'/../../resources/dist/components/flex-rich-editor.js';
    $gzipBytes = strlen((string) gzencode((string) file_get_contents($path), 9));
    $gzipKb = $gzipBytes / 1024;

    expect($gzipKb)->toBeLessThan(12);
});

it('ships flex rich editor as a thin shell without bundling filament tip tap runtime', function () {
    $path = __DIR__.'/../../resources/dist/components/flex-rich-editor.js';
    $source = (string) file_get_contents($path);
    $rawKb = strlen($source) / 1024;

    expect($rawKb)->toBeLessThan(35)
        ->and($source)->not->toContain('@tiptap')
        ->toContain('import(');
});

it('loads stylesheet via blade partial', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-rich-editor-field.blade.php');

    expect($blade)->toContain("load-stylesheet', ['component' => 'rich-editor-field']");
});

it('registers flex-only clear tools for shared toolbars', function () {
    $tools = FlexRichEditor::getFlexExtraTools();

    expect($tools)->toHaveCount(2)
        ->and(collect($tools)->map(fn ($tool) => $tool->getName())->all())->toBe(['clearFormatting', 'clearContent']);
});

it('registers flex rich editor playground sections', function () {
    $playground = app(FlexRichEditorPlayground::class);
    $components = $playground->components();
    $nativeToolbar = FlexRichEditor::make('_native')->getNativeComparisonToolbarButtons();

    expect($components)->toHaveCount(4)
        ->and($playground->defaultState())->toHaveKeys([
            'filament_rich_editor__native',
            'flex_rich_editor__basic',
            'flex_rich_editor__attachments',
            'flex_rich_editor__full_toolbar',
        ])
        ->and($nativeToolbar[2][0])->toBeInstanceOf(ToolbarButtonGroup::class)
        ->and($nativeToolbar[2][0])->not->toBeInstanceOf(FlexToolbarButtonGroup::class);
});

it('uses root css tokens in rich editor field bundle', function () {
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/rich-editor-field.css');

    expect($css)
        ->toContain('var(--fff-rich-editor-text)')
        ->toContain('var(--fff-rich-editor-bubble-shadow)')
        ->toContain('--fff-rich-editor-text:#fafafa');
});

it('defines reference toolbar and bubble sizing tokens', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/components/rich-editor-field.css');
    $tokens = file_get_contents(__DIR__.'/../../resources/css/base.css');

    expect($css)
        ->toContain('invisible absolute')
        ->toContain('rounded-full')
        ->toContain('--fff-rich-editor-icon-size: 1.25rem')
        ->toContain('var(--fff-rich-editor-bubble-radius)')
        ->toContain('var(--fff-rich-editor-tool-bg-hover)')
        ->toContain('border: 0')
        ->toContain('flex-nowrap')
        ->toContain('.fff-rich-editor__toolbar-group')
        ->toContain('.fff-rich-editor__toolbar-button--distraction-free-hidden')
        ->toContain('gap: 4px')
        ->toContain('overflow-y: visible')
        ->toContain('--fff-rich-editor-tooltip-z: 20')
        ->toContain('overflow-x-auto')
        ->toContain('overflow: hidden')
        ->toContain('border-radius: inherit')
        ->toContain('var(--fff-rich-editor-border')
        ->toContain('z-[30]')
        ->toContain('--fff-rich-editor-radius: 1rem')
        ->toContain('.fi-fo-rich-editor-dropdown-tool-menu[x-cloak]')
        ->toContain('var(--fff-rich-editor-bubble-radius)')
        ->toContain('var(--fff-rich-editor-bubble-shadow)')
        ->toContain('fi-fo-rich-editor-dropdown-tool-chevron');

    expect($css)->not->toContain('.fff-rich-editor__panels')
        ->not->toContain('.fff-rich-editor-custom-block-preview')
        ->not->toContain('.fi-fo-rich-editor-custom-block-btn');

    expect($tokens)
        ->toContain('--fff-rich-editor-bg: var(--fff-field-secondary-bg)')
        ->toContain('--fff-rich-editor-bubble-bg: rgb(255 255 255)')
        ->toContain('--fff-rich-editor-tool-bg-hover: #ebebec')
        ->toContain('--fff-rich-editor-toolbar-divider: rgb(212 212 216)')
        ->toContain('--fff-rich-editor-border: var(--fff-field-secondary-border)');

    expect($css)
        ->not->toContain('body:has(.fff-rich-editor button:hover)')
        ->toContain("[data-theme~='fff-rich-editor']")
        ->toContain('display: none !important');

    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/flex-rich-editor-field.blade.php');

    expect($blade)
        ->toContain('x-on:click.capture')
        ->not->toContain('fff-rich-editor-toolbar-scroll')
        ->not->toContain('x-show="! isDistractionFreeToolbarButton')
        ->toContain('fff-rich-editor__toolbar-button--distraction-free-hidden')
        ->toContain('fi-fo-rich-editor-dropdown-tool-trigger')
        ->toContain('x-ref="floatingToolbar::{{ $nodeName }}"');
});

it('exposes rich editor attachment optimization and restriction helpers', function () {
    $field = FlexRichEditor::make('body')
        ->imagesOnly()
        ->maxAttachmentSizeKb(2048)
        ->maxImageLongEdge(2000)
        ->optimizeImages()
        ->optimizeImagesToWebp(true);

    expect($field->getFileAttachmentsAcceptedFileTypes())->toBe(['image/*'])
        ->and($field->getFileAttachmentsMaxSize())->toBe(2048)
        ->and($field->getFlexRichEditorMaxImageLongEdge())->toBe(2000)
        ->and($field->shouldOptimizeRichEditorImages())->toBeTrue()
        ->and($field->shouldOptimizeRichEditorImagesToWebp())->toBeTrue()
        ->and($field->shouldProcessRichEditorAttachmentImages())->toBeTrue()
        ->and($field->usesRichEditorFileAttachmentProvider())->toBeFalse();
});

it('builds and applies a rich editor image processor from field options', function () {
    Storage::fake('public');

    $path = 'rich-editor/photo.jpg';
    Storage::disk('public')->put($path, createFlexRichEditorTestJpeg(400, 200));

    $field = FlexRichEditor::make('body')
        ->maxImageLongEdge(100)
        ->optimizeImages();

    $reflection = new ReflectionClass($field);
    $method = $reflection->getMethod('makeRichEditorImageProcessor');
    $method->setAccessible(true);

    /** @var Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadImageProcessor $processor */
    $processor = $method->invoke($field, true);
    $processedPath = $processor->process(Storage::disk('public'), $path);

    Storage::disk('public')->assertExists($processedPath);

    [$width, $height] = getimagesize(Storage::disk('public')->path($processedPath));

    expect(max($width, $height))->toBeLessThanOrEqual(100);
});

it('resolves max long edge dimensions only when the image exceeds the limit', function () {
    $processor = new Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadImageProcessor(
        maxImageLongEdge: 200,
    );

    $reflection = new ReflectionClass($processor);
    $method = $reflection->getMethod('resolveTargetDimensions');
    $method->setAccessible(true);

    expect($method->invoke($processor, 400, 200))->toBe([200, 100])
        ->and($method->invoke($processor, 200, 400))->toBe([100, 200])
        ->and($method->invoke($processor, 120, 80))->toBe([120, 80]);
});

it('normalizes rich editor image variants and auto-selects a master variant', function () {
    $variants = Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorImageVariant::normalizeCollection([
        'thumb' => ['max_long_edge' => 320, 'webp' => true],
        'large' => ['max_long_edge' => 2000, 'webp' => true],
    ]);

    expect($variants)->toHaveCount(2)
        ->and(collect($variants)->firstWhere('name', 'large')?->master)->toBeTrue()
        ->and(collect($variants)->firstWhere('name', 'thumb')?->master)->toBeFalse();
});

it('generates named attachment variants and writes a manifest on disk', function () {
    Storage::fake('public');

    $path = 'rich-editor/photo.jpg';
    Storage::disk('public')->put($path, createFlexRichEditorTestJpeg(800, 600));

    $manifest = (new Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentVariantGenerator)->generate(
        Storage::disk('public'),
        $path,
        Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorImageVariant::normalizeCollection([
            'thumb' => ['max_long_edge' => 100, 'webp' => true],
            'large' => ['max_long_edge' => 400, 'master' => true],
        ]),
    );

    expect($manifest->master)->toEndWith('.jpg')
        ->and($manifest->variants)->toHaveKey('thumb')
        ->and($manifest->widths)->toHaveKey('master')
        ->and(Storage::disk('public')->exists($manifest->variants['thumb']))->toBeTrue()
        ->and(Storage::disk('public')->exists($manifest->manifestPath()))->toBeTrue();
});

it('resolves image ids from rich editor json content', function () {
    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'image',
                        'attrs' => [
                            'id' => 'rich-editor/photo.webp',
                            'src' => 'https://example.test/photo.webp',
                        ],
                    ],
                ],
            ],
        ],
    ];

    expect(Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentIdResolver::fromContent($content))
        ->toBe(['rich-editor/photo.webp']);
});

it('deletes removed rich editor attachments with all generated variants', function () {
    Storage::fake('public');

    $path = 'rich-editor/photo.jpg';
    Storage::disk('public')->put($path, createFlexRichEditorTestJpeg(800, 600));

    $manifest = (new Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentVariantGenerator)->generate(
        Storage::disk('public'),
        $path,
        Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorImageVariant::normalizeCollection([
            'thumb' => ['max_long_edge' => 100, 'webp' => true],
            'large' => ['max_long_edge' => 400, 'master' => true],
        ]),
    );

    (new Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorAttachmentPruner)->deleteMastersWithVariants(
        Storage::disk('public'),
        [$manifest->master],
    );

    foreach ($manifest->allPaths() as $storedPath) {
        Storage::disk('public')->assertMissing($storedPath);
    }

    Storage::disk('public')->assertMissing($manifest->manifestPath());
});

it('enables orphan attachment pruning by default on flex rich editor', function () {
    expect(FlexRichEditor::make('body')->shouldPruneOrphanedRichEditorAttachments())->toBeTrue();
});

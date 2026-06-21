---
title: "FlexRichEditor"
---

[← Back to Table of Contents](/docs/index)

### Summary

**JSON-first** rich text editor built on Filament `RichEditor` / TipTap — premium shell, Gravity icons, content limits with live feedback, responsive image pipeline, optional Spatie Media Library, YouTube embeds, block images, editor image overlay, fullscreen, autosave, paste cleanup, and keyboard-accessible toolbar.

> **Scope of this doc:** only **package-specific** APIs (`variant()`, `imageVariants()`, `youtube()`, toolbar role presets, `FlexRichContentRenderer`, etc.). Everything else comes from Filament `RichEditor` — see [Filament Rich Editor](https://filamentphp.com/docs/forms/rich-editor).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor` |
| **Extends** | `Filament\Forms\Components\RichEditor` |
| **State type** | `array\|null` (TipTap JSON document) |
| **Renderer** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichContentRenderer` |
| **FieldType** | `flex_rich_editor` |

---

## Quick start

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;

FlexRichEditor::make('body')
    ->label('Article body')
    ->json()
    ->wordCount()
    ->columnSpanFull();
```

Content is stored as a TipTap JSON document. Render HTML on the frontend with `makeFlexRichContentRenderer()` (see [Rendering HTML](#rendering-html)).

---

## Configuration catalog (package API)

FlexRichEditor-specific options only. The field extends Filament `RichEditor`; inherited Filament APIs are not duplicated here.

### Shell & field chrome

| Method | Type / values | Default | Example section |
|--------|---------------|---------|-----------------|
| `variant()` | `primary`, `secondary`, `soft`, `flat` | `secondary` | [Variants](#shell-variants) |
| `size()` | `sm`, `md`, `lg` | `md` | [Sizes](#shell-sizes) |
| `focusOutline()` | `bool` | `false` | [Focus outline](#focus-outline) |
| `wordCount()` | `bool` | `false` | [Word count](#word-count) |
| `jsonBadge()` | `bool` | `false` | [JSON badge](#json-debug-badge) |

### Content limits & footer

| Method | Type / values | Default | Example section |
|--------|---------------|---------|-----------------|
| `minCharacters()` | `int` | — | [Limits](#content-limits) |
| `maxCharacters()` | `int` | — | [Limits](#content-limits) |
| `maxWords()` | `int` | — | [Limits](#content-limits) |
| `limitBehavior()` | `soft`, `hard` | `soft` | [Limit behavior](#limit-behavior-soft-vs-hard) |
| `readingTime()` | `bool`, optional WPM | `false`, 200 wpm | [Reading time](#reading-time) |
| `altTextRequired()` | `bool` | `false` | [Alt text](#alt-text-required) |

### Editor UX

| Method | Type / values | Default | Example section |
|--------|---------------|---------|-----------------|
| `fullscreen()` | `bool` | `false` | [Fullscreen](#fullscreen) |
| `distractionFree()` | `bool` | `false` | [Distraction-free](#distraction-free) |
| `autosave()` | `int` seconds, optional key | off | [Autosave](#autosave) |
| `pasteCleanup()` | `false`, `true`/`standard`, `aggressive` | off | [Paste cleanup](#paste-cleanup) |
| `youtube()` | `bool` | `false` | [YouTube](#youtube-embeds) |
| `youtubeNocookie()` | `bool` | `true` when YouTube on | [YouTube](#youtube-embeds) |
| `youtubeWidth()` | `int` px | `640` | [YouTube](#youtube-embeds) |
| `youtubeHeight()` | `int` px | `480` | [YouTube](#youtube-embeds) |

### Toolbar

| Method | Type / values | Default | Example section |
|--------|---------------|---------|-----------------|
| `toolbarForRole()` | `author`, `editor`, `admin` | — | [Toolbar roles](#toolbar-roles) |
| `toolbarForAuthor()` / `Editor()` / `Admin()` | shortcuts | — | [Toolbar roles](#toolbar-roles) |
| `disabledTools()` | `list<string>` | `[]` | [Disabled tools](#disabled-toolbar-tools) |

Static helpers (presets): `getFlexDefaultToolbarButtons()`, `getFlexFullToolbarButtons()`, `getFlexDefaultFloatingToolbars()`, `getFlexExtraTools()`, `getNativeComparisonToolbarButtons()`.

To override button layout, use Filament’s `toolbarButtons()` / `floatingToolbars()` / `tools()` with these presets — see [Filament Rich Editor](https://filamentphp.com/docs/forms/rich-editor).

### File attachments & images (package layer)

Requires Filament `fileAttachments()` / disk options to be enabled first. This table lists **Flex** processing and cleanup on top of that baseline.

| Method | Type / values | Default | Example section |
|--------|---------------|---------|-----------------|
| `scopedAttachmentDirectory()` | string prefix | — | [Scoped directories](#scoped-attachment-directories) |
| `imagesOnly()` | — | — | [MIME preset](#images-only) |
| `maxAttachmentSizeKb()` | int KB | Filament default | [Max upload size](#max-attachment-size) |
| `optimizeImages()` | `bool` | `false` | [Single-pass optimization](#single-pass-image-optimization) |
| `optimizeImagesToWebp()` | `bool` | `false` | [Single-pass optimization](#single-pass-image-optimization) |
| `maxImageWidth()` | int px | — | [Single-pass optimization](#single-pass-image-optimization) |
| `maxImageHeight()` | int px | — | [Single-pass optimization](#single-pass-image-optimization) |
| `maxImageLongEdge()` | int px | — | [Single-pass optimization](#single-pass-image-optimization) |
| `stripExif()` | `bool` | `true` | [Single-pass optimization](#single-pass-image-optimization) |
| `imageVariants()` | array / `RichEditorImageVariant` | `[]` | [Named variants](#named-image-variants) |
| `pruneOrphanedAttachmentsOnSave()` | `bool` | `true` | [Orphan cleanup](#automatic-attachment-cleanup) |

### HTML rendering

| Method | Type / values | Default | Example section |
|--------|---------------|---------|-----------------|
| `responsiveImages()` | `bool` | auto when variants set | [Responsive HTML](#responsive-images-in-html-output) |
| `lazyImages()` | `bool` | `true` | [Lazy loading](#lazy-images-onoff) |
| `imageSizes()` | CSS `sizes` string | `100vw` | [Image sizes](#image-sizes-attribute) |
| `makeFlexRichContentRenderer()` | mixed JSON | — | [Rendering HTML](#rendering-html) |

Built-in plugins (automatic): **block images** (always), **YouTube** (when `youtube()`), **paste cleanup** (when `pasteCleanup()`).

---

## Shell variants

### `primary`

```php
FlexRichEditor::make('body')
    ->variant('primary')
    ->wordCount();
```

### `secondary` (default)

```php
FlexRichEditor::make('body')
    ->variant('secondary')
    ->wordCount();
```

### `soft`

```php
FlexRichEditor::make('body')
    ->variant('soft')
    ->wordCount();
```

### `flat`

```php
FlexRichEditor::make('body')
    ->variant('flat')
    ->wordCount();
```

---

## Shell sizes

### `sm`

```php
FlexRichEditor::make('excerpt')
    ->size('sm')
    ->wordCount();
```

### `md` (default)

```php
FlexRichEditor::make('body')
    ->size('md')
    ->wordCount();
```

### `lg`

```php
FlexRichEditor::make('body')
    ->size('lg')
    ->wordCount();
```

---

## Focus outline

```php
// Default: no extra focus ring class
FlexRichEditor::make('body');

// Visible focus outline on the field wrapper
FlexRichEditor::make('body')->focusOutline();
```

---

## Word count

```php
// Off (default)
FlexRichEditor::make('body');

// Footer: "123 characters, 45 words"
FlexRichEditor::make('body')->wordCount();

// Explicitly off
FlexRichEditor::make('body')->wordCount(false);
```

---

## JSON debug badge

```php
// Hidden (default)
FlexRichEditor::make('body')->jsonBadge(false);

// Show "JSON" badge in footer (debug)
FlexRichEditor::make('body')->jsonBadge();
```

---

## Content limits

### Soft limits (default) — warn in footer, validate on save

```php
FlexRichEditor::make('body')
    ->minCharacters(100)
    ->maxCharacters(5000)
    ->maxWords(800)
    ->limitBehavior('soft')
    ->wordCount();
```

Footer states: **ok** → **warning** (≥90% of max) → **danger** (over limit). Form save still runs server validation.

### Hard limits — block typing past max

```php
FlexRichEditor::make('body')
    ->maxCharacters(500)
    ->maxWords(80)
    ->limitBehavior('hard')
    ->wordCount();
```

Hard mode calls `editor.commands.undo()` when the user exceeds max characters or words.

### Min characters only

```php
FlexRichEditor::make('body')
    ->minCharacters(50)
    ->wordCount();
```

### Max characters only

```php
FlexRichEditor::make('body')
    ->maxCharacters(10000)
    ->wordCount();
```

### Max words only

```php
FlexRichEditor::make('body')
    ->maxWords(1500)
    ->wordCount();
```

---

## Reading time

```php
// Off (default)
FlexRichEditor::make('body');

// Default WPM from config (200)
FlexRichEditor::make('body')->readingTime();

// Custom words per minute
FlexRichEditor::make('body')->readingTime(wordsPerMinute: 250);

// Off explicitly
FlexRichEditor::make('body')->readingTime(false);
```

Config: `config('filament-flex-fields.rich_editor.reading_time_words_per_minute')`.

---

## Fullscreen

```php
// Off (default)
FlexRichEditor::make('body');

// Adds flexFullscreen toolbar button + fullscreen shell CSS
FlexRichEditor::make('body')->fullscreen();

// Explicitly off
FlexRichEditor::make('body')->fullscreen(false);
```

---

## Distraction-free

Hides **clearFormatting** and **clearContent** in the toolbar while fullscreen is active.

```php
// Off (default) — distractionFree() alone only adds CSS class
FlexRichEditor::make('body')->distractionFree(false);

// With fullscreen — hides clear tools in fullscreen mode
FlexRichEditor::make('body')
    ->fullscreen()
    ->distractionFree();
```

---

## Autosave

Saves editor JSON to `localStorage`. On reload, prompts to restore if a newer draft exists.

```php
// Off (default)
FlexRichEditor::make('body');

// Every 30 seconds, auto-generated key from statePath + record + user
FlexRichEditor::make('body')->autosave(30);

// Custom storage key
FlexRichEditor::make('body')
    ->autosave(
        intervalSeconds: 30,
        key: fn (): string => 'article-draft-'.($this->record?->id ?? 'new'),
    );
```

Default key pattern: `{statePath}::{model:id|create}::user:{id}`.

---

## Paste cleanup

| Call | Mode | Behavior |
|------|------|----------|
| (none) | off | No paste plugin |
| `pasteCleanup()` or `pasteCleanup(true)` | `standard` | Strip inline `style`/`class`, Office markup |
| `pasteCleanup('aggressive')` | `aggressive` | Standard + unwrap `<span>` / `<font>` |

```php
// Off
FlexRichEditor::make('body');

// Standard
FlexRichEditor::make('body')->pasteCleanup();

// Aggressive (Word / Google Docs)
FlexRichEditor::make('body')->pasteCleanup('aggressive');
```

---

## YouTube embeds

Requires `->youtube()`. Adds toolbar button + TipTap extension + paste handler for YouTube URLs.

```php
// Off (default)
FlexRichEditor::make('body');

// Basic YouTube support
FlexRichEditor::make('body')->youtube();

// Privacy-enhanced embed domain (default when YouTube enabled)
FlexRichEditor::make('body')
    ->youtube()
    ->youtubeNocookie();

// Standard youtube.com embed domain
FlexRichEditor::make('body')
    ->youtube()
    ->youtubeNocookie(false);

// Custom iframe dimensions
FlexRichEditor::make('body')
    ->youtube()
    ->youtubeWidth(1280)
    ->youtubeHeight(720);

// Disable YouTube after enabling
FlexRichEditor::make('body')->youtube(false);
```

Accepted URL shapes: `youtube.com/watch?v=`, `youtu.be/`, `youtube.com/embed/`, `youtube.com/shorts/`.

Rendered HTML preserves `data-youtube-video` iframes through `FlexRichContentRenderer::toHtml()` sanitization.

---

## Block images (automatic)

`FlexRichEditorBlockImagePlugin` is **always registered**. Images inserted in the editor are block-level (not inline with paragraph text). No configuration required.

```php
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->imagesOnly();
```

---

## Image overlay (editor only)

When file attachments are enabled, selecting an image shows **Edit** and **Delete** controls (top-right overlay). Not included in frontend HTML output.

```php
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->fileAttachmentsDisk('public')
    ->scopedAttachmentDirectory('articles')
    ->imagesOnly();
```

- **Edit** → opens Filament `attachFiles` action (alt text, replace).
- **Delete** → removes image from document.

---

## Toolbar presets

### Default Flex toolbar (no `attachFiles`)

```php
FlexRichEditor::make('body');
// Same as:
FlexRichEditor::make('body')
    ->toolbarButtons(FlexRichEditor::make('_ref')->getFlexDefaultToolbarButtons());
```

Groups: undo/redo, formatting, headings dropdown, alignment dropdown, blockquote/code, lists dropdown, link, clear formatting/content.

### Full toolbar (with attachments button)

```php
FlexRichEditor::make('body')
    ->toolbarButtons(FlexRichEditor::make('_ref')->getFlexFullToolbarButtons())
    ->fileAttachments(true)
    ->imagesOnly();
```

### Custom toolbar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexToolbarButtonGroup;

FlexRichEditor::make('body')
    ->toolbarButtons([
        ['bold', 'italic', 'underline'],
        [
            FlexToolbarButtonGroup::make('Lists', ['bulletList', 'orderedList']),
        ],
        ['link', 'attachFiles'],
    ])
    ->fileAttachments(true);
```

### Native Filament comparison (playground)

```php
use Filament\Forms\Components\RichEditor;

RichEditor::make('body')
    ->json()
    ->toolbarButtons(FlexRichEditor::make('_ref')->getNativeComparisonToolbarButtons())
    ->floatingToolbars(FlexRichEditor::make('_ref')->getFlexDefaultFloatingToolbars());
```

---

## Toolbar roles

Presets in `config('filament-flex-fields.rich_editor.toolbar_roles')`.

### Author

```php
FlexRichEditor::make('body')->toolbarForAuthor();
// equivalent:
FlexRichEditor::make('body')->toolbarForRole('author');
```

### Editor

```php
FlexRichEditor::make('body')->toolbarForEditor();
```

### Admin

```php
FlexRichEditor::make('body')->toolbarForAdmin();
```

### Custom role (after publishing config)

```php
// config/filament-flex-fields.php → rich_editor.toolbar_roles.reviewer = [...]

FlexRichEditor::make('body')->toolbarForRole('reviewer');
```

Publish config:

```bash
php artisan vendor:publish --tag=filament-flex-fields-config
```

---

## Disabled toolbar tools

```php
FlexRichEditor::make('body')
    ->toolbarForAdmin()
    ->disabledTools(['codeBlock', 'attachFiles']);
```

Works with string button names from toolbar groups. `flexFullscreen` and plugin tools (`youtube`) use their tool names.

---

## Floating toolbars

Default paragraph bubble (bold, italic, underline, strike, link). Hidden when an image is selected.

```php
// Default
FlexRichEditor::make('body');

// Custom bubble for paragraphs
FlexRichEditor::make('body')
    ->floatingToolbars([
        'paragraph' => ['bold', 'italic', 'link'],
    ]);

// Disable paragraph bubble
FlexRichEditor::make('body')
    ->floatingToolbars([]);
```

---

## Custom tools

Flex adds Gravity-icon tools via `FlexRichEditor::getFlexExtraTools()`:

- `clearFormatting` — clear nodes + marks
- `clearContent` — empty document

```php
FlexRichEditor::make('body')
    ->tools([
        ...FlexRichEditor::getFlexExtraTools(),
        // plus custom RichEditorTool::make(...) instances
    ]);
```

When `fullscreen()` is enabled, `flexFullscreen` tool is appended automatically.

---

## File attachments

Uploads use Filament’s attachment API (`fileAttachments()`, disk, directory, visibility). Flex adds scoped paths, optimization, variants, and orphan cleanup on top.

Attachments are enabled when `fileAttachments(true)` **or** the toolbar includes `attachFiles` and Filament resolves `hasFileAttachments()`.

```php
// Explicitly enable uploads without attachFiles button (e.g. paste-only)
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->fileAttachmentsDisk('public')
    ->fileAttachmentsDirectory('uploads/editor');

// Typical: full toolbar + attachments
FlexRichEditor::make('body')
    ->toolbarButtons(FlexRichEditor::make('_ref')->getFlexFullToolbarButtons())
    ->fileAttachments(true)
    ->fileAttachmentsDisk('public')
    ->scopedAttachmentDirectory('articles')
    ->imagesOnly();
```

### Visibility

```php
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->fileAttachmentsDisk('s3')
    ->fileAttachmentsVisibility('private');
```

---

## Scoped attachment directories

`scopedAttachmentDirectory('rich-editor')` resolves:

- `{prefix}/{model}/{id}/` when editing a record
- `{prefix}/drafts/{userId}/` on create forms

```php
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->fileAttachmentsDisk('public')
    ->scopedAttachmentDirectory('articles');
```

---

## Images only

Restricts accepted MIME types to images (JPEG, PNG, GIF, WebP).

```php
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->imagesOnly();
```

---

## Max attachment size

```php
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->imagesOnly()
    ->maxAttachmentSizeKb(5120); // 5 MB
```

---

## Single-pass image optimization

Without named variants — resizes/optimizes the master file on upload.

```php
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->imagesOnly()
    ->optimizeImages()
    ->optimizeImagesToWebp()
    ->maxImageWidth(1920)
    ->maxImageHeight(1080)
    ->maxImageLongEdge(2000)
    ->stripExif(true);

// Minimal — optimize JPEG quality only
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->optimizeImages();

// Keep EXIF
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->optimizeImages()
    ->stripExif(false);
```

---

## Named image variants

Generates `photo__thumb.webp`, `photo__large.webp`, and `photo.jpg.flex-variants.json`.

### Array config

```php
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->imageVariants([
        'thumb' => ['max_long_edge' => 320, 'webp' => true],
        'medium' => ['max_long_edge' => 1200, 'webp' => true],
        'large' => ['max_long_edge' => 2000, 'master' => true, 'webp' => true],
    ]);
```

### Fluent `RichEditorImageVariant` objects

```php
use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorImageVariant;

FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->imageVariants([
        RichEditorImageVariant::make('thumb')->maxLongEdge(320)->webp(),
        RichEditorImageVariant::make('hero')->maxLongEdge(2000)->master()->webp(),
    ]);
```

Variant keys: `max_long_edge`, `max_width`, `max_height`, `webp`, `master`, `optimize`.

---

## Automatic attachment cleanup

### Native disk (default on)

```php
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->scopedAttachmentDirectory('articles')
    ->pruneOrphanedAttachmentsOnSave(); // default true

// Disable
FlexRichEditor::make('body')
    ->pruneOrphanedAttachmentsOnSave(false);
```

On save: compares image IDs in JSON before/after; deletes removed masters, variants, and `.flex-variants.json` manifests. Scoped directories also sweep unreferenced files.

### Spatie Media Library

Disk pruner is skipped. Filament calls `FileAttachmentProvider::cleanUpFileAttachments(exceptIds: [...])` on save.

---

## Responsive images in HTML output

### Lazy images on/off

```php
// Lazy (default)
FlexRichEditor::make('body')->lazyImages();

// Eager load
FlexRichEditor::make('body')->lazyImages(false);
```

### Responsive srcset on/off

```php
// Explicit responsive srcset
FlexRichEditor::make('body')
    ->imageVariants([/* ... */])
    ->responsiveImages();

// Disable srcset even with variants
FlexRichEditor::make('body')
    ->imageVariants([/* ... */])
    ->responsiveImages(false);
```

When variants are configured, `responsiveImages()` defaults to **on** if not set explicitly.

### Image sizes attribute

```php
FlexRichEditor::make('body')
    ->imageSizes('100vw');

FlexRichEditor::make('body')
    ->imageSizes('(max-width: 768px) 100vw, 50vw');
```

---

## Rendering HTML

Always use `makeFlexRichContentRenderer()` so disk, variants, plugins (YouTube), and lazy loading match the field.

### From field instance (recommended)

```php
$html = FlexRichEditor::make('body')
    ->imageVariants([/* same as form */])
    ->responsiveImages()
    ->makeFlexRichContentRenderer($post->body)
    ->toHtml();
```

### In Filament infolist / table column

```php
// Inside a form schema you already have $field configured:
$html = $field->makeFlexRichContentRenderer($state)->toHtml();
```

### Standalone renderer

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RichEditor\FlexRichContentRenderer;

$html = FlexRichContentRenderer::make($post->body)
    ->fileAttachmentsDisk('public')
    ->responsiveImages()
    ->lazyImages()
    ->imageSizes('100vw')
    ->imageVariants([
        'thumb' => ['max_long_edge' => 320, 'webp' => true],
        'large' => ['max_long_edge' => 2000, 'master' => true],
    ])
    ->toHtml();
```

### Example output

```html
<img
  src="https://cdn.example.test/articles/1/photo__large.webp"
  srcset="https://cdn.example.test/.../photo__thumb.webp 320w, https://.../photo__large.webp 2000w"
  sizes="100vw"
  width="2000"
  loading="lazy"
  decoding="async"
  alt="Sunset over the marina"
/>

<div data-youtube-video="">
  <iframe src="https://www.youtube-nocookie.com/embed/..." class="fff-rich-editor__youtube-iframe"></iframe>
</div>
```

### Blade view

```blade
{!! FlexRichEditor::make('body')
    ->fileAttachmentsDisk('public')
    ->imageVariants($post->getImageVariants())
    ->responsiveImages()
    ->makeFlexRichContentRenderer($post->body)
    ->toHtml() !!}
```

### Inertia / API controller

```php
return Inertia::render('Posts/Show', [
    'bodyHtml' => FlexRichEditor::make('body')
        ->fileAttachmentsDisk('public')
        ->responsiveImages()
        ->makeFlexRichContentRenderer($post->body)
        ->toHtml(),
]);
```

---

## Alt text required

```php
// Off (default)
FlexRichEditor::make('body');

// Footer counter + save validation
FlexRichEditor::make('body')->altTextRequired();
```

---

## Accessibility

Built-in without extra configuration:

| Feature | Behavior |
|---------|----------|
| Toolbar | `role="toolbar"`, `aria-orientation="horizontal"`, `aria-label` from field label |
| Roving tabindex | Arrow keys, Home, End between toolbar buttons |
| Editor | `role="textbox"`, `aria-multiline="true"` |
| Footer stats | `role="status"`, `aria-live="polite"` |
| Autosave | `aria-busy` while saving |
| Limit warnings | `aria-label` reflects warning/danger state |

```php
FlexRichEditor::make('body')
    ->label('Article body') // used for toolbar + editor aria-label
    ->wordCount();
```

---

## Spatie Media Library (optional)

### 1. Model — register conversions

```php
use Bjanczak\FilamentFlexFields\Support\RichEditor\Concerns\RegistersFlexRichEditorMediaConversions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersFlexRichEditorMediaConversions;

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->registerFlexRichEditorMediaConversions([
            'thumb' => ['max_long_edge' => 320, 'webp' => true],
            'medium' => ['max_long_edge' => 1200, 'webp' => true],
            'large' => ['max_long_edge' => 2000, 'master' => true, 'webp' => true],
        ]);
    }
}
```

### 2. Form

```php
FlexRichEditor::make('body')
    ->imageVariants([
        'thumb' => ['max_long_edge' => 320, 'webp' => true],
        'large' => ['max_long_edge' => 2000, 'master' => true, 'webp' => true],
    ])
    ->responsiveImages()
    ->lazyImages();
```

### 3. Render

```php
$html = FlexRichEditor::make('body')
    ->imageVariants([/* same */])
    ->makeFlexRichContentRenderer($post->body)
    ->toHtml();
```

| | Native disk | Spatie |
|---|-------------|--------|
| `data-id` | File path | Media UUID |
| Variants | `photo__thumb.webp` + manifest | Spatie conversions |
| Orphan cleanup | `RichEditorAttachmentPruner` | `cleanUpFileAttachments()` |

---

## Complete example: Filament Resource

```php
<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Models\Post;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),

            FlexRichEditor::make('body')
                ->label('Article body')
                ->columnSpanFull()
                ->json()
                ->variant('secondary')
                ->size('md')
                ->wordCount()
                ->readingTime()
                ->minCharacters(100)
                ->maxCharacters(10000)
                ->maxWords(1500)
                ->limitBehavior('soft')
                ->toolbarButtons(FlexRichEditor::make('_ref')->getFlexFullToolbarButtons())
                ->fileAttachments(true)
                ->scopedAttachmentDirectory('articles')
                ->fileAttachmentsDisk('public')
                ->imagesOnly()
                ->maxAttachmentSizeKb(5120)
                ->imageVariants([
                    'thumb' => ['max_long_edge' => 320, 'webp' => true],
                    'large' => ['max_long_edge' => 2000, 'master' => true, 'webp' => true],
                ])
                ->responsiveImages()
                ->lazyImages()
                ->imageSizes('(max-width: 768px) 100vw, 720px')
                ->altTextRequired()
                ->pruneOrphanedAttachmentsOnSave()
                ->fullscreen()
                ->distractionFree()
                ->youtube()
                ->youtubeNocookie()
                ->pasteCleanup('aggressive')
                ->autosave(30, fn (): string => 'post-body-'.(auth()->id() ?? 'guest')),
        ]);
    }
}
```

```php
// app/Http/Controllers/PostController.php — public show page
public function show(Post $post)
{
    return view('posts.show', [
        'post' => $post,
        'bodyHtml' => FlexRichEditor::make('body')
            ->fileAttachmentsDisk('public')
            ->imageVariants([
                'thumb' => ['max_long_edge' => 320, 'webp' => true],
                'large' => ['max_long_edge' => 2000, 'master' => true, 'webp' => true],
            ])
            ->responsiveImages()
            ->imageSizes('(max-width: 768px) 100vw, 720px')
            ->makeFlexRichContentRenderer($post->body)
            ->toHtml(),
    ]);
}
```

---

## Configuration recipes

### Minimal blog post

```php
FlexRichEditor::make('body')
    ->json()
    ->wordCount()
    ->maxCharacters(5000)
    ->limitBehavior('soft');
```

### Production article (native disk)

```php
FlexRichEditor::make('body')
    ->json()
    ->scopedAttachmentDirectory('articles')
    ->fileAttachmentsDisk('public')
    ->fileAttachments(true)
    ->toolbarButtons(FlexRichEditor::make('_ref')->getFlexFullToolbarButtons())
    ->imagesOnly()
    ->maxAttachmentSizeKb(5120)
    ->imageVariants([
        'thumb' => ['max_long_edge' => 320, 'webp' => true],
        'medium' => ['max_long_edge' => 1200, 'webp' => true],
        'large' => ['max_long_edge' => 2000, 'master' => true, 'webp' => true],
    ])
    ->responsiveImages()
    ->lazyImages()
    ->altTextRequired()
    ->pruneOrphanedAttachmentsOnSave()
    ->wordCount()
    ->readingTime()
    ->maxCharacters(10000)
    ->maxWords(1500)
    ->limitBehavior('soft')
    ->fullscreen()
    ->distractionFree()
    ->youtube()
    ->youtubeNocookie()
    ->autosave(30, fn (): string => 'draft-'.($this->record?->id ?? 'new'))
    ->pasteCleanup('aggressive')
    ->columnSpanFull();
```

### Author role (limited toolbar)

```php
FlexRichEditor::make('body')
    ->toolbarForAuthor()
    ->disabledTools(['codeBlock'])
    ->fileAttachments(true)
    ->imagesOnly()
    ->fileAttachmentsDisk('public')
    ->scopedAttachmentDirectory('posts');
```

---

## Package config

`config/filament-flex-fields.php`:

```php
'rich_editor' => [
    'reading_time_words_per_minute' => 200,
    'toolbar_roles' => [
        'author' => [
            ['bold', 'italic', 'underline'],
            ['link', 'attachFiles'],
        ],
        'editor' => [
            ['undo', 'redo'],
            ['bold', 'italic', 'underline', 'strike'],
            ['link', 'attachFiles'],
            ['bulletList', 'orderedList'],
        ],
        'admin' => [
            ['undo', 'redo'],
            ['bold', 'italic', 'underline', 'strike', 'code'],
            ['h1', 'h2', 'h3'],
            ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
            ['blockquote', 'codeBlock'],
            ['bulletList', 'orderedList'],
            ['link', 'attachFiles'],
            ['clearFormatting', 'clearContent'],
        ],
    ],
],
```

Publish:

```bash
php artisan vendor:publish --tag=filament-flex-fields-config
```

---

## Translations

Package strings: `filament-flex-fields::default.rich_editor.*` in `resources/lang/en/default.php` and `resources/lang/pl/default.php`.

Toolbar labels reuse Filament: `filament-forms::components.rich_editor.tools.*`.

### Publish translations

```bash
php artisan vendor:publish --tag=filament-flex-fields-translations
```

### Key reference

| Key | English | Polski |
|-----|---------|--------|
| `rich_editor.clear_content` | Clear content | Wyczyść treść |
| `rich_editor.youtube.tool` | YouTube | YouTube |
| `rich_editor.word_count.line` | :characters characters, :words words | :characters znaków, :words słów |
| `rich_editor.reading_time.line` | :minutes min read | :minutes min czytania |
| `rich_editor.limits.max_characters` | Content may not exceed :max characters. | Treść nie może przekraczać :max znaków. |
| `rich_editor.autosave.saved` | Draft saved | Szkic zapisany |
| `rich_editor.alt_text.validation` | :count image(s) are missing alt text. | :count obraz(ów) nie ma tekstu alternatywnego. |
| `rich_editor.fullscreen.toggle` | Toggle fullscreen | Przełącz pełny ekran |
| `rich_editor.image.edit` | Edit image | Edytuj obraz |
| `rich_editor.image.delete` | Delete image | Usuń obraz |

---

## Testing

### PHP (package)

```bash
php artisan test --compact packages/filament-flex-fields/tests/Unit/FlexRichEditorTest.php
php artisan test --compact packages/filament-flex-fields/tests/Feature/FlexRichEditorFieldRenderTest.php
php artisan test --compact packages/filament-flex-fields/tests/Unit/FlexRichContentRendererPerformanceTest.php
```

### JavaScript unit tests

```bash
cd packages/filament-flex-fields
npm run test:js -- tests/js/rich-editor-chrome.test.mjs tests/js/rich-editor-toolbar-a11y.test.mjs
```

### Playwright E2E (playground)

```bash
FLEX_FIELDS_PLAYGROUND_URL=https://your-app.test/admin/flex-fields-playground \
  npm run test:e2e:rich-editor
```

---

## Playground

Enable `FLEX_FIELDS_PLAYGROUND=true` and open **Flex Rich Editor** (`/flex-fields-playground/flex-rich-editor`).

Includes:

- Native Filament `RichEditor` comparison
- Full **Article body** demo (attachments, variants, limits, YouTube, fullscreen, autosave)
- Variant grid: compact / secondary / soft / flat
- Attachments-only and full-toolbar sections

---

## Assets (CSS & JS)

Follows the lazy-asset pattern from `DEVELOPMENT.md` (§3–§5 in the package repo): one lazy CSS bundle per field, Alpine `x-load` per component, optional support scripts `loadedOnRequest()`.

| Asset | Kind | When it loads |
|-------|------|----------------|
| `rich-editor-field.css` | Lazy CSS | `load-stylesheet` in blade when the field renders |
| `flex-rich-editor.js` | Alpine component | `x-load` on the editor root |
| `flex-rich-editor-paste-extension.js` | TipTap extension | When `pasteCleanup()` is enabled |
| `flex-rich-editor-block-image-extension.js` | TipTap extension | Always (block images) |
| `flex-rich-editor-youtube-extension.js` | TipTap extension | When `youtube()` is enabled |

### Does it duplicate Filament’s rich editor?

**On a page with only `FlexRichEditor`:** no double download of Filament’s `rich-editor` Alpine asset. The blade loads `flex-rich-editor`, not `filament/forms` → `rich-editor`.

**JS bundle:** at build time, `flex-rich-editor.js` bundles Filament’s `rich-editor-form-component` (esbuild alias to `vendor/filament/forms/dist/components/rich-editor.js`) plus Flex chrome (~500 KB raw — same order of magnitude as native Filament). That is a **single runtime script**, not Filament + Flex side by side.

**CSS:** Filament does not ship a separate `rich-editor.css` asset. Flex adds `rich-editor-field.css` for the `fff-rich-editor-*` shell and overrides on shared `fi-fo-rich-editor-*` markup classes. No second full editor stylesheet.

**Exception — playground comparison:** the Flex Rich Editor playground page may render native `RichEditor` next to `FlexRichEditor` for demo purposes. That page loads **both** `rich-editor` and `flex-rich-editor` scripts — intentional, not typical production usage.

---

## Performance notes

- Footer stats use `requestAnimationFrame` batching (not per-keystroke DOM thrashing).
- Image overlay sync runs on selection changes only.
- Attachment manifest reads are cached per request during HTML rendering.
- Bundle budget (CI): `flex-rich-editor.js` ~497 KB raw / ~157 KB gzip (aligned with Filament native).

```bash
cd packages/filament-flex-fields && npm run check:budgets
```

---

## Requirements & optional packages

| Package | Purpose |
|---------|---------|
| `filament/forms` ^5 | Base RichEditor / TipTap |
| `spatie/laravel-medialibrary` | Optional — Spatie attachment provider + conversions |
| `filament/spatie-laravel-media-library-plugin` | Optional — Filament Spatie file attachment provider |
| GD extension | Recommended for on-disk variant generation |

---

## Filament RichEditor baseline

`FlexRichEditor` extends `Filament\Forms\Components\RichEditor`. Options not documented on this page behave as in Filament.

- [Filament Rich Editor documentation](https://filamentphp.com/docs/forms/rich-editor)
- JSON storage is enabled by default in `setUp()` (`json()`)

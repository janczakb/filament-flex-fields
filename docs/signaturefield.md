---
title: "SignatureField"
---

![SignatureField](/art/sc-1.webp)

[← Back to Table of Contents](/docs/index)


### Summary

Canvas signature pad storing normalized SVG markup. Supports undo, fullscreen, optional download (SVG/WebP), and stroke validation.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField` |
| **State type** | `string\|null` — normalized SVG document |
| **FieldType** | `signature` |
| **Storage constants** | `STORE_SVG = 'svg'` |
| **Playground** | `signature-field` slug in Flex Fields playground |

### Mobile, touch & stylus (enterprise)

SignatureField uses the **Pointer Events** API — one code path for mouse, finger, and active stylus:

| Input | Support |
|-------|---------|
| **Mobile / tablet (finger)** | Yes — draw directly on the pad; `touch-action: none` prevents page scroll while signing |
| **Apple Pencil / active stylus** | Yes — `pointerType: pen` with **pressure-sensitive** stroke width |
| **Mouse / trackpad** | Yes — optional `trackpadGlide()` for Mac trackpad drawing without click-drag |
| **Retina / HiDPI** | Canvas scales with `devicePixelRatio` for sharp output on iPad and phones |

Toolbar icon buttons expose **Filament tooltips** (same copy as `aria-label`) and use larger hit targets on coarse pointers (`@media (pointer: coarse)`).

**Note:** passive capacitive styluses (no pressure, reported as touch) behave like finger input. Palm rejection is handled by single-pointer capture — only one active stroke at a time.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;

SignatureField::make('signature')
    ->label('Sign here')
    ->penColor('#18181b')
    ->backgroundColor('#ffffff')
    ->required();

SignatureField::make('approval_signature')
    ->fullscreen()
    ->undoable()
    ->downloadable(SignatureField::DOWNLOAD_WEBP)
    ->downloadFilename('approval')
    ->maxSizeKb(64)
    ->minStrokes(2);
```

Read-only preview of an existing signature:

```php
SignatureField::make('signed_at')
    ->default($record->signature_svg)
    ->readOnly();
```

### State format

State is a compact SVG string produced by `SignatureSvg::normalize()`. `null` or empty string means no signature.

Use `normalizeState(mixed $state): ?string` to sanitize external SVG before setting state.

### Legal pack & Media Capture OS

Enterprise e-sign metadata via [Media & Capture OS](/docs/media-capture-os):

```php
SignatureField::make('contract_signature')
    ->legalPack()
    ->timestampSeal()
    ->legalMetadataIn('signature_legal')
    ->inkTrail()
    ->pdfPreview();
```

On save, `legalMetadataIn` receives:

| Key | Source |
|-----|--------|
| `sealed_at` | UTC ISO-8601 timestamp |
| `signer_id` | `MediaCaptureOs::resolveLegalSignerId()` or authenticated user |
| `ip_address` | Current request IP |
| `user_agent` | Current request user agent (truncated) |
| `document_hash` | Optional `MediaCaptureOs::registerDocumentHashResolver()` |
| `signature_path_count` | SVG path count at seal time |

`legalPack()` requires visible ink (`SignatureLegalPack::requiresInk()`).

### Validation

| Check | Detail |
|-------|--------|
| `required()` | State must not be empty |
| Format | Must pass `SignatureSvg::isValid()` |
| Size | Byte size ≤ `maxSizeKb()` × 1024 |
| Strokes | Path count ≥ `minStrokes()` |

### Configuration API

#### `penColor(string|Closure $color)`


Hex pen color. Default: `#18181b`. Must match `#RGB` or `#RRGGBB`.

```php
SignatureField::make('field_name')
    ->penColor('#000000');
```
#### `penWidth(float|Closure $width)`


Stroke width in SVG units. Clamped to `0.5`–`12`. Default: `2.5`.

```php
SignatureField::make('field_name')
    ->penWidth(2.5);
```
#### `backgroundColor(string|Closure|null $color)`


Canvas background hex, or `null` / `'transparent'` for transparent. Default: `#ffffff`.

When left at the default, the pad follows Filament dark mode in the UI (dark zinc pad, light ink) while exported SVG/WebP still uses a white background for document output. Set an explicit hex to lock both display and export.

```php
SignatureField::make('field_name')
    ->backgroundColor('#ffffff');
```
#### `fullscreen(bool|Closure $condition = true)`


Enable fullscreen drawing mode. Default: `true`.

```php
SignatureField::make('field_name')
    ->fullscreen(true);
```
#### `undoable(bool|Closure $condition = true)`


Show undo control. Default: `true`.

```php
SignatureField::make('field_name')
    ->undoable(true);
```
#### `maxSizeKb(int|Closure $kilobytes)`


Maximum stored SVG size in kilobytes. Default: `48`.

```php
SignatureField::make('field_name')
    ->maxSizeKb(1024);
```
#### `minStrokes(int|Closure $strokes)`


Minimum number of SVG paths required. Default: `1`.

```php
SignatureField::make('field_name')
    ->minStrokes(3);
```
#### `viewBox(int|Closure $width, int|Closure $height)`


SVG viewBox dimensions. Defaults from `SignatureSvg::VIEWBOX_WIDTH` / `VIEWBOX_HEIGHT`.

```php
SignatureField::make('field_name')
    ->viewBox(400, 200);
```
#### `smoothing(bool|Closure $condition = true)`


Bezier smoothing on strokes. Default: `true`.

```php
SignatureField::make('field_name')
    ->smoothing(true);
```
#### `trackpadGlide(bool|Closure $condition = true)`


Hold modifier key to draw with trackpad without clicking. Default: `false`.

```php
SignatureField::make('field_name')
    ->trackpadGlide(true);
```
#### `trackpadGlideKey(string|Closure $key)`


Single letter `a`–`z` for glide modifier. Default: `d`.

```php
SignatureField::make('field_name')
    ->trackpadGlideKey('shift');
```
#### `guidelines(bool|Closure $condition = true)`


Show baseline guidelines on canvas. Default: `false`.

```php
SignatureField::make('field_name')
    ->guidelines(true);
```
#### `downloadable(string|Closure|null $format = 'svg')`


Enable client-side download. Formats: `SignatureField::DOWNLOAD_SVG` or `SignatureField::DOWNLOAD_WEBP`. Pass `null` to disable.

```php
SignatureField::make('field_name')
    ->downloadable('svg');
```
#### `downloadFilename(string|Closure $filename)`


Download file base name without extension. Default: `signature`.

```php
SignatureField::make('field_name')
    ->downloadFilename('signature.svg');
```
#### `webpQuality(float|Closure $quality)`


WebP export quality `0.1`–`1`. Default: `0.9`.

```php
SignatureField::make('field_name')
    ->webpQuality(0.8);
```
#### `undoIcon()` / `clearIcon()` / `downloadIcon()` / `fullscreenIcon()` / `closeIcon()`


Override toolbar icons (`string|BackedEnum|Htmlable|Closure|null`).

```php
SignatureField::make('field_name')
    ->undoIcon('heroicon-o-arrow-path')
    ->clearIcon('heroicon-o-trash')
    ->downloadIcon('heroicon-o-arrow-down-tray')
    ->fullscreenIcon('heroicon-o-arrows-pointing-out')
    ->closeIcon('heroicon-o-x-mark');
```
#### `readOnly(bool|Closure $condition = true)`


Disable drawing; display existing SVG only.

```php
SignatureField::make('field_name')
    ->readOnly(true);
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getPenColor()` | `string` | Lowercase hex |
| `getPenWidth()` | `float` | Clamped width |
| `getBackgroundColor()` | `string\|null` | Background hex or `null` |
| `isFullscreenEnabled()` | `bool` | Fullscreen available |
| `isUndoable()` | `bool` | Undo enabled |
| `getMaxSizeKb()` | `int` | Size limit |
| `getMinStrokes()` | `int` | Minimum paths |
| `getViewBoxWidth()` / `getViewBoxHeight()` | `int` | ViewBox size |
| `isSmoothingEnabled()` | `bool` | Smoothing on |
| `isTrackpadGlideEnabled()` | `bool` | Trackpad glide on |
| `getTrackpadGlideKey()` | `string` | Modifier key |
| `isGuidelinesEnabled()` | `bool` | Guidelines visible |
| `getDownloadFormat()` | `string\|null` | `svg`, `webp`, or `null` |
| `getDownloadFilename()` | `string` | Download base name |
| `getWebpQuality()` | `float` | WebP quality |
| `getUndoIcon()` etc. | `string\|BackedEnum\|Htmlable` | Resolved icons |
| `normalizeState(mixed $state)` | `string\|null` | Sanitized SVG |
| `getWrapperClasses()` | `array&lt;string, bool&gt;` | `fff-signature-field` |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `pen_color` | `penColor()` |
| `pen_width` | `penWidth()` |
| `background_color` | `backgroundColor()` |
| `fullscreen` | `fullscreen()` |
| `undoable` | `undoable()` |
| `max_size_kb` | `maxSizeKb()` |
| `min_strokes` | `minStrokes()` |
| `smoothing` | `smoothing()` |
| `download_format` | `downloadable()` |
| `download_filename` | `downloadFilename()` |
| `webp_quality` | `webpQuality()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-signature-field` | Root wrapper |
| `fff-signature-field__canvas` | Drawing surface |
| `fff-signature-field__toolbar` | Action buttons |

### Implementation notes

- Store SVG in `text` or `longText` columns; consider `maxSizeKb()` for DB limits.
- WebP download is generated client-side from the canvas — not stored in form state.

---

### Playground

Slug: **`signature-field`**

| Demo field | Shows |
|------------|-------|
| Default | Undo, pen color, stroke validation |
| Fullscreen | Fullscreen modal + guidelines |
| Download | SVG / WebP export options |

`/admin/flex-fields-playground/signature-field` — see [Playground](/docs/index#playground).

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexFileUpload](/docs/flexfileupload-and-fleximageupload) | Upload a scanned signature image |
| [FlexRichEditor](/docs/flex-rich-editor) | Rich text consent clauses above the pad |
| [BarcodeScannerField](/docs/barcode-scanner-field) | Capture IDs or document barcodes |

---

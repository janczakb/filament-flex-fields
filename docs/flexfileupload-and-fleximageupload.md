# FlexFileUpload & FlexImageUpload

[← Back to Table of Contents](index.md)


### Summary

Styled Filament file upload with security defaults, MIME presets, upload summaries, optional metadata sidecars, image optimization hooks, and scoped per-user directories.

| | |
|---|---|
| **FlexFileUpload** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload` |
| **FlexImageUpload** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexImageUpload` (extends `FlexFileUpload`, `imagesOnly()` preset) |
| **State type** | `string\|array\|null` — stored path(s) on disk |
| **FieldType** | `file` → `FlexFileUpload`, `image` → `FlexImageUpload` |
| **Extends** | `Filament\Forms\Components\FileUpload` |

### Basic usage

#### Avatar (image)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexImageUpload;

FlexImageUpload::make('avatar')
    ->label('Profile photo')
    ->withRecommendedDefaults()
    ->avatar()
    ->imageEditor()
    ->circleCropper()
    ->disk('public')
    ->directory('avatars')
    ->maxFiles(1)
    ->optimizeImages()
    ->maxImageWidth(512)
    ->maxImageHeight(512);
```

#### Single document

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;

FlexFileUpload::make('contract')
    ->label('Contract PDF')
    ->withRecommendedDefaults()
    ->documentsOnly()
    ->disk('local')
    ->directory('contracts')
    ->maxFiles(1)
    ->showFileIcon()
    ->uploadSummary()
    ->requireReplaceConfirmation();
```

#### Multiple files with total size guard

```php
FlexFileUpload::make('attachments')
    ->withRecommendedDefaults()
    ->multiple()
    ->maxFiles(5)
    ->maxTotalSizeKb(10240)
    ->remainingSlotsLabel()
    ->uploadSummary()
    ->disk('local')
    ->directory('inquiries/attachments');
```

#### Metadata sidecar

Stores original filename, MIME, size, and image dimensions in a sibling state path:

```php
FlexFileUpload::make('scan')
    ->withRecommendedDefaults()
    ->storeMetadataIn('scan_meta')
    ->disk('local')
    ->directory('scans');
// $data['scan_meta'] => ['original_name' => '...', 'mime' => '...', 'size' => ..., 'width' => ...]
```

### Configuration API

#### `withRecommendedDefaults()` / `applyRecommendedSecurityDefaults()`

Applies recommended security and UX defaults: `createFormStrategy()`, `deleteFileOnRemove()`, `deleteReplacedFiles()`, `maxSize(5120)` (5 MB), `downloadable()`, `openable()`, and `focusOutline()`.

```php
FlexFileUpload::make('attachment')
    ->withRecommendedDefaults();
```

#### `documentsOnly()`

Sets the accepted file types to typical document formats (PDF, Word, Excel, PowerPoint, Text, etc.).

```php
FlexFileUpload::make('resume')
    ->documentsOnly();
```

#### `imagesOnly()`

Sets the accepted file types to image formats (JPEG, PNG, GIF, WebP, SVG, etc.).

```php
FlexImageUpload::make('photo')
    ->imagesOnly();
```

#### `spreadsheetsOnly()`

Sets the accepted file types to spreadsheets (CSV, XLS, XLSX).

```php
FlexFileUpload::make('report')
    ->spreadsheetsOnly();
```

#### `allowedExtensions(array $extensions)`

Restricts uploads to a custom array of file extensions.

```php
FlexFileUpload::make('scan')
    ->allowedExtensions(['pdf', 'jpg', 'png']);
```

#### `rejectExecutableFiles(bool|Closure $condition = true)`

Blocks dangerous executable extensions (such as `.php`, `.sh`, `.bat`, etc.) and raises a validation error.

```php
FlexFileUpload::make('attachment')
    ->rejectExecutableFiles();
```

#### `scopedDirectory(string|Closure $prefix = 'uploads')`

Configures dynamic per-user scoped subdirectories: `{prefix}/{user_id}/...`.

```php
FlexFileUpload::make('document')
    ->scopedDirectory('user-files');
```

#### `createFormStrategy(bool|Closure $condition = true)`

Prevents file path tampering by deferring disk storage until form submission.

```php
FlexFileUpload::make('file')
    ->createFormStrategy();
```

#### `storeMetadataIn(string|Closure $statePath)`

Saves uploaded file metadata (original filename, MIME type, file size, image dimensions) to a separate database column or state path.

```php
FlexFileUpload::make('document')
    ->storeMetadataIn('document_metadata');
```

#### `maxTotalSizeKb(int|Closure $kilobytes)`

Sets a maximum total file size limit in kilobytes across all uploaded files (useful for multiple file uploads).

```php
FlexFileUpload::make('attachments')
    ->multiple()
    ->maxTotalSizeKb(10240); // 10 MB total limit
```

#### `remainingSlotsLabel(bool|Closure $condition = true)`

Displays a remaining slots label (e.g. "2 slots remaining") when `maxFiles` is set.

```php
FlexFileUpload::make('gallery')
    ->multiple()
    ->maxFiles(5)
    ->remainingSlotsLabel();
```

#### `minImageDimensions(int $width, int $height)`

Validates that uploaded images meet minimum width and height constraints.

```php
FlexImageUpload::make('banner')
    ->minImageDimensions(1200, 400);
```

#### `maxImageDimensions(int $width, int $height)`

Validates that uploaded images do not exceed maximum width and height constraints.

```php
FlexImageUpload::make('thumbnail')
    ->maxImageDimensions(800, 600);
```

#### `deleteFileOnRemove(bool|Closure $condition = true)`

Deletes files from storage when they are removed from the form UI.

```php
FlexFileUpload::make('document')
    ->deleteFileOnRemove();
```

#### `deleteReplacedFiles(bool|Closure $condition = true)`

Deletes old files from storage when they are replaced by new uploads.

```php
FlexFileUpload::make('document')
    ->deleteReplacedFiles();
```

#### `pruneOrphanedOnSave(bool|Closure $condition = true)`

Removes old, unreferenced files from the upload directory when saving the model.

```php
FlexFileUpload::make('gallery')
    ->pruneOrphanedOnSave();
```

#### `uploadSummary(bool|Closure $condition = true)`

Displays a summary block below the file list containing count and total file size.

```php
FlexFileUpload::make('attachments')
    ->multiple()
    ->uploadSummary();
```

#### `emptyStateHint(string|Closure $hint)`

Sets a custom hint text displayed inside the dropzone when it is empty.

```php
FlexFileUpload::make('avatar')
    ->emptyStateHint('Drag your avatar here...');
```

#### `dropzoneLabel(string|Closure $label)`

Overrides the main dropzone label text.

```php
FlexFileUpload::make('invoice')
    ->dropzoneLabel('Upload invoice (PDF)');
```

#### `requireReplaceConfirmation(bool|Closure $condition = true)`

Asks the user for confirmation before replacing an already uploaded file.

```php
FlexFileUpload::make('document')
    ->requireReplaceConfirmation();
```

#### `compactList(bool|Closure $condition = true)`

Renders a denser, more compact layout for the uploaded files list.

```php
FlexFileUpload::make('attachments')
    ->multiple()
    ->compactList();
```

#### `showFileIcon(bool|Closure $condition = true)`

Displays a descriptive file type icon in the uploaded files list.

```php
FlexFileUpload::make('attachments')
    ->multiple()
    ->showFileIcon();
```

#### `variant(string|Closure $variant)`

Sets the visual design variant (`primary`, `secondary`, or `flat`).

```php
FlexFileUpload::make('document')
    ->variant('flat');
```

#### `optimizeImages(bool|Closure $condition = true)`

Resizes and compresses images to optimize page weight (requires GD or Imagick).

```php
FlexImageUpload::make('photo')
    ->optimizeImages();
```

#### `optimizeImagesToWebp(bool|Closure $condition = false)`

Converts optimized image files to the WebP format.

```php
FlexImageUpload::make('photo')
    ->optimizeImagesToWebp();
```

#### `maxImageWidth(int|Closure $width)` / `maxImageHeight(int|Closure $height)`

Sets maximum image width and height boundaries for client-side resizing.

```php
FlexImageUpload::make('photo')
    ->maxImageWidth(1920)
    ->maxImageHeight(1080);
```

#### `stripExif(bool|Closure $condition = true)`

Strips EXIF metadata from uploaded images on save.

```php
FlexImageUpload::make('photo')
    ->stripExif();
```

### Inherited Filament `FileUpload` API

`disk()`, `directory()`, `visibility()`, `multiple()`, `maxFiles()`, `minFiles()`, `maxSize()`, `acceptedFileTypes()`, `imageEditor()`, `avatar()`, `downloadable()`, `openable()`, `previewable()`, `deletable()`, standard validation — all work unchanged.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `disk` | `disk()` |
| `directory` | `directory()` |
| `visibility` | `visibility()` |
| `multiple` | `multiple()` |
| `max_size_kb` / `max_size` | `maxSize()` |
| `max_files` / `min_files` | `maxFiles()` / `minFiles()` |
| `max_total_size_kb` | `maxTotalSizeKb()` |
| `accepted_types` | `acceptedFileTypes()` |
| `documents_only` | `documentsOnly()` |
| `images_only` | `imagesOnly()` |
| `variant` | `variant()` |
| `size` | `size()` |
| `store_metadata_in` | `storeMetadataIn()` |
| `scoped_directory` | `scopedDirectory()` |
| `optimize_images` | `optimizeImages()` |
| `max_image_width` / `max_image_height` | `maxImageWidth()` / `maxImageHeight()` |

### CSS classes

| Class | Meaning |
|-------|---------|
| `fff-flex-file-upload` | Root wrapper |
| `fff-flex-file-upload--{primary\|secondary\|flat}` | Variant |
| `fff-flex-file-upload--{sm\|md\|lg}` | Size |

### Implementation notes

- Requires Livewire temporary uploads; configure `FILESYSTEM_DISK` and disk credentials.
- Playground examples under **File upload** in Flex Fields Playground.
- For Spatie Media Library integration, see package `FlexSpatieMediaLibraryFileUpload` (if installed in app).

---

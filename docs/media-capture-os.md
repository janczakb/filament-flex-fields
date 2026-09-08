# Media Ingress

Enterprise media control plane for Flex Fields: file / image upload, voice notes, signatures, and rich-editor attachments.

**Disk** and **Spatie Media Library** are both first-class. They use different field classes and different stored state — never mixed on one field.

| Prefer for new code | Deprecated but supported |
|---------------------|--------------------------|
| `FlexMedia` / `MediaIngress` | `MediaCaptureOs` as the “product” name |
| `storage_driver: disk\|spatie` | `use_spatie_media_library` |
| Field APIs below | Public `SpatieMediaCaptureAdapter`, `SignatureStorage` |

External docs (we do not re-document every Spatie/Filament knob):

- [Spatie Media Library](https://spatie.be/docs/laravel-medialibrary)
- [Filament File upload](https://filamentphp.com/docs/forms/file-upload)
- [Filament Spatie Media Library plugin](https://filamentphp.com/docs/forms/file-upload#spatie-media-library-file-uploads)

---

## Choose disk vs Spatie

| | Disk (`FlexFileUpload`) | Spatie (`FlexSpatieMediaLibraryFileUpload`) |
|--|-------------------------|-----------------------------------------------|
| **Stored state** | Relative path(s) on a Laravel disk | Media UUID(s) |
| **Needs Eloquent `HasMedia` record?** | No | Yes (create-form timing matters) |
| **Named conversions (`thumb`, `medium`…)** | No — one file (+ optional resize/optimize) | Yes — via model `registerMediaConversions()` |
| **Documents / PDFs / Office** | Yes (`documentsOnly()`, MIME presets) | Yes (any collection MIME) |
| **Images + webcam / URL import** | Yes | Same sources when using Flex Spatie field + Flex sources traits |
| **S3 / any Laravel disk** | `->disk('s3')` | `->disk('s3')` + Spatie collection disk |
| **Virus scan / quarantine / signed URLs** | Via Media Ingress | Via Media Ingress (stream-safe) |

```php
// Disk — path state
FlexFileUpload::make('contract')
    ->withRecommendedDefaults()
    ->documentsOnly()
    ->disk('s3')
    ->directory('contracts');

// Spatie — UUID state (factory sugar returns Spatie class; does not make FileUpload “bipedal”)
FlexFileUpload::spatie('gallery')
    ->collection('gallery')
    ->conversion('thumb')
    ->responsiveImages()
    ->disk('s3')
    ->multiple()
    ->reorderable();

// Explicit Spatie class (same thing)
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;

FlexSpatieMediaLibraryFileUpload::make('gallery')
    ->collection('gallery')
    ->conversion('thumb');
```

---

## Architecture

| Layer | Role |
|-------|------|
| Field shells | `FlexFileUpload`, `FlexImageUpload`, `FlexSpatieMediaLibraryFileUpload`, VoiceNote disk/Spatie, `SignatureField`, `FlexRichEditor` + `FileAttachmentProvider` |
| `MediaIngress` | Capsules: normalize → pre-AV → driver persist → post-AV + quarantine → observability (+ kind hooks) |
| Drivers | `DiskMediaDriver` (stream write), `SpatieMediaDriver` (`addMedia` / stream — not full-file `addMediaFromString($file->get())` for uploads) |

```php
use Bjanczak\FilamentFlexFields\Support\Media\FlexMedia;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaContext;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaKind;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaPayload;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaStorageDriver;

$ref = FlexMedia::persist(
    MediaPayload::fromTemporaryUploadedFile($file),
    new MediaContext(
        kind: MediaKind::Upload,
        driver: MediaStorageDriver::Disk,
        field: 'docs',
        disk: 's3',
        directory: 'uploads',
        filename: 'contract.pdf',
    ),
);

// $ref->relativePath()  // disk
// $ref->mediaUuid()     // Spatie
```

Payload helpers: `MediaPayload::fromTemporaryUploadedFile()`, `fromPath()`, `fromStream()`, `fromInlineString()` (small bodies only, e.g. signature SVG).

---

## Filesystems (local, S3, custom)

Any disk from `config/filesystems.php` works. Media Ingress and Spatie use Laravel’s `Storage` / Spatie disk name — there is no separate “S3 mode”.

```php
// config/filesystems.php — standard Laravel S3 disk
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        // ...
    ],
],
```

```php
FlexFileUpload::make('invoice')
    ->disk('s3')
    ->directory('invoices')
    ->visibility('private');

FlexFileUpload::spatie('photos')
    ->disk('s3')
    ->collection('photos')
    ->conversionsDisk('s3'); // optional separate disk for generated conversions
```

Package default disk when nothing is set:

```env
FLEX_FIELDS_MEDIA_DISK=s3
```

Private files + temporary URLs:

```php
use Bjanczak\FilamentFlexFields\Support\Media\FlexMedia;
use Illuminate\Support\Facades\Storage;

FlexMedia::registerSignedUploadUrlResolver(
    fn (string $disk, string $path, array $context): ?string => Storage::disk($disk)->temporaryUrl(
        $path,
        now()->addMinutes((int) config('filament-flex-fields.media_capture.signed_url_minutes', 15)),
    ),
);
```

Or enable the built-in Laravel temporary URL resolver:

```env
FLEX_FIELDS_MEDIA_AUTO_SIGNED_URLS=true
FLEX_FIELDS_MEDIA_SIGNED_URL_MINUTES=15
```

---

## Disk uploads in depth

### Documents

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;

FlexFileUpload::make('attachments')
    ->label('Documents')
    ->withRecommendedDefaults()
    ->documentsOnly()
    ->disk('s3')
    ->directory('deals/attachments')
    ->multiple()
    ->maxFiles(10)
    ->maxTotalSizeKb(20480)
    ->uploadSummary()
    ->showFileIcon()
    ->requireReplaceConfirmation();
```

### Images (single processed file — not Spatie thumbs)

Disk path stores **one** file. Optional processing resizes/optimizes that file; it does **not** create named Spatie conversions (`thumb` / `medium` / `large`).

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexImageUpload;

FlexImageUpload::make('cover')
    ->withRecommendedDefaults()
    ->disk('public')
    ->directory('covers')
    ->optimizeImages()
    ->optimizeImagesToWebp()
    ->maxImageWidth(1920)
    ->maxImageHeight(1080)
    ->imageEditor();
```

### Webcam + URL import

Same disk pipeline after capture/fetch. Works with `imagesOnly()` / `FlexImageUpload`.

```php
FlexImageUpload::make('vehicle_photo')
    ->withRecommendedDefaults()
    ->allowWebcamUpload() // HTTPS required
    ->allowUrlUpload()    // SSRF-safe server fetch
    ->optimizeImages()
    ->maxImageWidth(1920)
    ->disk('s3')
    ->directory('vehicles');
```

### Metadata sidecar

```php
FlexFileUpload::make('scan')
    ->withRecommendedDefaults()
    ->storeMetadataIn('scan_meta')
    ->disk('local')
    ->directory('scans');

// $data['scan'] => 'scans/….pdf'
// $data['scan_meta'] => ['original_name' => …, 'mime' => …, 'size' => …, 'width' => …]
```

Full FlexFileUpload API: [FlexFileUpload & FlexImageUpload](/docs/flexfileupload-and-fleximageupload).

---

## Spatie uploads in depth

### Install

```bash
composer require spatie/laravel-medialibrary filament/spatie-laravel-media-library-plugin
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

Model must implement `HasMedia` and define collections/conversions as in Spatie’s docs.

### Field API (Flex layer)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;

FlexSpatieMediaLibraryFileUpload::make('attachments')
    ->withRecommendedDefaults()
    ->collection('documents')
    ->disk('s3')
    ->conversionsDisk('s3')
    ->conversion('thumb')        // which conversion to show in the form UI
    ->responsiveImages()
    ->customProperties(['source' => 'admin'])
    ->multiple()
    ->reorderable()
    ->panelLayout('grid');
```

| Method | Meaning |
|--------|---------|
| `collection()` | Spatie media collection name |
| `disk()` | Disk for original files |
| `conversionsDisk()` | Disk for generated conversions |
| `conversion()` | Conversion name for **preview in Filament UI** |
| `responsiveImages()` | Spatie responsive images for this upload |
| `customProperties()` | Merged into Media custom properties (plus Flex `flex_capture` stamp) |

Inherited Filament Spatie / FileUpload APIs (`multiple()`, `reorderable()`, `acceptedFileTypes()`, …) remain available.

### Conversions: `registerMediaConversions()` — full Spatie

Flex Fields **does not redefine or limit** Spatie conversions. After ingress persists media, Spatie runs whatever you registered on the model (width, height, format, queue, `performOnCollections`, sharpen, Fit enums, etc.).

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')->useDisk('s3');
        $this->addMediaCollection('documents')->useDisk('s3');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10)
            ->nonQueued();

        $this
            ->addMediaConversion('medium')
            ->width(800)
            ->format('webp')
            ->queued();

        $this
            ->addMediaConversion('large')
            ->fit(\Spatie\Image\Enums\Fit::Max, 2000, 2000)
            ->performOnCollections('gallery');
    }
}
```

```php
FlexFileUpload::spatie('gallery')
    ->collection('gallery')
    ->conversion('thumb') // form shows thumb; Spatie still generates all registered conversions
    ->responsiveImages()
    ->multiple()
    ->reorderable();
```

Reading conversions in your app (Spatie API):

```php
$url = $product->getFirstMediaUrl('gallery', 'thumb');
$full = $product->getFirstMediaUrl('gallery'); // original
```

### Create form / missing record

Spatie needs a persisted `HasMedia` model. If the record is missing, ingress fails closed and records `upload.fail` with `reason: spatie_record_missing`.

Use Filament create strategies / save the model before Spatie uploads, or stick to disk until the record exists.

```php
FlexFileUpload::spatie('photos')
    ->collection('gallery')
    // Ensure the form’s model implements HasMedia and is available on create,
    // or use a deferred create pattern from Filament docs.
    ;
```

### Documents on Spatie

```php
FlexFileUpload::spatie('contracts')
    ->collection('documents')
    ->acceptedFileTypes([
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ])
    ->disk('s3');

// Conversions usually apply to images only — PDFs stay as originals unless you add custom Spatie pipelines.
```

---

## FormBuilder / schema config

```php
[
    'type' => 'file', // or 'image'
    'slug' => 'contract',
    'config' => [
        'storage_driver' => 'spatie', // preferred: 'disk' | 'spatie'
        'media_collection' => 'contracts',
        'conversion' => 'preview',
        'conversions_disk' => 's3',
        'responsive_images' => true,
        'custom_properties' => ['dept' => 'legal'],
        'disk' => 's3',
        'directory' => 'contracts', // disk driver mainly
        'multiple' => true,
        'documents_only' => true,
    ],
],
```

```php
[
    'type' => 'file',
    'slug' => 'scan',
    'config' => [
        'storage_driver' => 'disk',
        'disk' => 's3',
        'directory' => 'scans',
        'optimize_images' => true,
        'max_image_width' => 2000,
        'allow_webcam_upload' => true,
        'allow_url_upload' => true,
    ],
],
```

`use_spatie_media_library => true` still works as a **deprecated alias** when `storage_driver` is omitted.

Voice note:

```php
[
    'type' => 'voice_note',
    'slug' => 'call_note',
    'config' => [
        'storage_driver' => 'spatie',
        'media_collection' => 'voice-notes',
        'max_duration' => 180,
    ],
],
```

---

## Voice notes

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;

// Disk — path state + transcription into metadata
VoiceNoteRecorderField::make('note')
    ->disk('s3')
    ->directory('voice-notes')
    ->storeMetadataIn('note_meta')
    ->storeWaveformIn('note_meta')
    ->maxDuration(120);

// Spatie — UUID state, same recorder UI
VoiceNoteRecorderField::spatie('note')
    ->collection('voice-notes')
    ->disk('s3')
    ->storeMetadataIn('note_meta');
```

Transcription: bind `FLEX_FIELDS_MEDIA_TRANSCRIPTION` or `MediaCaptureOs::registerTranscriptionInterface()`. See [Voice note recorder](/docs/voicenoterecorderfield).

---

## Signatures

Primary state is always SVG or `ffstage:` — **never** a Spatie UUID as the signature value.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;

SignatureField::make('signature')
    ->disk('signatures', 's3') // alias of storeToDisk()
    ->spatieSink(
        collection: 'signatures',
        disk: 's3',
        uuidStatePath: 'signature_media_uuid', // optional sidecar UUID only
    )
    ->legalPack()
    ->timestampSeal()
    ->legalMetadataIn('signature_legal');
```

| API | Effect |
|-----|--------|
| `disk()` / `storeToDisk()` | Persist SVG via Media Ingress disk driver → `ffstage:…` token |
| `spatieSink()` | Copy SVG bytes into Media Library; form state unchanged |
| Legal pack | IP / UA / signer / document hash metadata |

See [SignatureField](/docs/signaturefield).

---

## FlexRichEditor attachments

`FileAttachmentProvider` stays Filament-owned. Media Ingress wraps AV + signed URLs around provider saves.

### Native disk variants (not Spatie)

```php
FlexRichEditor::make('body')
    ->fileAttachments(true)
    ->fileAttachmentsDisk('s3')
    ->scopedAttachmentDirectory('articles')
    ->imageVariants([
        'thumb' => ['max_long_edge' => 320, 'webp' => true],
        'large' => ['max_long_edge' => 2000, 'master' => true, 'webp' => true],
    ])
    ->responsiveImages()
    ->lazyImages();
```

### Spatie conversions for editor images

1. Model: `HasMedia` + conversions (helper trait available).
2. Form: Spatie file attachment provider from the Filament Spatie plugin + matching `imageVariants()` names.
3. Render: `makeFlexRichContentRenderer()` resolves conversion URLs.

```php
use Bjanczak\FilamentFlexFields\Support\RichEditor\Concerns\RegistersFlexRichEditorMediaConversions;

class Post extends Model implements HasMedia
{
    use InteractsWithMedia;
    use RegistersFlexRichEditorMediaConversions;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerFlexRichEditorMediaConversions([
            'thumb' => ['max_long_edge' => 320, 'webp' => true],
            'medium' => ['max_long_edge' => 1200, 'webp' => true],
            'large' => ['max_long_edge' => 2000, 'master' => true, 'webp' => true],
        ]);
    }
}
```

```php
FlexRichEditor::make('body')
    ->fileAttachmentProvider(/* Filament Spatie Media Library file attachment provider */)
    ->imageVariants([
        'thumb' => ['max_long_edge' => 320, 'webp' => true],
        'large' => ['max_long_edge' => 2000, 'master' => true, 'webp' => true],
    ])
    ->responsiveImages();
```

Full walkthrough: [FlexRichEditor — Spatie Media Library](/docs/flex-rich-editor#spatie-media-library-optional).

| | Disk attachments | Spatie attachments |
|--|------------------|--------------------|
| Variant files | Package `imageVariants()` on disk | Spatie `registerMediaConversions()` |
| TipTap `data-id` | Path | Media UUID |
| Orphan cleanup | `RichEditorAttachmentPruner` | Provider `cleanUpFileAttachments()` |

---

## Tenant / disk precedence

Resolved in this order:

1. Explicit field `->disk()` / Spatie disk argument  
2. Spatie collection / media-disk configuration  
3. `MediaCaptureTenantDiskResolver` (`tenant.disk`, `auto_disk`)  
4. Package `media_capture.disk` default  

Path-prefix tenancy (`directory_prefix`) is separate from Spatie collection tenancy. Tenant `auto_disk` **never silently overrides** an explicit or collection Spatie disk.

```env
FLEX_FIELDS_MEDIA_TENANT_DISK=tenant-s3
FLEX_FIELDS_MEDIA_TENANT_DIRECTORY_PREFIX=tenant-42
FLEX_FIELDS_MEDIA_TENANT_AUTO_DISK=true
```

```php
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureTenantDiskResolver;

MediaCaptureTenantDiskResolver::registerDiskResolver(
    fn (array $context): ?string => auth()->user()?->tenant_disk,
);

MediaCaptureTenantDiskResolver::registerDirectoryPrefixResolver(
    fn (array $context): ?string => 'tenant-'.auth()->user()?->tenant_id,
);
```

---

## Virus scan, quarantine, observability

| Env | Behavior |
|-----|----------|
| `FLEX_FIELDS_REQUIRE_VIRUS_SCAN=false` (default) | Dev fail-open — permissive scanner if none registered |
| `FLEX_FIELDS_REQUIRE_VIRUS_SCAN=true` | Fail-closed until host registers a scanner |
| `FLEX_FIELDS_SCAN_BEFORE_PERSIST=true` (default) | Scan before disk/Spatie persist |
| `FLEX_FIELDS_QUARANTINE_DISK=quarantine` | Stream-copy rejected files instead of hard delete |

```php
use Bjanczak\FilamentFlexFields\Support\Media\FlexMedia;

FlexMedia::registerVirusScanCallback(
    fn (string $path): bool => app(\App\Services\VirusScanner::class)->isClean($path),
);
```

**Spatie quarantine parity:** on post-persist AV failure, ingress quarantines media bytes, then deletes the Media row (not delete-only).

Events: `upload.success` / `upload.fail` via `ObservabilityHooks`, always with `correlation_id` when routed through ingress.

```php
use Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks;

ObservabilityHooks::on(ObservabilityHooks::EVENT_UPLOAD_FAIL, function (array $payload): void {
    // $payload['reason'], $payload['stage'], $payload['correlation_id'], $payload['adapter'], …
});
```

---

## Host wiring (PCI, legal, transcription)

```php
use Bjanczak\FilamentFlexFields\Support\Media\FlexMedia;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;

FlexMedia::registerVirusScanCallback(fn (string $path): bool => /* … */);
FlexMedia::registerSignedUploadUrlResolver(fn (string $disk, string $path, array $context): ?string => /* … */);

// Still supported (same runtime):
MediaCaptureOs::registerTokenizeCreditCardCallback(fn (string $pan): ?string => /* … */);
MediaCaptureOs::registerLegalSignerIdResolver(fn (): ?string => (string) auth()->id());
MediaCaptureOs::registerDocumentHashResolver(fn (): ?string => hash('sha256', $documentBody));
MediaCaptureOs::registerTranscriptionInterface(app(\App\Media\VoiceNoteTranscriber::class));
```

```env
FLEX_FIELDS_PCI_NEVER_STORE_PAN=true
FLEX_FIELDS_PCI_REQUIRE_TOKENIZATION=true
FLEX_FIELDS_MEDIA_TRANSCRIPTION=App\Media\VoiceNoteTranscriber
FLEX_FIELDS_TRANSCRIPTION_CIRCUIT_BREAKER=true
```

---

## Retention & prune

`MediaKind` → retention category:

| MediaKind | Policy key |
|-----------|------------|
| `upload`, `image`, `rich_attachment` | `uploads` |
| `voice_note` | `voice_notes` |
| `signature` | `signatures` |
| `temp_capture` | `temp_captures` |

```env
FLEX_FIELDS_RETENTION_SCHEDULE=true
FLEX_FIELDS_RETENTION_TEMP_CAPTURES=true
FLEX_FIELDS_RETENTION_TEMP_CAPTURES_DAYS=7
FLEX_FIELDS_RETENTION_VOICE_NOTES=false
FLEX_FIELDS_RETENTION_VOICE_NOTES_DAYS=365
```

```bash
php artisan flex-fields:prune-capture-media
php artisan flex-fields:prune-capture-media --category=voice_notes --dry-run
php artisan flex-fields:prune-capture-media --category=spatie
```

Spatie prune only deletes Media rows stamped with `custom_properties.flex_capture` (Flex ingress stamp, includes `kind`).

Configure collections to prune:

```php
// config/filament-flex-fields.php
'media_capture' => [
    'spatie' => [
        'prune_collections' => ['temp', 'voice-notes'],
    ],
],
```

---

## Playground note

`/admin/flex-fields-playground/file-upload` demos **disk** UX (`local`): documents, images, metadata, UI variants, file / URL / webcam sources. It does **not** demo Spatie collections or `registerMediaConversions`. Use a resource with `HasMedia` + `FlexFileUpload::spatie()` to verify conversions locally.

---

## What Flex documents vs Spatie/Filament

| Covered here | Use upstream docs |
|--------------|-------------------|
| Disk vs Spatie field choice + state contracts | Full Spatie conversion DSL / image drivers |
| Media Ingress capsules, AV, quarantine, tenant precedence | Spatie path generators, custom Media models |
| Flex field factories & FormBuilder `storage_driver` | Every Filament FileUpload / Spatie plugin option |
| Rich Editor disk variants + Spatie bridge | TipTap internals |
| Signature sink, VoiceNote Spatie | — |

---

## Migration

See [Media Ingress migration](/docs/media-ingress-migration).

## Roadmap (not in this major)

Async AV pending UX, multipart resume, content-addressed uploads, GDPR erase API, soft-delete sync, HEIC first-class, collection ACL policies, async transcription jobs.

## Related

- [Media Ingress migration](/docs/media-ingress-migration)
- [FlexFileUpload & FlexImageUpload](/docs/flexfileupload-and-fleximageupload)
- [FlexRichEditor](/docs/flex-rich-editor)
- [SignatureField](/docs/signaturefield)
- [Voice note recorder](/docs/voicenoterecorderfield)
- [CreditCardField](/docs/creditcardfield)
- [SRE runbook](/docs/sre-runbook)
- [Compliance pack](/docs/compliance-pack)

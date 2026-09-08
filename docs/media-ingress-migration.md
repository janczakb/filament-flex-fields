# Media Ingress migration (major)

Upgrade notes when moving from field-local helpers / `MediaCaptureOs`-as-product to **Media Ingress**.

Full product docs with examples: [Media Ingress](/docs/media-capture-os).

## What changed

| Before | After |
|--------|--------|
| Field-local virus scan / observability | Shared `MediaIngress` capsules |
| `SpatieMediaCaptureAdapter` as public SoT | Internal path; prefer `FlexMedia` / `MediaIngress`. Adapter kept as deprecated BC wrapper (stream-safe) |
| `addMediaFromString($file->get())` | Path / stream Spatie ingest |
| Spatie post-AV = delete Media only | Quarantine file + delete Media row when quarantine disk configured |
| `use_spatie_media_library` | Prefer `storage_driver: 'disk'\|'spatie'` |
| `SignatureStorage` as public API | Internal store behind `SignatureField::disk()` |
| One class for disk + Spatie | **Not supported** — keep `FlexFileUpload` + `FlexSpatieMediaLibraryFileUpload` |

## App code checklist

### 1. FormBuilder / JSON schemas

```php
// Before
'config' => ['use_spatie_media_library' => true, 'media_collection' => 'docs'],

// After
'config' => ['storage_driver' => 'spatie', 'media_collection' => 'docs'],
```

### 2. Field construction

```php
// Before (still valid)
FlexSpatieMediaLibraryFileUpload::make('docs')->collection('documents');

// After (sugar)
FlexFileUpload::spatie('docs')->collection('documents');

// Voice notes
VoiceNoteRecorderField::spatie('note')->collection('voice-notes');
```

### 3. Signature optional Media copy

```php
SignatureField::make('sig')
    ->disk()
    ->spatieSink('signatures', uuidStatePath: 'sig_uuid');
// Do NOT store Spatie UUIDs as the signature field state
```

### 4. Host scanners / signed URLs

```php
use Bjanczak\FilamentFlexFields\Support\Media\FlexMedia;

FlexMedia::registerVirusScanCallback(...);
FlexMedia::registerSignedUploadUrlResolver(...);

// MediaCaptureOs::register* aliases still work
```

### 5. Custom Spatie persist

```php
// Before — avoid
$record->addMediaFromString($temporaryUploadedFile->get())->toMediaCollection(...);

// After
use Bjanczak\FilamentFlexFields\Support\Media\FlexMedia;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaContext;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaKind;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaPayload;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaStorageDriver;

FlexMedia::persist(
    MediaPayload::fromTemporaryUploadedFile($temporaryUploadedFile),
    new MediaContext(
        kind: MediaKind::Upload,
        driver: MediaStorageDriver::Spatie,
        field: 'docs',
        record: $record,
        collection: 'documents',
    ),
);
```

## State contracts (unchanged intent)

| Field | Disk state | Spatie state |
|-------|------------|--------------|
| File / Image / VoiceNote | relative path(s) | Media UUID(s) |
| Signature | SVG or `ffstage:` | **unchanged** + optional sink UUID in metadata |
| RichEditor attachments | provider IDs / disk paths | Spatie provider UUIDs in document JSON |

## Deprecations (supported this major)

- Public use of `SpatieMediaCaptureAdapter` (still works)
- Public `SignatureStorage` (still works; prefer field APIs)
- `MediaCaptureOs` as the documented product name (hooks still work)
- `use_spatie_media_library` config key

## Not deprecated

- `FlexSpatieMediaLibraryFileUpload` as a first-class type
- Filament `FileAttachmentProvider` on rich editor
- Spatie `registerMediaConversions()` on your models (unchanged, fully supported)

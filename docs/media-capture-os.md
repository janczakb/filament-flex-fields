# Media & Capture OS

Enterprise media pipeline for upload, capture, PCI, voice notes, signatures, and **Spatie Media Library** integration.

## Overview

Media & Capture OS (`MediaCaptureOs`) centralizes host-app hooks:

| Hook | Purpose |
|------|---------|
| Virus scan | Scan temp files pre-persist and stored paths post-persist; reject + observability on failure |
| Signed URLs | Mint time-limited download URLs for private disks |
| PCI tokenization | Never persist raw primary account numbers |
| Barcode capture | `BarcodeScannerField` emits `barcode.capture` via `MediaCaptureOs::recordBarcodeCapture()` |
| Auto signed URLs | `FLEX_FIELDS_MEDIA_AUTO_SIGNED_URLS=true` (default) — Laravel temporary URLs when supported |
| Retention audit | `retention.prune` observability event from `flex-fields:prune-capture-media` |
| Default transcription | `NullVoiceNoteTranscription` + optional circuit breaker; override via `FLEX_FIELDS_MEDIA_TRANSCRIPTION` |
| Retention | Category-based prune policies + artisan command + scheduler |
| Legal audit | Signature IP/UA/signer/document hash metadata |

Disk-based uploads (`FlexFileUpload`, `VoiceNoteRecorderField`) and Spatie uploads (`FlexSpatieMediaLibraryFileUpload`) both invoke the same hooks.

## Virus scan posture

| Env | Behavior |
|-----|----------|
| `FLEX_FIELDS_REQUIRE_VIRUS_SCAN=false` (default) | Dev-friendly fail-open — permissive no-op scanner registered |
| `FLEX_FIELDS_REQUIRE_VIRUS_SCAN=true` | **Fail-closed** — uploads rejected unless host registers `registerVirusScanCallback()` |
| `FLEX_FIELDS_SCAN_BEFORE_PERSIST=true` (default) | Scan Livewire temp file **before** disk/Spatie persist |
| `FLEX_FIELDS_QUARANTINE_DISK=…` | Move rejected files to quarantine disk instead of hard delete |

Scan stages emit `upload.fail` with `reason: virus_scan` and `stage: pre_persist|post_persist` on both disk and Spatie adapters.

## Spatie Media Library

Install optional dependencies:

```bash
composer require spatie/laravel-medialibrary filament/spatie-laravel-media-library-plugin
```

Use the Flex adapter instead of raw Filament Spatie upload:

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;

FlexSpatieMediaLibraryFileUpload::make('attachments')
    ->collection('documents')
    ->conversion('thumb')
    ->responsiveImages()
    ->withRecommendedDefaults();
```

### Enterprise Spatie path

`FlexSpatieMediaLibraryFileUpload` keeps Spatie's native `addMediaFromString()` persistence (UUID state, relationship save, conversions). It **does not** replace Spatie saves with disk-path `FlexFileUpload` hooks.

After each upload:

1. Optional **pre-persist** virus scan on the temp file.
2. Spatie `Media` row is created with `flex_capture` custom properties.
3. Optional **post-persist** virus scan on the media path.
4. Failed scans delete the `Media` row, record `upload.fail`, and return `null`.
5. Successful saves record `upload.success`.
6. Display URLs can be overridden via `MediaCaptureOs::registerSignedUploadUrlResolver()`.

`flex-fields:prune-capture-media --category=spatie` only deletes media rows stamped with `custom_properties.flex_capture`.

### FormBuilder

Enable Spatie on file/image field definitions:

```php
[
    'type' => 'file',
    'slug' => 'contract',
    'config' => [
        'use_spatie_media_library' => true,
        'media_collection' => 'contracts',
        'conversion' => 'preview',
        'responsive_images' => true,
    ],
],
```

## Multi-tenant storage

Configure tenant disk/directory prefix:

```env
FLEX_FIELDS_MEDIA_TENANT_DISK=tenant-s3
FLEX_FIELDS_MEDIA_TENANT_DIRECTORY_PREFIX=tenant-42
FLEX_FIELDS_MEDIA_TENANT_AUTO_DISK=true
```

- `directory('voice-notes')` automatically applies tenant directory prefix when configured.
- `scopedDirectory()` always resolves through `MediaCaptureTenantDiskResolver`.
- `auto_disk=true` wires `resolveDisk()` as the default upload disk.
- `SignatureStorage` uses the same disk resolver when no explicit disk is passed.

## Host wiring

Register callbacks in `AppServiceProvider`:

```php
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;

MediaCaptureOs::registerVirusScanCallback(fn (string $path): bool => app(Scanner::class)->clean($path));

MediaCaptureOs::registerSignedUploadUrlResolver(
    fn (string $disk, string $path, array $context): ?string => Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(15)),
);

MediaCaptureOs::registerTokenizeCreditCardCallback(
    fn (string $pan): ?string => app(PaymentGateway::class)->tokenize($pan),
);

MediaCaptureOs::registerLegalSignerIdResolver(fn (): ?string => (string) auth()->id());
MediaCaptureOs::registerDocumentHashResolver(fn (): ?string => hash('sha256', $documentBody));
```

Production PCI + AV:

```env
FLEX_FIELDS_PCI_NEVER_STORE_PAN=true
FLEX_FIELDS_PCI_REQUIRE_TOKENIZATION=true
FLEX_FIELDS_REQUIRE_VIRUS_SCAN=true
FLEX_FIELDS_QUARANTINE_DISK=quarantine
```

Or bind transcription via config:

```env
FLEX_FIELDS_MEDIA_TRANSCRIPTION=App\Media\VoiceNoteTranscriber
```

## Capture fields

### CreditCardField

When a tokenization callback is registered, dehydrate stores `{ token, last4, name, expiry }` — never the full PAN. CVV is never persisted. See [CreditCardField](/docs/creditcardfield) for PCI env vars.

### VoiceNoteRecorderField

When `storeMetadataIn()` / `storeWaveformIn()` is set:

- Transcription interface appends `transcript` after upload.
- Client persists `waveform` peaks JSON (`peaks`, `sample_count`, `duration`).

### SignatureField

Legal pack helpers:

```php
SignatureField::make('signature')
    ->legalPack()
    ->timestampSeal()
    ->legalMetadataIn('signature_legal')
    ->inkTrail()
    ->pdfPreview();
```

- `legalPack()` — requires visible ink (`SignatureLegalPack::requiresInk()`).
- `timestampSeal()` — writes legal audit metadata (`sealed_at`, `signer_id`, `ip_address`, `user_agent`, `document_hash`, `signature_path_count`) to `legalMetadataIn` on save.

## Retention

Configure categories in `config/filament-flex-fields.php` under `media_capture.retention`. Schedule is registered automatically when `retention.schedule_enabled=true`.

```bash
php artisan flex-fields:prune-capture-media
php artisan flex-fields:prune-capture-media --category=voice_notes --dry-run
php artisan flex-fields:prune-capture-media --category=spatie
```

| Category | Default |
|----------|---------|
| `temp_captures` | enabled, 7 days |
| `voice_notes` | disabled, 365 days when enabled |
| `uploads` | disabled |
| `signatures` | disabled |

## Related

- [FlexFileUpload & FlexImageUpload](/docs/flexfileupload-and-fleximageupload)
- [CreditCardField](/docs/creditcardfield)
- [SignatureField](/docs/signaturefield)
- [Voice note recorder](/docs/voicenoterecorderfield)
- [SRE runbook](/docs/sre-runbook)

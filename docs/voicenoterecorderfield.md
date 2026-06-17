# VoiceNoteRecorderField

![VoiceNoteRecorderField](/art/sc-12.png)

[← Back to Table of Contents](index.md)


### Summary

In-browser voice recorder with real-time frequency visualizer, inline playback (waveform + play/pause), and Filament `FileUpload` storage integration. Records audio from the microphone, previews locally, then uploads to Livewire temporary storage and persists to disk on form save.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField` |
| **State type** | `string\|null` — stored path on disk after save; keyed `TemporaryUploadedFile` object during upload |
| **FieldType** | — (use the PHP class directly; not mapped via `FieldType`) |
| **Extends** | `FlexFileUpload` → `Filament\Forms\Components\FileUpload` |

### Basic usage

#### Default — upload on form submit

Recording stays in the browser until the form is submitted. A loader is shown while the file uploads before save.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;

VoiceNoteRecorderField::make('voice_note')
    ->label('Voice message')
    ->required()
    ->disk('public')
    ->directory('voice-notes')
    ->maxDuration(120);
```

#### Immediate upload after recording

Uploads to Livewire temporary storage right after recording stops. Delete removes the file from storage (when `deleteFileOnRemove()` is enabled — default in `setUp()`).

```php
VoiceNoteRecorderField::make('voice_note')
    ->uploadImmediately()
    ->disk('public')
    ->directory('voice-notes');
```

Equivalent explicit defer:

```php
VoiceNoteRecorderField::make('voice_note')
    ->uploadOnSubmit(); // default behaviour
```

### Upload flow

| Stage | What happens |
|-------|----------------|
| **Record** | Audio captured in the browser (`MediaRecorder`); playback uses a local blob URL |
| **JS upload** | `$wire.upload('{statePath}.{uuid}', file)` — file lands in **Livewire temp** (`config/livewire.php` → `temporary_file_upload`) |
| **Form save** | Filament `beforeStateDehydrated` → `saveUploadedFiles()` moves/stores the file to **`disk()` + `directory()`** |
| **Persisted state** | Relative path string, e.g. `voice-notes/01H….webm` |

**Temporary path** is configured **globally** for all Livewire uploads (not per field):

```php
// config/livewire.php
'temporary_file_upload' => [
    'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
    'directory' => 'livewire-tmp', // default when null
],
```

**Final path** is configured **per field** with inherited `FileUpload` API:

```php
->disk('public')
->directory('voice-notes/recordings')
->visibility('public')
->moveFiles() // optional: move instead of copy when temp and target share the same disk
```

### State format

| Phase | State shape | Example |
|-------|-------------|---------|
| Empty | `null` | — |
| After JS upload (before save) | Associative array keyed by UUID | `['a1b2…' => TemporaryUploadedFile]` |
| After form dehydrate / save | `string` — path on `disk()` | `'voice-notes/01H….webm'` |
| Existing record (edit) | `string` path | Loaded for playback via `getInitialAudioUrl()` |

### Validation

| Rule | Detail |
|------|--------|
| `required()` | Recording must be present before submit |
| Inherited `FileUpload` | `maxSize()`, `acceptedFileTypes()`, etc. |

Default accepted MIME types (set in `setUp()`): `audio/*`, `audio/mpeg`, `audio/wav`, `audio/webm`, `audio/ogg`, `audio/x-m4a`, `audio/aac`.

### Configuration API

#### `maxDuration(int|Closure $seconds)`


Maximum recording length in seconds. Timer stops recording automatically. Default: `120` (2 minutes).

```php
->maxDuration(30)
```

#### `uploadImmediately(bool|Closure $condition = true)`


Upload to Livewire temp storage immediately after recording. Playback stays visible; background upload progress is shown on the pill.

```php
VoiceNoteRecorderField::make('field_name')
    ->uploadImmediately(true);
```
#### `uploadOnSubmit(bool|Closure $condition = true)`


Defer upload until form submit (default). Shows “Preparing voice note for save…” while uploading on submit.

```php
VoiceNoteRecorderField::make('field_name')
    ->uploadOnSubmit(true);
```
#### Icon overrides


| Method | Default (Gravity UI) | Config key (`filament-flex-fields.ui`) |
|--------|----------------------|----------------------------------------|
| `playIcon()` | `PlayFill` | `audio_play_icon` |
| `pauseIcon()` | `PauseFill` | `audio_pause_icon` |
| `microphoneIcon()` | `Microphone` | `microphone_icon` |
| `stopIcon()` | `Minus` | `stop_icon` |
| `trashIcon()` | `TrashBin` | `trash_icon` |
| `checkmarkIcon()` | `Check` | `checkmark_icon` |

```php
VoiceNoteRecorderField::make('voice_note')
    ->microphoneIcon('heroicon-o-microphone')
    ->maxDuration(60)
    ->size('lg');
```

#### `size(string|ControlSize|Closure $size)`


`sm`, `md`, `lg`. Inherited from `FlexFileUpload` / `HasControlSize`.

```php
VoiceNoteRecorderField::make('field_name')
    ->size('md');
```

### Inherited FileUpload API

`VoiceNoteRecorderField` uses the standard Filament file upload pipeline (not FilePond UI). Common options:

| Method | Description |
|--------|-------------|
| `disk(string\|Closure\|null $name)` | Target filesystem disk for persisted files |
| `directory(string\|Closure\|null $directory)` | Subdirectory on that disk |
| `visibility(string\|Closure\|null $visibility)` | `public` or `private` |
| `maxSize(int\|Closure\|null $size)` | Max file size in KB |
| `required()` / `nullable()` | Validation |
| `deleteFileOnRemove()` | Remove file from disk when user deletes recording (enabled by default) |
| `storeFileNamesIn(string\|Closure\|null $path)` | Sibling state for original filenames |
| `preserveFilenames()` / `moveFiles()` | See [FlexFileUpload](#flexfileupload--fleximageupload) |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getMaxDuration()` | `int` | Max recording seconds |
| `shouldUploadImmediately()` | `bool` | Immediate vs deferred upload |
| `getInitialAudioUrl()` | `string\|null` | Public URL for existing persisted file (edit forms) |
| `getPlayIcon()` / `getPauseIcon()` / … | `string\|BackedEnum\|Htmlable` | Resolved control icons |

### CSS classes

| Class | Role |
|-------|------|
| `fff-voice-recorder` | Root wrapper |
| `fff-voice-recorder--{sm\|md\|lg}` | Size modifier |
| `fff-voice-recorder__record-btn` | Start recording |
| `fff-voice-recorder__recording` | Active recording UI + canvas visualizer |
| `fff-voice-recorder__playback-pill` | Playback bar (play, waveform, time, delete) |
| `fff-voice-recorder__waveform` | Scrubbable waveform bars |
| `fff-voice-recorder__container.is-submitting` | Deferred upload in progress on submit |

Alpine component: `voice-note-recorder-field` (built to `resources/dist/components/voice-note-recorder-field.js`).

### Playground

Registered under **Audio field** playground (`AudioFieldPlayground`):

| Variant key | Description |
|-------------|-------------|
| `voice_note__basic` | Default recorder, deferred upload |
| `voice_note__sm` / `voice_note__lg` | Size variants |
| `voice_note__with_limit` | `maxDuration(30)` |
| `voice_note__immediate` | `uploadImmediately()` |

Use **Dump JSON** in the playground to inspect temp upload state (`livewire-file:…`). Permanent disk storage requires a real form save (playground has no save action).

### Implementation notes

- Requires **microphone permission** and a browser with `MediaRecorder` (Chrome, Safari, Firefox).
- Prefers `audio/mp4` when supported (Safari), falls back to `audio/webm` / `audio/ogg`.
- Local playback uses blob URLs; duration falls back to measured recording time when WebM metadata is missing.
- Deferred upload hooks the parent `<form>` `submit` event (capture phase), uploads via Livewire, then calls `form.requestSubmit()`.
- Delete calls `removeUploadedFile` (temp) or `deleteUploadedFile` (persisted) via `callSchemaComponentMethod` when `schemaComponentKey` is available.
- Translations: `filament-flex-fields::default.audio.*` (`record_label`, `uploading_on_submit`, etc.).

---

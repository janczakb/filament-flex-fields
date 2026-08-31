---
title: "VoiceNoteRecorderField"
description: In-browser voice recorder with real-time visualizer, inline playback, and Filament storage integration.
---

![VoiceNoteRecorderField](/art/sc-12.png)

[← Back to Table of Contents](/docs/index)

### Summary

In-browser voice recorder featuring a real-time frequency visualizer, inline playback (waveform + play/pause), and seamless Filament **FileUpload** storage integration. Records audio from the microphone, previews locally, and handles the entire upload/persistence pipeline.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField` |
| **State type** | `string\|null` — stored path on disk after save |
| **FieldType** | *(use the class directly)* |
| **Extends** | `FlexFileUpload` → `Filament\Forms\Components\FileUpload` |
| **Playground** | `audio-field` slug in Flex Fields playground |

---

### Basic usage

#### Default — Upload on Form Submit
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;

VoiceNoteRecorderField::make('voice_note')
    ->label('Voice message')
    ->disk('public')
    ->directory('voice-notes')
    ->maxDuration(120); // 2 minutes
```

#### Immediate Upload after Recording
```php
VoiceNoteRecorderField::make('feedback_audio')
    ->uploadImmediately()
    ->disk('s3')
    ->directory('feedback');
```

---

### State & validation

#### Stored value
State is a relative path string to the stored audio file on your configured disk.

```php
$record->voice_note; // 'voice-notes/01H….webm'
```

#### Validation rules
Inherits all `FileUpload` validation rules (`maxSize()`, `required()`, etc.). Default accepted MIME types include `audio/*`, `audio/mpeg`, `audio/wav`, `audio/webm`, `audio/aac`.

### Media Capture OS metadata

Integrates with [Media & Capture OS](/docs/media-capture-os) for transcription and waveform sidecars:

```php
VoiceNoteRecorderField::make('voice_note')
    ->directory('voice-notes')
    ->storeMetadataIn('voice_note_meta')
    ->storeWaveformIn('voice_note_meta'); // defaults to storeMetadataIn path
```

| Sidecar key | When populated |
|-------------|----------------|
| `transcript` | After upload when `FLEX_FIELDS_MEDIA_TRANSCRIPTION` or `registerTranscriptionInterface()` is bound |
| `waveform` | After recording finalize — `{ peaks, sample_count, duration }` |

Retention: configure `media_capture.retention.voice_notes` and schedule `flex-fields:prune-capture-media`.

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `maxDuration(int $seconds)` | Setup | `120` | Max recording length |
| `uploadImmediately(bool $cond)` | Setup | `false` | Upload to temp storage right after recording |
| `uploadOnSubmit(bool $cond)` | Setup | `true` | Defer upload until form submit |
| `microphoneIcon(string $icon)` | Setup | config | Custom microphone icon |
| `stopIcon(string $icon)` | Setup | config | Custom stop icon |
| `trashIcon(string $icon)` | Setup | config | Custom delete icon |
| `playIcon(string $icon)` | Setup | config | Custom play icon |
| `pauseIcon(string $icon)` | Setup | config | Custom pause icon |
| `size(string $size)` | Setup | `'md'` | `sm`, `md`, `lg` |
| `focusOutline(bool $cond)` | Setup | `false` | Enable shared focus ring |

---

### Inherited FileUpload API

Commonly used methods from the standard Filament FileUpload:
- `disk(string $name)`
- `directory(string $directory)`
- `visibility(string $visibility)`
- `maxSize(int $size)`
- `deleteFileOnRemove()` (Enabled by default)

---

### Real-world examples

#### Support Ticket Voice Note
```php
VoiceNoteRecorderField::make('issue_description')
    ->label('Describe the issue')
    ->disk('public')
    ->directory('tickets/voice-notes')
    ->maxDuration(60)
    ->required()
    ->helperText('Record up to 1 minute.');
```

#### Private S3 Recording
```php
VoiceNoteRecorderField::make('private_note')
    ->disk('s3')
    ->directory('internal/voice')
    ->visibility('private')
    ->uploadImmediately();
```

---

### Playground

`/admin/flex-fields-playground/audio-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [AudioField](/docs/audiofield) | Playback-only audio pill |
| [FlexFileUpload](/docs/flexfileupload-and-fleximageupload) | Standard file picker for existing audio files |
| [VideoField](/docs/videofield) | Video playback and recording (if supported) |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-voice-recorder` | Root wrapper |
| `fff-voice-recorder--{sm\|md\|lg}` | Size variant |
| `fff-voice-recorder__record-btn` | Microphone button |
| `fff-voice-recorder__recording` | Active recording UI |
| `fff-voice-recorder__visualizer` | Real-time frequency canvas |
| `fff-voice-recorder__playback-pill` | Playback bar wrapper |
| `fff-voice-recorder__waveform` | Scrubbable waveform |
| `is-submitting` | Deferred upload in progress |

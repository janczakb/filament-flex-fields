---
title: "AudioField"
description: Compact audio player with waveform visualization, play/pause control, and optional loop.
---

![AudioField](/art/sc-12.png)

[← Back to Table of Contents](/docs/index)

### Summary

Compact audio player featuring a modern "iMessage-style" voice note pill. Includes waveform visualization, play/pause controls, and optional looping. State is typically stored as a URL string to the audio file.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField` |
| **State type** | `string\|null` — audio URL (when not using static `src()`) |
| **FieldType** | `audio` |
| **Playground** | `audio-field` slug in Flex Fields playground |

---

### Basic usage

#### Dynamic Audio (from State)
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField;

AudioField::make('preview_url')
    ->label('Voice message')
    ->fullWidth()
    ->loop();
```

#### Static Audio (Fixed Source)
```php
AudioField::make('jingle')
    ->src('/audio/notification.mp3')
    ->waveform([20, 45, 80, 60, 35, 90, 50, 30])
    ->size('lg');
```

---

### State & validation

#### Stored value
State is a string representing the audio URL.

```php
$record->preview_url; // 'https://example.com/audio.mp3'
```

#### Validation rules (built-in)
Ensures the state is a valid media URL string.

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `src(string $src)` | Setup | `null` | Fixed audio URL (overrides state) |
| `fullWidth(bool $condition)` | Setup | `false` | Stretch player to container width |
| `loop(bool $condition)` | Setup | `false` | Loop playback |
| `waveform(array $waveform)` | Setup | default | Custom peak heights `8`–`100` |
| `playIcon(string $icon)` | Setup | config | Custom play icon |
| `pauseIcon(string $icon)` | Setup | config | Custom pause icon |
| `size(string $size)` | Setup | `'md'` | `sm`, `md`, `lg` |
| `readOnly(bool $condition)` | Setup | `false` | Disable interaction |
| `rounding(string $rounding)` | Setup | config | Border radius token |

---

### Real-world examples

#### Post-Recording Preview
```php
AudioField::make('recorded_audio')
    ->label('Listen to your recording')
    ->live()
    ->visible(fn ($state) => filled($state));
```

#### Fixed Demo Track
```php
AudioField::make('demo')
    ->src('https://cdn.example.com/demo.mp3')
    ->readOnly()
    ->waveform(fn () => [30, 70, 45, 90, 55, 40, 75, 50]);
```

---

### Playground

`/admin/flex-fields-playground/audio-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [VoiceNoteRecorderField](/docs/voicenoterecorderfield) | Record audio from the microphone |
| [VideoField](/docs/videofield) | Full video player with controls |
| [FlexFileUpload](/docs/flexfileupload-and-fleximageupload) | Standard file upload for audio files |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-audio-field-field` | Root wrapper |
| `fff-audio-field-field--{sm\|md\|lg}` | Size variant |
| `fff-audio-field__waveform` | Waveform bars container |
| `fff-audio-field__bar` | Individual waveform bar |
| `fff-audio-field__play` | Play/pause button |
| `fff-audio-field__duration` | Time display |

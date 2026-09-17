---
title: "AudioField"
description: Compact audio player with waveform visualization, play/pause control, optional loop, and optional client-side Whisper speech-to-text.
---

![AudioField](/art/sc-12.webp)

[← Back to Table of Contents](/docs/index)

### Summary

Compact audio player featuring a modern "iMessage-style" voice note pill. Includes waveform visualization, play/pause controls, optional looping, and optional **client-side Whisper transcription** (inspired by [Xenova/whisper-web](https://github.com/xenova/whisper-web)). State is typically stored as a URL string to the audio file — the transcript is shown in the UI only and is **not** written back to field state.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField` |
| **State type** | `string\|null` — audio URL (when not using static `src()`) |
| **FieldType** | `audio` |
| **Transcription** | Optional — browser Whisper ONNX; requires `php artisan fff:whisper:install` |
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

#### With client-side transcription
```php
AudioField::make('voicemail_url')
    ->label('Voicemail')
    ->transcription()
    ->fullWidth();
```

Install the Whisper runtime once per app first — see [Installing the Whisper runtime](#installing-the-whisper-runtime). Without it the player still works; Transcribe stays soft-disabled.

See [Speech-to-text (Whisper)](#speech-to-text-whisper) for model, language, and full details.

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

### Speech-to-text (Whisper)

Enable optional **client-side** speech-to-text on any `AudioField` that has a playable audio URL. Transcription runs entirely in the user's browser via `@xenova/transformers` — no server API key, no upload of audio to your backend for STT.

```php
AudioField::make('voice_message')
    ->label('Voice message')
    ->transcription()
    ->fullWidth();
```

When transcription is enabled **and** the Whisper runtime is installed, the field renders:

1. **Transcribe Audio** — fetches the audio URL, decodes it with Web Audio, runs Whisper, and shows the result below the player.
2. **Settings** (gear) — submenu for model, language, task, **Multilingual**, and **Quantized** toggles (same UX pattern as Xenova/whisper-web).

If the runtime is missing, the audio player still works. The Transcribe UI is soft-disabled and the field shows an install CTA with `php artisan fff:whisper:install`.

#### Installing the Whisper runtime

The browser ONNX / WASM runtime (**~40 MB**) is **not** included in the Composer package and is **not** published by `php artisan filament:assets`. Only apps that use `->transcription()` need it.

> **Important:** `filament:assets` syncs Flex Fields CSS/JS and small static media (beeps, NPS emoji, etc.). It never installs Whisper. Use the commands below.

##### One-time install

```bash
php artisan fff:whisper:install
```

What this does:

1. Downloads the pinned `@xenova/transformers` dist files (currently **2.17.2**) from the jsDelivr npm CDN.
2. Verifies each file with **SHA-256** against the package manifest (`WhisperRuntimeManifest`).
3. Writes them to `public/filament-flex-fields-assets/whisper/`.
4. Writes `public/filament-flex-fields-assets/whisper/.installed.json` (version + hashes + timestamp).
5. Does **not** install source maps (`.map` files are excluded on purpose).

After a successful install, hard-refresh the admin panel and use `->transcription()` as usual.

##### Inspect status

```bash
php artisan fff:whisper:status
```

Shows package pin, public directory, whether files are present, and any missing or SHA-256-mismatched files.

##### CI / deploy gate

When your app **requires** transcription in production, fail the pipeline if the runtime is missing or corrupt:

```bash
php artisan fff:whisper:install --check
```

Exit code `0` = verified. Exit code `1` = missing or invalid (print includes the fix command).

##### Re-download

```bash
php artisan fff:whisper:install --force
```

Re-fetches all files even when the current install already verifies. Use after a failed partial download, disk restore, or when upgrading Flex Fields to a release that pins a new transformers version (hashes change — status will report mismatches until you reinstall).

##### Command reference

| Command | Purpose |
|---------|---------|
| `php artisan fff:whisper:install` | Download + SHA-256 verify + write `public/.../whisper/` |
| `php artisan fff:whisper:install --force` | Same, always re-download |
| `php artisan fff:whisper:install --check` | Verify only (CI); exit `1` if missing/corrupt |
| `php artisan fff:whisper:status` | Human-readable installed vs expected |

##### Files on disk

| Path | Role |
|------|------|
| `public/filament-flex-fields-assets/whisper/transformers.min.js` | Transformers runtime module |
| `public/filament-flex-fields-assets/whisper/ort-wasm*.wasm` | ONNX Runtime WASM variants (simd / threaded) |
| `public/filament-flex-fields-assets/whisper/.installed.json` | Lockfile written by install |

URLs are resolved at runtime via `FlexFieldAssets::whisperRuntimeModuleSrc()` and `whisperRuntimeWasmBaseSrc()` (cache-busted with `?v=`).

##### Deploy notes

- Run `fff:whisper:install` on each environment that needs transcription (or copy the verified `public/filament-flex-fields-assets/whisper/` directory as a build artifact).
- Commit the whisper directory only if your deploy strategy requires it; many teams install during CI/CD instead.
- Do **not** expect `composer install` or `filament:assets` alone to restore Whisper after an upgrade.
- Upgrading from an older Flex Fields release that **bundled** Whisper in Composer: remove any stale vendor/public copies from the old layout, then run `fff:whisper:install` once.

##### Network requirements for install

The install command needs outbound HTTPS to the jsDelivr npm CDN (`cdn.jsdelivr.net/npm/@xenova/transformers@…`). Air-gapped hosts must vendor the verified files into `public/filament-flex-fields-assets/whisper/` by another channel, then confirm with `fff:whisper:install --check`.

Model **weights** (Hugging Face) are still downloaded in the **browser** on first Transcribe — that is separate from this artisan install.

#### Transcript is not field state

The transcript appears in `.fff-audio-field__transcript` for the current page session. It is **not** saved to the Eloquent attribute / form state. To persist text, bind a separate field (for example `Textarea`) or use server-side transcription on [VoiceNoteRecorderField](/docs/voicenoterecorderfield) via [Media Ingress](/docs/media-capture-os).

#### How it works

| Step | Behavior |
|------|----------|
| First **Transcribe** click | Loads the installed Whisper runtime (`transformers.min.js` + ONNX WASM from `fff:whisper:install`), then downloads model weights from Hugging Face |
| Later clicks (same model + quantized setting) | Reuses in-memory pipeline cache; weights served from **browser cache** (`useBrowserCache: true`) — no full re-download on page reload |
| Audio fetch | `fetch()` on the resolved audio URL — must be same-origin or CORS-enabled |
| Decode | `AudioContext.decodeAudioData()` — stereo is down-mixed to mono |
| Inference | 30 s chunks, 5 s stride; optional language + task when multilingual mode is on |

Progress during the first model load shows the downloading file and percentage (for example `encoder_model.onnx 42%`). Model load times out after **10 minutes** — enable **Quantized** or pick a smaller model on slow connections.

#### Requirements & limitations

| Requirement | Detail |
|-------------|--------|
| **HTTPS** | Required for stable Web Audio / WASM in production |
| **CORS** | Cross-origin audio URLs must allow browser `fetch` from your admin origin |
| **Whisper runtime** | Opt-in host install — see [Installing the Whisper runtime](#installing-the-whisper-runtime) |
| **Network (first Transcribe)** | Hugging Face CDN must be reachable to download ONNX **model weights** (browser) |
| **Read-only** | Transcribe is disabled when `readOnly()` is true |
| **Empty audio** | Shows "No speech detected" when Whisper returns blank text |

#### Configuration API (transcription)

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `transcription(bool\|Closure $condition)` | Setup | `false` | Enable Whisper toolbar + transcript panel |
| `transcriptionSettings(bool\|Closure $condition)` | Setup | `true` | Show gear menu (model / language / task / toggles) |
| `whisperModel(string\|Closure\|null $model)` | Setup | config | Hugging Face model id (see catalog below) |
| `whisperQuantized(bool\|Closure $condition)` | Setup | config (`true`) | Use quantized ONNX weights (smaller download) |
| `whisperMultilingual(bool\|Closure $condition)` | Setup | `true` | Multilingual models + language/task pickers |
| `whisperLanguage(string\|Closure\|null $language)` | Setup | `null` | ISO 639-1 hint (`pl`, `en`, …) or auto-detect |
| `whisperTask(string\|Closure $task)` | Setup | `'transcribe'` | `'transcribe'` or `'translate'` (English output) |

Invalid tasks throw `InvalidArgumentException` at config evaluation time.

#### Global defaults (`config/filament-flex-fields.php`)

These config keys apply when the matching field method is not set:

| Config key | Env variable | Used by |
|------------|--------------|---------|
| `default_model` | `FLEX_FIELDS_WHISPER_MODEL` | `whisperModel()` fallback — default `Xenova/whisper-tiny` |
| `default_quantized` | `FLEX_FIELDS_WHISPER_QUANTIZED` | `whisperQuantized()` fallback — default `true` |

```php
'audio' => [
    'transcription' => [
        'default_model' => env('FLEX_FIELDS_WHISPER_MODEL', 'Xenova/whisper-tiny'),
        'default_quantized' => env('FLEX_FIELDS_WHISPER_QUANTIZED', true),
        // Reserved for future global defaults:
        'default_multilingual' => env('FLEX_FIELDS_WHISPER_MULTILINGUAL', true),
        'default_language' => env('FLEX_FIELDS_WHISPER_LANGUAGE'),
        'default_task' => env('FLEX_FIELDS_WHISPER_TASK', 'transcribe'),
        'languages' => [],
    ],
],
```

Field-level defaults when you call `->transcription()` without extra methods:

| Setting | Default |
|---------|---------|
| Multilingual | `true` — set with `whisperMultilingual()` |
| Language | auto-detect — set with `whisperLanguage('pl')` etc. |
| Task | `'transcribe'` — set with `whisperTask('translate')` |
| Settings menu | visible — hide with `transcriptionSettings(false)` |

#### Model catalog & settings toggles

Models and filtering follow [whisper-web's AudioManager](https://github.com/xenova/whisper-web/blob/main/src/components/AudioManager.tsx):

| Model id | Multilingual | Notes |
|----------|--------------|-------|
| `Xenova/whisper-tiny` | Yes | Default — ~41 MB quantized |
| `Xenova/whisper-base` | Yes | |
| `Xenova/whisper-small` | Yes | |
| `Xenova/whisper-medium` | Yes | Largest Xenova model in catalog |
| `distil-whisper/distil-medium.en` | No (English) | Shown when Multilingual is off |
| `distil-whisper/distil-large-v2` | No (English) | Distil — hidden when Multilingual is on |

| Toggle | Effect |
|--------|--------|
| **Multilingual** ON | Xenova multilingual models; Language + Task submenus enabled; appends `.en` stripped |
| **Multilingual** OFF | English-only variants (`.en` suffix where applicable); distil models available |
| **Quantized** ON | Smaller ONNX files (single size in catalog) — **recommended default** |
| **Quantized** OFF | Full-precision weights where the catalog lists two sizes |

The settings menu lists models with approximate download size, for example `Xenova/whisper-tiny (41 MB)`.

The **Language** submenu exposes the full Whisper ISO 639-1 catalog (90+ languages plus **Auto detect**), aligned with whisper-web.

#### Real-world examples

##### Polish voice note with fixed language

```php
AudioField::make('support_call')
    ->transcription()
    ->whisperLanguage('pl')
    ->whisperModel('Xenova/whisper-small')
    ->fullWidth();
```

##### English-only, quantized distil model

```php
AudioField::make('voicemail')
    ->transcription()
    ->whisperModel('distil-whisper/distil-medium.en')
    ->whisperMultilingual(false)
    ->whisperQuantized(true);
```

##### Transcribe button only (no user-facing settings)

```php
AudioField::make('recording')
    ->transcription()
    ->transcriptionSettings(false)
    ->whisperModel('Xenova/whisper-tiny')
    ->whisperQuantized(true);
```

##### Translate foreign audio to English

```php
AudioField::make('foreign_clip')
    ->transcription()
    ->whisperTask('translate')
    ->whisperMultilingual(true);
```

#### Troubleshooting

| Symptom | Likely cause | What to try |
|---------|--------------|-------------|
| Install CTA / Transcribe UI missing though `->transcription()` is set | Whisper runtime not installed on this host | `php artisan fff:whisper:install`, then `fff:whisper:status`, hard refresh |
| `fff:whisper:install --check` exits `1` | Missing files or SHA-256 mismatch | `fff:whisper:install --force`; confirm deploy includes `public/.../whisper/` |
| SHA-256 mismatch during install | Truncated download or CDN/proxy tampering | Retry `--force`; allowlist `cdn.jsdelivr.net`; check disk space |
| "Whisper runtime assets are not configured" | Runtime URLs empty / not installed | `fff:whisper:install` (not `filament:assets` alone) |
| "Could not fetch the audio file" | CORS or 403 on audio URL | Same-origin URL, signed URL with CORS, or proxy |
| "This browser cannot decode audio" | Missing / blocked `AudioContext` | HTTPS, supported browser |
| Model download timeout | Slow network or full (non-quantized) weights | Enable **Quantized**, use `whisper-tiny` |
| Stuck on loading after first success | Rare pipeline error | Hard refresh; check browser console |
| Transcription failed (generic) | ONNX / memory / corrupt audio | Smaller model, shorter clip, check console |

User-facing copy is translatable under `filament-flex-fields::default.audio.transcription_*` in `resources/lang`.

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

Two sections:

| Section | What it demonstrates |
|---------|----------------------|
| **Audio player** | Sizes, full width, custom waveform |
| **Speech-to-text (Whisper Web)** | `->transcription()` demos — requires `fff:whisper:install` on the host; otherwise shows an install CTA |

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [VoiceNoteRecorderField](/docs/voicenoterecorderfield) | Record audio from the microphone; server-side transcript via Media Capture OS after upload |
| [VideoField](/docs/videofield) | Full video player with controls |
| [FlexFileUpload](/docs/flexfileupload-and-fleximageupload) | Standard file upload for audio files |
| [FlexTextareaField](/docs/flextextareafield) | Persist transcribed text — pair with `AudioField::transcription()` if you need stored copy |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-audio-field-field` | Root wrapper |
| `fff-audio-field-field--{sm\|md\|lg}` | Size variant |
| `fff-audio-field.has-transcription` | Transcription enabled — STT layout tokens |
| `fff-audio-field__waveform` | Waveform bars container |
| `fff-audio-field__bar` | Individual waveform bar |
| `fff-audio-field__play` | Play/pause button |
| `fff-audio-field__duration` | Time display |
| `fff-audio-field__stt-toolbar` | Transcribe button + settings gear row |
| `fff-audio-field__stt-button` | **Transcribe Audio** control |
| `fff-audio-field__settings-menu` | Model / language / task submenu popover |
| `fff-audio-field__stt-status` | Loading / error status line |
| `fff-audio-field__transcript` | Transcript output container |
| `fff-audio-field__transcript-text` | Transcript text (`<pre>`) |

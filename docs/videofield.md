---
title: "VideoField"
description: Custom video player with optional YouTube embed, skip controls, PiP, fullscreen, and compact control layout.
---

![VideoField](/art/videofield.webp)

[← Back to Table of Contents](/docs/index)

### Summary

Custom video player with optional YouTube embed, skip controls, PiP, fullscreen, and compact control layout.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\VideoField` |
| **State type** | `string\|null` — video URL or YouTube/Vimeo link |
| **Model cast** | `'video_url' => 'string'` |
| **FieldType** | `video` |
| **Playground** | `video-field` slug in Flex Fields playground |

---

### Basic usage

#### HTML5 video with poster

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VideoField;

VideoField::make('tutorial')
    ->src('https://cdn.example.com/tutorial.mp4')
    ->poster('/images/tutorial-poster.jpg')
    ->title('Getting Started')
    ->subtitle('Learn the basics in 5 minutes')
    ->skipSeconds(15)
    ->pictureInPictureable()
    ->compactControls();
```

#### YouTube embed from state

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VideoField;

VideoField::make('promo_video')
    ->label('Promo Video')
    ->default('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
    ->ratio('16:9')
    ->youtubeNoCookie()
    ->autoHideControls();
```

---

### State & validation

#### Stored value

State string = direct video URL or YouTube/Vimeo URL.

```php
$record->video_url; // string|null — e.g. "https://www.youtube.com/watch?v=..."
```

#### Validation rules

Built-in validation ensures the URL is a valid string and matches supported media patterns.

| Rule | Detail |
|------|--------|
| `nullable` | State may be empty |
| `string` | State must be string when present |
| `media_url` | Internal rule checking for valid Mapbox/YouTube/Vimeo/Direct patterns |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `src(string\|Closure\|null $src)` | Setup | `null` | Static video source (overrides state) |
| `poster(string\|Closure\|null $poster)` | Setup | `null` | Video poster image URL |
| `title(string\|Closure\|null $title)` | Setup | `null` | Overlay title |
| `subtitle(string\|Closure\|null $subtitle)` | Setup | `null` | Overlay subtitle |
| `ratio(string\|Closure\|null $ratio)` | Setup | `'16:9'` | Aspect ratio (e.g. `16:9`, `4:3`, `1:1`) |
| `fullWidth(bool\|Closure $condition = true)` | Setup | `false` | Expand player to fill container width |
| `controls(bool\|Closure $condition = true)` | Setup | `true` | Show playback controls |
| `nativeControls(bool\|Closure $condition = true)` | Setup | `false` | Use browser native controls |
| `autoplay(bool\|Closure $condition = true)` | Setup | `false` | Start playback automatically |
| `loop(bool\|Closure $condition = true)` | Setup | `false` | Restart video when finished |
| `muted(bool\|Closure $condition = true)` | Setup | `false` | Start playback muted |
| `playsInline(bool\|Closure $condition = true)` | Setup | `true` | Play inline on mobile devices |
| `skipSeconds(int\|Closure $seconds)` | Setup | `10` | Seconds to skip forward/backward |
| `fullscreenable(bool\|Closure $condition = true)` | Setup | `true` | Enable fullscreen mode |
| `autoHideControls(bool\|Closure $condition = true)` | Setup | `true` | Hide controls when inactive |
| `pictureInPictureable(bool\|Closure $condition = true)` | Setup | `false` | Enable Picture-in-Picture mode |
| `volumeControl(bool\|Closure $condition = true)` | Setup | `true` | Show volume slider |
| `allowYoutube(bool\|Closure $condition = true)` | Setup | `true` | Enable YouTube support |
| `allowVimeo(bool\|Closure $condition = true)` | Setup | `true` | Enable Vimeo support |
| `youtubeNoCookie(bool\|Closure $condition = true)` | Setup | `true` | Use youtube-nocookie.com |
| `controlsLayout(string\|Closure $layout)` | Setup | `'default'` | `default` or `compact` |
| `playIcon(...)` | Setup | `PlayFill` | Custom play icon |
| `pauseIcon(...)` | Setup | `PauseFill` | Custom pause icon |
| `volumeIcon(...)` | Setup | `VolumeFill` | Custom volume icon |
| `muteIcon(...)` | Setup | `VolumeSlashFill` | Custom mute icon |
| `fullscreenIcon(...)` | Setup | `Expand` | Custom fullscreen icon |
| `placeholderIcon(...)` | Setup | `Video` | Custom placeholder icon |

#### `ratio()`

Supports standard aspect ratios. Invalid strings throw an `InvalidArgumentException`.

```php
VideoField::make('video')->ratio('4:3');
VideoField::make('video')->ratio('1:1');
```

#### `controlsLayout()`

Use `compact` for a minimal control bar:

```php
VideoField::make('video')->compactControls();
```

---

### Real-world examples

#### Background video (Hero)

```php
VideoField::make('hero_video')
    ->src('/videos/hero.mp4')
    ->autoplay()
    ->loop()
    ->muted()
    ->controls(false)
    ->fullWidth();
```

#### YouTube training video

```php
VideoField::make('training')
    ->label('Internal Training')
    ->allowYoutube()
    ->youtubeNoCookie()
    ->autoHideControls()
    ->pictureInPictureable();
```

---

### Playground

`/admin/flex-fields-playground/video-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [AudioField](/docs/audiofield) | For audio-only playback |
| [FlexFileUpload](/docs/flexfileupload) | To allow users to upload video files |
| [LinkPreviewField](/docs/link-preview-field) | For simple link previews with thumbnails |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-video-field-field` | Root wrapper |
| `fff-video-field-field--{sm\|md\|lg}` | Size modifier |
| `fff-video-field__stage` | Video area |
| `fff-video-field__controls` | Control bar |
| `fff-video-field__controls--compact` | Compact layout modifier |
| `fff-video-field__metadata` | Title/subtitle overlay |

---

### Performance

| Mechanism | What it does |
|-----------|--------------|
| **Lazy Loading** | Video assets and Alpine components load only when the field is visible |
| **YouTube Facade** | Shows a thumbnail/poster until the user clicks play to save bandwidth |
| **Safe URLs** | Automatic sanitization of media URLs for security |

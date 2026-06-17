# FlexTextareaField

![FlexTextareaField](/art/sc-3.png)

[← Back to Table of Contents](index.md)


### Summary

SaaS-style **multi-line** textarea with optional toolbar, autosize, emoji picker, and speech dictation. Extends Filament `Textarea`.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField` |
| **Extends** | `Filament\Forms\Components\Textarea` |
| **State type** | `string\|null` |
| **FieldType** | `flex_textarea` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

FlexTextareaField::make('message')
    ->label('Message')
    ->placeholder('Write something…')
    ->maxLength(500)
    ->characterCounter()
    ->emojiPicker()
    ->speechDictation()
    ->footer('Markdown supported')
    ->toolbarSelect(
        'model',
        ['claude-4.6-opus' => 'Claude 4.6 Opus', 'gpt-5' => 'GPT-5'],
        icon: Heroicon::CpuChip,
    )
    ->toolbarAction(
        Action::make('bold')->icon(Heroicon::Bold)->action(fn () => null),
    )
    ->submitAction(
        Action::make('send')->icon(Heroicon::PaperAirplane)->color('primary'),
    );
```

### Layout

- Rounded shell with autosizing textarea
- Optional **toolbar** row: emoji, prefix actions, toolbar selects, suffix actions (e.g. Send)
- Optional **footer** text and character counter
- Default textarea content is **server-rendered** for instant display on page load

### Custom configuration API

#### Caching actions


Improves rendering performance by caching prefix and suffix action definitions:

```php
FlexTextareaField::make('body')
    ->cachePrefixActions()
    ->cacheSuffixActions();
```

#### Icon overrides


Override trigger icons for toolbar actions:

```php
FlexTextareaField::make('body')
    ->emojiPicker()
    ->speechDictation()
    ->emojiIcon('heroicon-o-face-smile')
    ->microphoneIcon('heroicon-o-microphone');
```
### Inherited Textarea API

Includes `rows()`, `cols()`, `autosize()`, `disableGrammarly()`, `maxLength()`, `minLength()`, `live()`, and all standard Filament `Field` methods.

Default setup calls `autosize()`, `rows(1)`, and `disableGrammarly()`.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `variant` | `variant()` |
| `character_counter` | `characterCounter()` |
| `animated_autosize` | `animatedAutosize()` |
| `max_height` | `maxHeight()` |
| `footer` | `footer()` |
| `rows` | `rows()` |
| `max_length` | `maxLength()` |
| `speech_dictation` | `speechDictation()` |
| `speech_dictation_language` | `speechDictationLanguage()` |
| `emoji_picker` | `emojiPicker()` |
| `emoji_picker_locale` | `emojiPickerLocale()` |
| `speech_dictation_label` | `speechDictationLabel()` |
| `emoji_picker_label` | `emojiPickerLabel()` |

Toolbar selects, toolbar actions, and `submitAction()` are **not** configurable via `FlexFieldFormBuilder` — use the fluent API in PHP.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getInitialHeightRem()` | `float` | Server-rendered initial textarea height in `rem` from `rows()` (formula: `max(rows × 1.5 + 0.25, 2.25)`). |
| `getToolbarSelects()` | `list&lt;array&lt;statePath, options, placeholder, icon, initialValue, initialLabel&gt;&gt;` | Resolved toolbar dropdown configs with server-rendered initial labels. |
| `isSubmitDisabled()` | `bool` | Whether the submit action should be disabled (trimmed state is blank). |

---

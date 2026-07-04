---
title: "FlexTextareaField"
description: Smart auto-sizing textarea with character counters, speech dictation, and emoji support.
---

![FlexTextareaField](/art/sc-3.png)

[← Back to Table of Contents](/docs/index)

### Summary

A highly capable alternative to Filament's native Textarea. `FlexTextareaField` features smooth animated auto-sizing, character counters, integrated speech-to-text dictation, and an emoji picker. It also supports custom toolbar actions and selects.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField` |
| **State type** | `string\|null` |
| **Model cast** | `'bio' => 'string'` · `'content' => 'text'` |
| **FieldType** | `flex-textarea` |
| **Playground** | `flex-textarea` slug in Flex Fields playground |
| **Extends** | `Filament\Forms\Components\Textarea` |

---

### Basic usage

#### Auto-sizing with counter
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;

FlexTextareaField::make('comment')
    ->label('Your Comment')
    ->characterCounter()
    ->maxLength(500);
```

#### Speech and Emoji support
```php
FlexTextareaField::make('message')
    ->emojiPicker()
    ->speechDictation()
    ->footer('Pro tip: Use the microphone icon to dictate your message.');
```

---

### State & validation

#### Stored value
The field stores a standard string. It inherits all validation rules from Filament's `Textarea`.

```php
$record->comment; // "Hello world!"
```

#### Character Counter
When `characterCounter()` is enabled, a live counter appears in the footer. If `maxLength()` is set, it shows "X / Max".

---

### Toolbar & Actions

#### Toolbar Selects
Add pill-style dropdowns to the field header to quickly insert templates or change states.

```php
FlexTextareaField::make('response')
    ->toolbarSelect('template_id', [
        'welcome' => 'Welcome Message',
        'support' => 'Support Reply',
    ], icon: 'heroicon-o-document-text');
```

#### Submit Actions
Add a primary action button (like "Send") directly into the textarea suffix.

```php
FlexTextareaField::make('chat_message')
    ->submitAction(
        Action::make('send')
            ->icon('heroicon-s-paper-airplane')
            ->action(fn ($state) => /* ... */)
    );
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `variant(string\|Closure $variant)` | Setup | `'primary'` | `primary`, `secondary`, `flat`, `soft` |
| `characterCounter(bool\|Closure $cond)`| Setup | `false` | Show live character count |
| `animatedAutosize(bool\|Closure $cond)`| Setup | `true` | Smooth height transitions |
| `maxHeight(string\|Closure\|null $h)` | Setup | `'24rem'` | Maximum auto-grow height |
| `footer(string\|Closure\|null $text)` | Setup | `null` | Help text below the field |
| `speechDictation(bool\|Closure $cond)` | Setup | `false` | Enable browser speech-to-text |
| `emojiPicker(bool\|Closure $cond)`    | Setup | `false` | Enable native emoji picker |
| `toolbarSelect(...)`                  | Setup | — | Add dropdown to toolbar |
| `submitAction(Action\|Closure $act)`  | Setup | — | Add primary suffix action |
| `size(string\|Closure $size)`         | Setup | `'md'` | `sm`, `md`, `lg` |
| `rounding(string\|Closure $round)`    | Setup | config | Border radius token |

---

### Real-world examples

#### Modern Chat Input
```php
FlexTextareaField::make('message')
    ->placeholder('Type a message...')
    ->rows(1)
    ->maxHeight('12rem')
    ->emojiPicker()
    ->speechDictation()
    ->submitAction(
        Action::make('send')
            ->icon('heroicon-s-paper-airplane')
            ->color('primary')
    )
    ->variant('soft')
    ->rounding('full');
```

---

### Playground

`/admin/flex-fields-playground/flex-textarea`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| `RichEditor` | When HTML formatting (bold, links) is required |
| [FlexTextInput](/docs/flextextinput) | Single-line inputs |
| [VoiceNoteRecorder](/docs/voicenoterecorderfield) | Recording audio files instead of dictation |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-flex-textarea-field` | Root wrapper |
| `fff-flex-textarea-field--{variant}` | Theme variant |
| `fff-flex-textarea-field__input` | The actual textarea element |
| `fff-flex-textarea-field__toolbar` | Top actions area |
| `fff-flex-textarea-field__footer` | Bottom info area |
| `fff-flex-textarea-field__counter` | Character count text |
| `fff-flex-textarea-field__speech-btn` | Microphone button |
| `fff-flex-textarea-field__emoji-btn` | Emoji button |

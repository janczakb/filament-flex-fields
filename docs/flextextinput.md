---
title: "FlexTextInput"
description: Single-line text input with pill layout, grouped action buttons, and optional toolbar features.
---

![FlexTextInput](/art/sc-25.png)

[← Back to Table of Contents](/docs/index)

### Summary

**Single-line** text input with pill layout, grouped action buttons, and optional toolbar features. Extends Filament `TextInput` — **all native TextInput APIs remain available**.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput` |
| **Extends** | `Filament\Forms\Components\TextInput` |
| **State type** | `string\|null` (or numeric types when using `numeric()`, etc.) |
| **Model cast** | `'name' => 'string'` · `'email' => 'string'` |
| **FieldType** | `flex_text_input` |
| **Playground** | `flex-text-input` slug in Flex Fields playground |

Also suitable for mapped types such as `email`, `password`, `url`, `phone`, `slug`, and `search` when configured through `FlexFieldFormBuilder`.

---

### Basic usage

#### Standard text input with icons

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Filament\Support\Icons\Heroicon;

FlexTextInput::make('email')
    ->label('Email')
    ->email()
    ->prefixIcon(Heroicon::Envelope)
    ->hintIcon(Heroicon::InformationCircle, 'Used for login and notifications.')
    ->live(debounce: 750)
    ->loading()
    ->default('hello@example.com');
```

#### Password with strength meter

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;

FlexTextInput::make('password')
    ->password()
    ->revealable()
    ->copyable()
    ->passwordStrength()
    ->default('secret-password');
```

---

### State & validation

#### Stored value

State is a **string** (or numeric when using `numeric()`).

```php
$record->name; // string|null — e.g. "John Doe"
```

#### Validation rules

Works with all standard Filament validation rules: `required()`, `email()`, `url()`, `numeric()`, `maxLength()`, etc.

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `variant(string\|Closure $variant)` | Setup | `'primary'` | Visual style: `primary`, `secondary`, `flat`, `soft` |
| `speechDictation(bool\|Closure $condition = true)` | Setup | `false` | Enable voice-to-text dictation |
| `speechDictationLanguage(string\|Closure\|null $language)` | Setup | `null` | Language code for dictation (e.g. `en-US`) |
| `emojiPicker(bool\|Closure $condition = true)` | Setup | `false` | Enable emoji picker action |
| `characterCounter(bool\|Closure $condition = true)` | Setup | `false` | Show character count meta row |
| `clearable(bool\|Closure $condition = true)` | Setup | `false` | Show clear button (×) |
| `loading(bool\|Closure $condition = true)` | Setup | `false` | Show spinner during Livewire requests |
| `passwordStrength(bool\|Closure $condition = true)` | Setup | `false` | Show password strength meter |
| `passwordStrengthLabels(array\|Closure $labels)` | Setup | defaults | Custom labels for strength scores 0–4 |
| `verificationStatus(string\|Htmlable\|Closure\|null $status)` | Setup | `null` | Show verification badge text |
| `verificationStatusIcon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Setup | `SealCheck` | Custom verification icon |
| `verificationStatusColor(string\|Closure $color)` | Setup | `'primary'` | Color for verification badge |
| `copyIcon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Setup | `Copy` | Custom copy action icon |
| `showPasswordIcon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Setup | `Eye` | Custom show password icon |
| `hidePasswordIcon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Setup | `EyeClosed` | Custom hide password icon |
| `emojiIcon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Setup | `FaceSmile` | Custom emoji picker icon |
| `microphoneIcon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Setup | `Microphone` | Custom dictation icon |
| `size(string\|ControlSize\|Closure $size)` | Setup | `'md'` | Control size: `sm`, `md`, `lg` |
| `rounding(string\|Closure\|null $rounding)` | Setup | config | Border radius token |

#### `passwordStrengthLabels()`

```php
FlexTextInput::make('password')
    ->password()
    ->passwordStrength()
    ->passwordStrengthLabels([
        'Very weak', 'Weak', 'Fair', 'Good', 'Strong'
    ]);
```

#### `verificationStatus()`

Ideal for verified e-mails or domains:

```php
FlexTextInput::make('email')
    ->verificationStatus('Verified')
    ->verificationStatusColor('success')
    ->verificationStatusIcon('heroicon-o-check-badge');
```

---

### Real-world examples

#### Search input with clear button

```php
FlexTextInput::make('search')
    ->label('Search records')
    ->placeholder('Type to filter...')
    ->prefixIcon('heroicon-o-magnifying-glass')
    ->clearable()
    ->live(debounce: 500);
```

#### Multi-language dictation

```php
FlexTextInput::make('comment')
    ->speechDictation()
    ->speechDictationLanguage('pl-PL')
    ->emojiPicker();
```

---

### Playground

`/admin/flex-fields-playground/flex-text-input`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexTextareaField](/docs/flextextareafield) | For multi-line text input |
| [PhoneField](/docs/phonefield) | For formatted phone numbers with country flags |
| [CurrencyField](/docs/currencyfield) | For locale-aware currency input |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-flex-text-input-field` | Root wrapper |
| `fff-flex-text-input-field--{sm\|md\|lg}` | Size modifier |
| `fff-flex-text-input-field--{variant}` | Visual variant |
| `fff-flex-text-input__track` | Input container |
| `fff-flex-text-input__actions` | Action button group |
| `fff-flex-text-input__meta` | Meta row (counter, strength) |

---

### Performance

| Mechanism | What it does |
|-----------|--------------|
| **Lazy Alpine** | `flex-text-input.js` loaded on demand via Filament `x-load` |
| **Server-side Meta** | Character counter and strength bar are server-rendered for zero layout flash |
| **Optimized Icons** | Uses Gravity UI icons by default for a consistent SaaS look |

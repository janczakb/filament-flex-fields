---
title: "FlexVerificationCode"
description: OTP / verification code input with grouped digit boxes, paste support, and optional account-verify chrome.
---

[← Back to Table of Contents](/docs/index)

### Summary

OTP / verification code input with grouped digit boxes, paste support, optional auto-submit, loading indicator, and optional account-verify chrome (heading, description, footer link action).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode` |
| **State type** | `string` — normalized code (no separators) |
| **Model cast** | `'verification_code' => 'string'` |
| **FieldType** | `verification_code` |
| **Playground** | `verification-code` slug in Flex Fields playground |

---

### Basic usage

#### Standard 6-digit OTP

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;

FlexVerificationCode::make('otp')
    ->label('Verification code')
    ->length(6)
    ->groups([3, 3])
    ->groupSeparator('-')
    ->required();
```

#### Alphanumeric with auto-submit

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;

FlexVerificationCode::make('backup_code')
    ->length(8)
    ->allowedCharacters('alphanumeric')
    ->autoSubmit()
    ->autoSubmitMethod('verifyOtp')
    ->loading();
```

---

### State & validation

#### Stored value

State is a **normalized string** of exactly `length()` characters. Separators are display-only.

```php
$record->verification_code; // string — e.g. "123456"
```

#### Validation rules

| Check | Detail |
|-------|--------|
| `required()` | Non-empty after normalization |
| Characters | Must match `allowedCharacters` pattern (`numeric` or `alphanumeric`) |
| Length | Exactly `length()` when non-empty |

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `length(int\|Closure $length)` | Setup | `6` | Total characters (1–16) |
| `groups(array\|Closure\|null $groups)` | Setup | `[3, 3]` | Group sizes summing to `length()` |
| `groupSeparator(string\|Closure\|null $separator)` | Setup | `'-'` | Visual separator between groups |
| `allowedCharacters(string\|Closure $allowedCharacters)` | Setup | `'numeric'` | `numeric` or `alphanumeric` |
| `color(string\|Closure\|null $color)` | Setup | `'primary'` | Accent color for active digit |
| `autoSubmit(bool\|Closure $condition = true)` | Setup | `false` | Submit when all digits are filled |
| `autoSubmitMethod(string\|Closure\|null $method)` | Setup | `null` | Livewire method to call on completion |
| `submitUsing(Closure $callback)` | Setup | `null` | PHP callback to run on completion |
| `loading(bool\|Closure $condition = true)` | Setup | `false` | Show spinner during Livewire requests |
| `heading(string\|Htmlable\|Closure\|null $heading)` | Setup | `null` | Title above the inputs |
| `description(string\|Htmlable\|Closure\|null $description)` | Setup | `null` | Supporting copy below the heading |
| `footer(string\|Htmlable\|Closure\|null $footer)` | Setup | `null` | Muted text before the footer action |
| `footerAction(Action\|Closure\|null $action)` | Setup | `null` | Link-style action beside the footer text |
| `size(string\|ControlSize\|Closure $size)` | Setup | `'md'` | Control size: `sm`, `md`, `lg` |
| `rounding(string\|Closure\|null $rounding)` | Setup | config | Border radius token |

#### `submitUsing()`

Run a PHP callback when the code is complete. Inject `$state`, `$livewire`, `$set`, etc.

```php
FlexVerificationCode::make('code')
    ->submitUsing(function (string $code, $livewire) {
        $livewire->verifyCode($code);
    });
```

#### `footerAction()`

Register a link-style action (e.g. **Resend**):

```php
FlexVerificationCode::make('otp')
    ->footer("Didn't receive a code?")
    ->footerAction(fn () => $livewire->resendCode());
```

---

### Real-world examples

#### Account verification screen

```php
FlexVerificationCode::make('otp')
    ->hiddenLabel()
    ->heading('Verify your account')
    ->description("We've sent a 6-digit code to your email.")
    ->footer("Didn't receive it?")
    ->footerAction(fn () => /* resend logic */)
    ->length(6)
    ->autoSubmit();
```

#### 2FA backup codes

```php
FlexVerificationCode::make('backup_code')
    ->length(8)
    ->allowedCharacters('alphanumeric')
    ->groups([4, 4])
    ->groupSeparator(' ');
```

---

### Playground

`/admin/flex-fields-playground/verification-code`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexTextInput](/docs/flextextinput) | For standard single-line text inputs |
| [PhoneField](/docs/phonefield) | For entering phone numbers |
| [NumberStepper](/docs/numberstepper) | For incrementing/decrementing numeric values |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-verification-code` | Digit inputs root |
| `fff-verification-code__input` | Single digit cell |
| `fff-verification-code__separator` | Group separator |
| `fff-verification-code-layout` | Vertical stack for heading/footer chrome |
| `fff-verification-code-layout__heading` | Title |
| `fff-verification-code-layout__footer` | Footer row |

---

### Performance

| Mechanism | What it does |
|-----------|--------------|
| **Smart Paste** | Distributes pasted codes across cells instantly |
| **Auto-focus** | Automatically moves focus to the next cell after input |
| **Zero Flash** | Server-renders the digit cells to prevent layout shifts |

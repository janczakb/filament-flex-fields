# FlexVerificationCode

[← Powrót do spisu treści](../README.md)


### Summary

OTP / verification code input with grouped digit boxes, paste support, optional auto-submit, loading indicator, and optional account-verify chrome (heading, description, footer link action).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode` |
| **State type** | `string` — normalized code (no separators) |
| **FieldType** | `verification_code` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;

FlexVerificationCode::make('otp')
    ->label('Verification code')
    ->length(6)
    ->groups([3, 3])
    ->groupSeparator('-')
    ->required();

FlexVerificationCode::make('backup_code')
    ->length(8)
    ->allowedCharacters('alphanumeric')
    ->autoSubmit()
    ->autoSubmitMethod('verifyOtp')
    ->loading();
```

Server-side callback on complete code:

```php
FlexVerificationCode::make('code')
    ->submitUsing(function (string $code, $livewire) {
        $livewire->verifyCode($code);
    });
```

Account verification layout (heading, masked destination, resend link):

```php
use Filament\Actions\Action;

FlexVerificationCode::make('otp')
    ->hiddenLabel()
    ->heading('Verify account')
    ->description("We've sent a code to a****@gmail.com")
    ->footer("Didn't receive a code?")
    ->footerAction(
        Action::make('resend')
            ->label('Resend')
            ->link()
            ->action(fn () => /* resend logic */),
    )
    ->length(6)
    ->groups([3, 3])
    ->groupSeparator('-');
```

Shorthand footer action (default **Resend** link label from translations):

```php
FlexVerificationCode::make('otp')
    ->footerAction(fn () => $livewire->resendCode());
```

Pair `hiddenLabel()` with `heading()` so the visible title replaces the standard Filament field label. When a heading is set, it is also used as the digit group `aria-label`.

### State format

Normalized uppercase alphanumeric string of exactly `length()` characters. Separators are display-only.

Default schema config uses `groups: [3, 3]` and `group_separator: '-'` for `123-456` layout.

### Validation

| Check | Detail |
|-------|--------|
| `required()` | Non-empty after normalize |
| Characters | Must match `allowedCharacters` pattern |
| Length | Exactly `length()` when non-empty |

### Configuration API

#### `length(int|Closure $length)`


Total characters `1`–`16`. Default: `6`.

```php
FlexVerificationCode::make('field_name')
    ->length(10);
```
#### `groups(array|Closure|null $groups)` / `groupSizes()`


Group sizes summing to `length()`. `null` = single group. Default in schema: `[3, 3]`.

```php
FlexVerificationCode::make('field_name')
    ->groups(['value1', 'value2'])
    ->groupSizes();
```
#### `groupSeparator(string|Closure|null $separator)`


Visual separator between groups (e.g. `-`). Display only.

```php
FlexVerificationCode::make('field_name')
    ->groupSeparator('value');
```
#### `allowedCharacters(string|Closure $allowedCharacters)`


`numeric` (default) or `alphanumeric`.

```php
FlexVerificationCode::make('field_name')
    ->allowedCharacters('value');
```
#### `color(string|Closure|null $color)`


Filament accent. Default: `primary`.

```php
FlexVerificationCode::make('field_name')
    ->color('primary');
```
#### `size(string|ControlSize|Closure $size)`


`sm`, `md`, `lg`. Default: `md`.

```php
FlexVerificationCode::make('field_name')
    ->size('md');
```
#### `autoSubmit(bool|Closure $condition = true)`


Submit when all digits filled.

```php
FlexVerificationCode::make('field_name')
    ->autoSubmit(true);
```
#### `autoSubmitMethod(string|Closure|null $method)`


Livewire method name; receives normalized code as first argument. Enables `autoSubmit(true)`.

```php
FlexVerificationCode::make('field_name')
    ->autoSubmitMethod('value');
```
#### `submitUsing(Closure $callback)`


PHP callback with `$state` / `$code` injection. Enables `live(debounce: 250)` if not already live.

```php
FlexVerificationCode::make('field_name')
    ->submitUsing();
```
#### `loading(bool|Closure $condition = true)` / `validating()`


Spinner during Livewire requests. `validating()` is an alias.

```php
FlexVerificationCode::make('field_name')
    ->loading(true)
    ->validating();
```
#### `heading(string|Htmlable|Closure|null $heading)`


Optional title above the digit inputs (e.g. **Verify account**). Use with `hiddenLabel()` when the heading should be the primary visible title.

```php
FlexVerificationCode::make('field_name')
    ->heading('value');
```
#### `description(string|Htmlable|Closure|null $description)`


Optional supporting copy below the heading (e.g. masked e-mail or phone destination).

```php
FlexVerificationCode::make('field_name')
    ->description('value');
```
#### `footer(string|Htmlable|Closure|null $footer)`


Optional muted text before the footer action (e.g. **Didn't receive a code?**). Translation key: `filament-flex-fields::default.verification_code.footer_prompt`.

```php
FlexVerificationCode::make('field_name')
    ->footer('value');
```
#### `footerAction(Action|Closure|null $action)`


Register a **link-style** Filament action beside the footer text (e.g. **Resend**). Pass a `Closure` to create a default link action named `{field}-footer-action` with label `filament-flex-fields::default.verification_code.resend`. Non-link actions passed to this method are automatically converted with `->link()`.

```php
FlexVerificationCode::make('field_name')
    ->footerAction();
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getLength()` | `int` | Code length |
| `getResolvedGroups()` | `list<int>` | Group sizes |
| `getGroupSeparator()` | `string\|null` | Separator character |
| `shouldShowSeparators()` | `bool` | Multiple groups |
| `getAllowedCharacters()` | `string` | `numeric` or `alphanumeric` |
| `isNumeric()` | `bool` | Numeric mode |
| `getColor()` | `string\|null` | Accent color |
| `shouldAutoSubmit()` | `bool` | Auto submit on |
| `getAutoSubmitMethod()` | `string\|null` | Livewire method |
| `shouldAutoSubmitUsingServerCallback()` | `bool` | `submitUsing` active |
| `shouldShowLoadingIndicator()` | `bool` | Loading spinner |
| `getLoadingWireTargets()` | `string` | `wire:loading` targets |
| `getInputMode()` | `string` | `numeric` or `text` |
| `getValidationPattern()` | `string` | Full-code regex |
| `getInputValidationPattern()` | `string` | Partial input regex |
| `normalizeState(string $state)` | `string` | Filtered code |
| `getDigitAriaLabel(int $index)` | `string` | Per-digit aria label |
| `getWrapperClasses()` | `list<string>` | `fff-verification-code` |
| `getHeading()` | `string\|Htmlable\|null` | Heading copy |
| `getDescription()` | `string\|Htmlable\|null` | Description copy |
| `getFooter()` | `string\|Htmlable\|null` | Footer prompt copy |
| `getFooterAction()` | `Action\|null` | Registered footer link action |
| `hasHeaderContent()` | `bool` | Heading or description present |
| `hasFooterContent()` | `bool` | Footer text or action present |
| `hasFooterAction()` | `bool` | Footer action registered |
| `hasLayoutChrome()` | `bool` | Any heading/description/footer chrome |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `length` | `length()` |
| `groups` | `groups()` |
| `group_separator` | `groupSeparator()` |
| `allowed_characters` | `allowedCharacters()` |
| `size` | `size()` |
| `color` | `color()` |
| `auto_submit` | `autoSubmit()` |
| `auto_submit_method` | `autoSubmitMethod()` |
| `loading` | `loading()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-verification-code-layout` | Vertical stack when heading/description/footer chrome is present |
| `fff-verification-code-layout__header` | Heading + description block |
| `fff-verification-code-layout__heading` | Title (e.g. Verify account) |
| `fff-verification-code-layout__description` | Supporting copy |
| `fff-verification-code-layout__footer` | Footer prompt + link action row |
| `fff-verification-code-layout__footer-text` | Muted footer prompt |
| `fff-verification-code-layout__footer-action` | Filament link action wrapper |
| `fff-verification-code-shell` | Input row + loading spinner |
| `fff-verification-code` | Digit inputs root |
| `fff-verification-code--{sm\|md\|lg}` | Size modifier |
| `fff-verification-code__input` | Single digit cell |
| `fff-verification-code__separator` | Group separator |

### Implementation notes

- Paste distributes characters across cells; non-allowed characters are stripped.
- Alphanumeric mode uppercases letters in `normalizeState()`.
- Playground section **Verification Code** demonstrates sizes, auto-submit, and the **Verify account** heading/footer/resend layout.
- Footer actions use Filament `Action` with `->link()` styling (`.fi-ac-link-action`).

---

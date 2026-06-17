# CreditCardField

![CreditCardField](/art/sc-10.png)

[← Back to Table of Contents](index.md)


### Summary

Interactive **credit card** form rendered as a flippable card UI with number formatting, brand detection, and expiry validation.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField` |
| **State type** | `array<number: string, name: string, expiry: string, cvv: string>` |
| **Model cast** | `'card' => 'array'` or `'json'` |
| **FieldType** | `credit_card` |

Example state:

```php
[
    'number' => '4242424242424242',
    'name' => 'Jane Doe',
    'expiry' => '12/28',
    'cvv' => '123',
]
```

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField;

CreditCardField::make('card')
    ->label('Payment method')
    ->variant('midnight')
    ->flipOnCvvFocus()
    ->required();
```

### Validation

Built-in custom rule validates when values are present:

| Check | Translation key |
|-------|-----------------|
| Number length 13–19 digits | `credit_card.invalid_number` |
| Expiry format `MM/YY` | `credit_card.invalid_expiry` |
| Expiry not in the past | `credit_card.expired` |
| CVV 3–4 digits | `credit_card.invalid_cvv` |

When `required()`, all four sub-fields must be filled.

`getRequiredValidationRule()` returns `'nullable'` — the field-level Laravel rule stays nullable even when `required()` is set. Required validation is enforced inside the component's custom rule, which checks that all four sub-fields (`number`, `name`, `expiry`, `cvv`) are filled.

### Configuration API

#### `inputVariant(string|Closure $variant)`


Sets the styling variant of the internal text inputs inside the card wrapper. Available options: `primary`, `secondary`, `flat`. Default is `primary`.

```php
CreditCardField::make('card_details')
    ->inputVariant('flat');
```
### State normalization

On hydrate and dehydrate:

- `number` and `cvv` — digits only (max 19 / 4)
- `expiry` — formatted as `MM/YY`
- `name` — trimmed string

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `variant` | `variant()` |
| `flip_on_cvv_focus` | `flipOnCvvFocus()` |
| `number_label` | `numberLabel()` |
| `name_label` | `nameLabel()` |
| `expiry_label` | `expiryLabel()` |
| `cvv_label` | `cvvLabel()` |
| `mark` | `mark()` |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `normalizeState(array $state)` | `array<number, name, expiry, cvv>` | Strips non-digits from `number`/`cvv`, formats `expiry` as `MM/YY`, trims `name`. |
| `getExpiryValidationMessage(string $expiry)` | `string\|null` | Translation key message for invalid or expired `MM/YY` values; `null` when valid or empty. |

---

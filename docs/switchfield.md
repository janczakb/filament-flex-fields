# SwitchField

[← Back to Table of Contents](index.md)


### Summary

SaaS-style boolean toggle: label and description on one side, switch on the other.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField` |
| **State type** | `bool` |
| **Model cast** | `'is_admin' =&gt; 'boolean'` |
| **FieldType** | `toggle` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;

SwitchField::make('notifications')
    ->label('Notifications')
    ->description('Receive email updates')
    ->badge('New')
    ->badgeColor('primary')
    ->layout('card')
    ->accepted();
```

### Validation

| Method | Laravel rule |
|--------|--------------|
| (default) | `boolean` |
| `accepted()` | `accepted` — must be `true` |
| `declined()` | `declined` — must be `false` |

Parity with Filament `Toggle` / `Checkbox` validation.

### Configuration API

#### `inline(bool|Closure $condition = true)`


Renders the toggle switch inline alongside other elements rather than in its own block layout.

```php
SwitchField::make('is_active')
    ->inline(true);
```

#### `inlineWithLabel(bool|Closure $condition = true)`


Positions the toggle switch inline on the same row, immediately next to the label.

```php
SwitchField::make('is_active')
    ->inlineWithLabel();
```
### Implementation notes

- `hiddenLabel()` is called in `setUp()` so the label renders **inside** the component, avoiding duplicate labels in field groups.
- Default state: `false`.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `variant` | `variant()` |
| `layout` | `layout()` |
| `size` | `size()` |
| `color` | `color()` |
| `badge` | `badge()` |
| `badge_color` | `badgeColor()` |
| `description` | `description()` |
| `on_color` | `onColor()` |
| `off_color` | `offColor()` |
| `on_icon` | `onIcon()` |
| `off_icon` | `offIcon()` |
| `label_position` | `labelPosition()` |
| `ripple` | `ripple()` |
| `compact` | `compact()` |

---

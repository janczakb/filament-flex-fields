---
title: "SwitchField"
description: Boolean toggle with row and card layouts, badges, and ripple effects.
---

[← Back to Table of Contents](/docs/index)

### Summary

Modern boolean toggle designed for settings pages and feature flags. Unlike the native Filament toggle, `SwitchField` supports description text, status badges, and two distinct layouts: a compact **row** and a bordered **card**.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField` |
| **State type** | `bool` — `true` or `false` |
| **Model cast** | `'is_active' => 'boolean'` · `'notifications_enabled' => 'boolean'` |
| **FieldType** | `switch` |
| **Playground** | `switch` slug in Flex Fields playground |
| **Default layout** | `row` |
| **Default state** | `false` |

---

### Basic usage

#### Simple row toggle
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;

SwitchField::make('is_active')
    ->label('Active status')
    ->description('Enable or disable this feature immediately.');
```

#### Bordered card with badge
```php
SwitchField::make('pro_features')
    ->label('Pro Features')
    ->description('Unlock advanced analytics and reporting.')
    ->layout('card')
    ->badge('PREMIUM')
    ->badgeColor('warning')
    ->color('success');
```

---

### State & validation

#### Stored value
The field stores a boolean `true` or `false`. It defaults to `false` in `setUp()`.

```php
$record->is_active; // bool — true
```

#### Validation rules
Built-in validation ensures the value is a boolean. You can use standard Filament/Laravel rules:

| Rule | Method | Description |
|------|--------|-------------|
| `accepted` | `accepted()` | Must be `true` (useful for Terms of Service) |
| `declined` | `declined()` | Must be `false` |
| `boolean` | (default) | Must be boolean |

```php
SwitchField::make('terms')
    ->label('I accept the terms')
    ->accepted();
```

---

### Layouts & Positioning

#### Layout: Row (default)
Label and description on the left, toggle on the right. Best for dense settings lists.

```php
SwitchField::make('notifications')
    ->layout('row');
```

#### Layout: Card
Adds a border and padding around the entire field. Ideal for highlighting important options.

```php
SwitchField::make('dark_mode')
    ->layout('card');
```

#### Label position
Move the toggle to the start (left) or end (right) of the row.

```php
SwitchField::make('is_active')
    ->labelPosition('start'); // Toggle on left, label on right
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `layout(string\|Closure $layout)` | Setup | `'row'` | `row` or `card` |
| `labelPosition(string\|Closure $pos)` | Setup | `'start'` | `start` or `end` |
| `description(string\|Closure\|null $text)` | Setup | `null` | Sub-label text |
| `color(string\|Closure\|null $color)` | Setup | `'primary'` | Toggle color when ON |
| `badge(string\|Closure\|null $badge)` | Setup | `null` | Optional text badge |
| `badgeColor(string\|Closure $color)` | Setup | `'primary'` | Badge color |
| `ripple(bool\|Closure $condition)` | Setup | `false` | Enable Material-style ripple |
| `compact(bool\|Closure $condition)` | Setup | `false` | Reduce padding |
| `inline(bool\|Closure $condition)` | Setup | `false` | Render without block wrapper |
| `inlineWithLabel(bool\|Closure $cond)` | Setup | `false` | Position toggle next to label |
| `size(string\|Closure $size)` | Setup | `'md'` | `sm`, `md`, `lg` |
| `onColor(string\|Closure $color)` | Setup | — | Inherited from Filament |
| `offColor(string\|Closure $color)` | Setup | — | Inherited from Filament |
| `onIcon(string\|Closure $icon)` | Setup | — | Inherited from Filament |
| `offIcon(string\|Closure $icon)` | Setup | — | Inherited from Filament |

---

### Real-world examples

#### Terms of Service (Wizard)
```php
Wizard\Step::make('Legal')
    ->schema([
        SwitchField::make('tos_accepted')
            ->label('Terms of Service')
            ->description('I have read and agree to the user agreement.')
            ->layout('card')
            ->accepted()
            ->required(),
    ]);
```

#### Compact Settings List
```php
Section::make('Preferences')
    ->schema([
        SwitchField::make('email_notifs')->compact(),
        SwitchField::make('sms_notifs')->compact(),
        SwitchField::make('push_notifs')->compact(),
    ]);
```

---

### Playground

`/admin/flex-fields-playground/switch`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [SegmentControl](/docs/segmentcontrol) | Choosing between 2+ distinct text options |
| [FlexChecklist](/docs/flexchecklist) | Multi-select boolean list |
| [ChoiceCards](/docs/choicecards) | Large visual selection cards |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-switch-field` | Root wrapper |
| `fff-switch-field--layout-row` | Row layout |
| `fff-switch-field--layout-card` | Card layout |
| `fff-switch-field--compact` | Compact mode |
| `fff-switch-field__label` | Main label |
| `fff-switch-field__description` | Description text |
| `fff-switch-field__toggle` | The toggle switch element |
| `fff-switch-field__badge` | Badge wrapper |

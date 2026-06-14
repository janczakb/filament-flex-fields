# FlexRadiolist

[← Back to Table of Contents](index.md)


### Summary

SaaS-style **single-select** list (radio behaviour). Same row layout as [FlexChecklist](flexchecklist.md) but one choice at a time.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist` |
| **State type** | `string\|null` — one option key |
| **FieldType** | `flex_radiolist` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist;

FlexRadiolist::make('delivery')
    ->label('Delivery method')
    ->options([
        'standard' => 'Standard',
        'express' => [
            'label' => 'Express',
            'description' => '2–5 business days',
            'icon' => 'heroicon-o-bolt',
        ],
    ])
    ->default('express');
```

Label-only options (no icon, no description):

```php
FlexRadiolist::make('role')
    ->options([
        'read' => 'Read',
        'write' => 'Write',
        'admin' => 'Admin',
    ]);
```

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | `Rule::in(...)` — value must be an option key |
| `required()` | A selection is required |

### Configuration API

#### `variant(string|Closure $variant)`


Sets the visual layout variant. Available: `default` (standard list), `cards` (SaaS choice cards), `label-only`.

```php
FlexRadiolist::make('gender')
    ->variant('cards');
```
### Autosave on change

```php
FlexRadiolist::make('delivery')
    ->options([...])
    ->live()
    ->afterStateUpdated(fn (string $state) => $this->saveDelivery($state));
```

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `icons` | `icons()` |
| `descriptions` / `desc` | `descriptions()` |
| `disabled_options` | `disabledOptions()` |
| `size` | `size()` |
| `color` | `color()` |

### Implementation notes

- Row styles live in the shared `flex-checklist.css` bundle (`.fff-flex-radiolist*` classes). The radiolist blade loads that bundle via the `flex-radiolist` → `flex-checklist` stylesheet alias — no separate CSS file.

---

---
title: Schema conditions
description: How Flex Fields compiles visibleWhen and requiredWhen JSON rules into Filament form closures.
---

# Schema conditions

[← Back to Table of Contents](/docs/index)

**Schema conditions** turn JSON visibility and validation rules from Flex Field Groups into Filament `visible()` and `required()` closures at runtime. The playground hub demonstrates the same pattern FormBuilder applies when you publish a field group.

| | |
|---|---|
| **Playground** | `schema-conditions` slug in the Meta category |
| **Compiler** | FormBuilder + `JsonFieldConditions` |
| **Admin (3.1+)** | Visibility builder v2 with field pickers and model-attribute sources |

## What it covers

- **visibleWhen** — show or hide fields when another field matches a value
- **requiredWhen** — conditional required validation on the same predicates
- **Live reactivity** — driving fields use `->live()` so dependent fields update immediately

## Example (playground pattern)

Switch **Account type** to **Company** and the company name field appears; VAT becomes required:

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Filament\Schemas\Components\Utilities\Get;

SelectField::make('account_type')
    ->label('Account type')
    ->options([
        'person' => 'Person',
        'company' => 'Company',
    ])
    ->live(),

FlexTextInput::make('company_name')
    ->label('Company name')
    ->visible(fn (Get $get): bool => $get('account_type') === 'company'),

FlexTextInput::make('vat_id')
    ->label('VAT ID')
    ->required(fn (Get $get): bool => $get('account_type') === 'company'),
```

## JSON schema shape

In exported field-group JSON, rules typically look like:

```json
{
  "visibleWhen": {
    "field": "account_type",
    "operator": "equals",
    "value": "company"
  }
}
```

Supported operators are validated by `JsonFieldConditions` (equals, not equals, in, empty, and related comparators — see playground meta export for the full list).

## Admin workflow

1. Create or edit a **Flex Field Group** in Filament.
2. Open a field’s **Visibility** or **Required** rules in the field-group editor.
3. Pick source fields and operators — no hand-written JSON required for standard cases.
4. Publish; runtime forms receive compiled closures via `InteractsWithFlexFieldSchemas`.

## Related

- [Calculated formulas](/docs/field-intelligence) — derived values alongside conditional visibility
- [Flex Field Groups](/docs/flex-field-groups) — manage schemas, import/export, and publish
- [Form layout patterns](/docs/form-layout-patterns) — compose layout components once fields are visible

---
title: Calculated formulas
description: Safe arithmetic formulas for Flex Fields schemas — evaluateMap chains, cycle detection, and CurrencyField money helpers.
---

# Calculated formulas

[← Back to Table of Contents](/docs/index)

Flex Fields ships an always-on **FormulaEngine** for calculated fields in JSON schemas and live Filament demos. Expressions use `{field}` placeholders, whitelist helpers (`pct`, `sum`, `clamp`, `if`, …), and short-circuit logic — no JavaScript eval and no PHP `eval()`.

| | |
|---|---|
| **Playground** | `field-intelligence` slug — **Calculated formulas** in the Meta category |
| **Engine** | `Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine` |
| **Admin (3.1+)** | Formula field type with cycle guard before publish |

## When to use it

- Charter or invoice lines where subtotals, VAT, and grand totals must stay in sync
- Progress or margin KPIs derived from other inputs
- Schema JSON with `formula` or `config.calculated` keys compiled by FormBuilder

## Quick start

```php
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;

FormulaEngine::evaluate('{subtotal}*pct({tax_rate})', [
    'subtotal' => 100,
    'tax_rate' => 23,
]); // 23.0

$computed = FormulaEngine::evaluateMap([
    'charter_fee' => '{day_rate}*{nights}',
    'apa' => '{charter_fee}*pct({apa_pct})',
    'deal_subtotal' => '{charter_fee}+{apa}',
], [
    'day_rate' => 850,
    'nights' => 7,
    'apa_pct' => 30,
]);
```

## Money with CurrencyField

`CurrencyField` stores **minor units** (cents). Convert at formula boundaries:

```php
FormulaEngine::moneyMajor(85000); // 850.0 EUR major units
FormulaEngine::moneyMinor(850);   // 85000 minor units
```

## Safety guarantees

| Feature | Behavior |
|---------|----------|
| Whitelist grammar | Only approved operators and helpers; `sin()`, `eval()`, and bare identifiers are rejected |
| `detectCycle()` | Blocks cyclic graphs (A → B → A) before save |
| `explain()` | Returns expression, `{field}` references, and evaluated result for debugging |
| Short-circuit | `if`, `and`, `or`, `coalesce`, and `nz` skip unused branches |

## Playground demos

Open **Calculated formulas** in Flex Fields Playground for live deal-desk, invoice VAT, progress, margin, formula lab, cycle guard, and security-wall scenarios with copyable PHP snippets.

## Related

- [Schema conditions](/docs/schema-conditions) — visibility and required rules alongside formulas
- [Calculator field](/docs/calculator-field) — interactive keypad field (separate from schema formulas)
- [Flex Field Groups](/docs/flex-field-groups) — define calculated fields in the admin editor

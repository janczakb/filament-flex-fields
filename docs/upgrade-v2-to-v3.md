---
title: "Upgrade guide — v2.x to v3.0"
---

# Upgrade guide — v2.x to v3.0

This guide covers the customer-visible changes when moving from Flex Fields **2.9.x** to **3.0**. Most upgrades are drop-in; a few features are opt-in behind config flags.

---

## Before you upgrade

1. Update Composer: `composer update janczakb/filament-flex-fields`
2. Republish assets: `php artisan filament:assets`
3. Clear cached config/views if you deploy with OPcache or config caching.

We recommend running the upgrade on staging first and opening the **Flex Fields Playground** to spot-check your most-used fields.

---

## Headless Select (opt-in)

v3 ships a headless combobox engine for SelectField. The legacy Filament patch runtime remains the default so existing forms behave identically.

### Automated migration

Report-only scan (JS + PHP token-aware):

```bash
node packages/filament-flex-fields/scripts/codemods/v2-to-v3-select.mjs app/
php packages/filament-flex-fields/scripts/codemods/v2-to-v3-select-ast.php app/
```

Apply safe transforms after review:

```bash
node packages/filament-flex-fields/scripts/codemods/v2-to-v3-select.mjs app/ --write
php packages/filament-flex-fields/scripts/codemods/v2-to-v3-select-ast.php app/ --write
```

The AST script removes redundant `->native(false)`, inserts `fff:v3:headless` review comments on `SelectField::make()` chains, and flags `relationship()` / `fffSelectFieldCoordinator` usage.

### Entity mentions (`entityMentions()`)

Async people/entity picker triggered with `@` in the closed trigger or dropdown search:

```php
SelectField::make('assignees')
    ->multiple()
    ->searchable()
    ->entityMentions(trigger: '@')
    ->getSearchResultsUsing(fn (string $search): array => User::query()
        ->where('name', 'like', "%{$search}%")
        ->pluck('name', 'id')
        ->all());
```

Selected mention chips render with the `@` prefix and `fff-select-entity-mention-chip` styling. Playground: `/select-field` → **Entity mentions**.

To opt in on staging:

```php
// config/filament-flex-fields.php
'select' => [
    'use_headless_engine' => env('FLEX_FIELDS_SELECT_USE_HEADLESS_ENGINE', false),
],
```

```dotenv
FLEX_FIELDS_SELECT_USE_HEADLESS_ENGINE=true
```

Keep the flag `false` in production until you have verified parity for rich options, grid layout, checklist multi-select, and chip variants on your forms.

---

## Tokens and density theme API

v3 formalizes global density and theme overrides through config and the `FlexFields` facade:

```php
// config/filament-flex-fields.php
'ui' => [
    'density' => 'comfortable', // compact | comfortable | spacious
    'theme' => [
        'primary' => 'rgb(34 197 94)',
        'radius' => '0.75rem',
    ],
],
```

At runtime:

```php
use Bjanczak\FilamentFlexFields\Facades\FlexFields;

FlexFields::setDensity('compact');
FlexFields::mergeTheme(['primary' => 'rgb(59 130 246)']);
```

Density maps to `data-fff-density` on the document root; theme keys become `--fff-*` CSS variables. Existing per-field `size()` calls continue to work alongside global density.

---

## FieldType: Schedule and Barcode

`FieldType::Schedule` and `FieldType::Barcode` are now first-class JSON flex-field types wired to `ScheduleField` and `BarcodeScannerField`.

If you use standalone components today, nothing changes. If you store flex fields as JSON, you can declare:

```php
['slug' => 'opening_hours', 'type' => 'schedule', ...]
['slug' => 'sku_scan', 'type' => 'barcode', ...]
```

Playground slugs: `schedule-field`, `barcode-scanner-field`.

---

## Schema conditions (JSON flex fields)

Dynamic visibility, required state, and disabled state can be declared in JSON schemas instead of duplicating PHP `Get` closures:

```json
{
  "slug": "vat_id",
  "type": "single_line_text",
  "visibleWhen": [
    { "field": "country", "operator": "equals", "value": "PL" }
  ]
}
```

Supported operators: `equals`, `not_equals`, `filled`, `empty`, `in`. Multiple rules in a list are combined with **AND** logic. The compiler lives in `JsonFieldConditions` and maps to standard Filament closures.

### Optional admin CRUD (Flex Field Groups)

Persist field groups in the database and manage them in Filament (opt-in):

```env
FLEX_FIELDS_SCHEMA_RESOURCE_ENABLED=true
```

Then migrate and open **Field groups** in the panel. Full steps: [Flex Field Groups](/docs/flex-field-groups).

---

## Option Schema v2 (rich choice fields)

Rich option payloads for Cards, Dual Listbox, Checklist, Tags, and Matrix fields normalize through `RichOptionSchemaV2` profiles:

| Profile | Use with |
| --- | --- |
| `cards` | ChoiceCards, ChoiceCheckboxCards |
| `dual_list` | DualListboxField |
| `checklist` | FlexChecklist |
| `tags` | TagsField |
| `matrix` | MatrixChoiceField |

Legacy flat option arrays still work; v2 adds validation helpers and consistent icon/description/image keys across choice components.

---

## Asset registry export

Maintainers and CI can export the lazy stylesheet registry for audits:

```bash
php artisan fff:assets:export-registry
```

This writes `resources/dist/asset-registry.json` with lazy stylesheets, dependency graph, playground aliases, and critical preload targets. Commit the file after asset pipeline changes so downstream tooling stays aligned.

---

## Need help?

- [Shared concepts](/docs/shared-concepts) — sizing, assets, option shapes
- [SRE runbook](/docs/sre-runbook) — asset publish, injector debug, select race checklist
- [Report an issue](https://github.com/janczakb/filament-flex-fields/issues)

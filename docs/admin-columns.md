---
title: Admin columns
description: Read-only Filament table columns for progress, status chips, signature previews, and map pins.
---

# Admin columns

[← Back to Table of Contents](/docs/index)

**Admin columns** are read-only Filament table column helpers for rich list views — progress bars, status chips, inline signature previews, and map pin labels. They render HTML via dedicated `format*Display()` helpers so you can preview output in playground demos and wire the same columns in production tables.

| | |
|---|---|
| **Playground** | `admin-columns` slug in the Tables category |
| **Components** | `ProgressColumn`, `StatusChipColumn`, `SignaturePreviewColumn`, `MapPinColumn` |

## Included column types

| Column | Use case |
|--------|----------|
| **ProgressColumn** | Numeric or ratio completion with optional value label |
| **StatusChipColumn** | Colored status chips from strings or `{label, color}` arrays |
| **SignaturePreviewColumn** | Inline SVG signature thumbnail in table rows |
| **MapPinColumn** | Location label with optional lat/lng metadata |

## Quick start

```php
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\ProgressColumn;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\StatusChipColumn;

ProgressColumn::make('completion')
    ->progressSize(ControlSize::Sm)
    ->progressColor('success')
    ->showValue(),

StatusChipColumn::make('status')
    ->chipSize(ControlSize::Md)
    ->chipColor('warning'),
```

## Playground preview

The **Admin columns** hub renders sample rows without a full Eloquent table — each cell uses the same formatters your table columns call at runtime. Use it to tune sizes, colors, and empty states before shipping a resource list.

## Related table column docs

- [RatingColumn](/docs/ratingcolumn)
- [IconColumn](/docs/iconcolumn)
- [UserColumn](/docs/usercolumn)

## Related

- [Flex Field Groups](/docs/flex-field-groups) — field definitions that feed table/infolist builders
- [Hold confirm action](/docs/hold-confirm-action) — destructive row actions with press-and-hold UX

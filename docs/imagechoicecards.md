---
title: "ImageChoiceCards"
description: Image card selection with full-bleed media, footer label, animated indicators, radio or checkbox modes, and default or overlay layouts.
---

[← Back to Table of Contents](/docs/index)

### Summary

Premium **image choice cards** — full-bleed image, footer with label and selection indicator, two layout variants. One component covers **radio exclusive** (`multiple(false)`) and **checkbox multi** (`multiple(true)` with min/max/exact).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\ImageChoiceCards` |
| **State type** | Single: `string\|int\|null` · Multi: `array` |
| **FieldType** | `image_choice_cards` |
| **Playground** | `image-choice-cards` slug |

---

### Layout variants

| Variant | Method | Behavior |
|---------|--------|----------|
| **Default** | `->variant('default')` (default) | Image block on top (aspect ratio on media). Footer bar below with inset + rounded corners. Selection highlight on the footer only. |
| **Overlay** | `->variant('overlay')` | Image fills the **entire card** (aspect ratio on the card). Footer bar overlaps the bottom edge (same inset + rounding + selection styling). A frosted scrim (`backdrop-filter: blur(20px) saturate(1.5)` + `mask-image` fade) sits **above the image, under the footer**, extending ~20px past the footer top. |

Both variants support radio and checkbox modes, `size`, `rounding`, `gridColumns`, `imageAspectRatio`, `imageFit`, `disabledOptions`, and dark mode.

Invalid `variant()` values fall back to `default`.

---

### Basic usage

#### Radio exclusive (default layout)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ImageChoiceCards;

ImageChoiceCards::make('body_type')
    ->options([
        'athletic' => [
            'label' => 'Athletic',
            'image' => asset('images/athletic.jpg'),
            'alt' => 'Athletic build',
        ],
        'average' => [
            'label' => 'Average',
            'image' => asset('images/average.jpg'),
        ],
    ])
    ->multiple(false)
    ->gridColumns(4)
    ->size('md')
    ->rounding('lg')
    ->imageAspectRatio('3/4')
    ->imageFit('cover');
```

Selecting another card **replaces** the previous value (native radio + Alpine `select`).

#### Overlay layout

```php
ImageChoiceCards::make('body_type')
    ->options([/* … */])
    ->variant('overlay')
    ->multiple(false)
    ->gridColumns(['default' => 2, 'md' => 3, 'lg' => 4, 'xl' => 4])
    ->size('md')
    ->rounding('lg')
    ->imageAspectRatio('3/4')
    ->imageFit('cover');
```

Use `imageAspectRatio()` to control card height in overlay mode (ratio applies to the whole card, not only the media block).

#### Checkbox multi with limits

```php
ImageChoiceCards::make('focus')
    ->options([/* … */])
    ->multiple()
    ->minSelections(1)
    ->maxSelections(2)
    ->exactSelections(null);
```

When `maxSelections` is reached, further options cannot be selected until one is cleared. Works with either layout variant.

---

### State & validation

#### Stored value

| Mode | State |
|------|--------|
| Single (`multiple(false)`) | Option key `string\|int\|null` |
| Multi (`multiple(true)`) | `array` of option keys (via `OptionsArrayStateCast`) |

#### Validation rules (built-in)

| Mode | Rules |
|------|--------|
| Single | `Rule::in(option keys)` (+ `required` when set) |
| Multi | `array`, invalid option keys, exact/min/max selection counts |

Translation keys: `filament-flex-fields::default.validation.image_choice_cards.{min,max,exact,invalid_option}`.

---

### Reactive disabled options

```php
use Filament\Schemas\Components\Utilities\Get;

SelectField::make('tier')->live()->options([/* … */]);

ImageChoiceCards::make('body_type')
    ->options([/* … */])
    ->disabledOptions(fn (Get $get) => match ($get('tier')) {
        'a' => ['shredded', 'bulky'],
        'b' => ['slim'],
        default => [],
    })
    ->live();
```

The sibling Select must be `->live()` so the Closure re-runs. Image Choice Cards has **no `wire:ignore` on the root**, so Livewire morph refreshes the disabled map. If the current selection becomes disabled, state is cleared (single → `null`, multi → filtered array) on the server and in Alpine.

---

### Options shape

| Key | Type | Notes |
|-----|------|--------|
| `label` | `string` | Footer label (defaults to option key) |
| `image` | `?string` | `http(s)` or relative asset URL — escape handled in Blade |
| `alt` | `?string` | Image alt text (defaults to `label`) |
| `disabled` | `bool` | Per-option disable |

Empty / missing `image` shows a muted placeholder block.

String shorthand is supported: `'athletic' => 'Athletic'` normalizes to `['label' => 'Athletic']`.

---

### Fluent API

All methods accept `Closure` unless noted.

| Method | Default | Description |
|--------|---------|-------------|
| `options(array\|Closure)` | `[]` | Option map (see [Options shape](#options-shape)) |
| `multiple(bool\|Closure)` | `false` | Radio vs checkbox |
| `minSelections` / `maxSelections` / `exactSelections` | `null` | Multi validation / UI max lock |
| `variant('default'\|'overlay')` | `default` | Layout variant — see [Layout variants](#layout-variants) |
| `size('sm'\|'md'\|'lg')` | `md` | Footer + indicator scale; overlay scrim height scales with size |
| `rounding(...)` | config `field_rounding` | Card + footer corner radius |
| `gridColumns(int\|array)` | `4` | Responsive grid (1–6 cols). Array keys: `default`, `sm`, `md`, `lg`, `xl`. Studio config may still send `columns` |
| `imageAspectRatio(string)` | `3/4` | CSS `aspect-ratio` on media (`default`) or whole card (`overlay`) |
| `imageFit('cover'\|'contain'\|'fill')` | `cover` | `object-fit`; images are centered (`object-position: center`) |
| `indicator('check'\|'checkbox'\|'radio'\|'none')` | auto | Single → check, multi → checkbox |
| `disabledOptions(array\|Closure)` | `[]` | Keys that cannot be selected |
| `ripple()` | `false` | Material-style ripple on click |

---

### FlexFieldFormBuilder / Studio config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `multiple` | `multiple()` |
| `columns` / `grid_columns` | `gridColumns()` |
| `variant` | `variant()` — `default` \| `overlay` |
| `size` | `size()` |
| `rounding` | `rounding()` |
| `indicator` | `indicator()` |
| `image_aspect_ratio` | `imageAspectRatio()` |
| `image_fit` | `imageFit()` |
| `disabled_options` | `disabledOptions()` |
| `min_selections` / `max_selections` / `exact_selections` | selection limits |
| `ripple` | `ripple()` |

Defaults: see `FieldTypeDefaultConfigRegistry` for `image_choice_cards`.

---

### Playground

`/admin/flex-fields-playground/image-choice-cards`

Demos: square 1:1, **overlay layout**, radio exclusive, checkbox min/max, size/rounding/aspect matrix, Select → reactive `disabledOptions`, required validation, dark mode via panel.

---

### CSS / JS

| Asset | Id |
|-------|-----|
| Stylesheet | `image-choice-cards` (lazy) |
| Alpine | `image-choice-cards.js` via `x-load` |

#### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-image-choice-cards` | Root wrapper |
| `fff-image-choice-cards--default` | Default layout (implicit when variant is `default`) |
| `fff-image-choice-cards--overlay` | Overlay layout — full-height image + bottom footer overlap + frosted scrim |
| `fff-image-choice-cards--{sm\|md\|lg}` | Size modifier |
| `fff-image-choice-cards--indicator-{check\|checkbox\|radio\|none}` | Indicator style |
| `fff-image-choice-cards__item` | Card label wrapper |
| `fff-image-choice-cards__item.is-selected` | Selected state (SSR + Alpine) |
| `fff-image-choice-cards__media` | Image container |
| `fff-image-choice-cards__footer` | Label + indicator bar |
| `fff-image-choice-cards.is-hydrated` | Alpine ready — enables selection transitions |

#### Visual behavior

- **Hover:** subtle image zoom (`scale(1.04)`) inside clipped media.
- **Reload:** selected state is server-rendered (`is-selected`, `checked`) before Alpine loads to avoid flash.
- **Overlay scrim:** `::after` on `.fff-image-choice-cards__item` — `z-index` between image and footer.

---

### See also

| Component | When to use |
|-----------|-------------|
| [ChoiceCards](/docs/choicecards) | Text/icon cards without images |
| [ChoiceCheckboxCards](/docs/choicecheckboxcards) | Multi-select text cards |
| [SelectField](/docs/selectfield) | Compact dropdown selection |

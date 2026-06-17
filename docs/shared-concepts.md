---
title: "Shared Concepts"
---

[← Back to table of contents](/docs/index)

# Components Reference

Complete API reference for custom form components shipped with **Filament Flex Fields** (`janczakb/filament-flex-fields`).

This document covers **custom UI components** (form fields, table columns, and layout/schema components). Standard Filament fields (TextInput, Select, etc.) are mapped via `FieldType` and are not described here.

---

## Table of contents

### Part I — Shared concepts

1. [Overview](#overview)
2. [Documentation conventions](#documentation-conventions)
3. [Control size](#control-size)
4. [Inherited Filament field API](#inherited-filament-field-api)
5. [Assets & playground](#assets--playground)
6. [Rich card option shape](#rich-card-option-shape)
7. [Rich select option shape](#rich-select-option-shape)
8. [Dual listbox option shape](#dual-listbox-option-shape)

### Part II — Components

9. [FlexTextInput](/docs/flextextinput)
10. [FlexTextareaField](/docs/flextextareafield)
11. [SelectField](/docs/selectfield)
12. [UserSelect](/docs/userselect)
13. [UserColumn](/docs/usercolumn)
14. [DualListboxField](/docs/duallistboxfield)
15. [PriceRangeField](/docs/pricerangefield)
16. [CreditCardField](/docs/creditcardfield)
17. [PhoneField](/docs/phonefield)
18. [SignatureField](/docs/signaturefield)
19. [MapPickerField](/docs/mappickerfield)
20. [AddressAutocompleteField](/docs/addressautocompletefield)
21. [ChoiceCards](/docs/choicecards)
22. [ChoiceCheckboxCards](/docs/choicecheckboxcards)
23. [FlexChecklist](/docs/flexchecklist)
24. [FlexRadiolist](/docs/flexradiolist)
25. [MatrixChoiceField](/docs/matrixchoicefield)
26. [SwitchField](/docs/switchfield)
27. [CurrencyField](/docs/currencyfield)
28. [CountryField](/docs/countryfield)
29. [TimezoneField](/docs/timezonefield)
30. [Date & time fields](#date--time-fields)
31. [FlexVerificationCode](/docs/flexverificationcode)
32. [AudioField](/docs/audiofield)
33. [VoiceNoteRecorderField](/docs/voicenoterecorderfield)
34. [VideoField](/docs/videofield)
35. [FlexFileUpload & FlexImageUpload](#flexfileupload--fleximageupload)
36. [ColorSwatchField](/docs/colorswatchfield)
37. [FlexColorPickerField](/docs/flexcolorpickerfield)
38. [NumberStepper](/docs/numberstepper)
39. [FlexSlider](/docs/flexslider)
40. [SegmentTabs](/docs/segmenttabs)
41. [SegmentControl](/docs/segmentcontrol)
42. [TrackSlider](/docs/trackslider)
43. [TrafficSplit](/docs/trafficsplit)
44. [RatingField](/docs/ratingfield)
45. [RatingColumn](/docs/ratingcolumn)
46. [CoverCard](/docs/covercard)
47. [ProgressBar](/docs/progressbar)
48. [ProgressCircle](/docs/progresscircle)
49. [ItemCard](/docs/itemcard)
50. [ItemCardGroup](/docs/itemcardgroup)
51. [ItemCardStack](/docs/itemcardstack)
52. [Layout components — quick comparison](#layout-components--quick-comparison)
53. [Form layout patterns](/docs/form-layout-patterns)
54. [SlugField & TitleSlugField](#slugfield--titleslugfield)
55. [TranslatableFields](/docs/translatablefields)

# Part I — Shared concepts

## Overview

Filament Flex Fields provides modern SaaS-inspired form controls with a unified design language:

- Shared **size scale** (`sm`, `md`, `lg`)
- Shared **CSS tokens** (`--fff-*` in `resources/css/base.css` and modular bundles under `resources/css/`)
- Filament **field wrapper** integration (labels, validation errors, helper text)
- Optional **playground** page for visual QA

All custom components extend `Filament\Forms\Components\Field`, or extend a native Filament input (`TextInput`, `Textarea`, `Select`) while replacing the view with a styled package template.

---

## Documentation conventions

Each component section documents four layers of API surface:

| Layer | Description |
|-------|-------------|
| **Chainable config methods** | Fluent `-&gt;method()` calls on the component class. Accept scalars, `Closure`, or Filament utility injection. |
| **Inherited APIs** | Methods from parent Filament classes (`Field`, `TextInput`, `Select`, traits) that work unchanged. Listed briefly where relevant. |
| **Public helper methods** | Non-chainable getters and utilities used by Blade views, tests, or custom extensions. Documented per component when they expose stable behaviour. |
| **FlexField schema keys** | Snake-case keys in field `config` arrays passed to `FlexFieldFormBuilder`. Map to chainable methods. Keys not yet wired in the builder may still be valid when configuring fields manually. |

---

## Control size

Most components accept a `size()` method.

| Value | Enum constant        | Track height |
|-------|----------------------|--------------|
| `sm`  | `ControlSize::Sm`    | 32px         |
| `md`  | `ControlSize::Md`    | 40px (default) |
| `lg`  | `ControlSize::Lg`    | 48px         |

```php
use Bjanczak\FilamentFlexFields\Enums\ControlSize;

->size('md')
->size(ControlSize::Lg)
```

Package defaults live in `config/filament-flex-fields.php` under the `ui` key, for example:

| Config key               | Affects              |
|--------------------------|----------------------|
| `flex_text_input_size`   | FlexTextInput        |
| `flex_text_input_variant`| FlexTextInput        |
| `flex_textarea_size`     | FlexTextareaField    |
| `flex_textarea_variant`  | FlexTextareaField    |
| `select_size`            | SelectField          |
| `select_variant`         | SelectField          |
| `dual_listbox_size`      | DualListboxField     |
| `dual_listbox_variant`   | DualListboxField     |
| `price_range_size`       | PriceRangeField      |
| `price_range_variant`    | PriceRangeField      |
| `credit_card_size`       | CreditCardField      |
| `credit_card_variant`    | CreditCardField      |
| `number_stepper_size`    | NumberStepper        |
| `segment_size`           | SegmentControl       |
| `slider_size`            | TrackSlider          |
| `switch_size`            | SwitchField          |
| `rating_size`            | RatingField          |

Choice card components fall back to `choice_cards_size` and `choice_cards_variant` when used via `FlexFieldFormBuilder` (add these keys to config if needed).

---

## Inherited Filament field API

Every component in Part II inherits the standard Filament `Field` API. Common methods:

| Method | Description |
|--------|-------------|
| `label()` | Field label rendered by the Filament wrapper |
| `helperText()` | Helper text below the field |
| `hint()` | Visible hint text next to the label |
| `hintIcon()` | Icon next to the label with optional tooltip |
| `hintIconTooltip()` | Tooltip text for `hintIcon()` |
| `hintAction()` | Clickable action next to the label |
| `placeholder()` | Placeholder where applicable |
| `required()` | Marks the field as required |
| `disabled()` | Disables the entire field |
| `default()` | Default state value |
| `live()` | Live / reactive updates |
| `dehydrated()` | Whether the value is saved to form state |
| `hidden()` / `visible()` | Conditional visibility |
| `rule()` / `rules()` | Additional validation rules |
| `afterStateUpdated()` | Callback after state changes |

Validation errors are displayed **below the component** using the standard Filament field wrapper.

All configuration methods accept a `Closure` for dynamic values and support Filament utility injection.

---

## Livewire `wire:ignore` strategy (map & heavy Alpine fields)

Several interactive fields (`MapPickerField`, `AddressAutocompleteField`, `SelectField`, `PhoneField`, `CountryField`, and others) wrap third-party or Filament Alpine trees inside `wire:ignore` so Livewire does not destroy DOM that Alpine manages.

| Concern | Strategy |
|---------|----------|
| **State sync** | Pass Livewire state through `$wire.$entangle('statePath')` in `x-data` — Alpine owns the UI, Livewire owns persistence. |
| **Config changes** | Add a `wire:key` hash over read-only/disabled/config props (token, `fields()`, `storeFormat()`, size, variant). When config changes, Livewire remounts the ignored subtree with fresh Alpine boot data. |
| **Server updates** | Avoid `-&gt;set('data.field')` on ignored fragments in tests; change upstream props or entangled state instead. |
| **Dropdowns / maps** | Teleport menus to `body`, use shared overlay coordinator (`fffOverlays`) so only one menu stays open. `teleported-menu.css` raises z-index when a Filament modal is open (`:has(.fi-modal.fi-modal-open)`). |

Example (`MapPickerField` / `AddressAutocompleteField`):

```blade
<div
    wire:ignore
    wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $field->getFields(), ...])), 0, 64) }}"
    x-data="mapPickerFormComponent({ state: $wire.$entangle('...') })"
>
```

When adding new map-like or Mapbox-backed fields, follow the same pattern: `$entangle` for state, `wire:key` for remounts, `x-load` ES module for JS.

---

## Assets & playground

CSS is split into bundles:

| Asset ID | File | Loading |
|----------|------|---------|
| `flex-fields-core` | `resources/dist/css/core.css` | Always loaded (tokens, switches, item cards, hold-confirm actions, shared layout) |
| `flex-fields-playground` | `resources/dist/css/playground.css` | Base playground chrome only |
| `flex-fields-playground-{slug}` | `resources/dist/css/playground-{slug}.css` | Per-slug playground bundle (e.g. `playground-phone-field.css`) |
| `flex-fields-{component}` | `resources/dist/css/{component}.css` | Lazy — injected by form field blades and `CoverCard` via `load-stylesheet` → `emit-assets` |

### JavaScript (tiered chunks)

All Alpine components are compiled together in a single esbuild build with `splitting: true`. If two fields import the same module from `resources/js/core/` or `resources/js/support/`, the code is split into a shared chunk — loaded once and cached by the browser.

| Output | Role |
|--------|------|
| `resources/dist/components/{component}.js` | Thin entry — Alpine `x-data` factory for the component |
| `resources/dist/components/flex-fields-{name}-{hash}.js` | Shared modules with semantic names (e.g. `flex-fields-emoji-*.js`) |
| `resources/dist/components/alpine-manifest.json` | Component-to-chunk map + `__chunk_modules__` metadata |

#### Currently shared chunks (auto-generated from build)

| Source modules | Components | Purpose / Example |
|----------------|------------|-------------------|
| `core/shared-emoji-picker.js` | `flex-text-input`, `flex-textarea` | Emoji picker (~45 KB) |
| `core/audio-waveform.js`, `format-time.js`, `waveform-bars.js`, `audio-playback.js` | `audio-field`, `voice-note-recorder-field` | Waveform, playback, time formatting |
| `core/dynamic-bars.js` | `price-range`, `audio-field`, `voice-note-recorder-field` | Waveform / price histogram bars |
| `support/mapbox-geocoding.js` | `map-picker`, `address-autocomplete` | Mapbox geocoding integration |
| `core/searchable-select-menu.js` | `country-field`, `timezone-field`, `currency-field` | Common dropdown overlay shell |
| `libphonenumber-js` (lazy `import()`) | `phone-field` | Phone validation (~190 KB, loaded on demand) |

Components **without** entries in the manifest (e.g. `rating-field`, `dual-listbox`) do not share code with other components — their entire logic remains inside their thin entry (which is expected).

Modules used by only a single field (e.g. `core/date-time/*` used only by `flex-date-time-field`, `nouislider` used only by `flex-slider`) remain inside their entries until another component starts importing them.

#### Preload & delivery

Every blade template rendering a component stylesheet registers CSS and Alpine chunks in request-scoped queues (`FlexFieldStylesheetQueue`, `FlexFieldAlpineQueue`). `load-stylesheet` immediately emits `emit-assets`:

- **Full page** — `@push('styles')` into Filament `@stack('styles')` in `&lt;head&gt;`.
- **Livewire partial** — inline `&lt;link&gt;` / `modulepreload` tags plus a hidden `data-fff-asset-batch` marker for the injector.

`queued-stylesheets` flushes any remaining `pending()` queues at `STYLES_AFTER` and `BODY_END`. Critical bundles may preload via `critical-stylesheet-preloads` at `HEAD_END`.

#### Asset injector (SPA / modals)

`flex-field-asset-injector.js` (registered Filament JS asset at `SCRIPTS_AFTER`) handles:

- href deduplication (`normalizeAssetUrl`, Map indices, in-flight promise cache),
- loading missing CSS and Alpine chunks from morph batches,
- modal FOUC prevention (`morph.updating` / `morph.updated`, `fff-flex-fields-assets-pending` / `ready` classes),
- protected links (`data-fff-stylesheet`, `data-fff-alpine-chunk`, `data-fff-playground-bundle`).

Components without custom CSS but requiring JS (e.g. `rating-field`) load their chunks dynamically via ESM `import` inside `x-load` — without explicit preloading, since their manifest entry is empty.

After modifying JavaScript (including the injector):

```bash
npm run build:js
php artisan filament:assets
```

Table column styles (`UserColumn`, `RatingColumn`, etc.) are **lazy-loaded per column** via the `load-stylesheet` partial (`UserColumn` → `user-display` + `user-column`; `RatingColumn` → `rating-column`). Request-scoped queue + `emit-assets` prevent duplicate `&lt;link&gt;` tags; `data-navigate-track` + `flex-field-asset-injector` keep SPA navigation clean.

Playground CSS uses base `playground.css` plus per-slug `playground-{slug}.css` bundles pushed via `playground-page-stylesheets`.

After changing package CSS or JS:

```bash
# Inside the package directory
npm run build:css          # core + playground + per-component bundles
npm run build              # CSS + JS

# Inside the Laravel application
php artisan filament:assets
```

Enable the playground (local by default):

```env
FLEX_FIELDS_PLAYGROUND=true
```

The playground renders live examples of every custom component and variant.

---

## Rich card option shape

Used by **ChoiceCards** and **ChoiceCheckboxCards** via `options()`.

### Simple option

```php
'pro' => 'Pro plan',
```

### Rich option

```php
'starter' => [
    'label' => 'Starter',
    'description' => 'For individuals and small projects',
    'price' => '$5',
    'price_suffix' => '/mo',
    'meta' => '4,200 subscribers',
    'icon' => 'heroicon-o-sparkles',
    'badge' => 'Popular',
    'badge_color' => 'success',
    'disabled' => false,
],
```

| Key | Type | Description |
|-----|------|-------------|
| `label` | `string` | Card title. Falls back to the option key. |
| `description` | `string\|null` | Secondary text under the title. |
| `price` | `string\|null` | Price line. Alias: `value`. |
| `price_suffix` | `string\|null` | Suffix after price, e.g. `/mo`. Alias: `suffix`. |
| `meta` | `string\|null` | Extra footer text (monospace in UI). |
| `icon` | `string\|null` | Heroicon name. Rendered in `media` and `featured` layouts. |
| `badge` | `string\|null` | Badge label. Rendered in `featured` layout. |
| `badge_color` | `string` | Badge color token. Default: `success`. |
| `disabled` | `bool` | Disables this option. Combined with `disabledOptions()`. |

---

## Rich select option shape

Used by **SelectField** when options use a rich array shape or when `richOptions()` / `optionLayout('grid')` is enabled.

### Simple option

```php
'livewire' => 'Livewire',
```

### Rich option

```php
'laravel' => [
    'label' => 'Laravel',
    'description' => 'The PHP framework for web artisans',
    'icon' => 'heroicon-o-bolt',
    'image' => null,
    'badge' => 'Popular',
    'badge_color' => 'success',
    'disabled' => false,
],
```

| Key | Type | Description |
|-----|------|-------------|
| `label` | `string` | Option title. Falls back to the option key. |
| `description` | `string\|null` | Secondary line in list layout. |
| `icon` | `string\|BackedEnum\|Htmlable\|null` | Heroicon rendered in the option row. |
| `image` | `string\|null` | Image URL for grid layout cards. |
| `badge` | `string\|null` | Badge label in list layout. |
| `badge_color` | `string` | Filament color token for the badge. Default: `primary`. |
| `disabled` | `bool` | Disables this option. |

Option groups use nested arrays: `'Backend' =&gt; ['laravel' =&gt; 'Laravel', ...]`.

---

## Dual listbox option shape

Used by **DualListboxField** via `options()`.

### Simple option

```php
'read' => 'Read',
```

### Rich option

```php
'write' => [
    'label' => 'Write',
    'description' => 'Create and update records',
    'disabled' => false,
],
```

| Key | Type | Description |
|-----|------|-------------|
| `label` | `string` | Item label. Falls back to the option key. |
| `description` | `string\|null` | Secondary text under the label. |
| `disabled` | `bool` | Disables this option. Combined with `disabledOptions()`. |

---

# Shared Concepts

[← Back to table of contents](../README.md)

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

9. [FlexTextInput](flextextinput.md)
10. [FlexTextareaField](flextextareafield.md)
11. [SelectField](selectfield.md)
12. [UserSelect](userselect.md)
13. [UserColumn](usercolumn.md)
14. [DualListboxField](duallistboxfield.md)
15. [PriceRangeField](pricerangefield.md)
16. [CreditCardField](creditcardfield.md)
17. [PhoneField](phonefield.md)
18. [SignatureField](signaturefield.md)
19. [MapPickerField](mappickerfield.md)
20. [AddressAutocompleteField](addressautocompletefield.md)
21. [ChoiceCards](choicecards.md)
22. [ChoiceCheckboxCards](choicecheckboxcards.md)
23. [FlexChecklist](flexchecklist.md)
24. [FlexRadiolist](flexradiolist.md)
25. [MatrixChoiceField](matrixchoicefield.md)
26. [SwitchField](switchfield.md)
27. [CurrencyField](currencyfield.md)
28. [CountryField](countryfield.md)
29. [TimezoneField](timezonefield.md)
30. [Date & time fields](#date--time-fields)
31. [FlexVerificationCode](flexverificationcode.md)
32. [AudioField](audiofield.md)
33. [VoiceNoteRecorderField](voicenoterecorderfield.md)
34. [VideoField](videofield.md)
35. [FlexFileUpload & FlexImageUpload](#flexfileupload--fleximageupload)
36. [ColorSwatchField](colorswatchfield.md)
37. [FlexColorPickerField](flexcolorpickerfield.md)
38. [NumberStepper](numberstepper.md)
39. [FlexSlider](flexslider.md)
40. [SegmentTabs](segmenttabs.md)
41. [SegmentControl](segmentcontrol.md)
42. [TrackSlider](trackslider.md)
43. [TrafficSplit](trafficsplit.md)
44. [RatingField](ratingfield.md)
45. [RatingColumn](ratingcolumn.md)
46. [CoverCard](covercard.md)
47. [ProgressBar](progressbar.md)
48. [ProgressCircle](progresscircle.md)
49. [ItemCard](itemcard.md)
50. [ItemCardGroup](itemcardgroup.md)
51. [ItemCardStack](itemcardstack.md)
52. [Layout components — quick comparison](#layout-components--quick-comparison)
53. [Form layout patterns](form-layout-patterns.md)
54. [SlugField & TitleSlugField](#slugfield--titleslugfield)
55. [TranslatableFields](translatablefields.md)

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
| **Chainable config methods** | Fluent `->method()` calls on the component class. Accept scalars, `Closure`, or Filament utility injection. |
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

## Assets & playground

CSS is split into bundles:

| Asset ID | File | Loading |
|----------|------|---------|
| `flex-fields-core` | `resources/dist/css/core.css` | Always loaded (tokens, switches, item cards, hold-confirm actions, table columns, shared layout) |
| `flex-fields-playground` | `resources/dist/css/playground.css` | Loaded on the playground page only |
| `flex-fields-{component}` | `resources/dist/css/{component}.css` | Lazy — injected by form field blades and `CoverCard` via `load-stylesheet` |

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

#### Preload

Every blade template rendering a component stylesheet will automatically register a `modulepreload` hint for its required chunks using the manifest (`FlexFieldAlpineQueue` deduplicates chunks rendered by multiple components on the same page).

Components without custom CSS but requiring JS (e.g. `rating-field`) load their chunks dynamically via ESM `import` inside `x-load` — without explicit preloading, since their manifest entry is empty.

After modifying JavaScript:

```bash
npm run build:js
php artisan filament:assets
```

Table column styles (`UserColumn`, `RatingColumn`, etc.) live in **core** and are never lazy-loaded.

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

Option groups use nested arrays: `'Backend' => ['laravel' => 'Laravel', ...]`.

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

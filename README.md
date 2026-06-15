<p align="center" class="filament-hidden">
    <img src="art/field-flex-thumb-r.png" width="100%" style="border-radius: 12px;" alt="Filament Flex Fields — dynamic custom fields and modern SaaS-inspired form components for Filament v5" class="filament-hidden">
</p>

<h1 align="center">Filament Flex Fields</h1>

<p align="center"><strong>61 Filament v5 form components · one design system · lazy assets · secure Mapbox geocoding · JSON custom fields without migrations.</strong><br>One Laravel admin UI toolkit instead of many separate field plugins — phone, map, matrix, slug, translatable, currency, tags, and more.</p>
<p align="center">Lazy CSS/JS in <code>&lt;head&gt;</code> · server-side geocoding proxy · schema versioning · audit trail · built-in Playground · no Node.js in production</p>

<p align="center">
    <a href="https://packagist.org/packages/janczakb/filament-flex-fields"><img src="https://img.shields.io/packagist/v/janczakb/filament-flex-fields.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/janczakb/filament-flex-fields/blob/main/LICENSE"><img src="https://img.shields.io/badge/license-proprietary-7c3aed.svg?style=flat-square" alt="License"></a>
    <a href="https://packagist.org/packages/janczakb/filament-flex-fields"><img src="https://img.shields.io/packagist/dt/janczakb/filament-flex-fields.svg?style=flat-square" alt="Total Downloads"></a>
    <a href="https://github.com/janczakb/filament-flex-fields/stargazers"><img src="https://img.shields.io/github/stars/janczakb/filament-flex-fields.svg?style=flat-square" alt="GitHub Stars"></a>
    <a href="https://github.com/janczakb/filament-flex-fields/issues"><img src="https://img.shields.io/github/issues/janczakb/filament-flex-fields.svg?style=flat-square" alt="GitHub Issues"></a>
    <a href="https://github.com/janczakb/filament-flex-fields/actions"><img src="https://img.shields.io/badge/tests-passing-success.svg?style=flat-square" alt="Tests"></a>
</p>

<p align="center">
    <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3+">
    <img src="https://img.shields.io/badge/Laravel-11%2B-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 11+">
    <img src="https://img.shields.io/badge/Filament-5.x-F59E0B?style=flat-square" alt="Filament 5.x">
</p>

A [Filament v5](https://filamentphp.com) plugin for **Laravel admin panels**: **61 custom form components**, optional **JSON custom attributes** (no EAV migrations), and a unified `--fff-*` design system. Use any field standalone in Filament forms, or wire schemas through `FlexFieldFormBuilder` + `HasFlexFields`.

Most Filament projects glue together separate plugins for phone, country, map, slug, matrix, translatable fields, and rich selects — each with its own CSS, JS, and UX quirks. **Flex Fields ships one cohesive toolkit**: layout primitives (`ItemCard`, `SegmentTabs`, `CoverCard`), table columns (`UserColumn`, `RatingColumn`), **lazy-loaded** pre-built assets in `resources/dist/` (consumers **do not need Node.js**), shared Alpine chunks with CI bundle budgets, secure Mapbox geocoding, and a local **Playground** for visual QA.

---

## What sets Flex Fields apart

| | **Flex Fields** | **Typical field plugins** |
|---|-----------------|---------------------------|
| **Scope** | **61** components, one `--fff-*` design system | Many single-purpose packages |
| **Assets** | Lazy per-field CSS/JS in `<head>`, shared chunks, pre-built dist — **no Node.js in your app** | Global bundle or per-page npm build |
| **Geocoding** | Server proxy by default (token on Laravel), cache + rate limit | Token in browser, direct API per keystroke |
| **Custom attributes** | JSON column + schema registry, version migrator, audit trail | Migrations, EAV, or untyped JSON |
| **Country / phone** | One shared registry per page (`iso` + `phone` pools) | Full country list embedded per field |
| **Menus** | One overlay coordinator — single open dropdown | Overlapping popovers, z-index issues |

Details below: [Lazy assets](#lazy-assets--shared-chunks) · [Mapbox](#secure-mapbox-geocoding-mappicker--addressautocomplete) · [JSON flex fields](#zero-migration-custom-fields-json--registry).

---

## Screenshots

<div style="display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; width: 100%;">
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-1.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="SignatureField - HTML5 canvas handwriting signature pad for Filament forms, allowing touch-friendly signatures with WebP export">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">SignatureField — Canvas Handwriting Signature Pad</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-2.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="MatrixChoiceField - Dynamic survey matrix choice grid with radio and checkbox modes, reactive disabled cells, and custom validations">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">MatrixChoiceField — Survey & Configurator Grid</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-3.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="FlexTextareaField - Advanced multi-line input with speech dictation, character counter, autosize, and integrated emoji picker">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">FlexTextareaField — Autosize Textarea with Voice & Emoji Input</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-4.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ProgressBar and ProgressCircle - Multi-style visual progress indicators, linear trackers, and circular dashboard widgets for Laravel Filament">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ProgressBar & ProgressCircle — Visual Progress Indicators</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-5.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="CurrencyField - Multi-currency localized money input with real-time formatting, automatic decimal separation, and prefix selector">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">CurrencyField — Multi-Currency Localized Input</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-6.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="MapPickerField - Interactive map coordinate pin picker with marker support, location autofill, and custom layouts for Filament">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">MapPickerField — Interactive Map Pin Selector</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-7.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ItemCardGroup - Modern SaaS-inspired card-based layout component for structured settings blocks, user profiles, and clean form layouts">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ItemCardGroup — Card-Based Layout Group</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-8.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="DualListboxField - Highly interactive searchable dual panel transfer list for selecting and reordering multiple options in Filament v5">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">DualListboxField — Reorderable Two-Panel Transfer List</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-9.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="PriceRangeField - Dual-handle interactive price filter with histogram slider and minimum/maximum range controls">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">PriceRangeField — Dual-Handle Price Filter</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-10.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="CreditCardField - Real-time credit card preview wrapper with Luhn validation and dynamic CVV flip animations">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">CreditCardField — Interactive Card Preview</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-11.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="FlexColorPickerField - Color picker with preset swatches, opacity slider, visual grid, and eyedropper support">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">FlexColorPickerField — Advanced Color Picker</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-12.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="AudioField and VoiceNoteRecorderField - Web-based audio player with waveform visualizer and in-browser voice note recorder">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">AudioField & VoiceNoteRecorderField — Waveform Audio & Voice Messages</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-13.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="NumberStepper - Pill-shaped numeric stepper control with plus/minus buttons and dynamic NumberFlow animation">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">NumberStepper — Animated Numeric Control</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-14.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ChoiceCards - Rich card-based selection list with custom icons, headers, badges, and selected highlight states">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ChoiceCards — Rich Selection Grid</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-15.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="VideoField - Interactive video URL player supporting YouTube, Vimeo, and local HTML5 videos with custom media controls">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">VideoField — Video Player & Embed Component</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-16.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="TrackSlider - Track-style range slider supporting single values, percentage progress, and min/max range handles">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">TrackSlider — Inline Range & Segment Slider</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-17.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="SegmentControl - Elegant segmented sliding tab controls with support for icons, disabled states, and dynamic sizing">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">SegmentControl — Segmented Button Tab Switcher</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-18.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="CoverCard - Beautiful media card component for hero sections, product banners, or settings header blocks">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">CoverCard — Media Rich Hero Banner</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-19.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ProgressCircle - Circular progress meters and semicircle tracking gauges for dashboard analytics">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ProgressCircle — Semicircle & Circular Dashboard Metrics</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-20.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="RatingField - Highly customizable star rating input supporting custom icons, semantic color states, and fractional display">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">RatingField — Visual Star Rating Input</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-21.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="HoldConfirmAction - Custom action button requiring the user to press and hold to confirm high-risk actions like deletion">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">HoldConfirmAction — Press & Hold Button</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-22.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="SlugField combined with TranslatableFields showing multi-lingual title fields and live localized URL slug generation">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">SlugField & TranslatableFields — Translatable SEO Slugs</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-23.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="PhoneField - International phone number input field with country flag selectors, calling code auto-detection, and libphonenumber validation">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">PhoneField — International Phone Input</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-24.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ColorSwatchField - Interactive color swatch picker supporting circle/square shapes, size configurations, and focus indicators">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ColorSwatchField — Preset Color Swatches</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-25.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="FlexEmojiPicker - Integrated searching emoji picker popover with skin tone categories and custom category tabs">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">FlexEmojiPicker — Integrated Searchable Emoji Picker</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-26.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="FlexDateRangeField - Dark-themed calendar date picker with date range selection and custom calendar rendering">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">FlexDateRangeField — Dark Mode Calendar & Date Range Picker</p>
  </div>
  <div style="flex-grow: 1; width: 100%; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/more.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="And More - Overview of the interactive Developer Playground displaying form fields, custom layouts, and UI components in Filament Flex Fields">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">And More — 61 Components & Visual Playground</p>
  </div>
</div>


---

## Table of contents

- [What sets Flex Fields apart](#what-sets-flex-fields-apart)
- [Why Flex Fields?](#why-flex-fields)
- [Custom Components (61)](#custom-components-61)
- [Use cases](#use-cases)
- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
- [Quick usage](#quick-usage)
- [Playground](#playground)
- [Documentation](#documentation)
- [FAQ](#faq)
- [Development](#development)

---

## Why Flex Fields?

Filament Flex Fields replaces many scattered community packages with **one design system** for Filament v5: shared sizes (`sm`/`md`/`lg`), tokens (`--fff-*`), and glass searchable menus.

### 📦 61 components in one plugin
**50 form fields**, **9 layout/schema** components, **2 table columns** — plus **HoldConfirmAction** for press-and-hold destructive actions. Full list: [Custom Components (61)](#custom-components-61).

### ⚡ Lazy assets & shared chunks {#lazy-assets--shared-chunks}
Loading dozens of complex fields shouldn't make your admin panel sluggish. Flex Fields implements a **three-layer, request-scoped asset pipeline** that most Filament plugins do not offer:

1. **Lean core** — `core.css` (~20 KB raw, ~4.5 KB gzip): design tokens + hint chrome only. No field UI bundled globally.
2. **Lazy per-component CSS** — each of the 44+ component bundles loads **only when that field is on the page**.
3. **Head delivery** — lazy stylesheets are **not** dumped inline in the form body. They are queued and pushed to Filament's native `@stack('styles')` in `<head>`.

#### Why head delivery matters (and how we do it)

Typical plugins either ship one fat CSS file for every page, or inject `<link>` tags mid-body when a field renders. Flex Fields uses the Laravel + Filament pattern:

```
Field blade renders
  → FlexFieldStylesheetQueue::enqueueFor('phone-field')   // dedup per request
  → @push('styles') <link rel="stylesheet" …>             // no body output
  → Filament layout @stack('styles') in <head>            // browser discovers CSS early
```

**Result:** a form with `PhoneField` + `TagsField` fetches **only** `phone-field.css` + `tags-field.css` (+ shared deps like `flex-text-input.css`), each **once**, from `<head>`, before paint — not styles for the other 59 components.

* **Lazy-Loaded CSS in `<head>`:** `@include('filament-flex-fields::partials.load-stylesheet')` on every field — zero body noise, deduplicated by `FlexFieldStylesheetQueue`.
* **Tiered JS Chunks (esbuild code splitting):** Shared libraries (Mapbox, libphonenumber, emoji picker, audio UI, virtualized select menus) split into semantic chunks, `modulepreload` in `<head>`, deduplicated by `FlexFieldAlpineQueue` — **exactly once per page request** no matter how many fields use them.
* **CI bundle budgets:** `npm run check:budgets` fails the build if dist assets exceed documented KB limits (`resources/dist/bundle-metrics.json`).

<details>
<summary>🔍 View Asset Optimization details (CSS & JS tables)</summary>

#### CSS — lazy per component

| Bundle | When it loads |
|--------|----------------|
| `core.css` | Always — design tokens, hint icons/tooltips |
| `{component}.css` | **Only when that field is on the page** — queued on render, pushed to `<head>` via `load-stylesheet` |
| `playground.css` | Playground page only |

Use `PhoneField` and `TagsField` on the same form → the browser fetches **only** `phone-field.css` and `tags-field.css`, not styles for the other 59 components.

#### JavaScript — tiered chunks (esbuild code splitting)

All Alpine components are built together with `splitting: true`:

| Layer | What you get |
|-------|----------------|
| **Entry** | Thin `{component}.js` — just the `x-data` factory for that field |
| **Shared chunks** | Reusable `core/` modules with **semantic names** (`flex-fields-emoji-*`, `flex-fields-audio-*`, `flex-fields-select-menu-*`, …) |
| **Manifest** | `alpine-manifest.json` maps each field → its chunks for `modulepreload` |
| **Dedup** | `FlexFieldAlpineQueue` preloads each shared chunk **once per request** |

#### Examples of what gets shared automatically:

| Shared module | Used by | Why it matters |
|---------------|---------|----------------|
| Emoji picker | `FlexTextInput`, `FlexTextarea` | ~45 KB library loaded once, not twice |
| Audio core | `AudioField`, `VoiceNoteRecorderField` | Waveform, playback, time formatting in one chunk |
| Select menu shell | `CountryField`, `TimezoneField`, `CurrencyField` | One composable for floating searchable menus |
| Mapbox helpers | `MapPickerField`, `AddressAutocompleteField` | Geocoding logic + Laravel server proxy (geocoding token stays server-side; map GL still needs a public token) |
| libphonenumber-js | `PhoneField` | ~190 KB **lazy `import()`** — only when the field mounts |

Fields with no shared deps (e.g. `RatingField`) stay as a single small entry — no artificial splitting.

#### Consumer-friendly
* **Zero Node.js for consumers:** Pre-built assets in `resources/dist/` — no Node.js required in your host app.
* **Auto-registered:** After `composer update`, run `php artisan filament:assets`.
* **Technical deep-dive:** [docs/index.md](docs/index.md) (Assets & playground).

</details>

### 🔒 Secure Mapbox geocoding (MapPicker & AddressAutocomplete)

Location fields are production-hardened — not just “drop a token in the blade”:

| Feature | What it does |
|---------|----------------|
| **Laravel server proxy** (default) | Geocoding API calls go through authenticated routes; `MAPBOX_ACCESS_TOKEN` stays server-side. Map tiles still use the public token only where Mapbox GL requires it. |
| **Server + client cache** | Laravel `Cache` on proxy responses; in-memory dedup on repeat searches in the same session. |
| **Rate limiting** | Configurable per-minute cap on proxy endpoints. |
| **`searchTypes()`** | Restrict results to streets, POI, cities, regions, etc. — or search all Mapbox place types. |
| **Keyboard accessibility** | Arrow/Home/End/Enter/Escape on suggestion lists with `aria-activedescendant`. |
| **`wire:ignore` strategy** | Map DOM isolated from Livewire re-renders; state synced via `$entangle`; remount via `wire:key` when config changes. |

Setup: [§4 Mapbox geocoding](#4-mapbox-geocoding-mappicker--addressautocomplete). API: [MapPickerField](docs/mappickerfield.md) · [AddressAutocompleteField](docs/addressautocompletefield.md).

### 🧩 Zero-migration custom fields (JSON + registry)

Define fields in PHP config or `FlexFieldSchemaRegistry` and store values in a **single JSON column** via `HasFlexFields` — no per-attribute migrations, no EAV joins:

* **Schema versioning** — `FlexFieldSchemaMigrator` upgrades legacy config shapes (`field` → `fields`, `name` → `slug`, …) automatically on register.
* **Audit trail** — enabled by default; appends who/when/what changed to a configurable `flex_field_audit` JSON column (`FLEX_FIELDS_AUDIT_ENABLED=false` to disable).
* **Query scopes** — `scopeWhereFlexField()` and related helpers for filtering models by stored flex values.
* **Form builder** — `FlexFieldFormBuilder` maps registry schemas to live Filament components.

Ideal for CMS page builders, tenant-specific settings, onboarding wizards, and CRM custom attributes.

### 🎨 SaaS-style UI
Smooth transitions, unified borders and focus rings, dark mode on interactive surfaces (emoji picker, geocoding menus, country/phone dropdowns). Shared `--fff-z-*` z-index tokens keep teleported dropdowns below the Filament topbar.

### 🧬 Highly reactive inputs teams actually need
Build complex forms that would otherwise take weeks of custom frontend development:
* **MatrixChoiceField:** A survey-style grid with radio/checkbox modes and reactive `disableCellWhen()` / `disableRowWhen()` logic to easily disable specific choices.
* **TitleSlugField & SlugField:** Beautiful inputs with live URL permalink previews, automatic slug generation, copy actions, and database uniqueness validation.
* **Specialized inputs:** Custom audio players with waveforms (`AudioField`), voice recorders (`VoiceNoteRecorderField`), weighted A/B splitters (`TrafficSplit`), multi-currency inputs (`CurrencyField`), card selection lists (`ChoiceCards`), and map pins (`MapPickerField`).

### 🧪 Standalone Filament Inputs or Dynamic Schema Registry
* **Standalone Fields:** Import any of the 61 components directly into your standard Filament forms, chaining them like native fields (no JSON column required).
* **Dynamic Builder:** Bind your registry schemas to forms automatically using `FlexFieldFormBuilder` for dynamic JSON attributes.

### 🔍 Built-in Local Developer Playground (Visual QA)
Validate, preview, and interact with all 61 custom components instantly. Flex Fields features an out-of-the-box local Playground page (`/admin/playground` by default) containing interactive previews of every field to speed up your development.

---

## Custom Components (61)

Every item below is a **custom class shipped by this package** — own Blade views, CSS, and configuration API. This list does **not** include native Filament fields (`TextInput`, `TagsInput`, `Repeater`, etc.) used only as passthrough inside `FlexFieldFormBuilder`.

Full API for each component: **[docs/index.md](docs/index.md)**.

### Text & input (9)

| Component | Description |
|-----------|-------------|
| [`FlexTextInput`](docs/flextextinput.md) | Enhanced text input — speech dictation, emoji picker, password strength, clearable |
| [`FlexTextareaField`](docs/flextextareafield.md) | Animated autosizing textarea with character counter |
| [`PhoneField`](docs/phonefield.md) | International phone input with libphonenumber validation |
| [`CountryField`](docs/countryfield.md) | Searchable country picker with flags |
| [`TimezoneField`](docs/timezonefield.md) | IANA timezone picker with UTC offset display |
| [`SlugField`](docs/slugfield-and-titleslugfield.md) | Slug input with permalink preview, uniqueness, regenerate/copy actions |
| [`TitleSlugField`](docs/slugfield-and-titleslugfield.md) | Title + slug pair with live URL preview and optional Spatie Sluggable |
| [`AddressAutocompleteField`](docs/addressautocompletefield.md) | Mapbox address search — server proxy, `searchTypes()` (streets/POI/cities), keyboard a11y |
| [`FlexVerificationCode`](docs/flexverificationcode.md) | OTP / 2FA verification code input with grouping |

### Number & range (6)

| Component | Description |
|-----------|-------------|
| [`NumberStepper`](docs/numberstepper.md) | +/- numeric stepper control |
| [`CurrencyField`](docs/currencyfield.md) | Multi-currency money input with locale-aware formatting |
| [`FlexSlider`](docs/flexslider.md) | Styled range slider with value display |
| [`TrackSlider`](docs/trackslider.md) | Track-style slider — single value, percentage, or min/max range |
| [`PriceRangeField`](docs/pricerangefield.md) | Dual-handle price filter with histogram |
| [`TrafficSplit`](docs/trafficsplit.md) | Weighted segment split control (A/B-style traffic allocation) |

### Choice & selection (13)

| Component | Description |
|-----------|-------------|
| [`SwitchField`](docs/switchfield.md) | Animated toggle switch with row/inline layouts |
| [`CellSwitch`](docs/switchfield.md) | Compact `SwitchField` variant for table cells |
| [`SegmentControl`](docs/segmentcontrol.md) | Segmented button control |
| [`ChoiceCards`](docs/choicecards.md) | Rich card-based radio selection |
| [`ChoiceCheckboxCards`](docs/choicecheckboxcards.md) | Rich card-based multi-select |
| [`FlexChecklist`](docs/flexchecklist.md) | Animated checklist with icons and descriptions |
| [`FlexRadiolist`](docs/flexradiolist.md) | Animated radio list with icons and descriptions |
| [`MatrixChoiceField`](docs/matrixchoicefield.md) | Survey / configurator matrix grid — radio or checkbox per row |
| [`SelectField`](docs/selectfield.md) | Rich select with avatars, badges, and descriptions |
| [`UserSelect`](docs/userselect.md) | User picker with avatar stacks and verification badges |
| [`DualListboxField`](docs/duallistboxfield.md) | Two-panel reorderable transfer list |
| [`TagsField`](docs/tags-field.md) | Tag input — pills below the field with inline remove buttons |
| [`FlexSpatieTagsField`](docs/tags-field.md) | Spatie Tags integration for `TagsField` |

### Date & time (9)

| Component | Description |
|-----------|-------------|
| [`FlexDateField`](docs/date-and-time-fields.md) | Segmented date input without calendar popover |
| [`FlexDatePicker`](docs/date-and-time-fields.md) | Date picker with calendar popover |
| [`FlexTimeField`](docs/date-and-time-fields.md) | Time picker (12h / 24h, seconds optional) |
| [`FlexDateTimePicker`](docs/date-and-time-fields.md) | Combined date + time picker |
| [`FlexDateRangeField`](docs/date-and-time-fields.md) | Start/end date range |
| [`FlexDurationField`](docs/date-and-time-fields.md) | Duration input (hours / minutes) |
| [`FlexTimeRangeField`](docs/date-and-time-fields.md) | Start/end time range |
| [`FlexMonthPicker`](docs/date-and-time-fields.md) | Month picker |
| [`FlexYearPicker`](docs/date-and-time-fields.md) | Year picker |

### Media, color & location (12)

| Component | Description |
|-----------|-------------|
| [`ColorSwatchField`](docs/colorswatchfield.md) | Preset color swatch picker |
| [`FlexColorPickerField`](docs/flexcolorpickerfield.md) | Advanced color picker with grid and eyedropper |
| [`FlexFileUpload`](docs/flexfileupload-and-fleximageupload.md) | Styled file upload with security presets |
| [`FlexImageUpload`](docs/flexfileupload-and-fleximageupload.md) | Image upload with processing options |
| [`FlexSpatieMediaLibraryFileUpload`](docs/flexfileupload-and-fleximageupload.md) | Spatie Media Library upload integration |
| [`VideoField`](docs/videofield.md) | Video URL / player with YouTube support |
| [`AudioField`](docs/audiofield.md) | Audio URL / player with waveform |
| [`VoiceNoteRecorderField`](docs/voicenoterecorderfield.md) | In-browser voice recorder — waveform, local playback, deferred or immediate upload |
| [`MapPickerField`](docs/mappickerfield.md) | Map pin picker — server geocoding proxy, `searchTypes()`, draggable marker, Livewire-safe map |
| [`SignatureField`](docs/signaturefield.md) | Canvas signature pad |
| [`CreditCardField`](docs/creditcardfield.md) | Card preview with Luhn validation and CVV flip |
| [`CellSlider`](docs/trackslider.md) | Compact `TrackSlider` variant for table cells |

### Rating (1)

| Component | Description |
|-----------|-------------|
| [`RatingField`](docs/ratingfield.md) | Star rating input |

### Layout & display — schemas (9)

| Component | Description |
|-----------|-------------|
| [`SegmentTabs`](docs/segmenttabs.md) | Tabbed segment navigation for forms |
| [`TranslatableFields`](docs/translatablefields.md) | Locale tabs wrapping any fields (JSON or Spatie Translatable) |
| [`TranslatableTabs`](docs/translatablefields.md) | Legacy alias for `TranslatableFields` |
| [`ItemCard`](docs/itemcard.md) | Single settings-style card row |
| [`ItemCardGroup`](docs/itemcardgroup.md) | Polished card-based settings group |
| [`ItemCardStack`](docs/itemcardstack.md) | Stacked card layout for profile / settings pages |
| [`CoverCard`](docs/covercard.md) | Hero cover card for tabbed editors |
| [`ProgressBar`](docs/progressbar.md) | Linear, pill, or segment progress bar |
| [`ProgressCircle`](docs/progresscircle.md) | Circular or semicircle progress indicator |

Ready-made layout recipes: [Form layout patterns](docs/index.md#form-layout-patterns).

### Table columns (2)

| Component | Description |
|-----------|-------------|
| [`UserColumn`](docs/usercolumn.md) | Avatar + name/email display with hover card |
| [`RatingColumn`](docs/ratingcolumn.md) | Star rating display in Filament tables |

**Total: 61 custom components** (50 form fields + 9 layout/schema + 2 table columns). **HoldConfirmAction** (press-and-hold Filament actions) is documented in the playground but not counted in the 61.

---

## Use cases

| Scenario | Recommended components |
|----------|------------------------|
| **CRM / SaaS custom attributes** | JSON flex fields + audit trail + `PhoneField`, `CountryField`, `UserSelect` |
| **CMS / page builder** | `TitleSlugField`, `TranslatableFields`, schema versioning, `FlexFileUpload`, `FlexImageUpload` |
| **Product configurator** | `MatrixChoiceField`, `ChoiceCards`, `PriceRangeField`, `ColorSwatchField` |
| **Surveys & assessments** | `MatrixChoiceField`, `FlexRadiolist`, `RatingField` |
| **SaaS onboarding** | `ChoiceCards`, `SegmentTabs`, `CoverCard`, `ProgressCircle` |
| **E-commerce filters** | `PriceRangeField`, `TrackSlider`, `DualListboxField` |
| **User profile settings** | `ItemCardGroup`, `PhoneField`, `TimezoneField`, `SignatureField` |
| **Payment forms** | `CreditCardField`, `FlexVerificationCode` |
| **Location services** | `MapPickerField`, `AddressAutocompleteField` (Mapbox geocoding via optional Laravel proxy) |
| **A/B configuration** | `TrafficSplit`, `SegmentControl` |

---

## Requirements

| Dependency | Version |
|------------|---------|
| PHP | 8.3+ |
| Laravel | 11+ |
| Filament | 5.x (`filament/filament ^5.0`) |

**Optional integrations** (see `composer.json` → `suggest`):

| Package | Used for |
|---------|----------|
| `spatie/laravel-sluggable` | Model-based slug generation in `SlugField` |
| `spatie/laravel-translatable` | JSON translation storage for translatable titles |
| `spatie/laravel-medialibrary` | `FlexSpatieMediaLibraryFileUpload` |
| `filament/spatie-laravel-media-library-plugin` | Filament base class for media upload |
| `spatie/laravel-tags` | `FlexSpatieTagsField` — sync tags on models using `HasTags` |

---

## Installation

### Composer (Packagist)

```bash
composer require janczakb/filament-flex-fields
```

### Composer (path repository — monorepo)

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/filament-flex-fields"
        }
    ],
    "require": {
        "janczakb/filament-flex-fields": "@dev"
    }
}
```

```bash
composer require janczakb/filament-flex-fields:@dev
```

> [!IMPORTANT]
> Register Filament assets after every install or update:
>
> ```bash
> php artisan filament:assets
> ```

Auto-discovered via `composer.json` → `extra.laravel.providers`.

**Keep assets in sync** — add to your host app `composer.json`:

```json
"scripts": {
    "post-autoload-dump": [
        "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
        "@php artisan package:discover --ansi",
        "@php artisan filament:assets"
    ]
}
```

---

## Setup

### 1. Register the plugin

```php
use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(FilamentFlexFieldsPlugin::make());
}
```

### 2. Publish configuration (optional)

```bash
php artisan vendor:publish --tag=filament-flex-fields-config
```

### 3. Publish translations (optional)

Built-in locales ship with the package (`en`, `pl`). Publish them only when you need to customize strings in your app:

```bash
php artisan vendor:publish --tag=filament-flex-fields-translations
```

Files are copied to:

```
lang/vendor/filament-flex-fields/
├── en/
│   ├── default.php
│   ├── countries.php
│   ├── currencies.php
│   └── timezones.php
└── pl/
    ├── default.php
    ├── countries.php
    └── timezones.php
```

**Why `lang/vendor/`?** Laravel resolves package translation overrides only from `lang/vendor/{namespace}/` (see `FileLoader::loadNamespaceOverrides`). A flat path such as `lang/filament-flex-fields/` is **not** picked up for `__('filament-flex-fields::...')` unless you add custom loader logic. The `vendor` segment here is Laravel’s convention for published package lang files — it is not Composer’s `vendor/` directory.

#### Translation files

| File | Purpose |
|------|---------|
| `default.php` | UI labels (placeholders, buttons, validation copy, search hints) |
| `countries.php` | Country names for `CountryField` / `PhoneField` |
| `currencies.php` | Currency names for `CurrencyField` |
| `timezones.php` | Optional timezone name overrides for `TimezoneField` |

**Timezone names** resolve in this order:

1. `timezones.php` override (`Europe/Warsaw` → key `Europe__Warsaw`)
2. PHP `Intl` for the active locale (requires `ext-intl`)
3. Humanized IANA identifier (`America/New_York` → `New York`)

The field renders `{name} (UTC±HH:MM)` — only the name uses the chain above; offset is computed at runtime. You usually **do not** need to publish `timezones.php` unless you want custom wording.

Example override:

```php
// lang/vendor/filament-flex-fields/pl/timezones.php
return [
    'Europe__Warsaw' => 'Warszawa',
];
```

Without publishing, the package uses its bundled translations automatically.

#### Adding a new locale

1. Copy the structure from `vendor/janczakb/filament-flex-fields/resources/lang/en/`.
2. Create `lang/vendor/filament-flex-fields/{locale}/` with the files you need (`default.php` is usually enough to start).
3. Add `timezones.php` only for manual timezone wording overrides.
4. Set `app.locale` / Filament panel locale to your new locale.

You do **not** need to register anything else — `filament-flex-fields::…` lines resolve automatically.

#### Updating translations after a plugin upgrade

You usually **do not** need to re-publish translations when you update the package.

Laravel loads translations in two layers:

1. Built-in files from the package (`resources/lang` inside the plugin)
2. Your overrides from `lang/vendor/filament-flex-fields/` merged on top with `array_replace_recursive`

That means:

- **New keys** added in a new plugin version appear automatically, even if your published `default.php` is older and does not contain them yet.
- **Keys you customized** in `lang/vendor/...` keep your wording.
- **Keys you never published/overrode** always follow the latest built-in package text.
- **Timezone list labels** follow PHP `Intl` by default, so new IANA zones work without updating lang files.

**Recommended workflow**

| Situation | What to do |
|-----------|------------|
| You never published translations | Run `composer update` only — new keys work out of the box |
| You customized a few strings | Keep your `lang/vendor/...` files; do not re-publish with `--force` |
| You want to customize a new key from an upgrade | Copy that key from `vendor/janczakb/filament-flex-fields/resources/lang/{locale}/` into your published file |
| You need new country/currency keys in a published file | Diff package `countries.php` / `currencies.php` and append only missing keys to your copy |
| You want custom timezone wording | Add only those zones to published `timezones.php` |

Re-run `vendor:publish --tag=filament-flex-fields-translations` only when you want a fresh file template. **Avoid `--force`** unless you intend to overwrite your edits.

### 4. Mapbox geocoding (MapPicker & AddressAutocomplete)

Set `MAPBOX_ACCESS_TOKEN` in `.env`. By default **`use_server_proxy` is `true`** — geocoding requests go through authenticated Laravel routes so the token never ships to the browser for search/reverse geocode:

```env
MAPBOX_ACCESS_TOKEN=pk.…
FLEX_FIELDS_MAPBOX_SERVER_PROXY=true
FLEX_FIELDS_MAPBOX_CACHE_TTL=3600
FLEX_FIELDS_MAPBOX_RATE_LIMIT=60
```

Proxy routes use `web` + `auth` middleware by default (`config/filament-flex-fields.php` → `mapbox.proxy_middleware`). Disable the proxy only when you intentionally expose a public Mapbox token client-side.

**Field API highlights:** `searchTypes()` (POI / street / city / …), `language()`, `minSearchLength()`, `searchDebounce()`, `streetAddressesOnly()`.

`CountryField` and `PhoneField` use a shared **country registry** delivered once per page (`iso` / `phone` pools) — see [CHANGELOG.md §2.2.0](CHANGELOG.md#220---2026-06-09).

### 5. Flex field audit trail (enabled by default)

```env
# Enabled by default — set false to disable
FLEX_FIELDS_AUDIT_ENABLED=true
FLEX_FIELDS_AUDIT_COLUMN=flex_field_audit
```

`HasFlexFields` records value changes (user, timestamp, field key, old/new snapshot) in the configured JSON column.

### 6. Flex fields on a model (optional)

```php
use Bjanczak\FilamentFlexFields\Concerns\HasFlexFields;

class Product extends Model
{
    use HasFlexFields;

    protected $casts = [
        'flex_field_values' => 'array',
    ];
}
```

Define schemas in `config/filament-flex-fields.php` or `FlexFieldSchemaRegistry`, then build with `FlexFieldFormBuilder`.

---

## Quick usage

### Standalone form components

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MatrixChoiceField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;

ChoiceCards::make('plan')
    ->options(['basic' => 'Basic', 'pro' => 'Pro'])
    ->required();

MatrixChoiceField::make('priorities')
    ->mode('checkbox')
    ->rows(['dark_mode' => 'Dark mode', 'csv_export' => 'CSV export'])
    ->matrixColumns(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
    ->disableCellWhen('csv_export', 'high', 'dark_mode', 'high');

PhoneField::make('phone')->defaultCountry('PL');

TitleSlugField::make('title', 'slug')
    ->permalinkPreview()
    ->slugUnique();
```

Full API for every option: **[docs/index.md](docs/index.md)**.

### Schema / display components

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardGroup;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressCircle;

ProgressCircle::make()
    ->value(72)
    ->displayValue('72%')
    ->variant('semicircle');

ItemCardGroup::make([
    // Polished card-based settings rows…
]);
```

---

## Playground

A dev UI page previews every custom component.

| Setting | Env variable | Default |
|---------|--------------|---------|
| Plugin enabled | `FLEX_FIELDS_ENABLED` | `true` |
| Playground | `FLEX_FIELDS_PLAYGROUND` | `true` when `APP_ENV=local` |
| Nav group | `FLEX_FIELDS_PLAYGROUND_NAV_GROUP` | `Settings & Tools` |
| Nav sort | `FLEX_FIELDS_PLAYGROUND_NAV_SORT` | `91` |

```env
FLEX_FIELDS_PLAYGROUND=false
```

Example slugs: `matrix-choice`, `choice-cards`, `tags-field`, `title-slug-field`, `phone-field`, `item-card-group`, `progress-circle`.

---

## Documentation

| Document | Contents |
|----------|----------|
| **[docs/index.md](docs/index.md)** | Complete per-component API — every method, option, validation rule, config key, and example |
| **[docs/shared-concepts.md](docs/shared-concepts.md)** | Asset pipeline, overlay coordinator, `wire:ignore` + Livewire sync patterns |
| **[CHANGELOG.md](CHANGELOG.md)** | Release history (geo proxy, schema versioning, audit trail, …) |
| **config/filament-flex-fields.php** | Default schemas, UI sizes, playground toggles, Mapbox proxy |

---

## FAQ

**Why choose Flex Fields over multiple Filament field plugins?**
One design system, one asset pipeline, one Playground, and **61** components that share chunks (phone lib, emoji picker, Mapbox helpers, select menus). You avoid conflicting CSS, duplicate JS, and inconsistent UX — plus features most single-purpose plugins lack (matrix grids, JSON flex fields, server geocoding proxy, audit trail).

**Do I need Node.js to use this package?**
No. Pre-built CSS/JS are committed to `resources/dist/`. Node is only needed when developing the package itself.

**How does asset loading work?**
Each component loads its own CSS/JS on demand. Shared JavaScript (emoji picker, audio UI, country/timezone menus, Mapbox, overlay coordinator, etc.) is split into cached chunks with semantic names — loaded once per page even when multiple fields use the same module. See [Development → Performance-first assets](#performance-first-assets).

**Is the Mapbox token safe in production?**
By default, **yes for geocoding** — search/reverse requests use the Laravel proxy (`use_server_proxy=true`). The browser only receives proxy URLs, not your secret token. Mapbox GL map tiles still need a public token for rendering; keep that key URL-restricted in the Mapbox dashboard.

**Can I use components without the JSON flex-field system?**
Yes. Import any component directly into Filament forms — the JSON column and `HasFlexFields` trait are optional.

**How many components are included?**
**61** custom UI classes with own views and CSS — listed in [Custom Components (61)](#custom-components-61). The optional JSON flex-field registry wires these components via `FlexFieldFormBuilder`.

**Does it work with Filament v4?**
No — this package targets **Filament v5** only.

**Is Spatie required?**
No. Sluggable, Translatable, and Media Library integrations are optional `composer suggest` packages.

**Where is the Matrix Choice / survey grid?**
`MatrixChoiceField` — radio or checkbox mode, per-row validation, reactive `disableCellWhen()` / `disableRowWhen()`. See [docs/index.md → MatrixChoiceField](docs/matrixchoicefield.md).

**How are dynamic custom field schemas upgraded?**
Register schemas via config or `FlexFieldSchemaRegistry`. Legacy shapes are migrated automatically by `FlexFieldSchemaMigrator` (see [CHANGELOG.md §2.4.0](CHANGELOG.md#240---2026-06-16)).

---

## Development

```bash
composer install
composer test          # Pest — 99+ PHP tests
composer analyse       # PHPStan

npm install
npm run build          # CSS + JS → resources/dist/
npm run test:js        # Node unit tests (theme-utils, geocoding, dropdown registry, …)
npm run test:e2e       # Playwright playground tests (requires FLEX_FIELDS_PLAYGROUND_URL)
composer format        # Laravel Pint
```

Rebuild assets after changing `resources/css/` or `resources/js/`.

```bash
npm run check:budgets   # CI bundle size guard (reads resources/dist/bundle-metrics.json)
```

### Performance-first assets

This is the technical reference for [Lazy assets & shared chunks](#lazy-assets--shared-chunks) above.

#### CSS delivery pipeline

| Step | Class / file | Role |
|------|----------------|------|
| 1 | Field blade `@include(…load-stylesheet)` | Registers needed bundles when the field is on the page |
| 2 | `FlexFieldStylesheetQueue` | Request-scoped dedup — 5× `ChoiceCards` → 1× `choice-cards.css` |
| 3 | `@push('styles')` in `load-stylesheet.blade.php` | Pushes `<link rel="stylesheet">` without rendering in body |
| 4 | Filament `@stack('styles')` in `layout/base.blade.php` | Renders all pushed links in `<head>` before content paint |
| 5 | `loadedOnRequest()` on Filament CSS assets | Prevents unused bundles from auto-loading via `@filamentStyles` |

Dependency order is declared in `FlexFieldAssets::STYLESHEET_DEPENDENCIES` (e.g. `phone-field` → `flex-text-input` first).

#### JavaScript delivery pipeline

| Step | Class / file | Role |
|------|----------------|------|
| 1 | `x-load` + thin `{component}.js` entry | Alpine factory only — heavy libs in shared chunks |
| 2 | esbuild `splitting: true` + semantic chunk names | `flex-fields-phone-lib-*`, `flex-fields-emoji-*`, … |
| 3 | `alpine-manifest.json` | Maps each field → chunk list for preload |
| 4 | `FlexFieldAlpineQueue` | Dedup `modulepreload` in `<head>` — one fetch per chunk per request |
| 5 | Dynamic `import()` where possible | e.g. libphonenumber, emoji picker — parse cost deferred until interaction |

#### Bundle inventory & CI

`npm run build` writes `resources/dist/bundle-metrics.json` (raw + gzip KB per file). Core CSS stays lean; component CSS/JS load lazily through the pipeline above.

```bash
npm run build          # CSS + JS → resources/dist/
npm run check:budgets  # fail if any bundle exceeds limits
```

| Field / component | JS (KB) | CSS (KB) |
|-------------------|--------:|---------:|
| PhoneField | 4.7 + phone-lib 185 (gzip 43) | 25.9 |
| CountryField | 2.6 | 25.9 |
| TagsField | — | 22.0 |
| FlexTextInput | 10.1 + emoji 6.4 lazy | 28.4 |
| RatingField | — | 19.3 |
| SwitchField | — | 18.5 |
| UserSelect | — | 52.0 |
| SegmentControl | — | 19.8 |
| core (always) | — | 19.9 (gzip 4.5) |

Full per-file metrics: `resources/dist/bundle-metrics.json`.

---

See [LICENSE](LICENSE) for license details.

<p align="center">Made with ❤️ by <a href="mailto:barek122@gmail.com">Bartłomiej Janczak</a></p>

<p align="center" class="filament-hidden">
    <img src="art/field-flex-thumb-r.png" width="100%" style="border-radius: 12px;" alt="Filament Flex Fields — 70 custom form components and JSON custom fields for Filament v5 Laravel admin panels" class="filament-hidden">
</p>

<h1 align="center">Filament Flex Fields</h1>

<p align="center"><strong>Filament v5 · Laravel admin forms · 70 custom components · one design system</strong><br>Replace a patchwork of field plugins with one cohesive form layer — lazy assets, optional JSON custom fields, built-in Playground.</p>
<p align="center">Pre-built CSS/JS · no Node.js in production · standalone fields or dynamic schemas · full per-component docs</p>

<p align="center">
    <a href="https://flex-fields.bjanczak.com/" target="_blank" rel="noopener noreferrer">
        <img src="art/docs-button-v2.png" width="210" alt="Documentation — flex-fields.mintlify.app">
    </a>
</p>

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

---

### Premium companion — [Filament Flex Forms](https://github.com/janczakb/filament-flex-forms)

Need a **drag-and-drop form Studio**, public fill & embed, submissions, Insights, and integrations on top of these same components? That’s **Flex Forms** — a **premium commercial** Filament plugin built on Flex Fields.

<p align="center">
  <a href="https://github.com/janczakb/filament-flex-forms">
    <img src="https://raw.githubusercontent.com/janczakb/filament-flex-forms/main/art/field-forms-thumb-radius.webp" width="200" alt="Filament Flex Forms — premium Studio for Filament">
  </a>
</p>

<p align="center">
  <a href="https://shop.bjanczak.com/checkout/buy/3d3a0d72-c9d5-4cfd-90c9-26869c444bb0"><strong>Buy Flex Forms</strong></a>
  ·
  <a href="https://flexforms.bjanczak.com">Docs</a>
  ·
  <a href="https://github.com/janczakb/filament-flex-forms">GitHub</a>
</p>

---

**Filament Flex Fields** is a [Filament v5](https://filamentphp.com) plugin for **Laravel admin panels**: **70 custom form components**, a unified `--fff-*` design system, and an optional **JSON custom-field layer** (no EAV, no per-attribute migrations). Use any field standalone, or wire schemas through `FlexFieldFormBuilder` + `HasFlexFields`.

---

## Quick start

**First-time install:**

```bash
composer require janczakb/filament-flex-fields
php artisan filament:assets
```

Register the plugin on your Filament panel:

```php
use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(FilamentFlexFieldsPlugin::make());
}
```

Then drop any component into a form — e.g. `MatrixChoiceField::make('priorities')`. Full install options (path repo, config, translations): [Installation](#installation). **Already installed?** See [Upgrading](#upgrading) below.

---

## Upgrading

When a new version is released, update the package and sync Filament assets into `public/`. **You do not need Node.js, npm, or `npm run build` in your Laravel app** — the plugin ships pre-built CSS/JS in `resources/dist/`.

### Standard upgrade (Packagist)

```bash
composer update janczakb/filament-flex-fields
php artisan filament:assets
```

That is the full required workflow for most apps.

`php artisan filament:assets` syncs **both** Filament CSS/JS/Alpine bundles and bundled static media (MP3, emoji images, etc.) into `public/filament-flex-fields-assets/`. Fields resolve those files through `FlexFieldAssets::assetUrl()` with automatic cache busting.

### Path repository (monorepo / local package)

```bash
composer update janczakb/filament-flex-fields
php artisan filament:assets
```

After updating the package, run `php artisan filament:assets` in your Laravel app to sync bundled media and Filament assets into `public/`.

### Automate asset sync (recommended)

Add this to your host app `composer.json` so `filament:assets` runs after every `composer install` / `composer update`:

```json
"scripts": {
    "post-autoload-dump": [
        "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
        "@php artisan package:discover --ansi",
        "@php artisan filament:assets --ansi"
    ]
}
```

With that hook in place, `composer update janczakb/filament-flex-fields` alone is enough.

### What you usually do **not** need on upgrade

| Step | Needed? |
|------|---------|
| `npm install` / `npm run build` in the host app | **No** — assets are pre-built in the package |
| Manual copy of `public/filament-flex-fields-assets/` | **No** — synced automatically by `filament:assets` (or on non-production app boot) |
| `php artisan vendor:publish --tag=filament-flex-fields-config` | **No** — unless [CHANGELOG](CHANGELOG.md) documents a new config key you want to set |
| `php artisan vendor:publish --tag=filament-flex-fields-translations` | **No** — see [Updating translations after a plugin upgrade](#updating-translations-after-a-plugin-upgrade) |
| `php artisan optimize:clear` | **Only** if the panel still serves stale CSS/JS after `filament:assets` (rare) |

### After upgrading in the browser

If a field looks unstyled after deploy, hard-refresh the Filament panel (`Cmd+Shift+R` / `Ctrl+Shift+R`) once so the browser drops cached asset URLs.

### Version-specific notes

Read [CHANGELOG.md](CHANGELOG.md) for breaking changes, new config keys, and migration steps for a given release.

---

## Why Flex Fields?

### Who it's for

Teams building **Filament v5** backends that need more than native inputs — **CRM** custom attributes, **CMS** page builders, **SaaS** onboarding, **marketplaces** with configurable product fields, or any admin UI that should look and behave like one product, not ten plugins stitched together.

### At a glance

| | **Flex Fields** | **Typical approach** |
|---|-----------------|----------------------|
| **Scope** | **70** fields, layouts, and table columns — one package | Many single-purpose Filament plugins |
| **Design** | One `--fff-*` system — sizes, focus, menus, dark mode | Mixed UI from unrelated packages |
| **Flexibility** | Standalone fields **or** dynamic JSON on models — same components | Usually one mode only |
| **Depth** | Validation, formatting, and interaction built in — not thin wrappers | Basic inputs; edge cases left to you |
| **Performance** | Lazy per-field CSS/JS in `<head>`, shared chunks, pre-built `dist/` — no npm in your app | Global bundles or consumer-side builds |
| **DX** | Playground for every component + dedicated doc per field | Trial-and-error per plugin |

### What's inside

**70 components** — 58 form fields, 9 layout/schema pieces, 3 table columns, plus `HoldConfirmAction`. Matrix grids, slugs, translatable groups, media, ratings, signatures, layouts — [full list](#custom-components-70).

**One design system** — shared `sm` / `md` / `lg` sizes, `--fff-*` tokens, glass searchable menus, dark mode, consistent focus rings.

<a id="lazy-assets--shared-chunks"></a>**Lazy assets** — each field loads only its CSS/JS; heavy libraries share chunks and preload once per page. Pre-built `resources/dist/` means **no Node.js or Vite in your Laravel project**.

<details>
<summary>Asset pipeline (technical)</summary>

1. **Lean core** — `core.css` (~20 KB): tokens and hint chrome only.
2. **Conditional critical preload** — `teleported-menu` at `HEAD_END` only when a dropdown field is on the page (`FlexFieldStylesheetQueue::needsTeleportedMenu()`). Hold-confirm preloads per action via `@push` in `hold-confirm.blade.php`, not globally.
3. **Per-component bundles** — queued when the field renders, deduped per request via `FlexFieldStylesheetQueue` / `FlexFieldAlpineQueue`. Alpine entries register once; manifest chunks load on demand.
4. **Head delivery** — `emit-assets` pushes `<link>` / `modulepreload` via `@stack('styles')` on full pages; Livewire partials emit inline asset batches.
5. **SPA injector** — `flex-field-asset-injector.js` loads missing lazy CSS/JS on morph and navigation, with FOUC prevention inside Filament modals.
6. **Lazy Alpine mount** — heavy fields defer `x-data` init until `x-intersect` (see `lazy-alpine-mount` Blade component).

See [Performance-first assets](#performance-first-assets) for classes, manifest, and bundle metrics.

</details>

<a id="dynamic-custom-fields-json"></a>**JSON custom fields** — define schemas in PHP config or `FlexFieldSchemaRegistry`, store values in one JSON column via `HasFlexFields`. `FlexFieldFormBuilder` renders live Filament forms. Ideal for CMS, tenant settings, and CRM-style attributes. Options: [config/filament-flex-fields.php](config/filament-flex-fields.php).

**Playground & docs** — local preview of all 70 components; every field documented in `docs/` with methods, validation, and examples.

---

## Table of contents

- [Quick start](#quick-start)
- [Upgrading](#upgrading)
- [Why Flex Fields?](#why-flex-fields)
- [Screenshots](#screenshots)
- [Custom Components (70)](#custom-components-70)
- [Use cases](#use-cases)
- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
- [Quick usage](#quick-usage)
- [Playground](#playground)
- [Documentation](#documentation)
- [FAQ](#faq)
- [Performance-first assets](#performance-first-assets)

---

## Screenshots

<div style="display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; width: 100%;">
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/nps-field"><img src="art/sc-33.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="NpsField - Enterprise NPS, CSAT, and Likert scale inputs with pills, segments, and emoji variants, color-coded detractor/passive/promoter ranges, and optional deselect"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">NpsField — NPS, CSAT & Likert Survey Scales</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/calculator-field"><img src="art/sc-34.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="CalculatorField - Numeric input with shared iOS-style calculator panel, per-field session memory, floating desktop panel, and mobile bottom sheet"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">CalculatorField — Shared Floating Calculator Panel</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/signaturefield"><img src="art/sc-1.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="SignatureField - HTML5 canvas handwriting signature pad for Filament forms, allowing touch-friendly signatures with WebP export"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">SignatureField — Canvas Handwriting Signature Pad</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/matrixchoicefield"><img src="art/sc-2.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="MatrixChoiceField - Dynamic survey matrix choice grid with radio and checkbox modes, reactive disabled cells, and custom validations"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">MatrixChoiceField — Survey & Configurator Grid</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/flextextareafield"><img src="art/sc-3.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="FlexTextareaField - Advanced multi-line input with speech dictation, character counter, autosize, and integrated emoji picker"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">FlexTextareaField — Autosize Textarea with Voice & Emoji Input</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/progressbar"><img src="art/sc-4.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ProgressBar and ProgressCircle - Multi-style visual progress indicators, linear trackers, and circular dashboard widgets for Laravel Filament"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ProgressBar & ProgressCircle — Visual Progress Indicators</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/currencyfield"><img src="art/sc-5.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="CurrencyField - Multi-currency localized money input with real-time formatting, automatic decimal separation, and prefix selector"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">CurrencyField — Multi-Currency Localized Input</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/mappickerfield"><img src="art/sc-6.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="MapPickerField - Interactive map coordinate pin picker with marker support, location autofill, and custom layouts for Filament"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">MapPickerField — Interactive Map Pin Selector</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/itemcardgroup"><img src="art/sc-7.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ItemCardGroup - Card-based layout component for structured settings blocks, user profiles, and clean Filament form layouts"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ItemCardGroup — Card-Based Layout Group</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/duallistboxfield"><img src="art/sc-8.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="DualListboxField - Highly interactive searchable dual panel transfer list for selecting and reordering multiple options in Filament v5"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">DualListboxField — Reorderable Two-Panel Transfer List</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/pricerangefield"><img src="art/sc-9.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="PriceRangeField - Dual-handle interactive price filter with histogram slider and minimum/maximum range controls"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">PriceRangeField — Dual-Handle Price Filter</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/creditcardfield"><img src="art/sc-10.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="CreditCardField - Real-time credit card preview wrapper with Luhn validation and dynamic CVV flip animations"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">CreditCardField — Interactive Card Preview</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/flexcolorpickerfield"><img src="art/sc-11.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="FlexColorPickerField - Color picker with preset swatches, opacity slider, visual grid, and eyedropper support"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">FlexColorPickerField — Advanced Color Picker</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/audiofield"><img src="art/sc-12.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="AudioField and VoiceNoteRecorderField - Web-based audio player with waveform visualizer and in-browser voice note recorder"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">AudioField & VoiceNoteRecorderField — Waveform Audio & Voice Messages</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/numberstepper"><img src="art/sc-13.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="NumberStepper - Pill-shaped numeric stepper control with plus/minus buttons and dynamic NumberFlow animation"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">NumberStepper — Animated Numeric Control</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/choicecards"><img src="art/sc-14.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ChoiceCards - Rich card-based selection list with custom icons, headers, badges, and selected highlight states"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ChoiceCards — Rich Selection Grid</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/videofield"><img src="art/sc-15.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="VideoField - Interactive video URL player supporting YouTube, Vimeo, and local HTML5 videos with custom media controls"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">VideoField — Video Player & Embed Component</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/trackslider"><img src="art/sc-16.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="TrackSlider - Track-style range slider supporting single values, percentage progress, and min/max range handles"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">TrackSlider — Inline Range & Segment Slider</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/segmentcontrol"><img src="art/sc-17.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="SegmentControl - Elegant segmented sliding tab controls with support for icons, disabled states, and dynamic sizing"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">SegmentControl — Segmented Button Tab Switcher</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/covercard"><img src="art/sc-18.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="CoverCard - Beautiful media card component for hero sections, product banners, or settings header blocks"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">CoverCard — Media Rich Hero Banner</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/progresscircle"><img src="art/sc-19.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ProgressCircle - Circular progress meters and semicircle tracking gauges for dashboard analytics"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ProgressCircle — Semicircle & Circular Dashboard Metrics</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/ratingfield"><img src="art/sc-20.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="RatingField - Highly customizable star rating input supporting custom icons, semantic color states, and fractional display"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">RatingField — Visual Star Rating Input</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/sc-21.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="HoldConfirmAction - Custom action button requiring the user to press and hold to confirm high-risk actions like deletion">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">HoldConfirmAction — Press & Hold Button</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/slugfield-and-titleslugfield"><img src="art/sc-22.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="SlugField combined with TranslatableFields showing multi-lingual title fields and live localized URL slug generation"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">SlugField & TranslatableFields — Translatable SEO Slugs</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/phonefield"><img src="art/sc-23.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="PhoneField - International phone number input field with country flag selectors, calling code auto-detection, and libphonenumber validation"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">PhoneField — International Phone Input</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/colorswatchfield"><img src="art/sc-24.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ColorSwatchField - Interactive color swatch picker supporting circle/square shapes, size configurations, and focus indicators"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ColorSwatchField — Preset Color Swatches</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/flextextinput"><img src="art/sc-25.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="FlexEmojiPicker - Integrated searching emoji picker popover with skin tone categories and custom category tabs"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">FlexEmojiPicker — Integrated Searchable Emoji Picker</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/date-and-time-fields"><img src="art/sc-26.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="FlexDateRangeField - Dark-themed calendar date picker with date range selection and custom calendar rendering"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">FlexDateRangeField — Dark Mode Calendar & Date Range Picker</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/schedule-field"><img src="art/sc-27.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="ScheduleField - Weekly schedule editor with day toggles, multiple time slots, breaks, and copy-to-weekdays functionality"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">ScheduleField — Weekly Schedule Editor</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/link-preview-field"><img src="art/sc-28.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="LinkPreviewField - URL input field with live Open Graph / meta tag preview cards and server-side scraping"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">LinkPreviewField — Open Graph Link Preview Card</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/social-links-field"><img src="art/sc-29.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="SocialLinksField - Social profile link editor with brand icons, validation, reordering, and custom platforms support"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">SocialLinksField — Social Profile Link Editor</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/barcode-scanner-field"><img src="art/sc-30.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="BarcodeScannerField - Barcode and QR input with Filament modal camera scanner, format filtering, EAN/UPC checksum validation, and hybrid native + ZXing engines"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">BarcodeScannerField — Camera Barcode & QR Scanner</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/flexfileupload-and-fleximageupload"><img src="art/sc-31.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="FlexFileUpload - Styled file upload with webcam capture, URL import, and security presets"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">FlexFileUpload — Webcam & URL File Import</p>
  </div>
  <div style="flex-grow: 1; width: 48%; min-width: 280px; text-align: center; box-sizing: border-box; padding: 10px;">
    <a href="https://flex-fields.bjanczak.com/docs/icon-picker-field"><img src="art/sc-32.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="IconPickerField - Highly optimized searchable SVG icon picker with virtual scrolling, asynchronous preview loading, and WAI-ARIA combobox accessibility"></a>
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">IconPickerField — Virtual Scrolling & W3C ARIA</p>
  </div>
  <div style="flex-grow: 1; width: 100%; text-align: center; box-sizing: border-box; padding: 10px;">
    <img src="art/more.png" width="100%" style="border-radius: 12px; border: 1px solid #e5e7eb;" alt="And More - Overview of the interactive Developer Playground displaying form fields, custom layouts, and UI components in Filament Flex Fields">
    <p style="margin-top: 8px; font-weight: 600; color: #374151;">And More — 70 Components & Visual Playground</p>
  </div>
</div>

---

## Custom Components (70)

Every item below is a **custom class shipped by this package** — own Blade views, CSS, and configuration API. This list does **not** include native Filament fields (`TextInput`, `TagsInput`, `Repeater`, etc.) used only as passthrough inside `FlexFieldFormBuilder`.

Full API for each component: **[https://flex-fields.bjanczak.com/docs/index](https://flex-fields.bjanczak.com/docs/index)**.

### Text & input (13)

| Component | Description |
|-----------|-------------|
| [`FlexTextInput`](https://flex-fields.bjanczak.com/docs/flextextinput) | Enhanced text input — speech dictation, emoji picker, password strength, clearable |
| [`FlexTextareaField`](https://flex-fields.bjanczak.com/docs/flextextareafield) | Animated autosizing textarea with character counter |
| [`FlexRichEditor`](https://flex-fields.bjanczak.com/docs/flex-rich-editor) | JSON-first rich editor — responsive images, limits, fullscreen, autosave, optional Spatie |
| [`PhoneField`](https://flex-fields.bjanczak.com/docs/phonefield) | International phone input with libphonenumber validation |
| [`CountryField`](https://flex-fields.bjanczak.com/docs/countryfield) | Searchable country picker with flags |
| [`TimezoneField`](https://flex-fields.bjanczak.com/docs/timezonefield) | IANA timezone picker with UTC offset display |
| [`LinkPreviewField`](https://flex-fields.bjanczak.com/docs/link-preview-field) | URL input with live Open Graph preview card (horizontal, vertical, or full-width layouts) |
| [`BarcodeScannerField`](https://flex-fields.bjanczak.com/docs/barcode-scanner-field) | Barcode/QR input — Filament modal camera scanner, format whitelist, EAN/UPC checksum, hybrid BarcodeDetector + ZXing, torch & front/back switch, iOS-safe preview *(v2.6.0)* |
| [`SocialLinksField`](https://flex-fields.bjanczak.com/docs/social-links-field) | Social profile links — platform picker, URL validation, custom platforms, reorder |
| [`SlugField`](https://flex-fields.bjanczak.com/docs/slugfield-and-titleslugfield) | Slug input with permalink preview, uniqueness, regenerate/copy actions |
| [`TitleSlugField`](https://flex-fields.bjanczak.com/docs/slugfield-and-titleslugfield) | Title + slug pair with live URL preview and optional Spatie Sluggable |
| [`AddressAutocompleteField`](https://flex-fields.bjanczak.com/docs/addressautocompletefield) | Mapbox-powered address search with structured storage |
| [`FlexVerificationCode`](https://flex-fields.bjanczak.com/docs/flexverificationcode) | OTP / 2FA verification code input with grouping |

### Number & range (7)

| Component | Description |
|-----------|-------------|
| [`NumberStepper`](https://flex-fields.bjanczak.com/docs/numberstepper) | +/- numeric stepper control |
| [`CalculatorField`](https://flex-fields.bjanczak.com/docs/calculator-field) | Numeric input with shared iOS-style calculator panel and per-field session memory *(v2.8.1)* |
| [`CurrencyField`](https://flex-fields.bjanczak.com/docs/currencyfield) | Multi-currency money input with locale-aware formatting |
| [`FlexSlider`](https://flex-fields.bjanczak.com/docs/flexslider) | Styled range slider with value display |
| [`TrackSlider`](https://flex-fields.bjanczak.com/docs/trackslider) | Track-style slider — single value, percentage, or min/max range |
| [`PriceRangeField`](https://flex-fields.bjanczak.com/docs/pricerangefield) | Dual-handle price filter with histogram |
| [`TrafficSplit`](https://flex-fields.bjanczak.com/docs/trafficsplit) | Weighted segment split control (A/B-style traffic allocation) |

### Choice & selection (14)

| Component | Description |
|-----------|-------------|
| [`SwitchField`](https://flex-fields.bjanczak.com/docs/switchfield) | Animated toggle switch with row/inline layouts |
| [`CellSwitch`](https://flex-fields.bjanczak.com/docs/switchfield) | Compact `SwitchField` variant for table cells |
| [`SegmentControl`](https://flex-fields.bjanczak.com/docs/segmentcontrol) | Segmented button control |
| [`ChoiceCards`](https://flex-fields.bjanczak.com/docs/choicecards) | Rich card-based radio selection |
| [`ChoiceCheckboxCards`](https://flex-fields.bjanczak.com/docs/choicecheckboxcards) | Rich card-based multi-select |
| [`FlexChecklist`](https://flex-fields.bjanczak.com/docs/flexchecklist) | Animated checklist with icons and descriptions |
| [`FlexRadiolist`](https://flex-fields.bjanczak.com/docs/flexradiolist) | Animated radio list with icons and descriptions |
| [`MatrixChoiceField`](https://flex-fields.bjanczak.com/docs/matrixchoicefield) | Survey / configurator matrix grid — radio or checkbox per row |
| [`FlexMatrixTable`](https://flex-fields.bjanczak.com/docs/flex-matrix-table) | Advanced matrix grid with full Filament components inside cells |
| [`SelectField`](https://flex-fields.bjanczak.com/docs/selectfield) | Rich select with avatars, badges, and descriptions |
| [`UserSelect`](https://flex-fields.bjanczak.com/docs/userselect) | User picker with avatar stacks and verification badges |
| [`DualListboxField`](https://flex-fields.bjanczak.com/docs/duallistboxfield) | Two-panel reorderable transfer list |
| [`TagsField`](https://flex-fields.bjanczak.com/docs/tags-field) | Tag input — pills below the field with inline remove buttons |
| [`IconPickerField`](https://flex-fields.bjanczak.com/docs/icon-picker-field) | Searchable blade-icons picker with lazy SVG rendering, virtual scroll, and paginated search *(v2.7.0)* |
| [`FlexSpatieTagsField`](https://flex-fields.bjanczak.com/docs/tags-field) | Spatie Tags integration for `TagsField` |

### Date & time (11)

| Component | Description |
|-----------|-------------|
| [`FlexDateField`](https://flex-fields.bjanczak.com/docs/date-and-time-fields) | Segmented date input without calendar popover |
| [`FlexDatePicker`](https://flex-fields.bjanczak.com/docs/date-and-time-fields) | Date picker with calendar popover |
| [`FlexTimeField`](https://flex-fields.bjanczak.com/docs/date-and-time-fields) | Segmented time input (12h / 24h, seconds optional) |
| [`FlexTimeSegmentsField`](https://flex-fields.bjanczak.com/docs/date-and-time-fields) | Dropdown time picker (hour / minute columns, `HH:MM`) |
| [`ScheduleField`](https://flex-fields.bjanczak.com/docs/schedule-field) | Weekly opening-hours editor — day toggles, slots, breaks, copy-to-weekdays, timezone |
| [`FlexDateTimePicker`](https://flex-fields.bjanczak.com/docs/date-and-time-fields) | Combined date + time picker |
| [`FlexDateRangeField`](https://flex-fields.bjanczak.com/docs/date-and-time-fields) | Start/end date range |
| [`FlexDurationField`](https://flex-fields.bjanczak.com/docs/date-and-time-fields) | Duration input (hours / minutes) |
| [`FlexTimeRangeField`](https://flex-fields.bjanczak.com/docs/date-and-time-fields) | Start/end time range |
| [`FlexMonthPicker`](https://flex-fields.bjanczak.com/docs/date-and-time-fields) | Month picker |
| [`FlexYearPicker`](https://flex-fields.bjanczak.com/docs/date-and-time-fields) | Year picker |

### Media, color & location (12)

| Component | Description |
|-----------|-------------|
| [`ColorSwatchField`](https://flex-fields.bjanczak.com/docs/colorswatchfield) | Preset color swatch picker |
| [`FlexColorPickerField`](https://flex-fields.bjanczak.com/docs/flexcolorpickerfield) | Advanced color picker with grid and eyedropper |
| [`FlexFileUpload`](https://flex-fields.bjanczak.com/docs/flexfileupload-and-fleximageupload) | Styled file upload with webcam capture, URL import, and security presets *(v2.6.1)* |
| [`FlexImageUpload`](https://flex-fields.bjanczak.com/docs/flexfileupload-and-fleximageupload) | Image upload with processing options |
| [`FlexSpatieMediaLibraryFileUpload`](https://flex-fields.bjanczak.com/docs/flexfileupload-and-fleximageupload) | Spatie Media Library upload integration |
| [`VideoField`](https://flex-fields.bjanczak.com/docs/videofield) | Video URL / player with YouTube support |
| [`AudioField`](https://flex-fields.bjanczak.com/docs/audiofield) | Audio URL / player with waveform |
| [`VoiceNoteRecorderField`](https://flex-fields.bjanczak.com/docs/voicenoterecorderfield) | In-browser voice recorder — waveform, local playback, deferred or immediate upload |
| [`MapPickerField`](https://flex-fields.bjanczak.com/docs/mappickerfield) | Interactive map pin picker with draggable marker and address autofill |
| [`SignatureField`](https://flex-fields.bjanczak.com/docs/signaturefield) | Canvas signature pad |
| [`CreditCardField`](https://flex-fields.bjanczak.com/docs/creditcardfield) | Card preview with Luhn validation and CVV flip |
| [`CellSlider`](https://flex-fields.bjanczak.com/docs/trackslider) | Compact `TrackSlider` variant for table cells |

### Rating (2)

| Component | Description |
|-----------|-------------|
| [`RatingField`](https://flex-fields.bjanczak.com/docs/ratingfield) | Star rating input |
| [`NpsField`](https://flex-fields.bjanczak.com/docs/nps-field) | NPS, CSAT & Likert survey scales — pills, segments, and emoji variants *(v2.8.0)* |

### Layout & display — schemas (9)

| Component | Description |
|-----------|-------------|
| [`SegmentTabs`](https://flex-fields.bjanczak.com/docs/segmenttabs) | Tabbed segment navigation for forms |
| [`TranslatableFields`](https://flex-fields.bjanczak.com/docs/translatablefields) | Locale tabs wrapping any fields (JSON or Spatie Translatable) |
| [`TranslatableTabs`](https://flex-fields.bjanczak.com/docs/translatablefields) | Legacy alias for `TranslatableFields` |
| [`ItemCard`](https://flex-fields.bjanczak.com/docs/itemcard) | Single settings-style card row |
| [`ItemCardGroup`](https://flex-fields.bjanczak.com/docs/itemcardgroup) | Polished card-based settings group |
| [`ItemCardStack`](https://flex-fields.bjanczak.com/docs/itemcardstack) | Stacked card layout for profile / settings pages |
| [`CoverCard`](https://flex-fields.bjanczak.com/docs/covercard) | Hero cover card for tabbed editors |
| [`ProgressBar`](https://flex-fields.bjanczak.com/docs/progressbar) | Linear, pill, or segment progress bar |
| [`ProgressCircle`](https://flex-fields.bjanczak.com/docs/progresscircle) | Circular or semicircle progress indicator |

Ready-made layout recipes: [Form layout patterns](https://flex-fields.bjanczak.com/docs/index#form-layout-patterns).

### Table columns (3)

| Component | Description |
|-----------|-------------|
| [`UserColumn`](https://flex-fields.bjanczak.com/docs/usercolumn) | Avatar + name/email display with hover card |
| [`RatingColumn`](https://flex-fields.bjanczak.com/docs/ratingcolumn) | Star rating display in Filament tables |
| [`IconColumn`](https://flex-fields.bjanczak.com/docs/iconcolumn) | Blade-icons display for `IconPickerField` values *(v2.7.0)* |

**Total: 70 custom components** (58 form fields + 9 layout/schema + 3 table columns). **HoldConfirmAction** (press-and-hold Filament actions) is documented in the playground but not counted in the 70.

---

## Use cases

| Scenario | Recommended components |
|----------|------------------------|
| **CRM / SaaS custom attributes** | JSON flex fields + `PhoneField`, `CountryField`, `UserSelect` |
| **CMS / page builder** | `TitleSlugField`, `TranslatableFields`, `FlexFileUpload`, `FlexImageUpload` |
| **Product configurator** | `MatrixChoiceField`, `ChoiceCards`, `PriceRangeField`, `ColorSwatchField` |
| **Surveys & assessments** | `NpsField`, `MatrixChoiceField`, `FlexRadiolist`, `RatingField` |
| **SaaS onboarding** | `ChoiceCards`, `SegmentTabs`, `CoverCard`, `ProgressCircle` |
| **E-commerce filters** | `PriceRangeField`, `TrackSlider`, `DualListboxField`, `CalculatorField` |
| **User profile settings** | `ItemCardGroup`, `PhoneField`, `TimezoneField`, `SignatureField` |
| **Payment forms** | `CreditCardField`, `FlexVerificationCode` |
| **Location services** | `MapPickerField`, `AddressAutocompleteField` |
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

Already ran [Quick start](#quick-start)? Jump to [Setup](#setup). For version bumps, see [Upgrading](#upgrading). Below: Packagist install, monorepo path repo, and optional Composer automation.

### Composer (Packagist)

```bash
composer require janczakb/filament-flex-fields
php artisan filament:assets
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
php artisan filament:assets
```

Auto-discovered via `composer.json` → `extra.laravel.providers`.

**Asset sync on every Composer run** — optional but recommended; see [Automate asset sync](#automate-asset-sync-recommended) in [Upgrading](#upgrading).

```json
"scripts": {
    "post-autoload-dump": [
        "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
        "@php artisan package:discover --ansi",
        "@php artisan filament:assets --ansi"
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

**Field API highlights:** `searchTypes()`, `language()`, `minSearchLength()`, `searchDebounce()`, `streetAddressesOnly()`. See [MapPickerField](https://flex-fields.bjanczak.com/docs/mappickerfield) and [AddressAutocompleteField](https://flex-fields.bjanczak.com/docs/addressautocompletefield).

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

Full API for every option: **[https://flex-fields.bjanczak.com/docs/index](https://flex-fields.bjanczak.com/docs/index)**.

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

Example slugs: `matrix-choice`, `choice-cards`, `tags-field`, `title-slug-field`, `phone-field`, `file-upload`, `item-card-group`, `progress-circle`.

---

## Documentation

| Document | Contents |
|----------|----------|
| **[https://flex-fields.bjanczak.com/docs/index](https://flex-fields.bjanczak.com/docs/index)** | Complete per-component API — every method, option, validation rule, config key, and example |
| **[https://flex-fields.bjanczak.com/docs/shared-concepts](https://flex-fields.bjanczak.com/docs/shared-concepts)** | Asset pipeline, overlay coordinator, `wire:ignore` + Livewire sync patterns |
| **[CHANGELOG.md](CHANGELOG.md)** | Version history and release notes |
| **config/filament-flex-fields.php** | Schemas, UI defaults, playground, Mapbox, audit |

---

## FAQ

**Why choose Flex Fields over multiple Filament field plugins?**
One design system, one asset pipeline, one Playground, and **70** components that work together — standalone or as dynamic JSON attributes. You avoid conflicting CSS, duplicate JS, and inconsistent field APIs.

**Do I need Node.js to use this package?**
No. Pre-built CSS/JS are committed to `resources/dist/`.

**How does asset loading work?**
Each component loads its own CSS/JS on demand. Shared libraries are split into cached chunks and loaded once per page. See [Performance-first assets](#performance-first-assets).

**Can I use components without the JSON flex-field system?**
Yes. Import any component directly into Filament forms — the JSON column and `HasFlexFields` trait are optional.

**How many components are included?**
**70** custom UI classes with own views and CSS — listed in [Custom Components (70)](#custom-components-70).

**Does it work with Filament v4?**
No — this package targets **Filament v5** only.

**Is Spatie required?**
No. Sluggable, Translatable, and Media Library integrations are optional `composer suggest` packages.

**Where is the Matrix Choice / survey grid?**
`MatrixChoiceField` — radio or checkbox mode, per-row validation, reactive `disableCellWhen()` / `disableRowWhen()`. See [https://flex-fields.bjanczak.com/docs/matrixchoicefield](https://flex-fields.bjanczak.com/docs/matrixchoicefield).

---

## Performance-first assets

This is the technical reference for [Lazy assets & shared chunks](#lazy-assets--shared-chunks) above.

#### CSS delivery pipeline

| Step | Class / file | Role |
|------|----------------|------|
| 1 | Field blade `@include(…load-stylesheet)` | Registers needed bundles when the field is on the page |
| 2 | `FlexFieldStylesheetQueue` / `FlexFieldAlpineQueue` | Request-scoped dedup — 5× `ChoiceCards` → 1× `choice-cards.css` |
| 3 | `emit-assets` (via `load-stylesheet`) | Full-page: `@push('styles')` into `<head>`; Livewire partial: inline `<link>` / `modulepreload` batches |
| 4 | `queued-stylesheets` render hook | Flushes any remaining `pending()` queues at `STYLES_AFTER` and `BODY_END` |
| 5 | Filament `@stack('styles')` in `layout/base.blade.php` | Renders pushed links in `<head>` before content paint |
| 6 | `flex-field-asset-injector.js` | SPA/morph: loads missing lazy assets, dedupes hrefs, prevents modal FOUC |
| 7 | `loadedOnRequest()` on Filament CSS assets | Prevents unused bundles from auto-loading via `@filamentStyles` |

Dependency order is declared in `FlexFieldAssets::STYLESHEET_DEPENDENCIES` and resolved depth-first in `stylesheetsFor()` (e.g. `schedule-field` → `timezone-field` → `flex-time-segments`).

#### JavaScript delivery pipeline

| Step | Class / file | Role |
|------|----------------|------|
| 1 | `x-load` + thin `{component}.js` entry | Alpine factory only — heavy libs in shared chunks |
| 2 | esbuild `splitting: true` + semantic chunk names | `flex-fields-phone-lib-*`, `flex-fields-emoji-*`, … |
| 3 | `alpine-manifest.json` | Maps each field → chunk list for preload |
| 4 | `FlexFieldAlpineQueue` | Dedup `modulepreload` in `<head>` — one fetch per chunk per request |
| 5 | `flex-field-asset-injector.js` | Loads missing chunks from morph batches; in-flight promise cache prevents duplicate fetches |
| 6 | Dynamic `import()` where possible | e.g. libphonenumber, emoji picker — parse cost deferred until interaction |

#### Bundle inventory

Pre-built assets ship in `resources/dist/`. The table below lists sample bundle sizes (raw + gzip KB). Full metrics are in [`resources/dist/bundle-metrics.json`](resources/dist/bundle-metrics.json). JS = entry + preloaded chunks from `alpine-manifest.json`; CSS `+ deps` = declared stylesheet dependencies.

<!-- bundle-summary:start -->
| Field / component | JS (KB) | CSS (KB) |
|-------------------|--------:|---------:|
| core (always) | — | 25.5 (gzip 5.5) |
| PhoneField | 5.9 (gzip 1.9) + virtualized-list 7.3 (gzip 2.5) + select-menu 5.4 (gzip 1.9) + flex-dropdown-coordinator 1.7 (gzip 0.8) + theme-utils 0.5 (gzip 0.3) + phone-lib 185 (gzip 43.3) | 32.3 (gzip 6.4) + deps 68.6 |
| CountryField | 3.9 (gzip 1.4) + virtualized-list 7.3 (gzip 2.5) + select-menu 5.4 (gzip 1.9) + flex-dropdown-coordinator 1.7 (gzip 0.8) + theme-utils 0.5 (gzip 0.3) | 28.6 (gzip 5.9) + deps 68.6 |
| FlexTextInput | 10.9 (gzip 3.3) + flex-dropdown-coordinator 1.7 (gzip 0.8) + emoji 19.7 (gzip 6.2) lazy | 40.4 (gzip 7.4) + deps 22.5 |
| TagsField | 3.1 (gzip 1.1) | 23.4 (gzip 5.2) + deps 65.7 |
| RatingField | 0.7 (gzip 0.3) | 25.4 (gzip 5.6) |
| SwitchField | Alpine inline | 32.5 (gzip 6.2) |
| UserSelect | 14.6 (gzip 4.8) + select-menu 5.4 (gzip 1.9) + theme-utils 0.5 (gzip 0.3) + flex-dropdown-coordinator 1.7 (gzip 0.8) | 31.9 (gzip 6.4) + deps 159.9 |
| MapPickerField | 9.3 (gzip 2.9) + mapbox 6.1 (gzip 2.3) + select-menu 5.4 (gzip 1.9) + flex-dropdown-coordinator 1.7 (gzip 0.8) + theme-utils 0.5 (gzip 0.3) | 29.8 (gzip 6.5) + deps 52.7 |
| SelectField | 14.6 (gzip 4.8) + select-menu 5.4 (gzip 1.9) + theme-utils 0.5 (gzip 0.3) + flex-dropdown-coordinator 1.7 (gzip 0.8) | 82.2 (gzip 12.7) + deps 28.2 |

Sample bundles (10 of **59** production CSS files). Full per-file metrics — every component, shared chunk, and gzip size — live in [`resources/dist/bundle-metrics.json`](resources/dist/bundle-metrics.json) (regenerated on `npm run build`). JS = entry + preloaded chunks from `alpine-manifest.json`; CSS `+ deps` = declared stylesheet dependencies.
<!-- bundle-summary:end -->

---

See [LICENSE](LICENSE) for license details. Third-party attributions: [CREDITS.md](CREDITS.md).

<p align="center">Made with ❤️ by <a href="mailto:barek122@gmail.com">Bartłomiej Janczak</a></p>

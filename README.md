<p align="center" class="filament-hidden">
    <img src="art/plugin-filament-flex-fields.png" width="100%" style="border-radius: 12px;" alt="Filament Flex Fields — dynamic custom fields and HeroUI-inspired form components for Filament v5" class="filament-hidden">
</p>

<h1 align="center">Filament Flex Fields</h1>

<p align="center"><strong>The most complete Filament v5 form component kit — 59 custom UI components + migration-free JSON custom fields.</strong><br>Choice cards · Matrix grid · Tags · Slug & permalink · Phone · Currency · Maps · Translatable · Playground</p>

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

A [Filament v5](https://filamentphp.com) plugin for **dynamic custom fields** and a polished, HeroUI-inspired form UI. Store all flex field values in a **single JSON column** — no per-field migrations, no EAV tables — or drop any component directly into Filament forms, tables, and schemas.

Stop stitching together a dozen separate Filament field plugins. Flex Fields ships **59 custom UI components** with HeroUI-inspired styling, layout primitives (`ItemCard`, `SegmentTabs`, `CoverCard`), table columns (`UserColumn`, `RatingColumn`), optional **JSON custom-field storage**, and a dev **Playground** — with pre-built CSS/JS in `resources/dist/` so consumers **do not need Node.js** at install time. Assets are **lazy-loaded per component** with **shared JS chunks** so heavy pages stay fast.

---

## Screenshots

<table width="100%" border="0">
  <tr>
    <td width="50%"><img src="art/screenshot-01-playground.png" width="100%" style="border-radius: 8px;" alt="Filament Flex Fields playground — live preview of all form components"></td>
    <td width="50%"><img src="art/screenshot-02-matrix-choice.png" width="100%" style="border-radius: 8px;" alt="MatrixChoiceField — multiple choice grid with radio and checkbox modes"></td>
  </tr>
  <tr>
    <td width="50%"><img src="art/screenshot-03-choice-cards.png" width="100%" style="border-radius: 8px;" alt="ChoiceCards and ChoiceCheckboxCards — rich card-based selection UI"></td>
    <td width="50%"><img src="art/screenshot-04-item-cards.png" width="100%" style="border-radius: 8px;" alt="ItemCardGroup — polished settings layout for Filament forms"></td>
  </tr>
</table>

---

## Table of contents

- [Why Flex Fields?](#why-flex-fields)
- [Performance-first assets](#performance-first-assets)
- [Features](#features)
- [Custom components (59)](#custom-components-59)
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

## Key Benefits

Filament Flex Fields simplifies and elevates your form building experience:

* **Migration-Free Custom Fields:** Store dynamic attributes in a single JSON column using the `HasFlexFields` trait. Perfect for CMS, CRM, and SaaS customization.
* **Premium UX & Design:** HeroUI-inspired form controls with a unified `--fff-*` CSS design token scale for a polished, modern look.
* **Complex Forms Made Simple:** Features like `MatrixChoiceField` with reactive row/cell disabling make building surveys and product configurators effortless.
* **All-in-One Component Suite:** Built-in fields for Phones, Currencies, Countries, Timezones, and Slugs with live permalink preview and uniqueness checking.
* **Performance-First Asset Pipeline:** Tired of heavy pages? Flex Fields lazy-loads CSS and uses esbuild JS code-splitting to only download what is visible on the screen.
* **Visual QA (Playground):** A built-in local Playground page to preview and interact with all 59 components during development.

**Two ways to use it:**

* **Dynamic flex fields** — define schemas in your configuration, store values as JSON, and build forms dynamically with `FlexFieldFormBuilder`.
* **Standalone components** — import and chain any field directly in your forms, just like native Filament components.

---

## Performance-first assets

Most Filament field plugins ship one fat CSS/JS bundle. Flex Fields is built for **real admin panels with dozens of fields** — assets load **per component**, shared modules are **split once and cached**, and heavy libraries stay **lazy**.

### CSS — lazy per component

| Bundle | When it loads |
|--------|----------------|
| `core.css` | Always — design tokens, shared layout, table columns |
| `{component}.css` | **Only when that field is on the page** (injected via `load-stylesheet`) |
| `playground.css` | Playground page only |

Use `PhoneField` and `TagsField` on the same form → the browser fetches **only** `phone-field.css` and `tags-field.css`, not styles for the other 57 components.

### JavaScript — tiered chunks (esbuild code splitting)

All **29 Alpine components** are built together with `splitting: true`:

| Layer | What you get |
|-------|----------------|
| **Entry** | Thin `{component}.js` — just the `x-data` factory for that field |
| **Shared chunks** | Reusable `core/` modules with **semantic names** (`flex-fields-emoji-*`, `flex-fields-audio-*`, `flex-fields-select-menu-*`, …) |
| **Manifest** | `alpine-manifest.json` maps each field → its chunks for `modulepreload` |
| **Dedup** | `FlexFieldAlpineQueue` preloads each shared chunk **once per request**, even with multiple fields on the page |

**Examples of what gets shared automatically:**

| Shared module | Used by | Why it matters |
|---------------|---------|----------------|
| Emoji picker | `FlexTextInput`, `FlexTextarea` | ~45 KB library loaded once, not twice |
| Audio core | `AudioField`, `VoiceNoteRecorderField` | Waveform, playback, time formatting in one chunk |
| Select menu shell | `CountryField`, `TimezoneField`, `CurrencyField` | One composable for floating searchable menus |
| Mapbox helpers | `MapPickerField`, `AddressAutocompleteField` | Geocoding logic shared |
| libphonenumber-js | `PhoneField` | ~190 KB **lazy `import()`** — only when the field mounts |

Fields with no shared deps (e.g. `RatingField`) stay as a single small entry — no artificial splitting.

### Consumer-friendly

- Pre-built assets in `resources/dist/` — **no Node.js required** in your app.
- After `composer update`, run `php artisan filament:assets` (or wire it in `post-autoload-dump`).
- Technical deep-dive: [docs/index.md → Assets & playground](docs/index.md#assets--playground).

---

## Features

### Dynamic flex fields (JSON storage)

- 📦 **Migration-free custom attributes** — one JSON column per model; no EAV tables, no schema migrations when adding fields.
- 🧩 **Schema registry** — `FlexFieldSchemaRegistry` + config-driven field groups for CMS, CRM, and product catalogs.
- 🧩 **Schema registry** — `FlexFieldSchemaRegistry` + `FlexFieldFormBuilder` wire custom components into config-driven field groups (CMS, CRM, product catalogs).
- 🌍 **Translatable fields** — locale tabs via `TranslatableFields` (JSON/array storage or optional Spatie Translatable).
- 🔗 **Optional Spatie integrations** — Sluggable (slug generation), Translatable (i18n), Media Library (`FlexSpatieMediaLibraryFileUpload`), Tags (`FlexSpatieTagsField` with `spatie/laravel-tags`).

### Form UI & UX

- 🎴 **Rich choice UI** — `ChoiceCards`, `ChoiceCheckboxCards`, `FlexChecklist`, `FlexRadiolist`, `MatrixChoiceField` (radio/checkbox grid, per-row validation, reactive cell disabling).
- 📞 **International inputs** — `PhoneField` (libphonenumber), `CountryField`, `TimezoneField`, `CurrencyField` with locale-aware formatting.
- 🗺️ **Location** — `MapPickerField` (interactive map) + `AddressAutocompleteField` (Mapbox geocoding).
- 💳 **Payments & verification** — `CreditCardField` (Luhn validation, card flip on CVV), `FlexVerificationCode` (OTP / 2FA codes).
- 📅 **Date & time suite** — 9 pickers: segmented date, calendar date, time, datetime, date range, time range, duration, month, year.
- 🎚️ **Numbers & ranges** — `NumberStepper`, `FlexSlider`, `TrackSlider`, `PriceRangeField`, `TrafficSplit` (weighted A/B-style segments).
- 🔘 **Toggles & segments** — `SwitchField`, `SegmentControl`, `SegmentTabs`.
- 📎 **Media** — `FlexFileUpload`, `FlexImageUpload`, `VideoField`, `AudioField`, `VoiceNoteRecorderField`, `SignatureField`.
- 🎨 **Color** — `ColorSwatchField` (presets), `FlexColorPickerField` (advanced picker with eyedropper).
- 🔗 **Slug & permalink** — `SlugField`, `TitleSlugField` with live preview, copy/visit/regenerate actions, homepage slug support.
- 👤 **Users** — `UserSelect` with avatars, verification badges, and stacked display.
- 📋 **Lists** — `DualListboxField`, `SelectField`, `TagsField` (pills below input with inline remove).

### Layout & display (schemas)

- 🃏 **Cards** — `ItemCard`, `ItemCardGroup`, `ItemCardStack`, `CoverCard` — build polished card-based settings, tabbed editors, and profile layouts.
- 📊 **Progress** — `ProgressBar` (linear, pills, segments), `ProgressCircle` (circle, semicircle, gradients).
- 🧪 **Playground** — visual QA page for every component; enable in `local` by default.

### Developer experience

- ⚡ **Performance-first assets** — lazy per-component CSS, esbuild JS code splitting, semantic shared chunks, `modulepreload` dedup (see [Performance-first assets](#performance-first-assets)).
- 🎨 **Unified design tokens** — shared `--fff-*` CSS scale (`sm` / `md` / `lg`) across all components.
- 📦 **Zero Node.js for consumers** — pre-built assets committed to `resources/dist/`.
- 📖 **Full API reference** — [docs/index.md](docs/index.md) documents every method, option, validation rule, and example.
- ✅ **Tested** — Pest feature/unit tests, PHPStan, Laravel Pint.

---

## Custom components (59)

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
| [`AddressAutocompleteField`](docs/addressautocompletefield.md) | Mapbox-powered address search with structured storage |
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

### Choice & selection (12)

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
| [`TagsField`](docs/index.md) | Tag input — pills below the field with inline remove buttons |

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

### Media, color & location (11)

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
| [`MapPickerField`](docs/mappickerfield.md) | Interactive map pin picker |
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

**Total: 59 custom components** (48 form fields + 9 schema/display + 2 table columns).

---

## Use cases

| Scenario | Recommended components |
|----------|------------------------|
| **CMS / page builder** | `TitleSlugField`, `TranslatableFields`, `FlexFileUpload`, `FlexImageUpload` |
| **CRM custom fields** | JSON flex fields + `PhoneField`, `CountryField`, `UserSelect` |
| **Product configurator** | `MatrixChoiceField`, `ChoiceCards`, `PriceRangeField`, `ColorSwatchField` |
| **Surveys & assessments** | `MatrixChoiceField`, `FlexRadiolist`, `RatingField` |
| **SaaS onboarding** | `ChoiceCards`, `SegmentTabs`, `CoverCard`, `ProgressCircle` |
| **E-commerce filters** | `PriceRangeField`, `TrackSlider`, `DualListboxField` |
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

### 3. Flex fields on a model (optional)

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
| **config/filament-flex-fields.php** | Default schemas, UI sizes, playground toggles |

---

## FAQ

**Do I need Node.js to use this package?**
No. Pre-built CSS/JS are committed to `resources/dist/`. Node is only needed when developing the package itself.

**How does asset loading work?**
Each component loads its own CSS/JS on demand. Shared JavaScript (emoji picker, audio UI, country/timezone menus, Mapbox, etc.) is split into cached chunks with semantic names — loaded once per page even when multiple fields use the same module. See [Performance-first assets](#performance-first-assets).

**Can I use components without the JSON flex-field system?**
Yes. Import any component directly into Filament forms — the JSON column and `HasFlexFields` trait are optional.

**How many components are included?**
**59** custom UI classes with own views and CSS — listed in [Custom components (59)](#custom-components-59). The optional JSON flex-field registry wires these components via `FlexFieldFormBuilder`.

**Does it work with Filament v4?**
No — this package targets **Filament v5** only.

**Is Spatie required?**
No. Sluggable, Translatable, and Media Library integrations are optional `composer suggest` packages.

**Where is the Matrix Choice / survey grid?**
`MatrixChoiceField` — radio or checkbox mode, per-row validation, reactive `disableCellWhen()` / `disableRowWhen()`. See [docs/index.md → MatrixChoiceField](docs/matrixchoicefield.md).

---

## Development

```bash
composer install
composer test          # Pest
composer analyse       # PHPStan

npm install
npm run build          # CSS + JS → resources/dist/
composer format        # Laravel Pint
```

Rebuild assets after changing `resources/css/` or `resources/js/`.

---

See [LICENSE](LICENSE) for license details.

<p align="center">Made with ❤️ by <a href="mailto:barek122@gmail.com">Bartłomiej Janczak</a></p>

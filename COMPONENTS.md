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

9. [FlexTextInput](#flextextinput)
10. [FlexTextareaField](#flextextareafield)
11. [SelectField](#selectfield)
12. [UserSelect](#userselect)
13. [UserColumn](#usercolumn)
14. [DualListboxField](#duallistboxfield)
15. [PriceRangeField](#pricerangefield)
16. [CreditCardField](#creditcardfield)
17. [PhoneField](#phonefield)
18. [SignatureField](#signaturefield)
19. [MapPickerField](#mappickerfield)
20. [AddressAutocompleteField](#addressautocompletefield)
21. [ChoiceCards](#choicecards)
22. [ChoiceCheckboxCards](#choicecheckboxcards)
23. [FlexChecklist](#flexchecklist)
24. [FlexRadiolist](#flexradiolist)
25. [MatrixChoiceField](#matrixchoicefield)
26. [SwitchField](#switchfield)
27. [CurrencyField](#currencyfield)
28. [CountryField](#countryfield)
29. [TimezoneField](#timezonefield)
30. [Date & time fields](#date--time-fields)
31. [FlexVerificationCode](#flexverificationcode)
32. [AudioField](#audiofield)
33. [VoiceNoteRecorderField](#voicenoterecorderfield)
34. [VideoField](#videofield)
35. [FlexFileUpload & FlexImageUpload](#flexfileupload--fleximageupload)
36. [ColorSwatchField](#colorswatchfield)
37. [FlexColorPickerField](#flexcolorpickerfield)
38. [NumberStepper](#numberstepper)
39. [FlexSlider](#flexslider)
40. [SegmentTabs](#segmenttabs)
41. [SegmentControl](#segmentcontrol)
42. [TrackSlider](#trackslider)
43. [TrafficSplit](#trafficsplit)
44. [RatingField](#ratingfield)
45. [RatingColumn](#ratingcolumn)
46. [CoverCard](#covercard)
47. [ProgressBar](#progressbar)
48. [ProgressCircle](#progresscircle)
49. [ItemCard](#itemcard)
50. [ItemCardGroup](#itemcardgroup)
51. [ItemCardStack](#itemcardstack)
52. [Layout components — quick comparison](#layout-components--quick-comparison)
53. [Form layout patterns](#form-layout-patterns)
54. [SlugField & TitleSlugField](#slugfield--titleslugfield)
55. [TranslatableFields](#translatablefields)

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

**Wszystkie 29 komponentów** przechodzą przez jeden build esbuild z `splitting: true`. Jeśli dwa pola importują ten sam moduł z `resources/js/core/` lub `resources/js/support/`, kod trafia do wspólnego chunka — ładowany raz i cache’owany w przeglądarce.

| Output | Role |
|--------|------|
| `resources/dist/components/{component}.js` | Cienki entry — fabryka Alpine `x-data` |
| `resources/dist/components/flex-fields-{name}-{hash}.js` | Współdzielone moduły (semantyczne nazwy, np. `flex-fields-emoji-*.js`) |
| `resources/dist/components/alpine-manifest.json` | Mapa komponent → chunki + `__chunk_modules__` |

#### Aktualnie współdzielone chunki (automatycznie z buildu)

| Moduły źródłowe | Komponenty | Przykład |
|-----------------|------------|----------|
| `core/shared-emoji-picker.js` | `flex-text-input`, `flex-textarea` | Emoji picker (~45 KB) |
| `core/audio-waveform.js`, `format-time.js`, `waveform-bars.js`, `audio-playback.js` | `audio-field`, `voice-note-recorder-field` | Audio UI |
| `core/dynamic-bars.js` | `price-range`, `audio-field`, `voice-note-recorder-field` | Słupki waveform / histogram |
| `support/mapbox-geocoding.js` | `map-picker`, `address-autocomplete` | Mapbox API |
| `core/searchable-select-menu.js` | `country-field`, `timezone-field`, `currency-field` | Menu select w shellu flex-text-input |
| `libphonenumber-js` (lazy `import()`) | `phone-field` | Walidacja telefonu (~190 KB, ładowane on-demand) |

Komponenty **bez** wpisów w manifeście (np. `rating-field`, `dual-listbox`) nie mają współdzielonego kodu z innymi polami — cała logika zostaje w cienkim entry (to poprawne).

Moduły używane tylko przez jedno pole (np. `core/date-time/*` → tylko `flex-date-time-field`, `nouislider` → tylko `flex-slider`) pozostają w entry, dopóki drugi komponent ich nie zaimportuje.

#### Preload

Każdy blade z `@include('...load-stylesheet', ['component' => '...'])` automatycznie robi też `modulepreload` chunków z manifestu (`FlexFieldAlpineQueue` deduplikuje między polami na stronie).

Komponenty bez własnego CSS ale z JS (np. `rating-field`) ładują chunki przez ESM `import` przy `x-load` — bez osobnego preloadu, bo manifest jest pusty.

Po zmianie JS:

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

# Part II — Components

---

## FlexTextInput

### Summary

SaaS-style **single-line** text input with pill layout, grouped action buttons, and optional toolbar features. Extends Filament `TextInput` — **all native TextInput APIs remain available**.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput` |
| **Extends** | `Filament\Forms\Components\TextInput` |
| **State type** | `string\|null` (or numeric types when using `numeric()`, etc.) |
| **FieldType** | `flex_text_input` |

Also suitable for mapped types such as `email`, `password`, `url`, `phone`, `slug`, and `search` when configured through `FlexFieldFormBuilder`.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Filament\Support\Icons\Heroicon;

FlexTextInput::make('email')
    ->label('Email')
    ->email()
    ->prefixIcon(Heroicon::Envelope)
    ->hintIcon(Heroicon::InformationCircle, 'Used for login and notifications.')
    ->live(debounce: 750)
    ->loading()
    ->default('hello@example.com');

FlexTextInput::make('password')
    ->password()
    ->revealable()
    ->copyable()
    ->passwordStrength()
    ->default('secret-password');
```

### Layout

- Pill-shaped track with inline prefix / suffix support
- Optional **action group** on the right (emoji, dictation, clear, copy, reveal, loading)
- Optional **meta row** below the track (character counter, password strength bar)
- Hint icon renders beside the label (not pushed to the far right)

Default values, character counter, and password strength are **server-rendered** in HTML so they appear immediately — Alpine enhances interactivity after `x-load`.

### Custom configuration API

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `primary` | Grey pill track. **Default.** |
| `secondary` | Lighter background. |
| `flat` | Transparent background and border. |

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

#### `emojiPicker(bool|Closure $condition = true)`

Shows an emoji picker button in the action group.

#### `emojiPickerLocale(string|Closure|null $locale)` / `emojiPickerLabel(string|Closure $label)`

Picker locale (e.g. `pl`, `en`) and accessible button label.

#### `speechDictation(bool|Closure $condition = true)`

Browser speech-to-text button. Requires `SpeechRecognition` / `webkitSpeechRecognition`.

#### `speechDictationLanguage(string|Closure|null $language)` / `speechDictationLabel(string|Closure $label)`

Override recognition language and button label.

#### `characterCounter(bool|Closure $condition = true)`

Shows a live character count below the field. Respects `maxLength()`.

#### `clearable(bool|Closure $condition = true)`

Shows a clear (×) button when the field has a value.

#### `loading(bool|Closure $condition = true)` / `validating(bool|Closure $condition = true)`

Shows a spinner while Livewire processes a request for this field's state path. Pair with `live()` or any server round-trip. **Does not perform validation by itself** — it reflects sync/network time only. `validating()` is an alias for `loading()`.

#### `verificationStatus(string|Htmlable|Closure|null $status)` / `verificationStatusIcon()` / `verificationStatusColor()`

Shows a verified-style status row **below the input shell** (under the field border), e.g. `Verified 2 Jan, 2027` with a seal-check icon. Useful for email or phone confirmation states.

| Method | Description |
|--------|-------------|
| `verificationStatus()` | Status label. Pass `null` or omit to hide the row. |
| `verificationStatusIcon()` | Icon beside the label. Default: `GravityIcon::SealCheck`. |
| `verificationStatusColor()` | Filament color token for the row. Default: `primary`. |

```php
FlexTextInput::make('email')
    ->email()
    ->prefixIcon(Heroicon::Envelope)
    ->default('hi@siennahewitt.com')
    ->verificationStatus('Verified 2 Jan, 2027');
```

#### `passwordStrength(bool|Closure $condition = true)`

Shows a strength bar below the field. **Only active when `password()` is set.** Scores 0–4 from length, mixed case, digits, and symbols.

#### `copyable(bool|Closure $condition = true, string|Closure|null $copyMessage = null, int|Closure|null $copyMessageDuration = null)`

Inherited from TextInput with the same parameters:

| Parameter | Description |
|-----------|-------------|
| `$condition` | Whether the copy button is shown. **Default: true.** |
| `$copyMessage` | Toast message after copying. Default: Filament copy message. |
| `$copyMessageDuration` | Toast duration in milliseconds. |

This component applies the **outline** Heroicon (`OutlinedClipboardDocumentList`) to the copy action.

#### `revealable(bool|Closure $condition = true)`

Inherited from TextInput. Applies **outline** Heroicons for show/hide password actions.

### Inherited TextInput API

All standard Filament `TextInput` methods work unchanged, including:

| Method | Description |
|--------|-------------|
| `email()` / `url()` / `tel()` / `numeric()` / `integer()` | Input type and validation helpers |
| `password()` | Password input type |
| `prefix()` / `suffix()` / `prefixIcon()` / `suffixIcon()` | Inline affixes |
| `mask()` | Input mask via Alpine |
| `maxLength()` / `minLength()` | Length constraints |
| `autocomplete()` / `autofocus()` | HTML attributes |
| `live()` / `debounce()` | Reactive updates |
| `datalist()` | Browser datalist support |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `variant` | `variant()` |
| `speech_dictation` | `speechDictation()` |
| `speech_dictation_language` | `speechDictationLanguage()` |
| `emoji_picker` | `emojiPicker()` |
| `emoji_picker_locale` | `emojiPickerLocale()` |
| `character_counter` | `characterCounter()` |
| `clearable` | `clearable()` |
| `loading` | `loading()` |
| `validating` | `validating()` |
| `verification_status` | `verificationStatus()` |
| `verification_status_icon` | `verificationStatusIcon()` |
| `verification_status_color` | `verificationStatusColor()` |
| `password_strength` | `passwordStrength()` |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `calculatePasswordStrength(string $password)` | `array{score: int, label: string, percent: float\|int}` | Password strength score **0–4**, human label (`Very weak` … `Strong`), and fill percent (`score / 4 × 100`). Empty password returns score `0`, empty label, percent `0`. Used by the strength bar meta row. |

---

## FlexTextareaField

### Summary

SaaS-style **multi-line** textarea with optional toolbar, autosize, emoji picker, and speech dictation. Extends Filament `Textarea`.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField` |
| **Extends** | `Filament\Forms\Components\Textarea` |
| **State type** | `string\|null` |
| **FieldType** | `flex_textarea` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

FlexTextareaField::make('message')
    ->label('Message')
    ->placeholder('Write something…')
    ->maxLength(500)
    ->characterCounter()
    ->emojiPicker()
    ->speechDictation()
    ->footer('Markdown supported')
    ->toolbarSelect(
        'model',
        ['claude-4.6-opus' => 'Claude 4.6 Opus', 'gpt-5' => 'GPT-5'],
        icon: Heroicon::CpuChip,
    )
    ->toolbarAction(
        Action::make('bold')->icon(Heroicon::Bold)->action(fn () => null),
    )
    ->submitAction(
        Action::make('send')->icon(Heroicon::PaperAirplane)->color('primary'),
    );
```

### Layout

- Rounded shell with autosizing textarea
- Optional **toolbar** row: emoji, prefix actions, toolbar selects, suffix actions (e.g. Send)
- Optional **footer** text and character counter
- Default textarea content is **server-rendered** for instant display on page load

### Custom configuration API

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `primary` | Grey track. **Default.** |
| `secondary` | Lighter background. |
| `flat` | Transparent background. |

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

#### `characterCounter(bool|Closure $condition = true)`

Live character count in the toolbar area. Respects `maxLength()`.

#### `animatedAutosize(bool|Closure $condition = true)`

Smooth height animation on resize. **Default: true.**

#### `maxHeight(string|Closure|null $height)`

CSS max-height for autosize. Default: `24rem`.

#### `footer(string|Closure|null $footer)`

Muted helper line below the textarea shell.

#### `emojiPicker(bool|Closure $condition = true)`

Shows an emoji picker button in the toolbar.

#### `emojiPickerLocale(string|Closure|null $locale)`

Emoji picker locale (e.g. `pl`, `en`). Leave unset to use the browser language.

#### `emojiPickerLabel(string|Closure $label)`

Accessible label for the emoji picker button. **Default: `Insert emoji`.**

#### `speechDictation(bool|Closure $condition = true)`

Browser speech-to-text button in the toolbar. Requires `SpeechRecognition` / `webkitSpeechRecognition`.

#### `speechDictationLanguage(string|Closure|null $language)`

Override speech recognition language. Leave unset to use the browser default locale.

#### `speechDictationLabel(string|Closure $label)`

Accessible label for the dictation button. **Default: `Speak`.**

#### `toolbarSelect(string $statePath, array|Closure $options, …)`

Pill-style dropdown in the toolbar. Binds to a **separate** form state path (not the textarea value). Supports `icon` and `placeholder`. Initial label is server-rendered to avoid layout shift.

#### `toolbarAction(Action|Closure $action)` / `toolbarActions(array $actions)`

Icon buttons in the toolbar (maps to prefix actions).

#### `submitAction(Action|Closure|null $action)`

Primary action button on the right of the toolbar (maps to suffix action). When registered via `submitAction()`, the button is **automatically disabled** while the trimmed textarea value is empty:

- **Client:** Alpine `canSubmit` binds `x-bind:disabled="!canSubmit"` on the action.
- **Server:** `isSubmitDisabled()` evaluates `blank(trim(state))` and applies Filament `->disabled()` on the action.

Whitespace-only content keeps the button disabled.

### Inherited Textarea API

Includes `rows()`, `cols()`, `autosize()`, `disableGrammarly()`, `maxLength()`, `minLength()`, `live()`, and all standard Filament `Field` methods.

Default setup calls `autosize()`, `rows(1)`, and `disableGrammarly()`.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `variant` | `variant()` |
| `character_counter` | `characterCounter()` |
| `animated_autosize` | `animatedAutosize()` |
| `max_height` | `maxHeight()` |
| `footer` | `footer()` |
| `rows` | `rows()` |
| `max_length` | `maxLength()` |
| `speech_dictation` | `speechDictation()` |
| `speech_dictation_language` | `speechDictationLanguage()` |
| `emoji_picker` | `emojiPicker()` |
| `emoji_picker_locale` | `emojiPickerLocale()` |
| `speech_dictation_label` | `speechDictationLabel()` |
| `emoji_picker_label` | `emojiPickerLabel()` |

Toolbar selects, toolbar actions, and `submitAction()` are **not** configurable via `FlexFieldFormBuilder` — use the fluent API in PHP.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getInitialHeightRem()` | `float` | Server-rendered initial textarea height in `rem` from `rows()` (formula: `max(rows × 1.5 + 0.25, 2.25)`). |
| `getToolbarSelects()` | `list<array{statePath, options, placeholder, icon, initialValue, initialLabel}>` | Resolved toolbar dropdown configs with server-rendered initial labels. |
| `isSubmitDisabled()` | `bool` | Whether the submit action should be disabled (trimmed state is blank). |

---

## SelectField

### Summary

Styled Filament **Select** with pill trigger, rich option rows, grid layout, and multi-select chips. Extends Filament `Select`.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField` |
| **Extends** | `Filament\Forms\Components\Select` |
| **State type** | `string\|int\|null` (single) · `array` (multiple) |
| **FieldType** | `select`, `multi_select` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

SelectField::make('framework')
    ->label('Framework')
    ->options([
        'laravel' => [
            'label' => 'Laravel',
            'description' => 'PHP web framework',
            'icon' => 'heroicon-o-bolt',
        ],
        'livewire' => 'Livewire',
    ])
    ->searchable()
    ->variant('bordered')
    ->size('md');

SelectField::make('tags')
    ->multiple()
    ->options(['draft' => 'Draft', 'published' => 'Published'])
    ->chipColor('primary');
```

### Custom configuration API

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `bordered` | Standard bordered pill. **Default.** |
| `flat` | Flat background. |
| `faded` | Subtle faded track. |
| `underlined` | Underline-style trigger. |

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

#### `color(string|Closure|null $color)`

Filament color token applied to the field wrapper.

#### `chipColor(string|Closure $chipColor)`

Chip colour in multi-select mode. Default: `neutral`.

#### `richOptions(bool|Closure $condition = true)`

Force rich HTML option rendering even for simple labels.

#### `optionLayout(string|Closure $layout)`

| Value | Description |
|-------|-------------|
| `list` | Icon + label + description rows. **Default.** |
| `grid` | Card grid with image/icon tiles. |

#### `inlineFieldLabel(bool|Closure $condition = true)`

Renders the field label inline beside the trigger instead of above it.

#### Implementation notes

| Method | Behaviour |
|--------|-----------|
| `usesRichOptionHtml()` | Returns `true` when `optionLayout('grid')`, `allowHtml()`, `richOptions(true)`, or any option uses the rich array shape. Drives HTML option rendering in the Blade view. |
| `hasClientSideOptionList()` | Returns `true` only for static, non-relationship option lists without dynamic disabled options or preload. When `false`, options are resolved server-side per request. |

### Inherited Select API

All Filament `Select` methods work, including:

| Method | Description |
|--------|-------------|
| `options()` | Static or dynamic option list |
| `searchable()` | Client-side or server-side search |
| `multiple()` | Multi-select with chips |
| `preload()` / `optionsLimit()` | Async option loading |
| `relationship()` | Eloquent relationship binding |
| `native()` | Native HTML select (overrides custom UI when `true`) |
| `allowHtml()` | Allow HTML in option labels |

Rich option shape: see [Rich select option shape](#rich-select-option-shape).

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `color` | `color()` |
| `chip_color` | `chipColor()` |
| `rich_options` | `richOptions()` |
| `option_layout` | `optionLayout()` |
| `searchable` | `searchable()` |
| `native` | `native()` |
| `inline_field_label` | `inlineFieldLabel()` |

---

## UserSelect

### Summary

Rich user picker extending [SelectField](#selectfield): avatar, name, email, and verified badge in dropdown and trigger. Single selection shows a compact rich trigger; multiple selection shows a names summary in the trigger plus removable avatar tags below the field.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect` |
| **State type** | `string\|int\|null` (single) or `list<string\|int>` (multiple) |
| **FieldType** | `user_select` |
| **Parent** | `SelectField` — inherits select API unless overridden below |

For **read-only user display in tables**, use [UserColumn](#usercolumn) instead.

### Basic usage

With Eloquent model search (`optionModel`):

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use App\Models\User;

UserSelect::make('assignee_id')
    ->label('Assignee')
    ->optionModel(User::class)
    ->nameColumn('name')
    ->emailColumn('email')
    ->avatarColumn('avatar_url')
    ->verificationColumn('email_verified_at')
    ->searchable()
    ->required();
```

With a relationship:

```php
UserSelect::make('team_member_ids')
    ->label('Team members')
    ->relationship('members', 'name')
    ->emailColumn('email')
    ->multiple()
    ->searchable();
```

Static rich options (no database):

```php
UserSelect::make('reviewer_id')
    ->options([
        1 => [
            'label' => 'Jane Doe',
            'description' => 'jane@example.com',
            'image' => '/avatars/jane.jpg',
            'verified' => true,
        ],
    ]);
```

Custom resolvers (aligned with [UserColumn](#usercolumn)):

```php
UserSelect::make('owner_id')
    ->optionModel(User::class)
    ->getAvatarUrlUsing(fn (User $record): ?string => $record->getFilamentAvatarUrl())
    ->getNameUsing(fn (User $record): string => $record->name)
    ->isVerifiedUsing(fn (User $record): bool => $record->hasVerifiedEmail());
```

### Display behaviour

| Mode | Trigger | Dropdown |
|------|---------|----------|
| **Single** | Avatar + name + email + verified badge | Rich option rows; selected user hidden from list |
| **Multiple (1 user)** | Same as single | Same as single |
| **Multiple (2+ users)** | Truncated comma-separated names (`+N` when overflow) | Rich option rows; removable avatar tags rendered below the field |

Options for `optionModel()` are loaded **lazily** — default suggestions and search results are fetched when the dropdown opens or when the user searches, not on initial page render.

### State format

Same as SelectField: scalar ID for single mode, array of IDs for `multiple()`. Values must match model primary keys when using `optionModel()` or `relationship()`.

### Validation

Inherits SelectField / Filament Select validation (`required`, etc.). Option keys must exist in search results or static `options()`.

### Avatar resolution order

When resolving a user record (trigger, tags, or search results):

1. `getAvatarUrlUsing()` callback, if set
2. `avatarColumn()` value on the model
3. If neither yields a URL, initials are shown on a gradient surface

Use `getAvatarUrlUsing(fn ($record) => $record->getFilamentAvatarUrl())` to integrate with Filament's user avatar convention.

### Configuration API (UserSelect-specific)

#### `optionModel(string|Closure $model)`

Eloquent model class for async search. Enables `searchable()`, `getSearchResultsUsing`, and lazy option loading automatically.

#### `query(Closure $query)`

Modify the base query. Receives `['query' => Builder]`.

```php
->query(fn (Builder $query) => $query->where('active', true))
```

#### `relationship(...)`

Overrides parent to wire `recordToOptionArray()` for rich labels. Optional `$titleAttribute` maps to `nameColumn()`.

#### `nameColumn(string|Closure $column)`

Display name attribute. Default: `name`.

#### `emailColumn(string|Closure|null $column)`

Subtitle / description column. Default: `null`.

#### `avatarColumn(string|Closure|null $column)`

Image URL column. Default: `null`.

#### `verificationColumn(string|Closure|null $column)`

Column for verified badge (`filled()` or bool). Default: `null`.

#### `getAvatarUrlUsing(?Closure $callback)`

Custom avatar resolver. Receives `['record' => Model]`.

#### `getNameUsing(?Closure $callback)`

Custom name resolver.

#### `getEmailUsing(?Closure $callback)`

Custom email resolver.

#### `isVerifiedUsing(?Closure $callback)`

Custom verified-state resolver.

#### `minSearchLength(int|Closure $length)`

Minimum characters before server search runs. Default: `2`. Set to `0` to search on any non-empty query.

#### `defaultSuggestionsLimit(int|Closure $limit)`

Number of users shown when the dropdown opens without a search query. Default: `5`.

#### `applySearchUsing(?Closure $callback)`

Replace the built-in prefix `LIKE` search. Receives `['query' => Builder, 'search' => string]`. Use for Scout, full-text search, or custom ranking.

#### `maxVisibleAvatars(int|Closure $limit)`

Accepted for FlexField schema compatibility. **Not used** by the UserSelect form UI (multiple mode uses names + tags). Use [UserColumn](#usercolumn) `maxVisibleAvatars()` for stacked avatar display in tables.

### Inherited SelectField API

All [SelectField](#selectfield) methods apply: `multiple()`, `searchable()`, `options()`, `variant()`, `size()`, `richOptions()`, `native()`, `placeholder()`, `preload()` (with `relationship()`), etc.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getUserModel()` | `string\|null` | Model class from `optionModel()` |
| `getNameColumn()` | `string` | Name attribute |
| `getEmailColumn()` | `string\|null` | Email attribute |
| `getAvatarColumn()` | `string\|null` | Avatar attribute |
| `getVerificationColumn()` | `string\|null` | Verification attribute |
| `getMinSearchLength()` | `int` | Minimum search query length |
| `getDefaultSuggestionsLimit()` | `int` | Default suggestion count on open |
| `shouldRenderMultipleUserTags()` | `bool` | `true` when `multiple()` |
| `renderUserOption(array $option, string $layout)` | `string` | HTML for `list`, `trigger`, or `tag` layout |
| `recordToOptionArray(Model $record)` | `array` | Normalized option shape |
| `resolveOptionLabelsForValues(array $values)` | `array` | Labels per value |
| `searchRecords(?string $search)` | `array` | Model search results |
| `getUserSelectInitials(string $name)` | `string` | Initials fallback |
| `getInitialTriggerLabel()` | `string\|null` | Rich trigger HTML (single) |
| `getInitialMultipleTriggerHtml()` | `string\|null` | SSR trigger HTML (multiple) |
| `getInitialSelectedUserTagsHtml()` | `string\|null` | SSR tag row HTML (multiple, 2+ users) |
| `getInitialSelectedUserEntriesForJs()` | `list<array>` | Hydration payload for client display |
| `getInitialOptionsForJs()` | `list<array>` | Empty on page load for `optionModel()` (lazy) |
| `getOptionsForJs()` | `list<array>` | Full options for Livewire fetches |

### FlexField schema config

Inherits SelectField config keys plus:

| Config key | Maps to |
|------------|---------|
| `option_model` / `model` | `optionModel()` |
| `name_column` | `nameColumn()` |
| `email_column` | `emailColumn()` |
| `avatar_column` | `avatarColumn()` |
| `verification_column` | `verificationColumn()` |
| `max_visible_avatars` | `maxVisibleAvatars()` (schema only; see note above) |
| `multiple` | `multiple()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-user-select` | Root (with SelectField classes) |
| `fff-user-select--single` / `--multiple` | Mode modifier |
| `fff-user-select-option--list` | Dropdown option row |
| `fff-user-select-option--trigger` | Single / one-user trigger |
| `fff-user-select-option--tag` | Multi-select tag chip |
| `fff-user-select__trigger-names` | Comma-separated names (multiple, 2+ users) |
| `fff-user-select__selected-tags` | Tag container below field |
| `fff-user-select__selected-tag` | Individual removable tag |
| `fff-user-select__avatar` | Avatar wrapper |
| `fff-user-select__verified-badge` | Verified seal icon |
| `fff-select-field--rich-list-trigger` | Rich trigger layout |

### Implementation notes

- `richOptions()` and `allowHtml()` are enabled in `setUp()`.
- User option shape: `label`, `description`, `image`, `verified` (optional `disabled`).
- Client-side rendering uses lean JSON (`user` payload) with virtual scroll for lists larger than 30 options.
- Search uses prefix `LIKE` on name and email by default, with relevance ordering and request-level caching.

---

## UserColumn

### Summary

Read-only **table column** for displaying users with the same visual language as [UserSelect](#userselect). Automatically picks the layout based on how many users are in the cell state:

| Users in state | Display |
|----------------|---------|
| **0** | Empty cell |
| **1** | Rich row: avatar + name + email + verified badge |
| **2+** | Overlapping circular avatar stack with `+N` overflow badge |

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Tables\Columns\UserColumn` |
| **Context** | Filament tables (`$table->columns([...])`) |
| **State type** | `Model`, `Collection` of models, or `list<Model>` |
| **Parent** | `Filament\Tables\Columns\TextColumn` |

Shares user field configuration with UserSelect via the `ResolvesUserDisplay` concern (`nameColumn`, `emailColumn`, `avatarColumn`, custom resolvers).

### Basic usage

Single user (BelongsTo / HasOne):

```php
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\UserColumn;

UserColumn::make('author')
    ->label('Author')
    ->nameColumn('name')
    ->emailColumn('email')
    ->avatarColumn('avatar_url')
    ->verificationColumn('email_verified_at');
```

Multiple users (BelongsToMany / HasMany collection):

```php
UserColumn::make('members')
    ->label('Members')
    ->maxVisibleAvatars(4)
    ->stackedRing(2)
    ->stackedOverlap(10);
```

Filament resolves `$record->author` or `$record->members` as column state when the relationship is eager-loaded. Ensure the relation is loaded in the table query to avoid N+1 queries.

Custom avatar via Filament convention:

```php
UserColumn::make('owner')
    ->getAvatarUrlUsing(fn (User $record): ?string => $record->getFilamentAvatarUrl());
```

### Avatar resolution order

1. `getAvatarUrlUsing()` callback, if set
2. `getFilamentAvatarUrl()` on the model, when the method exists
3. `avatarColumn()` value on the model
4. Initials on a gradient surface when no URL is available

### Configuration API (UserColumn-specific)

#### `maxVisibleAvatars(int|Closure $limit)`

Maximum avatars shown in stack mode before the `+N` overflow badge. Default: `4`, minimum `1`.

#### `stackedRing(int|Closure $ring)`

White ring width (px) around each stacked avatar. Default: `2`.

#### `stackedOverlap(int|Closure $overlap)`

Horizontal overlap (px) between stacked avatars. Default: `10`.

#### `stackTooltips(bool|Closure $condition = true)`

Show each user's name in a native `title` tooltip on stack avatars. Default: `true`.

### Shared user display API

These methods are identical to [UserSelect](#userselect):

| Method | Description |
|--------|-------------|
| `nameColumn(string\|Closure $column)` | Name attribute. Default: `name` |
| `emailColumn(string\|Closure\|null $column)` | Email / subtitle column |
| `avatarColumn(string\|Closure\|null $column)` | Avatar URL column |
| `verificationColumn(string\|Closure\|null $column)` | Verified badge column |
| `getAvatarUrlUsing(?Closure $callback)` | Custom avatar resolver |
| `getNameUsing(?Closure $callback)` | Custom name resolver |
| `getEmailUsing(?Closure $callback)` | Custom email resolver |
| `isVerifiedUsing(?Closure $callback)` | Custom verified resolver |

### Inherited TextColumn API

All Filament `TextColumn` methods apply: `label()`, `sortable()`, `searchable()`, `toggleable()`, `alignStart()` / `alignCenter()`, `url()`, `tooltip()`, etc. The column uses `html()` internally — do not call `html(false)` unless you override `formatStateUsing()`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `formatUserDisplay(mixed $state)` | `string` | Rendered HTML for a state value |
| `normalizeUsersFromState(mixed $state)` | `list<array>` | Normalized user display arrays |
| `recordToDisplayArray(Model $record)` | `array` | `label`, `description`, `image`, `verified`, `initials` |
| `renderRichUser(array $user)` | `string` | Single-user rich HTML |
| `renderAvatarStack(array $users)` | `string` | Multi-user stack HTML |
| `getMaxVisibleAvatars()` | `int` | Stack visibility limit |
| `getStackedRing()` | `int` | Stack ring width (px) |
| `getStackedOverlap()` | `int` | Stack overlap (px) |
| `shouldShowStackTooltips()` | `bool` | Whether stack items show name tooltips |
| `resolveUserDisplayInitials(string $name)` | `string` | Initials fallback |

### CSS classes

| Class | Role |
|-------|------|
| `fff-user-column` | Cell wrapper |
| `fff-user-column--rich` | Single-user layout |
| `fff-user-column--stacked` | Multi-user stack layout |
| `fff-user-column__avatar-stack` | Overlapping avatar row |
| `fff-user-column__avatar-stack-item` | Individual stack avatar |
| `fff-user-column__avatar-stack-overflow` | `+N` overflow badge |
| `fff-user-select__avatar--stack` | Stack avatar size modifier |

### Implementation notes

- Requires package **core CSS** (`flex-fields-core` / `resources/dist/css/core.css`) — table column styles are always included there.
- Stack mode hides the verified badge on individual avatars (names are available via tooltip when `stackTooltips()` is enabled).
- Only Eloquent `Model` instances in state are rendered; scalar IDs are not resolved automatically — eager-load the relationship or use a custom `getStateUsing()` that returns models.

---

## DualListboxField

### Summary

Two-panel **multi-select** listbox with search, transfer buttons, drag reorder, and double-click to move items.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\DualListboxField` |
| **State type** | `list<string>` — ordered selected option keys |
| **Model cast** | `'permissions' => 'array'` or `'json'` |
| **FieldType** | `dual_listbox` |

Example state: `['read', 'write', 'delete']`.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\DualListboxField;

DualListboxField::make('permissions')
    ->label('Permissions')
    ->options([
        'read' => ['label' => 'Read', 'description' => 'View records'],
        'write' => 'Write',
        'delete' => 'Delete',
    ])
    ->minItems(1)
    ->maxItems(5)
    ->searchable()
    ->reorderable()
    ->listHeight('16rem')
    ->variant('bordered');
```

### Validation

| Rule / method | Description |
|---------------|-------------|
| `array` | Built-in — state must be an array |
| Option keys | Unknown keys fail with `dual_listbox.invalid_option` |
| `minItems(n)` | Minimum selected items |
| `maxItems(n)` | Maximum selected items |
| `exactItems(n)` | Exactly `n` items (sets both min and max) |

Translation keys: `filament-flex-fields::default.validation.dual_listbox.*`

### Configuration API

#### `options(array|Closure $options)`

See [Dual listbox option shape](#dual-listbox-option-shape).

#### `disabledOptions(array|Closure $keys)`

Disable options by key.

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `bordered` | Bordered panels. **Default.** |
| `flat` | Flat panels. |
| `faded` | Subtle faded styling. |

#### `listHeight(string|Closure $height)`

CSS height for each list panel. Default: `16rem`.

#### `searchable(bool|Closure $condition = true)`

Filter boxes above each panel. **Default: true.**

#### `reorderable(bool|Closure $condition = true)`

Drag to reorder selected items. **Default: true.**

#### `moveOnDoubleClick(bool|Closure $condition = true)`

Double-click to transfer items between panels. **Default: true.**

#### `showTransferButtons(bool|Closure $condition = true)`

Show ← / → transfer buttons between panels. **Default: true.**

#### `availableLabel(string|Closure|null $label)` / `selectedLabel(string|Closure|null $label)`

Panel headings. Defaults from translation files.

#### `minItems(int|Closure|null $count)` / `maxItems(int|Closure|null $count)` / `exactItems(int|Closure|null $count)`

Selection count constraints.

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `list_height` | `listHeight()` |
| `searchable` | `searchable()` |
| `reorderable` | `reorderable()` |
| `move_on_double_click` | `moveOnDoubleClick()` |
| `show_transfer_buttons` | `showTransferButtons()` |
| `available_label` | `availableLabel()` |
| `selected_label` | `selectedLabel()` |
| `disabled_options` | `disabledOptions()` |
| `min_items` | `minItems()` |
| `max_items` | `maxItems()` |
| `exact_items` | `exactItems()` |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `normalizeState(array $state)` | `list<string>` | Filters state to allowed, non-disabled option keys; preserves order and deduplicates. |
| `getNormalizedOptions()` | `array<string, array{label, description, disabled}>` | Flattened option map from simple strings or rich arrays plus `disabledOptions()`. |
| `getOptionsForJs()` | `list<array{value, label, description, disabled}>` | Option list shape passed to the Alpine/JS layer. |

---

## PriceRangeField

### Summary

Dual-handle **price range** slider with histogram backdrop, min/max numeric inputs, and currency prefix.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField` |
| **State type** | `array{min: int|float, max: int|float}` |
| **Model cast** | `'price_range' => 'array'` or `'json'` |
| **FieldType** | `price_range` |

Example state: `['min' => 100, 'max' => 1124]`.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField;

PriceRangeField::make('price_range')
    ->label('Price range')
    ->min(0)
    ->max(5000)
    ->step(1)
    ->prefix('$')
    ->histogram([30, 74, 85, 36, 98])
    ->showInputs()
    ->variant('bordered')
    ->default(['min' => 100, 'max' => 1124]);
```

### Validation

Built-in rules via custom validator:

| Failure | Translation key |
|---------|-----------------|
| Non-numeric min/max | `price_range.invalid` |
| Values outside bounds | `price_range.out_of_bounds` |
| min > max | `price_range.min_greater_than_max` |

### Configuration API

#### `min(int|float|Closure $min = 0)` / `max(int|float|Closure $max = 1000)`

Range boundaries for slider and inputs.

#### `step(int|float|Closure $step = 1)`

Snap increment.

#### `integer(bool|Closure $condition = true)`

Restrict to whole numbers. **Default: true.**

#### `decimalPlaces(int|Closure|null $places)`

Fixed decimal precision when not integer.

#### `prefix(string|Closure|null $prefix)` / `withoutPrefix()`

Currency or unit prefix shown in inputs. Default config: `$`.

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `bordered` | Bordered track. **Default.** |
| `flat` | Flat styling. |
| `faded` | Subtle faded track. |

#### `showInputs(bool|Closure $condition = true)`

Min/max numeric inputs below the slider. **Default: true.**

#### `minInputLabel(string|Closure|null $label)` / `maxInputLabel(string|Closure|null $label)`

Accessible labels for the numeric inputs.

#### `histogram(array|Closure $heights)`

Bar heights (8–100) for the background chart. Uses a built-in default pattern when omitted.

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

### State normalization

On hydrate and dehydrate, values are clamped to `[min, max]`, stepped, and `min` is never greater than `max`.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `min` | `min()` |
| `max` | `max()` |
| `step` | `step()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `prefix` | `prefix()` |
| `histogram` | `histogram()` |
| `integer` | `integer()` |
| `decimal_places` | `decimalPlaces()` |
| `show_inputs` | `showInputs()` |
| `min_input_label` | `minInputLabel()` |
| `max_input_label` | `maxInputLabel()` |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `normalizeState(array $state)` | `array{min, max}` | Clamps min/max to bounds, applies step rounding, ensures `min ≤ max`. |
| `defaultHistogram()` | `list<float>` | Built-in 32-bar histogram heights (8–100) used when `histogram()` is empty. |
| `hasPrefix()` | `bool` | Whether a currency/unit prefix is configured (not `withoutPrefix()`). |

---

## CreditCardField

### Summary

Interactive **credit card** form rendered as a flippable card UI with number formatting, brand detection, and expiry validation.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField` |
| **State type** | `array{number: string, name: string, expiry: string, cvv: string}` |
| **Model cast** | `'card' => 'array'` or `'json'` |
| **FieldType** | `credit_card` |

Example state:

```php
[
    'number' => '4242424242424242',
    'name' => 'Jane Doe',
    'expiry' => '12/28',
    'cvv' => '123',
]
```

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField;

CreditCardField::make('card')
    ->label('Payment method')
    ->variant('midnight')
    ->flipOnCvvFocus()
    ->required();
```

### Validation

Built-in custom rule validates when values are present:

| Check | Translation key |
|-------|-----------------|
| Number length 13–19 digits | `credit_card.invalid_number` |
| Expiry format `MM/YY` | `credit_card.invalid_expiry` |
| Expiry not in the past | `credit_card.expired` |
| CVV 3–4 digits | `credit_card.invalid_cvv` |

When `required()`, all four sub-fields must be filled.

`getRequiredValidationRule()` returns `'nullable'` — the field-level Laravel rule stays nullable even when `required()` is set. Required validation is enforced inside the component's custom rule, which checks that all four sub-fields (`number`, `name`, `expiry`, `cvv`) are filled.

### Configuration API

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `midnight` | Dark blue gradient. **Default.** |
| `ocean` | Blue-teal gradient. |
| `sunset` | Warm orange gradient. |
| `slate` | Neutral grey gradient. |

#### `flipOnCvvFocus(bool|Closure $condition = true)`

Flip the card to show the CVV field on the back when CVV is focused. **Default: true.**

#### `numberLabel()` / `nameLabel()` / `expiryLabel()` / `cvvLabel(string|Closure|null $label)`

Override sub-field labels. Defaults from translation files.

#### `mark(string|Closure|null $mark)`

Card brand mark text in the corner (e.g. `VISA`).

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

### State normalization

On hydrate and dehydrate:

- `number` and `cvv` — digits only (max 19 / 4)
- `expiry` — formatted as `MM/YY`
- `name` — trimmed string

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `variant` | `variant()` |
| `flip_on_cvv_focus` | `flipOnCvvFocus()` |
| `number_label` | `numberLabel()` |
| `name_label` | `nameLabel()` |
| `expiry_label` | `expiryLabel()` |
| `cvv_label` | `cvvLabel()` |
| `mark` | `mark()` |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `normalizeState(array $state)` | `array{number, name, expiry, cvv}` | Strips non-digits from `number`/`cvv`, formats `expiry` as `MM/YY`, trims `name`. |
| `getExpiryValidationMessage(string $expiry)` | `string\|null` | Translation key message for invalid or expired `MM/YY` values; `null` when valid or empty. |

---

## PhoneField

### Summary

International phone input with searchable country picker, libphonenumber validation, and structured state (`country`, `national`, `e164`).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField` |
| **State type** | `array{country: string, national: string, e164: string}` |
| **FieldType** | `phone` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;

PhoneField::make('phone')
    ->label('Mobile number')
    ->defaultCountry('PL')
    ->required();

PhoneField::make('contact_phone')
    ->countries(['PL', 'DE', 'GB', 'US'])
    ->mobileOnly()
    ->browserLocaleDefault()
    ->browserLocaleSortFirst();
```

Load from an E.164 string on edit:

```php
PhoneField::make('phone')
    ->afterStateHydrated(function (PhoneField $component, mixed $state): void {
        if (is_string($state) && filled($state)) {
            $component->state($component->normalizeState($state));
        }
    });
```

Store only E.164 in the database:

```php
PhoneField::make('phone')
    ->dehydrateStateUsing(fn (array $state): ?string => filled($state['e164'] ?? null)
        ? $state['e164']
        : null);
```

### State format

| Key | Description | Example |
|-----|-------------|---------|
| `country` | ISO 3166-1 alpha-2 region code | `PL` |
| `national` | National number digits only | `512345678` |
| `e164` | E.164 format when valid | `+48512345678` |

On hydrate and dehydrate, `normalizeState()` runs automatically. A plain `string` state (e.g. `+48 512 345 678`) is parsed on hydrate.

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | Custom rule on normalized array; uses libphonenumber |
| `required()` | `national` must not be empty |
| `mobileOnly()` | Number type must be mobile (or fixed-line-or-mobile) |
| `fixedLineOnly()` | Number type must be fixed line (or fixed-line-or-mobile) |
| Filament `required` rule | Overridden to `nullable` — validation handled by custom rule |

> Do not combine `mobileOnly()` and `fixedLineOnly()` on the same field — throws `InvalidArgumentException`.

### Configuration API

#### `variant(string|Closure $variant)`

Visual style shared with FlexTextInput. Values: `primary` (default), `secondary`, `flat`.

```php
->variant('secondary')
```

#### `size(string|ControlSize|Closure $size)`

Control height. See [Control size](#control-size). Default: `md`.

#### `defaultCountry(string|Closure $countryCode)`

ISO country code when no country is selected. Default: `PL`. Falls back to first allowed country or `US`.

#### `countries(array|Closure|null $countries)`

Whitelist of ISO codes. `null` = all countries (minus `exceptCountries`).

```php
->countries(['PL', 'DE', 'FR'])
```

#### `exceptCountries(array|Closure $countries)`

Blacklist applied after the whitelist. Default: `[]`.

#### `searchable(bool|Closure $condition = true)`

Show search input in the country dropdown. Default: `true`.

#### `suffixIcon(string|BackedEnum|Htmlable|Closure|bool|null $icon = null)`

Trailing icon. Pass `false` to hide. Pass an icon name to set a custom icon. Default: config `filament-flex-fields.ui.phone_suffix_icon` or `GravityIcon::Smartphone`.

```php
->suffixIcon(false)
->suffixIcon('heroicon-o-device-phone-mobile')
```

#### `internationalPrefix(bool|Closure $condition = true)`

Show dial code prefix next to the national input. Default: `true`.

#### `mobileOnly(bool|Closure $condition = true)`

Restrict to mobile numbers.

#### `fixedLineOnly(bool|Closure $condition = true)`

Restrict to fixed-line numbers.

#### `browserLocaleDefault(bool|Closure $condition = true)`

When enabled and national number is empty, pre-select country from `Accept-Language` / browser locale.

#### `browserLocaleSortFirst(bool|Closure $condition = true)`

Sort country list with browser locale country first.

#### `placeholder(string|Closure|null $placeholder)`

Inherited from Filament `HasPlaceholder`.

#### `readOnly(bool|Closure $condition = true)`

Inherited from Filament `CanBeReadOnly`.

#### `focusOutline(bool|Closure $condition = true)`

Inherited from `HasFieldFocusOutline`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved variant |
| `getAllowedCountryCodes()` | `list<string>\|null` | Whitelist or `null` |
| `getExceptCountryCodes()` | `list<string>` | Blacklist |
| `getDefaultCountryCode()` | `string` | Effective default region |
| `isSearchable()` | `bool` | Country search enabled |
| `hasSuffixIcon()` | `bool` | Suffix icon visible |
| `showsInternationalPrefix()` | `bool` | Dial prefix visible |
| `isMobileOnly()` | `bool` | Mobile validation |
| `isFixedLineOnly()` | `bool` | Fixed-line validation |
| `shouldUseBrowserLocaleDefault()` | `bool` | Browser locale default |
| `shouldSortCountriesByBrowserLocale()` | `bool` | Browser locale sort |
| `getBrowserLocaleCountryCode()` | `string\|null` | Detected locale country |
| `getCountriesMetadata()` | `list<array>` | `code`, `name`, `dial_code`, `flag_url` |
| `getCountrySelectOptions()` | `array` | Options for internal select |
| `getDefaultSuffixIcon()` | `string\|BackedEnum\|Htmlable` | Default suffix icon |
| `getSuffixIcon()` | `string\|BackedEnum\|Htmlable\|null` | Resolved suffix icon |
| `normalizeState(mixed $state)` | `array` | Canonical `{country, national, e164}` |
| `getPhoneValidationMessage(array $state)` | `string\|null` | Error message or `null` |
| `getWrapperClasses()` | `list<string>` | CSS class list |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `variant` | `variant()` |
| `default_country` | `defaultCountry()` |
| `countries` | `countries()` |
| `except_countries` | `exceptCountries()` |
| `searchable` | `searchable()` |
| `suffix_icon` | `suffixIcon()` |
| `international_prefix` | `internationalPrefix()` |
| `mobile_only` | `mobileOnly()` |
| `fixed_line_only` | `fixedLineOnly()` |
| `browser_locale_default` | `browserLocaleDefault()` |
| `browser_locale_sort_first` | `browserLocaleSortFirst()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-phone-field` | Root wrapper |
| `fff-phone-field--{sm\|md\|lg}` | Size modifier |
| `fff-phone-field__country-trigger` | Country picker button |
| `fff-phone-field__country-menu` | Teleported dropdown (`is-positioned` when open) |
| `fff-phone-field__control` | National number input area |
| `fff-phone-field__dial-prefix` | International prefix display |

Shares FlexTextInput shell classes (`fff-flex-text-input-field`, variant modifiers).

### Implementation notes

- Country dropdown uses `x-teleport="body"` to avoid overflow clipping.
- Depends on `giggsey/libphonenumber-for-php` for parsing and validation.
- Empty national number dehydrates to `e164: ''` regardless of partial dial prefix.

---

## SignatureField

### Summary

Canvas signature pad storing normalized SVG markup. Supports undo, fullscreen, optional download (SVG/WebP), and stroke validation.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField` |
| **State type** | `string\|null` — normalized SVG document |
| **FieldType** | `signature` |
| **Storage constants** | `STORE_SVG = 'svg'` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;

SignatureField::make('signature')
    ->label('Sign here')
    ->penColor('#18181b')
    ->backgroundColor('#ffffff')
    ->required();

SignatureField::make('approval_signature')
    ->fullscreen()
    ->undoable()
    ->downloadable(SignatureField::DOWNLOAD_WEBP)
    ->downloadFilename('approval')
    ->maxSizeKb(64)
    ->minStrokes(2);
```

Read-only preview of an existing signature:

```php
SignatureField::make('signed_at')
    ->default($record->signature_svg)
    ->readOnly();
```

### State format

State is a compact SVG string produced by `SignatureSvg::normalize()`. `null` or empty string means no signature.

Use `normalizeState(mixed $state): ?string` to sanitize external SVG before setting state.

### Validation

| Check | Detail |
|-------|--------|
| `required()` | State must not be empty |
| Format | Must pass `SignatureSvg::isValid()` |
| Size | Byte size ≤ `maxSizeKb()` × 1024 |
| Strokes | Path count ≥ `minStrokes()` |

### Configuration API

#### `penColor(string|Closure $color)`

Hex pen color. Default: `#18181b`. Must match `#RGB` or `#RRGGBB`.

#### `penWidth(float|Closure $width)`

Stroke width in SVG units. Clamped to `0.5`–`12`. Default: `2.5`.

#### `backgroundColor(string|Closure|null $color)`

Canvas background hex, or `null` / `'transparent'` for transparent. Default: `#ffffff`.

#### `fullscreen(bool|Closure $condition = true)`

Enable fullscreen drawing mode. Default: `true`.

#### `undoable(bool|Closure $condition = true)`

Show undo control. Default: `true`.

#### `maxSizeKb(int|Closure $kilobytes)`

Maximum stored SVG size in kilobytes. Default: `48`.

#### `minStrokes(int|Closure $strokes)`

Minimum number of SVG paths required. Default: `1`.

#### `viewBox(int|Closure $width, int|Closure $height)`

SVG viewBox dimensions. Defaults from `SignatureSvg::VIEWBOX_WIDTH` / `VIEWBOX_HEIGHT`.

#### `smoothing(bool|Closure $condition = true)`

Bezier smoothing on strokes. Default: `true`.

#### `trackpadGlide(bool|Closure $condition = true)`

Hold modifier key to draw with trackpad without clicking. Default: `false`.

#### `trackpadGlideKey(string|Closure $key)`

Single letter `a`–`z` for glide modifier. Default: `d`.

#### `guidelines(bool|Closure $condition = true)`

Show baseline guidelines on canvas. Default: `false`.

#### `downloadable(string|Closure|null $format = 'svg')`

Enable client-side download. Formats: `SignatureField::DOWNLOAD_SVG` or `SignatureField::DOWNLOAD_WEBP`. Pass `null` to disable.

#### `downloadFilename(string|Closure $filename)`

Download file base name without extension. Default: `signature`.

#### `webpQuality(float|Closure $quality)`

WebP export quality `0.1`–`1`. Default: `0.9`.

#### `undoIcon()` / `clearIcon()` / `downloadIcon()` / `fullscreenIcon()` / `closeIcon()`

Override toolbar icons (`string|BackedEnum|Htmlable|Closure|null`).

#### `readOnly(bool|Closure $condition = true)`

Disable drawing; display existing SVG only.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getPenColor()` | `string` | Lowercase hex |
| `getPenWidth()` | `float` | Clamped width |
| `getBackgroundColor()` | `string\|null` | Background hex or `null` |
| `isFullscreenEnabled()` | `bool` | Fullscreen available |
| `isUndoable()` | `bool` | Undo enabled |
| `getMaxSizeKb()` | `int` | Size limit |
| `getMinStrokes()` | `int` | Minimum paths |
| `getViewBoxWidth()` / `getViewBoxHeight()` | `int` | ViewBox size |
| `isSmoothingEnabled()` | `bool` | Smoothing on |
| `isTrackpadGlideEnabled()` | `bool` | Trackpad glide on |
| `getTrackpadGlideKey()` | `string` | Modifier key |
| `isGuidelinesEnabled()` | `bool` | Guidelines visible |
| `getDownloadFormat()` | `string\|null` | `svg`, `webp`, or `null` |
| `getDownloadFilename()` | `string` | Download base name |
| `getWebpQuality()` | `float` | WebP quality |
| `getUndoIcon()` etc. | `string\|BackedEnum\|Htmlable` | Resolved icons |
| `normalizeState(mixed $state)` | `string\|null` | Sanitized SVG |
| `getWrapperClasses()` | `array<string, bool>` | `fff-signature-field` |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `pen_color` | `penColor()` |
| `pen_width` | `penWidth()` |
| `background_color` | `backgroundColor()` |
| `fullscreen` | `fullscreen()` |
| `undoable` | `undoable()` |
| `max_size_kb` | `maxSizeKb()` |
| `min_strokes` | `minStrokes()` |
| `smoothing` | `smoothing()` |
| `download_format` | `downloadable()` |
| `download_filename` | `downloadFilename()` |
| `webp_quality` | `webpQuality()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-signature-field` | Root wrapper |
| `fff-signature-field__canvas` | Drawing surface |
| `fff-signature-field__toolbar` | Action buttons |

### Implementation notes

- Store SVG in `text` or `longText` columns; consider `maxSizeKb()` for DB limits.
- WebP download is generated client-side from the canvas — not stored in form state.

---

## MapPickerField

### Summary

Mapbox-powered address picker with geocoding search, draggable pin, and configurable stored fields.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField` |
| **State type (internal)** | Canonical array with keys from `ALL_FIELDS` |
| **Dehydrated state** | Subset of fields per `fields()`, or formatted string per `storeFormat()` |
| **FieldType** | `map_picker` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MapPickerField;

MapPickerField::make('location')
    ->label('Office address')
    ->fields(['lat', 'lng', 'street', 'city', 'postcode', 'country', 'place_name'])
    ->requiredFields(['lat', 'lng', 'city'])
    ->streetAddressesOnly()
    ->defaultCenter([52.2297, 21.0122])
    ->defaultZoom(12)
    ->countries(['PL', 'DE']);

MapPickerField::make('venue')
    ->storeFormat(MapPickerField::STORE_STRING)
    ->stringFormat('{street}, {city} ({country})');
```

Requires Mapbox token in `config('filament-flex-fields.mapbox.access_token')` or per-field `mapboxToken()`.

### State format

**Canonical internal keys** (`ALL_FIELDS`):

`lat`, `lng`, `street`, `city`, `region`, `postcode`, `country`, `country_name`, `place_name`

| `storeFormat` | Dehydrated value |
|---------------|------------------|
| `structured` (default) | Array with only keys from `fields()` |
| `string` | Single string from `stringFormat()` placeholders `{field}` |

On hydrate, strings set `place_name`; arrays merge into canonical state. Coordinates rounded to 7 decimal places.

### Validation

| Behaviour | Detail |
|-----------|--------|
| `required()` | At least one configured field must have a value |
| `requiredFields()` | Listed fields must be filled when any value present |
| Latitude | Must be −90…90 when `lat` is in `fields()` |
| Longitude | Must be −180…180 when `lng` is in `fields()` |
| `countries()` | `country` must be in whitelist when set |
| `streetAddressesOnly()` | `street` must be filled; cities, regions, and other non-address results are rejected |

### Configuration API

#### `fields(array|Closure $fields)`

Which address parts to store and show. Must include at least one valid key from `ALL_FIELDS`. Default: `lat`, `lng`, `street`, `city`, `postcode`, `country`, `place_name`.

#### `storeFormat(string|Closure $format)`

`MapPickerField::STORE_STRUCTURED` or `MapPickerField::STORE_STRING`.

#### `stringFormat(string|Closure $format)`

Template for string storage. Placeholders: `{lat}`, `{lng}`, `{street}`, etc. Default: `{place_name}`.

#### `requiredFields(array|Closure $fields)`

Fields required when the location is partially filled. Intersected with `fields()`.

#### `mapboxToken(string|Closure|null $token)`

Override Mapbox access token. Falls back to config.

#### `defaultCenter(array|Closure $center)`

`[latitude, longitude]`. Default: Warsaw `[52.2297, 21.0122]`.

#### `defaultZoom(int|Closure $zoom)`

Initial map zoom `1`–`22`. Default: `12`.

#### `searchable(bool|Closure $condition = true)`

Address search box. Default: `true`.

#### `countries(array|Closure|null $countries)`

Restrict geocoding results to ISO country codes. `null` = worldwide.

#### `streetAddressesOnly(bool|Closure $condition = true)`

Restrict search, map clicks, and pin drags to **full street addresses** only. Uses Mapbox `types=address`, filters autocomplete results client-side, and validates that `street` is present. Cities, regions, postcodes alone, and other area-level results cannot be selected. Default: `false`.

#### `readOnly(bool|Closure $condition = true)`

Disable map interaction.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getFields()` | `list<string>` | Configured field keys |
| `getStoreFormat()` | `string` | `structured` or `string` |
| `getStringFormat()` | `string` | String template |
| `getRequiredFields()` | `list<string>` | Required subset |
| `getMapboxToken()` | `string\|null` | Resolved token |
| `getDefaultCenter()` | `array{0: float, 1: float}` | Map center |
| `getDefaultZoom()` | `int` | Zoom level |
| `isSearchable()` | `bool` | Search enabled |
| `isStreetAddressesOnly()` | `bool` | Street-address restriction enabled |
| `getCountries()` | `list<string>\|null` | Country filter |
| `hasStreetAddress(array $state)` | `bool` | Whether canonical state has a street name |
| `getEmptyCanonicalState()` | `array` | All keys `null` |
| `hydrateToCanonical(mixed $state)` | `array` | Normalize incoming state |
| `dehydrateFromCanonical(array $state)` | `mixed` | Output for save |
| `normalizeCanonical(array $state)` | `array` | Merge + auto `place_name` |
| `hasAnyStoredValue(array $state)` | `bool` | Any configured field filled |
| `formatString(array $state)` | `string` | Apply string template |
| `getSummaryLabel(array $state)` | `string\|null` | Display label |
| `getValidationMessage(array $state)` | `string\|null` | First validation error |
| `getWrapperClasses()` | `array` | `fff-map-picker-field` |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `fields` | `fields()` |
| `store_format` | `storeFormat()` |
| `string_format` | `stringFormat()` |
| `required_fields` | `requiredFields()` |
| `mapbox_token` | `mapboxToken()` |
| `default_center` | `defaultCenter()` |
| `default_zoom` | `defaultZoom()` |
| `searchable` | `searchable()` |
| `countries` | `countries()` |
| `street_addresses_only` | `streetAddressesOnly()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-map-picker-field` | Root wrapper |
| `fff-map-picker` | Alpine root |
| `fff-map-picker__search-wrap` | Search input + dropdown container |
| `fff-map-picker__dropdown-panel` | Autocomplete suggestions list |
| `fff-map-picker__selection-error` | Inline error when a non-street result is rejected |
| `fff-map-picker__canvas` | Mapbox map container |
| `fff-map-picker__summary` | Selected address summary below map |

### Implementation notes

- Set `MAPBOX_ACCESS_TOKEN` or `filament-flex-fields.mapbox.access_token` before use.
- When `lat`/`lng` exist but `place_name` is empty, a summary is built from street, postcode, city, and country.
- Shared geocoding helpers live in `resources/js/support/mapbox-geocoding.js` (also used by `AddressAutocompleteField`).
- With `streetAddressesOnly()`, a map click or pin drag outside a resolvable street address shows an inline error and does not update state (the pin snaps back on drag).

---

## AddressAutocompleteField

### Summary

Mapbox-powered **address search** without a map — combobox autocomplete with structured or string storage. Shares geocoding logic with `MapPickerField` but omits coordinates by default.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField` |
| **State type (internal)** | Canonical array with keys from `ALL_FIELDS` |
| **Dehydrated state** | Subset of fields per `fields()`, or formatted string per `storeFormat()` |
| **FieldType** | `address_autocomplete` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField;

AddressAutocompleteField::make('delivery_address')
    ->label('Delivery address')
    ->fields(['street', 'city', 'postcode', 'country', 'place_name'])
    ->requiredFields(['city'])
    ->streetAddressesOnly()
    ->countries(['PL'])
    ->size('md')
    ->variant('primary');

AddressAutocompleteField::make('billing_city')
    ->storeFormat(AddressAutocompleteField::STORE_STRING)
    ->stringFormat('{city}, {country_name}')
    ->variant('secondary')
    ->size('sm');
```

Requires Mapbox token in `config('filament-flex-fields.mapbox.access_token')` or per-field `mapboxToken()`.

### State format

**Canonical internal keys** (`ALL_FIELDS`):

`street`, `city`, `region`, `postcode`, `country`, `country_name`, `place_name`

No `lat`/`lng` — use `MapPickerField` when coordinates are required.

| `storeFormat` | Dehydrated value |
|---------------|------------------|
| `structured` (default) | Array with only keys from `fields()` |
| `string` | Single string from `stringFormat()` placeholders `{field}` |

On hydrate, strings set `place_name`; arrays merge into canonical state.

### Validation

| Behaviour | Detail |
|-----------|--------|
| `required()` | At least one configured field must have a value |
| `requiredFields()` | Listed fields must be filled when any value present |
| `countries()` | `country` must be in whitelist when set |
| `streetAddressesOnly()` | `street` must be filled; cities, regions, and other non-address results are rejected |

### Configuration API

#### `fields(array|Closure $fields)`

Which address parts to store. Default: `street`, `city`, `postcode`, `country`, `country_name`, `place_name`.

#### `storeFormat(string|Closure $format)`

`AddressAutocompleteField::STORE_STRUCTURED` or `AddressAutocompleteField::STORE_STRING`.

#### `stringFormat(string|Closure $format)`

Template for string storage. Placeholders: `{street}`, `{city}`, etc. Default: `{place_name}`.

#### `requiredFields(array|Closure $fields)`

Fields required when the address is partially filled. Intersected with `fields()`.

#### `mapboxToken(string|Closure|null $token)`

Override Mapbox access token. Falls back to config.

#### `searchable(bool|Closure $condition = true)`

Enable address suggestions. Default: `true`.

#### `countries(array|Closure|null $countries)`

Restrict geocoding results to ISO country codes. `null` = worldwide.

#### `streetAddressesOnly(bool|Closure $condition = true)`

Restrict autocomplete to **full street addresses** only. Uses Mapbox `types=address`, filters results client-side, and validates that `street` is present. Cities, regions, and other area-level results cannot be selected. Default: `false`.

#### `language(string|Closure $language)`

Mapbox Geocoding API language. Default: `pl`.

#### `size(string|Closure $size)`

`sm`, `md`, or `lg`. Default: `md`.

#### `variant(string|Closure $variant)`

`primary`, `secondary`, or `flat`. Default: `primary`.

#### `placeholder(string|Closure|null $placeholder)`

Search input placeholder. Default translation: “Search for an address…”.

#### `prefixIcon(string|BackedEnum|Htmlable|Closure|null $icon)`

Gravity UI (or other Filament) icon before the input. Default: `gravityui-map-pin` from `config('filament-flex-fields.ui.address_autocomplete_prefix_icon')`.

#### `clearIcon(string|BackedEnum|Htmlable|Closure|null $icon)`

Clear-button icon. Default: `gravityui-circle-xmark` from `config('filament-flex-fields.ui.address_autocomplete_clear_icon')`.

#### `readOnly(bool|Closure $condition = true)`

Disable search and selection.

### Public helper methods

Same geocoded-address helpers as `MapPickerField` (`hydrateToCanonical`, `dehydrateFromCanonical`, `getSummaryLabel`, `isStreetAddressesOnly()`, `hasStreetAddress()`, etc.) — see MapPickerField table. Map-specific methods (`getDefaultCenter`, `getDefaultZoom`) are not available. Additional icon getters: `getPrefixIcon()`, `getClearIcon()`.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `fields` | `fields()` |
| `store_format` | `storeFormat()` |
| `string_format` | `stringFormat()` |
| `required_fields` | `requiredFields()` |
| `mapbox_token` | `mapboxToken()` |
| `searchable` | `searchable()` |
| `countries` | `countries()` |
| `street_addresses_only` | `streetAddressesOnly()` |
| `language` | `language()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `placeholder` | `placeholder()` |

Config defaults (`config/filament-flex-fields.php` → `ui`):

| Config key | Default |
|------------|---------|
| `address_autocomplete_size` | `md` |
| `address_autocomplete_variant` | `primary` |
| `address_autocomplete_prefix_icon` | `gravityui-map-pin` |
| `address_autocomplete_clear_icon` | `gravityui-circle-xmark` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-address-autocomplete-field` | Filament wrapper |
| `fff-address-autocomplete` | Alpine root (FlexTextInput shell) |
| `fff-address-autocomplete__search-wrap` | Input + dropdown positioning context |
| `fff-flex-text-input__shell` | Rounded input container (`primary` / `secondary` / `flat`) |
| `fff-map-picker__dropdown-panel` | Autocomplete suggestions (shared with MapPicker) |
| `fff-address-autocomplete__selection-error` | Inline error when a non-street result is rejected |

### Implementation notes

- Alpine component: `address-autocomplete` (built from `resources/js/components/address-autocomplete.js`).
- Shared geocoding helpers live in `resources/js/support/mapbox-geocoding.js` (also used by `MapPickerField`).
- UI matches `FlexTextInput` (`primary` / `secondary` / `flat` variants, Gravity UI icons).
- Autocomplete dropdown reuses MapPicker dropdown styles, including skeleton loading states.
- Unlike `MapPickerField`, coordinates are not stored; only address fields from `fields()` are dehydrated.

---

## ChoiceCards

### Summary

SaaS-style **single-select** card group (radio behaviour).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards` |
| **State type** | `string\|null` — one option key |
| **Model cast** | `'plan' => 'string'` or backed enum |
| **FieldType** | `choice_cards` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;

ChoiceCards::make('plan')
    ->label('Select a plan')
    ->helperText('Choose the plan that suits your needs')
    ->options([
        'starter' => [
            'label' => 'Starter',
            'description' => 'For individuals',
            'price' => '$5',
            'price_suffix' => '/mo',
        ],
        'pro' => 'Pro',
    ])
    ->layout('stack')
    ->default('pro');
```

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | `Rule::in(...)` — value must be a key from `options()` |
| `required()` | At least one option must be selected |

### Configuration API

#### `options(array|Closure $options)`

Option list. See [Rich card option shape](#rich-card-option-shape).

#### `disabledOptions(array|Closure $keys)`

Disables options by key. Merged with per-option `disabled`.

#### `layout(string|Closure $layout)`

| Value | Description |
|-------|-------------|
| `stack` | Vertical list of cards. **Default.** |
| `grid` | Responsive column grid. Use with `gridColumns()`. |
| `media` | Horizontal row: icon + text. Best for icon-led options. |
| `featured` | Plan-style cards: icon box, badge, large price. |

#### `gridColumns(int|array|Closure $columns)`

Column count for `grid` and multi-column `media` layouts.

```php
->gridColumns(3)

->gridColumns([
    'default' => 1,
    'sm' => 2,
    'md' => 3,
    'lg' => 4,
])
```

Breakpoints cascade upward (`sm` → `md` → `lg`). Maximum: **4 columns**.

#### `indicator(string|Closure|null $indicator)`

Selection marker in the top-right corner.

| Value | Description |
|-------|-------------|
| `radio` | Radio dot. Default for `stack`. |
| `check` | Filled circle with checkmark. Default for `featured`. |
| `none` | Border-only selection. Default for `media`. |

When omitted, the default is resolved from `layout()`.

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `default` | Standard grey card background. |
| `primary` | Stronger selected state (featured plans). |
| `secondary` | Subtle grid styling. |

#### `color(string|Closure|null $color)`

Accent color for the selected border. Default: `primary`. Supports Filament color tokens (`success`, `danger`, etc.).

#### `size(string|ControlSize|Closure $size)`

Scales padding, typography, indicators, and icons. See [Control size](#control-size).

#### `ripple(bool|Closure $condition = true)`

Enables a Material-style click ripple on each card.

### Animations

- Smooth border and background transitions on select/deselect
- Indicator scale animation for `check` / `radio` states
- Respects `prefers-reduced-motion`

### FlexField schema config

When built via `FlexFieldFormBuilder`:

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `layout` | `layout()` |
| `grid_columns` / `columns` | `gridColumns()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `color` | `color()` |
| `ripple` | `ripple()` |
| `indicator` | `indicator()` |
| `disabled_options` | `disabledOptions()` |

---

## ChoiceCheckboxCards

### Summary

SaaS-style **multi-select** card group (checkbox behaviour).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCheckboxCards` |
| **State type** | `array<int\|string>` — list of selected option keys |
| **Model cast** | `'toppings' => 'array'` or `'json'` |
| **FieldType** | `choice_checkbox_cards` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCheckboxCards;

ChoiceCheckboxCards::make('toppings')
    ->label('Pizza toppings')
    ->helperText('Select 1 to 3 toppings')
    ->options([
        'cheese' => ['label' => 'Extra cheese', 'description' => 'Mozzarella blend'],
        'mushrooms' => ['label' => 'Mushrooms', 'description' => 'Fresh button mushrooms'],
    ])
    ->minSelections(1)
    ->maxSelections(3)
    ->layout('stack')
    ->default(['cheese']);
```

### Validation

| Rule / method | Description |
|---------------|-------------|
| `array` | Built-in — state must be an array |
| Option keys | Unknown keys fail with `choice_checkbox_cards.invalid_option` |
| `required()` | If `minSelections` is not set, requires at least **1** selection |
| `minSelections(n)` | Minimum number of selected options |
| `maxSelections(n)` | Maximum number; UI blocks further selection when limit is reached |
| `exactSelections(n)` | Exactly `n` selections; overrides min/max validation |

Translation keys: `filament-flex-fields::default.validation.choice_checkbox_cards.*`

### Comparison with Filament `Checkbox`

| Filament `Checkbox` | ChoiceCheckboxCards |
|---------------------|---------------------|
| Boolean state | Array of option keys |
| `accepted()` | `required()` or `minSelections(1)` |
| `declined()` | Not applicable |
| `inline()` | Not applicable — label uses field wrapper above cards |

Use native `Checkbox` or `SwitchField` for a single boolean. Use `ChoiceCheckboxCards` for multi-select card UI.

### Configuration API

Shares the same card API as ChoiceCards:

- `options()` — see [Rich card option shape](#rich-card-option-shape)
- `disabledOptions()`
- `layout()`
- `gridColumns()`
- `variant()`
- `color()`
- `size()`
- `ripple()`

#### `indicator(string|Closure|null $indicator)`

| Value | Description |
|-------|-------------|
| `checkbox` | Square checkbox with animated checkmark. Default for `stack`. |
| `check` | Circle checkmark. Default for `featured`. |
| `none` | Border-only. Default for `media`. |

#### `minSelections(int|Closure|null $count)`

Minimum selected options.

#### `maxSelections(int|Closure|null $count)`

Maximum selected options. Unselected cards become non-interactive when the limit is reached; selected cards can still be unchecked.

#### `exactSelections(int|Closure|null $count)`

Requires exactly this many selections.

### Animations

Same card transitions as ChoiceCards, plus:

- Checkbox box fill animation on select
- Checkmark scale-in with slight delay
- Empty ring → filled check crossfade for `check` indicator

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| All ChoiceCards keys | Same mapping |
| `min_selections` | `minSelections()` |
| `max_selections` | `maxSelections()` |
| `exact_selections` | `exactSelections()` |

---

## FlexChecklist

### Summary

SaaS-style **multi-select** checklist. Same row layout as [FlexRadiolist](#flexradiolist) but stores an array of selected keys.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist` |
| **State type** | `list<string\|int>` — selected option keys |
| **FieldType** | `flex_checklist` |
| **State cast** | `OptionsArrayStateCast` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist;

FlexChecklist::make('features')
    ->label('Included features')
    ->options([
        'wifi' => 'Wi‑Fi',
        'parking' => [
            'label' => 'Parking',
            'description' => 'On-site parking included',
            'icon' => 'heroicon-o-truck',
        ],
    ])
    ->minSelections(1)
    ->maxSelections(3)
    ->color('primary');

FlexChecklist::make('permissions')
    ->options(['read' => 'Read', 'write' => 'Write', 'admin' => 'Admin'])
    ->exactSelections(2)
    ->disabledOptions(['admin']);
```

### State format

Array of unique string keys. Default: `[]`. Duplicate values are deduplicated on validation.

### Validation

| Rule | Detail |
|------|--------|
| `array` | State must be an array |
| Option keys | Each value must exist in `options()` |
| `exactSelections(n)` | Exactly `n` items selected |
| `minSelections(n)` | At least `n` items |
| `maxSelections(n)` | At most `n` items |
| `required()` | Implies `minSelections(1)` when no explicit min |

### Configuration API

#### `options(array|Closure $options)`

`key => label` or rich array (`label`, `description`, `desc`, `icon`, `disabled`). From `HasChecklistOptions`.

#### `icons(array|Closure $icons)`

Per-key icon map merged into options.

#### `descriptions(array|Closure $descriptions)`

Per-key description map. Config key `desc` also supported.

#### `disabledOptions(array|Closure $keys)`

Keys rendered locked with lock icon.

#### `size(string|ControlSize|Closure $size)`

`sm`, `md` (default), `lg`.

#### `color(string|Closure|null $color)`

Filament color for selected rows. Default: `primary`.

#### `minSelections(int|Closure|null $count)`

Minimum selections. `null` = no minimum (unless `required()`).

#### `maxSelections(int|Closure|null $count)`

Maximum selections.

#### `exactSelections(int|Closure|null $count)`

Exact count; overrides min/max semantics when set.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getColor()` | `string\|null` | Accent color |
| `getLockIcon()` | `string\|BackedEnum\|Htmlable` | Lock icon for disabled rows |
| `getMinSelections()` | `int\|null` | Min count |
| `getMaxSelections()` | `int\|null` | Max count |
| `getExactSelections()` | `int\|null` | Exact count |
| `getOptionKeys()` | `list` | Valid keys |
| `getNormalizedOptions()` | `array` | Merged option metadata |
| `getDisabledOptions()` | `array` | Disabled keys |
| `isOptionDisabled(string\|int $key)` | `bool` | Row disabled |
| `getChecklistSizeStyles()` | `array` | CSS custom properties |
| `getWrapperClasses()` | `list<string>` | `fff-flex-checklist` |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `icons` | `icons()` |
| `descriptions` / `desc` | `descriptions()` |
| `disabled_options` | `disabledOptions()` |
| `size` | `size()` |
| `color` | `color()` |
| `min_selections` | `minSelections()` |
| `max_selections` | `maxSelections()` |
| `exact_selections` | `exactSelections()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-flex-checklist` | Root wrapper |
| `fff-flex-checklist--{sm\|md\|lg}` | Size modifier |
| `fff-flex-checklist__row` | Option row |
| `fff-flex-checklist__indicator` | Checkbox indicator |

### Implementation notes

- Pair with `live()` + `afterStateUpdated()` for autosave patterns (same as FlexRadiolist).
- Locked options show `getLockIcon()` and cannot be toggled.

---

## FlexRadiolist

### Summary

SaaS-style **single-select** list (radio behaviour). Same row layout as [FlexChecklist](#flexchecklist) but one choice at a time.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist` |
| **State type** | `string\|null` — one option key |
| **FieldType** | `flex_radiolist` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist;

FlexRadiolist::make('delivery')
    ->label('Delivery method')
    ->options([
        'standard' => 'Standard',
        'express' => [
            'label' => 'Express',
            'description' => '2–5 business days',
            'icon' => 'heroicon-o-bolt',
        ],
    ])
    ->default('express');
```

Label-only options (no icon, no description):

```php
FlexRadiolist::make('role')
    ->options([
        'read' => 'Read',
        'write' => 'Write',
        'admin' => 'Admin',
    ]);
```

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | `Rule::in(...)` — value must be an option key |
| `required()` | A selection is required |

### Configuration API

Shares the checklist options API via `HasChecklistOptions`:

| Method | Description |
|--------|-------------|
| `options(array\|Closure $options)` | `key => label` or rich array (`label`, `description`, `desc`, `icon`, `disabled`) |
| `icons(array\|Closure $icons)` | Optional per-key icons |
| `descriptions(array\|Closure $descriptions)` | Optional per-key descriptions |
| `disabledOptions(array\|Closure $keys)` | Lock rows by key |
| `size(string\|ControlSize\|Closure $size)` | `sm`, `md` (default), `lg` |
| `color(string\|Closure\|null $color)` | Accent for selected radio. Default: `primary` |

### Autosave on change

```php
FlexRadiolist::make('delivery')
    ->options([...])
    ->live()
    ->afterStateUpdated(fn (string $state) => $this->saveDelivery($state));
```

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `icons` | `icons()` |
| `descriptions` / `desc` | `descriptions()` |
| `disabled_options` | `disabledOptions()` |
| `size` | `size()` |
| `color` | `color()` |

### Implementation notes

- Row styles live in the shared `flex-checklist.css` bundle (`.fff-flex-radiolist*` classes). The radiolist blade loads that bundle via the `flex-radiolist` → `flex-checklist` stylesheet alias — no separate CSS file.

---

## MatrixChoiceField

### Summary

**Multiple choice grid** (matrix / survey table): row labels on the left, column headers on top, radio or checkbox in each cell. Gray inset frame with white body panel. Per-row validation and **reactive conditional disabling** (no `live()` required).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\MatrixChoiceField` |
| **State type** | Radio: `array<string, string\|null>` · Checkbox: `array<string, list<string>>` |
| **FieldType** | `matrix_choice` |
| **Playground** | `matrix-choice` |
| **Stylesheet** | Lazy `matrix-choice-field` bundle |
| **Model cast** | `'responses' => 'array'` or `'responses' => 'json'` |

> Use `matrixColumns()` — **not** `columns()` — because `columns()` is reserved by Filament layout grids.

### Full example

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MatrixChoiceField;

MatrixChoiceField::make('feature_priorities')
    ->label('Feature priorities')
    ->helperText('Assign priority per feature. Dark mode High blocks CSV High.')
    ->mode('checkbox')
    ->size('md')
    ->color('primary')
    ->rows([
        'dark_mode' => [
            'label' => 'Dark mode',
            'description' => 'UI theme support',
            'required' => true,
            'max_selections' => 1,
        ],
        'csv_export' => [
            'label' => 'CSV export',
            'min_selections' => 1,
            'max_selections' => 2,
        ],
        'api_access' => [
            'label' => 'API access',
            'disabled' => true,
        ],
    ])
    ->matrixColumns([
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => [
            'label' => 'High',
            'icon' => 'heroicon-o-bolt',
        ],
    ])
    ->requiredRows(['dark_mode'])
    ->disabledCells([
        // Static: always lock CSV → Low (example)
        // 'csv_export' => ['low'],
    ])
    ->disableCellWhen('csv_export', 'high', 'dark_mode', 'high')
    ->disableRowWhen('api_access', 'dark_mode', 'low')
    ->default([
        'dark_mode' => ['high'],
        'csv_export' => ['medium'],
    ]);
```

Radio mode (one answer per row — survey / mood matrix):

```php
MatrixChoiceField::make('mood')
    ->label('Tell us about your mood')
    ->mode('radio')
    ->rows([
        'saturday' => ['label' => 'Saturday', 'required' => true],
        'sunday' => ['label' => 'Sunday', 'required' => true],
        'monday' => 'Monday',
    ])
    ->matrixColumns([
        'happy' => 'Happy',
        'neutral' => 'Neutral',
        'sad' => 'Sad',
        'pleading' => 'Pleading',
        'party' => 'Party',
        'zany' => 'Zany',
    ])
    ->default([
        'saturday' => 'happy',
        'sunday' => 'neutral',
    ]);
```

### Row option shape

Each key in `rows()` is stored in the database. Value can be a plain string (used as label) or a rich array:

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `label` | `string` | row key | Left column row title |
| `description` / `desc` | `string\|null` | `null` | Optional subtitle under row label |
| `required` | `bool` | `false` | Row must have at least one selection. Overrides `requiredRows()` when set explicitly |
| `disabled` | `bool` | `false` | Entire row locked (all cells disabled) |
| `min_selections` / `min` | `int\|null` | `null` | Checkbox only — minimum selected columns in this row |
| `max_selections` / `max` | `int\|null` | `null` | Checkbox only — maximum selected columns in this row |

```php
->rows([
    'billing' => 'Billing', // shorthand for ['label' => 'Billing']
    'shipping' => [
        'label' => 'Shipping',
        'description' => 'Delivery options',
        'required' => true,
        'min_selections' => 1,
        'max_selections' => 2,
    ],
])
```

### Column option shape

Each key in `matrixColumns()` is a selectable column id (stored in state).

| Form | Example |
|------|---------|
| `key => 'Label'` | `'happy' => 'Happy'` |
| Rich array | `'high' => ['label' => 'High', 'icon' => 'heroicon-o-bolt', 'disabled' => true]` |

| Key | Type | Description |
|-----|------|-------------|
| `label` | `string` | Header text (or emoji) shown above cells |
| `icon` | `string\|null` | Optional Heroicon above label (alternative to `columnIcons()`) |
| `disabled` | `bool` | Disables this column in **every** row |

```php
->matrixColumns([
    'low' => 'Low',
    'high' => ['label' => 'High', 'icon' => 'heroicon-o-fire'],
])
->columnIcons([
    'low' => 'heroicon-o-arrow-down',
    'high' => 'heroicon-o-arrow-up',
]);
```

### State format

**Radio mode** — one column key per row (or omitted if empty):

```json
{
  "saturday": "happy",
  "sunday": "neutral"
}
```

**Checkbox mode** — list of column keys per row:

```json
{
  "dark_mode": ["high"],
  "csv_export": ["medium", "low"]
}
```

- Default state: `[]`
- On dehydrate, empty rows and invalid keys are stripped
- Use Eloquent cast `'field' => 'array'` or `'field' => 'json'`

### Validation

#### Built-in (per row)

| Rule | Radio | Checkbox | Detail |
|------|-------|----------|--------|
| `required` on row | ✓ | ✓ | Row must have a selection |
| `requiredRows([...])` | ✓ | ✓ | Mark rows required by key |
| `required()` on field | ✓ | ✓ | All non-disabled rows required when no `requiredRows()` set |
| `min_selections` | — | ✓ | Min columns selected in row |
| `max_selections` | — | ✓ | Max columns selected in row |
| Static `disabled` / `disabledRows` | ✓ | ✓ | Selection in locked row fails |
| `disabledCells` | ✓ | ✓ | Selection in locked cell fails |
| `disableCellWhen` / `disableRowWhen` | ✓ | ✓ | Same rules enforced server-side |

Translation keys (`resources/lang/en/default.php`):

| Key | When |
|-----|------|
| `validation.matrix_choice.invalid` | State is not an array |
| `validation.matrix_choice.invalid_option` | Unknown or disabled column selected |
| `validation.matrix_choice.row_required` | Required row empty (`:row`) |
| `validation.matrix_choice.row_min` | Too few selections (`:row`, `:count`) |
| `validation.matrix_choice.row_max` | Too many selections (`:row`, `:count`) |

#### Custom cross-row rules

Use standard Filament `->rule()` for business logic across rows:

```php
use Closure;

MatrixChoiceField::make('features')
    ->mode('checkbox')
    ->rows([...])
    ->matrixColumns([...])
    ->rule(function (): Closure {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $value = is_array($value) ? $value : [];

            if (in_array('high', $value['dark_mode'] ?? [], true)
                && in_array('high', $value['csv_export'] ?? [], true)) {
                $fail('High priority can only be assigned to one feature.');
            }
        };
    });
```

### Configuration API

#### `mode('radio'|'checkbox')`

| Value | Behaviour |
|-------|-------------|
| `radio` (default) | Exactly one column per row |
| `checkbox` | Zero or more columns per row |

```php
->mode('radio')    // survey grid
->mode('checkbox') // multi-tag per row
```

#### `rows(array|Closure $rows)`

Row definitions — see [Row option shape](#row-option-shape). Accepts `Closure` for dynamic rows.

#### `matrixColumns(array|Closure $columns)`

Column headers — see [Column option shape](#column-option-shape).

#### `columnIcons(array|Closure $icons)`

Per-column icon map merged into column metadata:

```php
->columnIcons([
    'happy' => 'heroicon-o-face-smile',
    'sad' => 'heroicon-o-face-frown',
])
```

#### `requiredRows(array|Closure $keys)`

Mark rows as required without inline `required => true`:

```php
->requiredRows(['saturday', 'sunday'])
```

#### `disabledRows(array|Closure $keys)`

Lock entire rows by key (static, always on):

```php
->disabledRows(['archived_feature', 'legacy_api'])
```

#### `disabledCells(array|Closure $map)`

Lock specific cells. Map shape: `rowKey => [columnKey, ...]`:

```php
->disabledCells([
    'csv_export' => ['low'],
    'dark_mode' => ['high', 'medium'],
])
```

Accepts `Closure` for server-side dynamic maps (re-evaluated on each render; use with `live()` for server-driven updates).

#### `disableCellWhen($row, $column, $whenRow, $whenColumns)`

**Reactive** (client-side Alpine) — disable one cell when a trigger row matches column key(s). No `live()` needed.

```php
// When dark_mode includes High → disable csv_export → High
->disableCellWhen('csv_export', 'high', 'dark_mode', 'high')

// Multiple trigger columns (any match)
->disableCellWhen('csv_export', 'high', 'dark_mode', ['high', 'critical'])
```

| Argument | Description |
|----------|-------------|
| `$row` | Target row to disable |
| `$column` | Target column to disable |
| `$whenRow` | Row to watch |
| `$whenColumns` | `string` or `list<string>` — trigger column key(s) |

| Trigger mode | Match condition |
|--------------|-----------------|
| `radio` | `whenRow` selected column **equals** one of `whenColumns` |
| `checkbox` | `whenRow` selection **includes any** of `whenColumns` |

Invalid selections in newly disabled cells are removed automatically.

#### `disableRowWhen($row, $whenRow, $whenColumns)`

**Reactive** — disable an entire row when trigger row matches:

```php
// When dark_mode is Low → disable entire api_access row
->disableRowWhen('api_access', 'dark_mode', 'low')
```

#### `size('sm'|'md'|'lg')`

Control scale for row labels, column headers, and radio/checkbox indicators. Default: `md`.

```php
->size('sm')  // compact tables
->size('lg')  // touch-friendly
```

#### `color('primary'|'secondary'|'success'|'warning'|'danger'|null)`

Filament accent for selected radio/checkbox indicators. Default: `primary`.

```php
->color('success')
```

### Inherited Filament field API

Also supports standard [Inherited Filament field API](#inherited-filament-field-api):

| Method | Typical use |
|--------|-------------|
| `label()` / `helperText()` | Field title above grid |
| `required()` | All rows required (unless `requiredRows()` narrows scope) |
| `disabled()` | Disable entire field |
| `default()` / `dehydrated()` | Initial state and persistence |
| `live()` | Optional — not needed for `disableCellWhen` / `disableRowWhen` |
| `afterStateUpdated()` | React to changes (autosave, logging) |
| `rule()` | Custom validation (see above) |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getMode()` | `string` | `radio` or `checkbox` |
| `isCheckboxMode()` | `bool` | Checkbox mode flag |
| `getRowKeys()` / `getColumnKeys()` | `list<string>` | Valid keys |
| `getNormalizedRows()` | `array` | Merged row metadata |
| `getNormalizedColumns()` | `array` | Merged column metadata |
| `getDisabledCellsMap()` | `array<string, list<string>>` | Static disabled cells |
| `getConditionalDisableRules()` | `list<array>` | `disableCellWhen` / `disableRowWhen` rules |
| `matchesConditionalDisableRule($rule, $state)` | `bool` | Test rule against state |
| `isRowDisabled($row, $state?)` | `bool` | Static + conditional row lock |
| `isCellDisabled($row, $column, $state?)` | `bool` | Static + conditional cell lock |
| `dehydrateValue($state)` | `array` | Normalize state for storage |
| `getWrapperClasses()` | `list<string>` | `fff-matrix-choice` BEM classes |
| `getMatrixSizeStyles()` | `array` | CSS custom properties |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `mode` | `mode()` |
| `rows` | `rows()` |
| `columns` | `matrixColumns()` |
| `column_icons` | `columnIcons()` |
| `disabled_rows` | `disabledRows()` |
| `required_rows` | `requiredRows()` |
| `disabled_cells` | `disabledCells()` |
| `disable_cell_when` | `disableCellWhen()` — list of rule arrays |
| `disable_row_when` | `disableRowWhen()` — list of rule arrays |
| `size` | `size()` — default from `config('filament-flex-fields.ui.matrix_choice_size', 'md')` |
| `color` | `color()` |

`disable_cell_when` / `disable_row_when` rule array:

```php
'disable_cell_when' => [
    [
        'row' => 'csv_export',
        'column' => 'high',
        'when_row' => 'dark_mode',
        'when_columns' => 'high', // or ['high', 'medium']
    ],
],
'disable_row_when' => [
    [
        'row' => 'api_access',
        'when_row' => 'dark_mode',
        'when_columns' => 'low',
    ],
],
```

### CSS classes

| Class | Role |
|-------|------|
| `fff-matrix-choice` | Root wrapper |
| `fff-matrix-choice--{sm\|md\|lg}` | Size modifier |
| `fff-matrix-choice--{radio\|checkbox}` | Mode modifier |
| `fff-matrix-choice__frame` | Gray outer frame |
| `fff-matrix-choice__header` | Column header row |
| `fff-matrix-choice__body` | White inset panel |
| `fff-matrix-choice__row` | Data row |
| `fff-matrix-choice__cell` | Clickable grid cell |
| `fff-matrix-choice__cell.is-selected` | Selected cell (animated indicator) |
| `fff-matrix-choice__cell.is-disabled` | Locked cell |

### Implementation notes

- Radio/checkbox indicators reuse Flex Radiolist / Flex Checklist animation tokens (`fff-choice-cards-indicator-pop`).
- All clicks are handled on `fff-matrix-choice__cell`; inner inputs use `pointer-events-none` to prevent double-toggle.
- Conditional rules run in Alpine on every state change; `pruneDisabledSelections()` clears invalid picks.
- Playground slug: `matrix-choice` (demos: mood radio grid + feature priorities checkbox).

---

## SwitchField

### Summary

SaaS-style boolean toggle: label and description on one side, switch on the other.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField` |
| **State type** | `bool` |
| **Model cast** | `'is_admin' => 'boolean'` |
| **FieldType** | `toggle` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;

SwitchField::make('notifications')
    ->label('Notifications')
    ->description('Receive email updates')
    ->badge('New')
    ->badgeColor('primary')
    ->layout('card')
    ->accepted();
```

### Validation

| Method | Laravel rule |
|--------|--------------|
| (default) | `boolean` |
| `accepted()` | `accepted` — must be `true` |
| `declined()` | `declined` — must be `false` |

Parity with Filament `Toggle` / `Checkbox` validation.

### Configuration API

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `default` | Standard track styling. **Default.** |
| `secondary` | Subtle secondary track. |

#### `layout(string|Closure $layout)`

| Value | Description |
|-------|-------------|
| `row` | Single horizontal row. **Default.** |
| `card` | Card container with padding and shadow. |

#### `labelPosition(string|Closure $position)`

| Value | Description |
|-------|-------------|
| `start` | Label on the left, switch on the right. **Default.** |
| `end` | Label on the right, switch on the left. |

Throws `InvalidArgumentException` for unsupported values.

#### `description(string|Closure|null $description)`

Secondary text rendered below the label inside the component.

#### `badge(string|Closure|null $badge)`

Small label next to the title, e.g. `"New"`.

#### `badgeColor(string|Closure $badgeColor)`

Filament color for the badge. Default: `primary`.

#### `color(string|Closure|null $color)`

Effective ON color when `onColor()` is not set. Default: `primary`.

#### `onColor(string|Closure|null $color)` / `offColor(string|Closure|null $color)`

Separate track/thumb colors for ON and OFF states. Default OFF: `gray`.

From Filament `HasToggleColors`.

#### `onIcon()` / `offIcon()`

Icons inside the thumb for ON and OFF states. Accepts Heroicon, enum, or `Htmlable`.

From Filament `HasToggleIcons`.

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

#### `compact(bool|Closure $condition = true)`

Reduces padding in `card` layout.

#### `ripple(bool|Closure $condition = true)`

Click ripple on the interactive area.

#### `fixIndistinctState()`

Normalizes ambiguous values (e.g. `null` → `false`). From Filament `CanFixIndistinctState`.

#### `extraAlpineAttributes(array|Closure $attributes)`

Extra Alpine bindings on the root element. From Filament `HasExtraAlpineAttributes`.

### Implementation notes

- `hiddenLabel()` is called in `setUp()` so the label renders **inside** the component, avoiding duplicate labels in field groups.
- Default state: `false`.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `variant` | `variant()` |
| `layout` | `layout()` |
| `size` | `size()` |
| `color` | `color()` |
| `badge` | `badge()` |
| `badge_color` | `badgeColor()` |
| `description` | `description()` |
| `on_color` | `onColor()` |
| `off_color` | `offColor()` |
| `on_icon` | `onIcon()` |
| `off_icon` | `offIcon()` |
| `label_position` | `labelPosition()` |
| `ripple` | `ripple()` |
| `compact` | `compact()` |

---

## CurrencyField

### Summary

Revolut-style currency input: locale-aware formatting, digit animations, optional currency picker, and **minor-unit** storage.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField` |
| **State type (internal)** | `int\|null` (single currency) or `array{amount: int\|null, currency: string}\|null` (multi-currency) |
| **Default DB format** | **Minor units** as integer — e.g. `66 666,60 PLN` → `6666660` |
| **FieldType** | `currency` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CurrencyField;

// Fixed currency (PLN only)
CurrencyField::make('price')
    ->label('Amount')
    ->currency('PLN')
    ->locale('pl_PL')
    ->required();

// Multi-currency with picker
CurrencyField::make('budget')
    ->currencies(['EUR', 'USD', 'GBP', 'PLN'])
    ->currency('EUR')
    ->min(0)
    ->max(99999.99);
```

---

### Storage format vs display format

**Important:** commas, spaces, and currency symbols (`zł`, `€`) are **display only**. They are controlled by `locale()` and never written to the database by default.

| Layer | Example (PLN) | Format |
|-------|---------------|--------|
| **UI** | `66 666,60 zł` | Locale grouping + symbol |
| **Form state (Alpine / Livewire)** | `6666660` | Integer, minor units |
| **Database (default dehydrate)** | `6666660` | Integer, minor units |

#### Minor units

Amounts are stored as the smallest currency unit (e.g. grosze, cents):

| Display | Minor units (`int`) |
|---------|---------------------|
| `99,99 PLN` | `9999` |
| `1 250,50 EUR` | `125050` |
| `¥1,500` (JPY, 0 decimals) | `1500` |

Multi-currency state:

```php
[
    'amount' => 125050,   // minor units
    'currency' => 'EUR',
]
```

#### What the field accepts on load (`normalizeState`)

On hydrate, `afterStateHydrated` normalizes incoming values:

| Value from DB / model | Result in form state |
|-----------------------|----------------------|
| `6666660` (`int`) | Treated as **minor units** → `6666660` |
| `66666.60` (`float`) | Treated as **major units** → converted to `6666660` |
| `"12.50"` (`string` with `.`) | Major units → `1250` |
| `"66666,60"` (`string` with `,`) | **Not supported** out of the box |
| `"66 666,60"` | **Not supported** out of the box |
| `null` | `null` |

> `min()` / `max()` are defined in **major units** (e.g. `10.50`) but validated internally as minor units.

---

### Custom database formats

By default, `dehydrateStateUsing()` saves **minor units** as `int` (or `array` with multi-currency). There is **no** built-in `->storeAsMajor()` method — override dehydration (and optionally hydration) when your column uses a different format.

#### When to override `dehydrateStateUsing()`

Call **after** other field configuration. Your closure receives the **normalized minor-unit state** from the component (unless you also override hydration).

| Your DB column | Strategy |
|----------------|----------|
| `integer` minor units (recommended) | **Default** — no override needed |
| `decimal(12,2)` major units | `dehydrateStateUsing` → divide by `10^decimals` |
| `varchar` e.g. `"66666,60"` | `dehydrateStateUsing` → format string; `afterStateHydrated` → parse string |
| Legacy mixed data | Eloquent **cast** on the model (preferred) |

#### Example: store major units (`decimal`)

```php
CurrencyField::make('price')
    ->currency('PLN')
    ->dehydrateStateUsing(function (?int $state): ?float {
        if ($state === null) {
            return null;
        }

        return $state / 100; // 6666660 → 66666.60
    });
```

On load, floats with a dot are already converted to minor units by `normalizeState()` (e.g. `66666.60` → `6666660`), so no extra hydration hook is required for plain floats.

#### Example: store formatted string with comma

```php
CurrencyField::make('price')
    ->currency('PLN')
    ->afterStateHydrated(function (CurrencyField $component, mixed $state): void {
        if (! is_string($state)) {
            return;
        }

        // "66 666,60" → minor units
        $normalized = str_replace([' ', ','], ['', '.'], $state);
        $component->state((int) round((float) $normalized * 100));
    })
    ->dehydrateStateUsing(function (?int $state): ?string {
        if ($state === null) {
            return null;
        }

        return number_format($state / 100, 2, ',', ''); // 6666660 → "66666,60"
    });
```

#### Example: keep dehydration default, only transform on save

If you only need a one-off transform when persisting the parent form, you can still use `dehydrateStateUsing` on the field — it runs when Filament dehydrates form state to the model:

```php
CurrencyField::make('price')
    ->currency('PLN')
    ->dehydrateStateUsing(fn (CurrencyField $component, mixed $state) => $state === null
        ? null
        : $state / 100
    );
```

> **Note:** The built-in closure is `fn (CurrencyField $component, mixed $state) => $component->normalizeState($state)`. When overriding, you receive state that is already in the component’s internal shape (minor units). Use `normalizeState()` only if you need to re-normalize arbitrary input.

#### Recommended: Eloquent cast

Keep `CurrencyField` on minor units in the form and map at the model layer:

```php
// Model — illustrative custom cast
protected function casts(): array
{
    return [
        'price' => MinorUnitsCast::class, // DB decimal ↔ app int
    ];
}
```

This avoids duplicating conversion logic across forms and API resources.

#### Migration recommendation

For legacy projects, prefer a **one-time migration** to `integer` minor units or `decimal` major units rather than storing locale-specific strings long term.

---

### Configuration API

#### `currency(string|Closure $code)`

Default ISO 4217 code when not using multi-currency, or default selection when `currencies()` is set. Example: `'PLN'`, `'EUR'`.

#### `currencies(array|Closure|null $codes)`

Enables the currency picker. Pass a list of codes, e.g. `['EUR', 'USD', 'GBP']`. When omitted, the field is single-currency.

**35 built-in currencies** (`AED`, `ARS`, `AUD`, … `ZAR`) — see `CurrencyCountries::allSupportedCodes()`. Codes not registered (built-in or config) are ignored by `currencies()` and throw on `currency()`.

#### Extending or overriding currencies (after `composer install`)

Publish config (`php artisan vendor:publish --tag=filament-flex-fields-config`) and add entries under `currencies`. Config is **merged on top** of the built-in list; matching ISO codes **override** symbol, name, decimals, and locale.

```php
// config/filament-flex-fields.php
'currencies' => [
    'VND' => [
        'symbol' => '₫',
        'name' => 'Vietnamese dong',
        'decimals' => 0,
        'locale' => 'vi-VN',
    ],
],
```

```php
CurrencyField::make('price')
    ->currency('VND')
    ->currencies(['VND', 'EUR', 'USD']);
```

Optional translated names: `lang/vendor/filament-flex-fields/{locale}/currencies.php` (key = ISO code). `CurrencyCountries::isSupported('VND')` checks built-in + config.

#### `locale(string|Closure|null $locale)`

Display formatting only (grouping separator, decimal separator). Does **not** change DB format. Use BCP 47 or Laravel-style tags (`pl_PL`, `pl-PL`); the UI normalizes underscores for `Intl`.

#### `min(float|int|Closure|null $value)` / `max(float|int|Closure|null $value)`

Bounds in **major units** (e.g. `0`, `99999.99`).

#### `allowNegative(bool|Closure $condition = true)`

Allow negative amounts. **Default: false.**

#### `animated(bool|Closure $condition = true)`

Digit roll animation on change. **Default: true.**

#### `commitDecimalsOnBlur(bool|Closure $condition = true)`

Pad fractional digits on blur (e.g. `,6` → `,60`). **Default: true.**

#### `searchable(bool|Closure $condition = true)`

Search in the currency dropdown. **Default: true.**

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

#### `placeholder(string|Closure|null $placeholder)`

Empty-state hint (default: `0`).

### Public helper methods

| Method | Description |
|--------|-------------|
| `normalizeState(mixed $state)` | Convert arbitrary input to minor-unit state shape |
| `extractAmount(int\|array\|null $state)` | Get amount in minor units |
| `extractCurrency(int\|array\|null $state)` | Get currency code |
| `getInitialDisplay(?mixed $state = null)` | Server-rendered segments for first paint (no layout flash) |
| `hasCurrencySelect()` | Whether `currencies()` is configured |
| `getCurrenciesMetadata()` | Metadata for Alpine (`code`, `symbol`, `decimals`, `locale`) |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `currency` | `currency()` |
| `locale` | `locale()` |
| `currencies` | `currencies()` |
| `min` | `min()` |
| `max` | `max()` |
| `allow_negative` | `allowNegative()` |
| `animated` | `animated()` |
| `commit_decimals_on_blur` | `commitDecimalsOnBlur()` |
| `searchable` | `searchable()` |
| `size` | `size()` |

### CSS classes

| Class | Meaning |
|-------|---------|
| `fff-currency-field` | Root wrapper |
| `fff-currency-field--{sm\|md\|lg}` | Size modifier |
| `fff-currency-field__currency-trigger` | Currency picker chip |
| `fff-currency-field__digits` | Animated digit display |
| `fff-currency-field__symbol` | Trailing currency symbol |

---

## CountryField

### Summary

Searchable country picker with circle flags. Stores a single **ISO 3166-1 alpha-2** code. Uses the full country list (~255 codes) — broader than `PhoneField`’s libphonenumber region set — with the same flag assets as the phone country dropdown.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField` |
| **State type** | `string\|null` — ISO alpha-2 country code |
| **FieldType** | `country` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;

CountryField::make('country')
    ->label('Country')
    ->defaultCountry('PL')
    ->required();

CountryField::make('shipping_country')
    ->countries(['PL', 'DE', 'GB', 'US'])
    ->exceptCountries(['RU', 'BY'])
    ->showCountryCode()
    ->browserLocaleDefault()
    ->browserLocaleSortFirst();
```

### State format

| Value | Description | Example |
|-------|-------------|---------|
| Selected country | Uppercase ISO 3166-1 alpha-2 | `PL`, `US`, `DE` |
| Empty | `null` when cleared or invalid | `null` |

On hydrate and dehydrate, `normalizeState()` uppercases and validates against the resolved country list. Invalid stored values fall back to `defaultCountry()` when allowed, otherwise `null`.

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | Custom rule — submitted code must be in resolved list |
| `required()` | Value must not be blank |
| Filament `required` rule | Overridden to `nullable` — validation handled by custom rule |

### Configuration API

#### `variant(string|Closure $variant)`

Visual style shared with FlexTextInput. Values: `primary` (default), `secondary`, `flat`.

#### `size(string|ControlSize|Closure $size)`

Control height. See [Control size](#control-size). Default: `md` (config: `filament-flex-fields.ui.country_size`).

#### `defaultCountry(string|Closure|null $countryCode)`

ISO code when no value is selected. Default: `PL` (config: `filament-flex-fields.ui.country_default_country`). Falls back to first allowed country when the default is not in the list.

#### `countries(array|Closure|null $countries)`

Whitelist of ISO codes. `null` = all countries from the built-in ISO list (minus `exceptCountries`).

```php
->countries(['PL', 'DE', 'FR'])
```

#### `exceptCountries(array|Closure $countries)`

Blacklist applied after the whitelist. Default: `[]`.

#### `searchable(bool|Closure $condition = true)`

Show search input in the dropdown. Default: `true`. Search matches country name, code, and dial code.

#### `showCountryCode(bool|Closure $condition = true)`

Show ISO code next to the country name in the trigger and menu. Default: `false`.

#### `showDialCode(bool|Closure $condition = true)`

Show international dial code when available (libphonenumber-supported regions). Default: `false`.

#### `browserLocaleDefault(bool|Closure $condition = true)`

When enabled and state is empty on hydrate, pre-select country from `Accept-Language` (server) or `navigator.languages` (client Alpine).

#### `browserLocaleSortFirst(bool|Closure $condition = true)`

Sort country list with browser-detected country first.

#### `placeholder(string|Closure|null $placeholder)`

Inherited from Filament `HasPlaceholder`. Default translation: `filament-flex-fields::country.placeholder`.

#### `readOnly(bool|Closure $condition = true)`

Inherited from Filament `CanBeReadOnly`.

#### `focusOutline(bool|Closure $condition = true)`

Inherited from `HasFieldFocusOutline`. Default: `false`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved variant |
| `getAllowedCountryCodes()` | `list<string>\|null` | Whitelist or `null` |
| `getExceptCountryCodes()` | `list<string>` | Blacklist |
| `getResolvedCountryCodes()` | `list<string>` | Effective allowed codes |
| `getDefaultCountryCode()` | `string\|null` | Effective default |
| `isSearchable()` | `bool` | Search enabled |
| `shouldShowCountryCode()` | `bool` | ISO code visible |
| `shouldShowDialCode()` | `bool` | Dial code visible |
| `shouldUseBrowserLocaleDefault()` | `bool` | Browser locale default |
| `shouldSortCountriesByBrowserLocale()` | `bool` | Browser locale sort |
| `getBrowserLocaleCountryCode()` | `string\|null` | Detected locale country |
| `getCountriesMetadata()` | `list<array>` | `code`, `name`, `dial_code`, `flag_url` |
| `getCountrySelectOptions()` | `array` | Options map for internal select |
| `normalizeState(mixed $state)` | `string\|null` | Canonical country code |
| `getWrapperClasses()` | `list<string>` | CSS class list |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `variant` | `variant()` |
| `default_country` | `defaultCountry()` |
| `countries` | `countries()` |
| `except_countries` | `exceptCountries()` |
| `searchable` | `searchable()` |
| `browser_locale_default` | `browserLocaleDefault()` |
| `browser_locale_sort_first` | `browserLocaleSortFirst()` |
| `show_country_code` | `showCountryCode()` |
| `show_dial_code` | `showDialCode()` |

Config defaults (`config/filament-flex-fields.php` → `ui`):

| Config key | Default |
|------------|---------|
| `country_size` | `md` |
| `country_variant` | `primary` |
| `country_default_country` | `PL` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-country-field` | Root wrapper |
| `fff-country-field--{sm\|md\|lg}` | Size modifier |
| `fff-country-field__trigger` | Picker button (flag + label) |
| `fff-country-field__flag` | Circle flag image |
| `fff-country-field__menu` | Teleported dropdown (`is-positioned` when open) |
| `fff-country-field__search` | Dropdown search input |
| `fff-country-field__option` | Menu row |

Shares FlexTextInput shell classes (`fff-flex-text-input-field`, variant modifiers).

### Implementation notes

- Country names use `filament-flex-fields::countries.{CODE}` translations when published, then `locale_get_display_region()` as fallback.
- Flag URLs reuse `PhoneCountries::flagUrl()` (same CDN as `PhoneField`).
- Dropdown uses `x-teleport="body"` to avoid overflow clipping.
- SSR label is rendered server-side; Alpine `displayReady` swaps to live state without layout flash on reload.
- Dial codes are only shown for regions supported by libphonenumber — not every ISO code has a dial code.

---

## TimezoneField

### Summary

Searchable IANA timezone picker with a **Gravity UI clock** icon on the trigger and in menu rows. Stores a single timezone identifier string.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField` |
| **State type** | `string\|null` — IANA timezone identifier |
| **FieldType** | — (use directly in Filament forms; not yet mapped in `FieldType`) |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

TimezoneField::make('timezone')
    ->label('Timezone')
    ->defaultTimezone('Europe/Warsaw')
    ->showOffset()
    ->required();

TimezoneField::make('user_timezone')
    ->timezones(['Europe/Warsaw', 'UTC', 'America/New_York'])
    ->browserTimezoneDefault()
    ->browserTimezoneSortFirst()
    ->prefixIcon(GravityIcon::Clock); // default when omitted
```

### State format

| Value | Description | Example |
|-------|-------------|---------|
| Selected timezone | IANA identifier | `Europe/Warsaw`, `UTC`, `America/New_York` |
| Empty | `null` when cleared or invalid | `null` |

On hydrate and dehydrate, `normalizeState()` trims and validates against the resolved timezone list. Invalid stored values fall back to `defaultTimezone()` when allowed, otherwise `null`.

### Validation

| Behaviour | Detail |
|-----------|--------|
| Built-in | Custom rule — submitted identifier must be in resolved list |
| `required()` | Value must not be blank |
| Filament `required` rule | Overridden to `nullable` — validation handled by custom rule |

### Configuration API

#### `variant(string|Closure $variant)`

Visual style shared with FlexTextInput. Values: `primary` (default), `secondary`, `flat`.

#### `size(string|ControlSize|Closure $size)`

Control height. See [Control size](#control-size). Default: `md`.

#### `defaultTimezone(string|Closure|null $timezone)`

IANA identifier when no value is selected. Falls back to first allowed timezone when the default is not in the list.

#### `timezones(array|Closure|null $timezones)`

Whitelist of IANA identifiers. `null` = full PHP `timezone_identifiers_list()` (~419 zones, minus `exceptTimezones`).

```php
->timezones(['Europe/Warsaw', 'UTC', 'America/Chicago'])
```

#### `exceptTimezones(array|Closure $timezones)`

Blacklist applied after the whitelist. Default: `[]`.

#### `searchable(bool|Closure $condition = true)`

Show search input in the dropdown. Default: `true`. Search matches identifier, region, and UTC offset.

#### `showOffset(bool|Closure $condition = true)`

Show UTC offset badge (e.g. `UTC+02:00`) in trigger and menu. Default: `true`. Offset reflects **current** DST rules.

#### `prefixIcon(string|BackedEnum|Htmlable|Closure|null $icon)`

Leading icon on trigger and menu rows. Default: `GravityIcon::Clock` (`gravityui-clock`).

```php
->prefixIcon('heroicon-o-clock')
```

#### `browserTimezoneDefault(bool|Closure $condition = true)`

When enabled and state is empty on hydrate, pre-select timezone from:

1. **Client:** `Intl.DateTimeFormat().resolvedOptions().timeZone` (Alpine on init)
2. **Server:** `config('app.timezone')` when not `UTC` and identifier is allowed

#### `browserTimezoneSortFirst(bool|Closure $condition = true)`

Sort timezone list with browser-detected timezone first.

#### `placeholder(string|Closure|null $placeholder)`

Inherited from Filament `HasPlaceholder`. Default translation: `filament-flex-fields::timezone.placeholder`.

#### `readOnly(bool|Closure $condition = true)`

Inherited from Filament `CanBeReadOnly`.

#### `focusOutline(bool|Closure $condition = true)`

Inherited from `HasFieldFocusOutline`. Default: `false`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved variant |
| `getAllowedTimezoneIdentifiers()` | `list<string>\|null` | Whitelist or `null` |
| `getExceptTimezoneIdentifiers()` | `list<string>` | Blacklist |
| `getResolvedTimezoneIdentifiers()` | `list<string>` | Effective allowed identifiers |
| `getDefaultTimezoneIdentifier()` | `string\|null` | Effective default |
| `isSearchable()` | `bool` | Search enabled |
| `shouldShowOffset()` | `bool` | Offset badge visible |
| `getPrefixIcon()` | `string\|BackedEnum\|Htmlable` | Resolved prefix icon |
| `shouldUseBrowserTimezoneDefault()` | `bool` | Browser timezone default |
| `shouldSortTimezonesByBrowserTimezone()` | `bool` | Browser timezone sort |
| `getBrowserTimezoneIdentifier()` | `string\|null` | Server-side detected timezone |
| `getTimezonesMetadata()` | `list<array>` | `id`, `label`, `offset`, `offset_seconds`, `region` |
| `getTimezoneSelectOptions()` | `array` | Options map for internal select |
| `normalizeState(mixed $state)` | `string\|null` | Canonical timezone identifier |
| `getWrapperClasses()` | `list<string>` | CSS class list |

### CSS classes

| Class | Role |
|-------|------|
| `fff-timezone-field` | Root wrapper |
| `fff-timezone-field--{sm\|md\|lg}` | Size modifier |
| `fff-timezone-field__trigger` | Picker button (clock icon + label) |
| `fff-timezone-field__icon` | Clock icon in trigger |
| `fff-timezone-field__offset` | UTC offset badge |
| `fff-timezone-field__menu` | Teleported dropdown (`is-positioned` when open) |
| `fff-timezone-field__search` | Dropdown search input |
| `fff-timezone-field__option` | Menu row |

Shares FlexTextInput shell classes (`fff-flex-text-input-field`, variant modifiers).

### Implementation notes

- Timezone list comes from PHP `timezone_identifiers_list()` via `Timezones` support class.
- Labels use format `{identifier} ({UTC±HH:MM})` — e.g. `Europe/Warsaw (UTC+02:00)`.
- Dropdown uses `x-teleport="body"` to avoid overflow clipping.
- SSR label is rendered server-side; Alpine `displayReady` swaps to live state without layout flash on reload.
- Browser timezone detection runs client-side on empty fields; server hydrate uses `config('app.timezone')` as a fallback when not in console.

---

## Date & time fields

Spectrum-style segmented date and time inputs powered by [`@internationalized/date`](https://react-spectrum.adobe.com/internationalized/date/). All variants share one Alpine component (`flex-date-time-field`), one Blade view, and the `InteractsWithDateTimeConfiguration` trait.

### Summary

| Component | Class | Mode | Calendar | Typical state |
|-----------|-------|------|----------|---------------|
| **FlexDateField** | `FlexDateField` | `date` | No | `string` — e.g. `2026-06-15` |
| **FlexDatePicker** | `FlexDatePicker` | `date` | Yes (popover) | `string` — e.g. `2026-06-15` |
| **FlexTimeField** | `FlexTimeField` | `time` | No | `string` — e.g. `14:30:00` |
| **FlexDateTimePicker** | `FlexDateTimePicker` | `dateTime` | Yes (popover) | `string` — e.g. `2026-06-15T14:30:00` |
| **FlexDateRangeField** | `FlexDateRangeField` | `dateRange` | Yes (range UI) | `array{start: string\|null, end: string\|null}` |
| **FlexDurationField** | `FlexDurationField` | `duration` | No | `string` — e.g. `02:30:00` |
| **FlexTimeRangeField** | `FlexTimeRangeField` | `timeRange` | No | `array{start: string\|null, end: string\|null}` |
| **FlexMonthPicker** | `FlexMonthPicker` | `month` | Yes (month grid) | `string` — e.g. `2026-06` |
| **FlexYearPicker** | `FlexYearPicker` | `year` | Yes (year grid) | `string` — e.g. `2026` |

| | |
|---|---|
| **Base class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimeField` (abstract) |
| **Granularity enum** | `Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity` — `Day`, `Hour`, `Minute`, `Second` |
| **Month display enum** | `Bjanczak\FilamentFlexFields\Enums\MonthDisplay` — `Numeric`, `Short`, `Long` |
| **FlexField `FieldType`** | `date` → `FlexDatePicker`, `time` → `FlexTimeField`, `date_time` → `FlexDateTimePicker`, `date_range` → `FlexDateRangeField`, `duration` → `FlexDurationField`, `time_range` → `FlexTimeRangeField`, `month` → `FlexMonthPicker`, `year` → `FlexYearPicker`, `timezone` → `TimezoneField` |

`FlexFieldFormBuilder::configureDateTimeField()` applies JSON config keys (`granularity`, `hour_cycle`, `show_seconds`, `min_value`/`min_date`, `max_value`/`max_date`, `display_format`, `storage_format`, `locale`, `time_zone`, `force_leading_zeros`, `hide_time_zone`, `hide_time_section`, `close_on_select`, `allow_same_day`, `range_separator`, `variant`, `size`, `first_day_of_week`, `unavailable_dates`, `show_year_segment`, `month_display`) to all date/time field types.

Use `FlexDateField::make()` explicitly when you need segmented date input **without** a calendar popover.

### Basic usage

#### FlexDateField — segmented date, no calendar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Carbon\Carbon;

FlexDateField::make('starts_on')
    ->label('Start date')
    ->required()
    ->default('2026-06-15')
    ->minValue(Carbon::today())
    ->withRecommendedDefaults();
```

#### FlexDatePicker — segmented date with calendar popover

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDatePicker;

FlexDatePicker::make('published_on')
    ->label('Publish date')
    ->highlightToday()
    ->closeOnSelect()
    ->withRecommendedDefaults();
```

#### FlexTimeField — segmented time (12h or 24h)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeField;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

// 12-hour (default recommended preset)
FlexTimeField::make('opens_at')
    ->label('Opening time')
    ->hourCycle(12)
    ->minValue('09:00')
    ->maxValue('18:00')
    ->withRecommendedDefaults();

// 24-hour with minute precision
FlexTimeField::make('starts_at')
    ->label('Start time')
    ->hourCycle(24)
    ->granularity(DateTimeGranularity::Minute)
    ->hideTimeZone()
    ->default('14:30');
```

#### FlexDateTimePicker — date + time with calendar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

FlexDateTimePicker::make('scheduled_at')
    ->label('Scheduled at')
    ->granularity(DateTimeGranularity::Minute)
    ->hourCycle(24)
    ->hideTimeZone()
    ->default('2026-06-15T14:30:00')
    ->withRecommendedDefaults();
```

#### FlexDateRangeField — start/end range with optional time under calendar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

FlexDateRangeField::make('booking_range')
    ->label('Booking period')
    ->granularity(DateTimeGranularity::Minute)
    ->allowSameDay(false)
    ->default([
        'start' => '2026-06-10T09:00:00',
        'end' => '2026-06-14T17:00:00',
    ])
    ->withRecommendedDefaults();
```

#### FlexMonthPicker — month + year with calendar

Calendar opens on the **years** grid; pick a year, then the **months** grid.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMonthPicker;

FlexMonthPicker::make('period')
    ->locale('pl_PL')
    ->withRecommendedDefaults();
```

#### FlexMonthPicker — month only (no year segment)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMonthPicker;
use Bjanczak\FilamentFlexFields\Enums\MonthDisplay;

FlexMonthPicker::make('season')
    ->showYearSegment(false)
    ->monthDisplay(MonthDisplay::Long)
    ->locale('pl_PL');
```

State is still stored as `Y-m` (e.g. `2026-06`); when the year segment is hidden, the current calendar year is used when composing the stored value.

#### FlexYearPicker — year with calendar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexYearPicker;

FlexYearPicker::make('fiscal_year')
    ->label('Fiscal year')
    ->default('2026')
    ->withRecommendedDefaults();
```

#### Full form example

```php
use Filament\Schemas\Components\Section;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDatePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeField;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

Section::make('Schedule')
    ->schema([
        FlexDateField::make('date')
            ->required()
            ->withRecommendedDefaults(),

        FlexDatePicker::make('date_with_calendar')
            ->withRecommendedDefaults(),

        FlexTimeField::make('time')
            ->hourCycle(24)
            ->withRecommendedDefaults(),

        FlexDateTimePicker::make('date_time')
            ->granularity(DateTimeGranularity::Minute)
            ->withRecommendedDefaults(),

        FlexDateRangeField::make('range')
            ->withRecommendedDefaults(),
    ]);
```

### State format

Values are **normalized on hydrate and dehydrate** via `DateTimeFieldValue` and the configured `storageFormat()`.

#### Single value fields (`FlexDateField`, `FlexDatePicker`, `FlexTimeField`, `FlexDateTimePicker`)

| Mode | Default storage format | Example stored value |
|------|------------------------|----------------------|
| Date | `Y-m-d` | `2026-06-15` |
| Time | `H:i` (or `H:i:s` with `showSeconds()`) | `14:30` / `14:30:00` |
| DateTime — Hour | `Y-m-d\TH:00:00` | `2026-06-15T14:00:00` |
| DateTime — Minute | `Y-m-d\TH:i:00` | `2026-06-15T14:30:00` |
| DateTime — Second | `Y-m-d\TH:i:s` | `2026-06-15T14:30:45` |
| Month (`FlexMonthPicker`) | `Y-m` | `2026-06` |
| Year (`FlexYearPicker`) | `Y` | `2026` |

`normalizeState()` accepts strings, numeric strings, and `CarbonInterface` instances. Invalid values become `null` and fail validation when the field is required or non-empty.

For `FlexMonthPicker` with `showYearSegment(false)`, storage format remains `Y-m`; the year defaults to the current calendar year when only the month segment is filled.

#### Range field (`FlexDateRangeField`)

```php
[
    'start' => '2026-06-10T09:00:00',
    'end' => '2026-06-14T17:00:00',
]
```

When `granularity` is `Day`, start/end are date-only strings (`Y-m-d`). With time granularity, ISO-like datetime strings are used (see defaults above).

### Display format vs storage format

Use **`displayFormat()`** for how values are formatted for display helpers / SSR `initialDisplay`, and **`storageFormat()`** for what is persisted in form state and the database.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;

FlexDateField::make('event_date')
    ->displayFormat('d/m/Y')       // display helper: 15/06/2026
    ->storageFormat('Y-m-d')       // database: 2026-06-15
    ->default('2026-06-15');

// Custom dotted storage
FlexDateField::make('invoice_date')
    ->storageFormat('d.m.Y')
    ->default('2026-06-15');
// normalizeState('2026-06-15') → '15.06.2026'
```

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

FlexDateTimePicker::make('logged_at')
    ->granularity(DateTimeGranularity::Second)
    ->showSeconds()
    ->displayFormat('d/m/Y H:i:s')
    ->storageFormat('Y-m-d H:i:s')
    ->default('2026-06-15T14:30:45');
```

**Segment order is locale-aware** via `DateTimeLocaleOrder` (PHP `IntlDateFormatter` + JS `Intl`). Examples:

| Locale | Date segment order | Example |
|--------|-------------------|---------|
| `pl_PL`, `en_GB`, `de_DE` | day-first | `dd · mm · yyyy` |
| `en_US` | month-first | `mm · dd · yyyy` |

Locale strings such as `pl_PL` are normalized to BCP 47 (`pl-PL`) for `Intl` in JS calendar labels and month display.

`displayFormat()` affects server-side display helpers only (`initialDisplay`, `formatForDisplay`) — it does **not** change segment order in the UI.

### Granularity and time precision

`granularity()` controls which time segments appear and how values are stored.

| `DateTimeGranularity` | Date segments | Time segments | Use case |
|----------------------|---------------|---------------|----------|
| `Day` | month, day, year | — | Date only |
| `Hour` | month, day, year | hour | Date + hour |
| `Minute` | month, day, year | hour, minute | Date + hour:minute (default for DateTime) |
| `Second` | month, day, year | hour, minute, second | Full precision |

```php
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

// Hour only (minutes zeroed in storage)
FlexDateTimePicker::make('slot')
    ->granularity(DateTimeGranularity::Hour)
    ->hourCycle(24)
    ->default('2026-06-15T08:00:00');

// Minute precision (recommended default for date/time)
FlexDateTimePicker::make('appointment')
    ->granularity(DateTimeGranularity::Minute);

// Seconds — use granularity Second or showSeconds() on time fields
FlexDateTimePicker::make('timestamp')
    ->granularity(DateTimeGranularity::Second)
    ->showSeconds()
    ->default('2026-06-15T14:30:45');

FlexTimeField::make('lap_time')
    ->granularity(DateTimeGranularity::Second)
    ->showSeconds()
    ->hourCycle(24)
    ->default('00:01:23');
```

For **range fields**, when `granularity` is not `Day` and `hideTimeSection()` is false, time rows appear under the calendar popover (start/end time segments).

For **date/time picker**, when time is enabled, a single **Time** row appears under the calendar for editing hour/minute/second without closing the popover (`closeOnSelect(false)` in recommended defaults).

### Validation

Built-in validation runs through a custom `rule()` on `FlexDateTimeField`. Filament's default `required` rule is overridden to `nullable` — emptiness and constraints are handled by the custom rule.

| Check | When | Message key |
|-------|------|-------------|
| Required | `required()` and empty state | `validation.required` |
| Invalid / unparsable | `normalizeState()` returns `null` | `date_time.validation.invalid` |
| Below minimum | `minValue()` | `date_time.validation.before_min` |
| Above maximum | `maxValue()` | `date_time.validation.after_max` |
| Unavailable date | `isDateUnavailable()` callback | `date_time.validation.unavailable` |
| Incomplete range | Missing `start` or `end` | `date_time.validation.incomplete_range` |
| Range order | `end` before `start` | `date_time.validation.range_order` |
| Same day | `allowSameDay(false)` and equal dates | `date_time.validation.same_day_not_allowed` |

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Carbon\Carbon;

FlexDateField::make('starts_on')
    ->required()
    ->minValue('2026-06-01')
    ->maxValue(Carbon::parse('2026-12-31'))
    ->isDateUnavailable(fn (Carbon $date) => $date->isWeekend());
```

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeField;

FlexTimeField::make('business_hours')
    ->hourCycle(24)
    ->minValue('09:00')
    ->maxValue('17:00')
    ->required();
```

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;

FlexDateRangeField::make('leave')
    ->required()
    ->allowSameDay(false)
    ->minValue('2026-01-01')
    ->maxValue('2026-12-31');
```

**Client-side:** calendar days outside `minValue` / `maxValue` are disabled. `isDateUnavailable()` is validated on submit but unavailable days are **not** yet disabled in the calendar UI.

On segment **blur**, client-side validation sets `segmentInvalid` when segments are incomplete or out of bounds. The message appears **below** the input shell (`.fff-date-time-field__segment-error`, `role="alert"`), not inside individual segments. Text comes from `config.segmentInvalidMessage` / translation key `date_time.validation.invalid`. Server-side validation (form submit) is unchanged.

### Preset bundle: `withRecommendedDefaults()`

Applies sensible defaults per mode:

| Mode | Preset |
|------|--------|
| Date | `granularity(Day)`, `closeOnSelect()`, `highlightToday()` |
| Time | `granularity(Minute)`, `hourCycle(12)`, `hideTimeZone()` |
| DateTime | `granularity(Minute)`, `hourCycle(24)`, `closeOnSelect(false)` |
| DateRange | `granularity(Day)`, `allowSameDay()`, `highlightToday()`, `closeOnSelect(false)` |
| Month | `closeOnSelect()`, `highlightToday()` |
| Year | `closeOnSelect()`, `highlightToday()` |

```php
FlexDatePicker::make('date')->withRecommendedDefaults();
FlexTimeField::make('time')->withRecommendedDefaults();
FlexDateTimePicker::make('date_time')->withRecommendedDefaults();
FlexDateRangeField::make('range')->withRecommendedDefaults();
FlexMonthPicker::make('period')->withRecommendedDefaults();
FlexYearPicker::make('year')->withRecommendedDefaults();
```

### Configuration API

All date/time components share these chainable methods (from `InteractsWithDateTimeConfiguration` + `HasControlSize` + `HasFieldFocusOutline`).

#### `variant(string|Closure $variant)`

Visual shell style. Values: `primary` (default), `secondary`, `flat`. Shared with FlexTextInput tokens.

```php
->variant('secondary')
->variant('flat')
```

#### `size(string|ControlSize|Closure $size)`

Control height. See [Control size](#control-size). Default: `md`.

```php
use Bjanczak\FilamentFlexFields\Enums\ControlSize;

->size('sm')
->size(ControlSize::Lg)
```

#### `granularity(DateTimeGranularity|string|Closure $granularity)`

Time precision. Default: `Day` for date/range date-only; use `Minute` or `Second` for datetime/time. See [Granularity and time precision](#granularity-and-time-precision).

```php
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

->granularity(DateTimeGranularity::Day)
->granularity(DateTimeGranularity::Hour)
->granularity(DateTimeGranularity::Minute)
->granularity(DateTimeGranularity::Second)
```

#### `locale(string|Closure|null $locale)`

BCP 47 locale for segment order, placeholders, separators, and calendar labels. Default: `app()->getLocale()`. Laravel-style tags (`pl_PL`) are normalized to BCP 47 (`pl-PL`) for JS `Intl` APIs. Segment order follows locale via `DateTimeLocaleOrder` — see [Display format vs storage format](#display-format-vs-storage-format).

```php
->locale('en_US')
->locale('pl_PL')
```

#### `timeZone(string|Closure|null $timeZone)`

IANA timezone identifier passed to Alpine (`config.timeZone`). Default: `config('app.timezone')`. Affects calendar "today" and datetime parsing context.

```php
->timeZone('Europe/Warsaw')
->timeZone('UTC')
```

#### `hourCycle(int|Closure $hourCycle)`

`12` or `24`. `12` adds an AM/PM (`dayPeriod`) segment on time and datetime fields. Invalid values throw `InvalidArgumentException`.

```php
->hourCycle(24)
->hourCycle(12)
```

#### `displayFormat(string|Closure|null $format)`

PHP date format for display helpers (`initialDisplay`, `formatForDisplay`). When omitted, mode-specific defaults apply (e.g. `m/d/Y`, `m/d/Y H:i:s`). Does not affect segment order — that is driven by `locale()`. See [Display format vs storage format](#display-format-vs-storage-format).

```php
->displayFormat('d/m/Y')
->displayFormat('Y-m-d H:i')
->displayFormat('m/d/Y g:i A')  // 12-hour display
```

#### `storageFormat(string|Closure|null $format)`

Format used when normalizing state for storage. When omitted, mode/granularity defaults apply. See [State format](#state-format).

```php
->storageFormat('Y-m-d')
->storageFormat('d.m.Y')
->storageFormat('Y-m-d H:i:s')
->storageFormat('H:i:s')
```

#### `forceLeadingZeros(bool|Closure $condition = true)`

Pad segment values with leading zeros (`06` vs `6`). Default: `true`.

```php
->forceLeadingZeros()
->forceLeadingZeros(false)
```

#### `showYearSegment(bool|Closure $condition = true)`

Default: `true`. When `false`, only the month segment is shown (no year segment). Used by `FlexMonthPicker` for month-only fields. Storage format remains `Y-m`; the current calendar year is used when composing the stored value from a month-only segment.

```php
FlexMonthPicker::make('season')
    ->showYearSegment(false)
    ->monthDisplay(MonthDisplay::Long)
    ->locale('pl_PL');
```

#### `monthDisplay(MonthDisplay|string|Closure $display)`

Controls how the month segment is rendered. Enum `Bjanczak\FilamentFlexFields\Enums\MonthDisplay`:

| Value | Segment display | Example (`pl_PL`) |
|-------|-----------------|-------------------|
| `Numeric` (default) | zero-padded number | `06` |
| `Short` | abbreviated name | `cze` |
| `Long` | full name | `czerwiec` |

Works on any field with a month segment (date, datetime, month picker). Textual months are left-aligned with width fitted to content (`field-sizing: content`); CSS variable `--fff-date-time-month-ch` sets character width. The calendar month grid always uses **short** month labels regardless of `monthDisplay()`.

```php
use Bjanczak\FilamentFlexFields\Enums\MonthDisplay;

FlexMonthPicker::make('billing_month')
    ->showYearSegment(false)
    ->monthDisplay(MonthDisplay::Long)
    ->locale('pl_PL');

FlexDatePicker::make('event_date')
    ->locale('pl_PL')
    ->monthDisplay(MonthDisplay::Long);
```

#### `minValue(string|CarbonInterface|Closure|null $value)`

Minimum allowed value. Accepts date/time strings or `Carbon` instances. Enforced on submit; calendar disables earlier **days** (date portion).

```php
use Carbon\Carbon;

->minValue('2026-06-01')
->minValue(Carbon::today())
->minValue('09:00')  // time fields
```

Alias for Filament-style naming (identical to `minValue()`):

```php
->minDate(now()->subYears(150))
->minDate(Carbon::today())
```

#### `maxValue(string|CarbonInterface|Closure|null $value)`

Maximum allowed value. Same types as `minValue()`.

```php
->maxValue('2026-12-31')
->maxValue(Carbon::parse('2026-12-31'))
->maxValue('18:00')
```

Alias for Filament-style naming (identical to `maxValue()`):

```php
->maxDate(now())
->maxDate(Carbon::parse('2026-12-31'))
```

#### Example: date of birth bounds

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDatePicker;

FlexDatePicker::make('birth_date')
    ->minDate(now()->subYears(150))
    ->maxDate(now())
    ->required();
```

#### `isDateUnavailable(Closure $callback)`

Mark specific dates as invalid. Callback receives `Carbon` at start of day; return `true` to reject.

```php
use Carbon\Carbon;

->isDateUnavailable(fn (Carbon $date) => $date->isWeekend())
->isDateUnavailable(fn (Carbon $date) => $date->isPast())
->isDateUnavailable(function (Carbon $date): bool {
    return in_array($date->format('Y-m-d'), ['2026-12-24', '2026-12-25'], true);
})
```

#### `rangeSeparator(string|Closure $separator)`

Text between start and end segments in range fields. Default: `' - '`.

```php
->rangeSeparator(' – ')
->rangeSeparator(' to ')
```

#### `allowSameDay(bool|Closure $condition = true)`

Whether start and end can be the same calendar day in range mode. Default: `true`. `FlexDateRangeField` recommended preset keeps `true`; set `false` for multi-day-only ranges.

```php
->allowSameDay()
->allowSameDay(false)
```

#### `highlightToday(bool|Closure $condition = true)`

Show a dot on today's date in the calendar. Default: `true`.

```php
->highlightToday()
->highlightToday(false)
```

#### `showCalendar(bool|Closure $condition)` / `showCalendarButton(bool|Closure $condition)`

Enable calendar popover and trailing calendar trigger button. Set automatically per component class (`FlexDatePicker`, `FlexDateTimePicker`, `FlexDateRangeField`, `FlexMonthPicker`, `FlexYearPicker` = on; `FlexDateField`, `FlexTimeField` = off).

```php
// Rare: override on a date field subclass
FlexDateField::make('custom')
    ->showCalendar(true)
    ->showCalendarButton(true);
```

#### `closeOnSelect(bool|Closure $condition = true)`

Close calendar popover after selecting a date. Default: `true` for date picker, `false` for date/time and range (recommended) so users can adjust time under the calendar.

```php
->closeOnSelect()       // close immediately
->closeOnSelect(false)  // keep open for time editing
```

#### `firstDayOfWeek(int|Closure $day)`

Week start for calendar grid. `0` = Sunday, `1` = Monday, … `6` = Saturday. Default: `0`.

```php
->firstDayOfWeek(1)  // Monday-first (EU)
```

#### `hideTimeZone(bool|Closure $condition = true)`

Hide timezone label segment on time-only fields. Default: `false`; recommended time preset enables it.

```php
->hideTimeZone()
```

#### `hideTimeSection(bool|Closure $condition = true)`

Hide time rows under the calendar (range / date-time). When hidden, only date segments are used in the main input.

```php
FlexDateRangeField::make('nights')
    ->granularity(DateTimeGranularity::Day)
    ->hideTimeSection();
```

#### `showSeconds(bool|Closure $condition = true)`

Include second segment on time/datetime fields. Implied when `granularity(Second)`.

```php
->showSeconds()
->granularity(DateTimeGranularity::Second)
    ->showSeconds()
```

#### `withRecommendedDefaults()`

Mode-specific preset bundle. See [Preset bundle](#preset-bundle-withrecommendeddefaults).

#### `focusOutline(bool|Closure $condition = true)`

Inherited from `HasFieldFocusOutline`. Show focus ring on the outer shell.

```php
->focusOutline()
```

#### Inherited Filament `Field` API

`label()`, `helperText()`, `hint()`, `placeholder()`, `required()`, `disabled()`, `readOnly()`, `default()`, `live()`, `dehydrated()`, `hidden()`, `visible()`, `rule()`, `rules()`, `afterStateUpdated()` — all work as usual. See [Inherited Filament field API](#inherited-filament-field-api).

Default placeholders (translation keys under `filament-flex-fields::default.date_time`):

| Mode | Key |
|------|-----|
| Date | `placeholder_date` |
| Time | `placeholder_time` |
| DateTime | `placeholder_date_time` |
| DateRange | `placeholder_date_range` |

### Recipe examples

#### Booking form with bounds and blocked weekends

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;
use Carbon\Carbon;

FlexDateRangeField::make('stay')
    ->label('Stay dates')
    ->required()
    ->granularity(DateTimeGranularity::Day)
    ->minValue(Carbon::today())
    ->maxValue(Carbon::today()->addYear())
    ->isDateUnavailable(fn (Carbon $date) => $date->isWeekend())
    ->allowSameDay(false)
    ->rangeSeparator(' – ')
    ->withRecommendedDefaults();
```

#### Event scheduling with 12-hour clock and seconds

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

FlexDateTimePicker::make('starts_at')
    ->label('Event start')
    ->granularity(DateTimeGranularity::Second)
    ->showSeconds()
    ->hourCycle(12)
    ->default('2026-07-04T18:30:00')
    ->minValue('2026-01-01')
    ->closeOnSelect(false)
    ->size('lg');
```

#### EU display, ISO storage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Carbon\Carbon;

FlexDateField::make('birth_date')
    ->label('Date of birth')
    ->displayFormat('d.m.Y')
    ->storageFormat('Y-m-d')
    ->maxValue(Carbon::today())
    ->forceLeadingZeros()
    ->required();
```

#### Dynamic min date from another field (Closure)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Carbon\Carbon;
use Filament\Schemas\Components\Utilities\Get;

FlexDateField::make('ends_on')
    ->minValue(fn (Get $get): string => $get('starts_on') ?? '2026-01-01')
    ->required();
```

#### Read-only review field

```php
FlexDateTimePicker::make('confirmed_at')
    ->default('2026-06-15T10:00:00')
    ->disabled()
    ->hideTimeZone();
```

#### Custom validation rule alongside built-in

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Carbon\Carbon;

FlexDateField::make('cutoff')
    ->required()
    ->rule('after:today')
    ->minValue(Carbon::today());
```

#### Hydrate from Eloquent datetime cast

```php
// Model: protected $casts = ['scheduled_at' => 'datetime'];

FlexDateTimePicker::make('scheduled_at')
    ->granularity(DateTimeGranularity::Minute)
    ->withRecommendedDefaults();
// Carbon from model is normalized to storage format on dehydrate
```

#### Store only date in MySQL `DATE` column

```php
FlexDateField::make('published_on')
    ->storageFormat('Y-m-d')
    ->required();

// In migration: $table->date('published_on');
```

#### Range with per-day time windows under calendar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

FlexDateRangeField::make('rental')
    ->label('Rental period')
    ->granularity(DateTimeGranularity::Minute)
    ->default([
        'start' => '2026-06-10T09:30:00',
        'end' => '2026-06-14T17:00:00',
    ])
    ->closeOnSelect(false)
    ->withRecommendedDefaults();
```

### Calendar UX

Components with `showCalendar()` expose a teleported popover calendar:

- **Month navigation** — chevron previous/next; header label click drills down: days → months → years (Spectrum-style).
- **FlexMonthPicker (with year segment)** — opens on the **years** grid; pick a year → **months** grid.
- **FlexMonthPicker with `showYearSegment(false)`** — opens directly on the **months** grid (no year selection step).
- **FlexYearPicker** — year grid only.
- **Locale** — calendar labels use the configured locale; month names in the grid are always short form.
- **Range selection** — continuous pill highlight between start and end; hover preview while selecting end.
- **Today** — optional dot via `highlightToday()`.
- **Primary color** — selected day uses theme `--primary-500`.
- **Time under calendar** — when time granularity is enabled, editable time segments appear below the grid (single row for `FlexDateTimePicker`, start/end rows for `FlexDateRangeField`).

### FlexField schema config

When using `FlexFieldFormBuilder`, these `FieldType` values map to components with `withRecommendedDefaults()`:

| `FieldType` | Component |
|-------------|-----------|
| `date` | `FlexDateField` |
| `time` | `FlexTimeField` |
| `date_time` | `FlexDateTimePicker` |
| `date_range` | `FlexDateRangeField` |
| `month` | `FlexMonthPicker` |
| `year` | `FlexYearPicker` |

```php
use Bjanczak\FilamentFlexFields\Enums\FieldType;

// FlexField definition config (snake_case keys map to chainable methods when wired)
[
    'type' => FieldType::DateTime,
    'name' => 'scheduled_at',
    'label' => 'Scheduled at',
    'config' => [
        'granularity' => 'minute',
        'hour_cycle' => 24,
        'min_value' => '2026-01-01',
        'storage_format' => 'Y-m-d H:i:s',
    ],
]

// Month picker with textual month display
[
    'type' => FieldType::Month,
    'name' => 'billing_month',
    'label' => 'Billing month',
    'config' => [
        'locale' => 'pl_PL',
        'show_year_segment' => false,
        'month_display' => 'long',
    ],
]

// Year picker
[
    'type' => FieldType::Year,
    'name' => 'fiscal_year',
    'label' => 'Fiscal year',
    'config' => [
        'min_value' => '2020',
        'max_value' => '2030',
    ],
]
```

Configure fields manually in Filament schemas for full API access (`FlexDatePicker`, custom `displayFormat`, etc.).

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getMode()` | `DateTimeFieldMode` | `date`, `time`, `dateTime`, `dateRange`, `month`, `year`, `duration`, `timeRange` |
| `getVariant()` | `string` | `primary`, `secondary`, `flat` |
| `getGranularity()` | `DateTimeGranularity` | Resolved granularity |
| `getLocale()` | `string` | Effective locale |
| `getTimeZone()` | `string` | IANA timezone |
| `getHourCycle()` | `int` | `12` or `24` |
| `getDisplayFormat()` | `string` | Resolved display format |
| `getStorageFormat()` | `string` | Resolved storage format |
| `shouldForceLeadingZeros()` | `bool` | Leading zero padding |
| `getMinValue()` / `getMaxValue()` | mixed | Raw configured bounds |
| `getRangeSeparator()` | `string` | Range text separator |
| `shouldAllowSameDay()` | `bool` | Same-day range allowed |
| `shouldHighlightToday()` | `bool` | Today dot in calendar |
| `shouldShowCalendar()` | `bool` | Calendar popover enabled |
| `shouldShowCalendarButton()` | `bool` | Trailing calendar button |
| `shouldCloseOnSelect()` | `bool` | Auto-close on date pick |
| `getFirstDayOfWeek()` | `int` | Calendar week start |
| `shouldHideTimeZone()` | `bool` | Timezone segment hidden |
| `shouldHideTimeSection()` | `bool` | Time rows under calendar hidden |
| `shouldShowSeconds()` | `bool` | Second segment visible |
| `shouldShowYearSegment()` | `bool` | Year segment visible (month picker) |
| `getMonthDisplay()` | `MonthDisplay` | Month segment display mode |
| `normalizeState(mixed $state)` | `string\|array\|null` | Canonical stored value |
| `isEmptyState(mixed $state)` | `bool` | Whether value is considered empty |
| `getAlpineConfiguration()` | `array` | Full Alpine bootstrap payload |
| `getViewSegments()` | `array` | SSR segment hydration |
| `getWrapperClasses()` | `list<string>` | CSS class list |

### CSS classes

| Class | Role |
|-------|------|
| `fff-date-time-field` | Root wrapper |
| `fff-date-time-field--{date\|time\|dateTime\|dateRange\|month\|year}` | Mode modifier |
| `fff-date-time-field--{sm\|md\|lg}` | Size modifier |
| `fff-date-time-field--{primary\|secondary\|flat}` | Variant modifier |
| `fff-date-time-field--show-seconds` | Wider shell when seconds visible |
| `fff-date-time-field--textual-month` | Month segment uses short/long text (not numeric) |
| `fff-date-time-field--textual-month-short` | Short month labels in segment (`cze`) |
| `fff-date-time-field--textual-month-long` | Long month labels in segment (`czerwiec`) |
| `fff-date-time-field__shell` | Pill input border (FlexTextInput shell) |
| `fff-date-time-field__segments` | Segment group |
| `fff-date-time-field__segment` | Individual segment input |
| `fff-date-time-field__segment-error` | Client-side validation message below shell (`role="alert"`) |
| `fff-date-time-field__suffix` | Calendar button column |
| `fff-date-time-field__calendar` | Teleported popover (`is-positioned` when placed) |
| `fff-date-time-field__day.is-selected` | Selected calendar day (primary background) |
| `fff-date-time-field__time-rows` | Time editor under calendar |

Textual month segments set CSS variable `--fff-date-time-month-ch` on the root wrapper for character-width sizing.

Segment focus uses theme primary (`--primary-500`) for active background and ring.

### Implementation notes

- Client logic: `resources/js/components/flex-date-time-field.js` (Alpine), bundled to `resources/dist/components/flex-date-time-field.js`.
- Date math: `@internationalized/date` via `resources/js/core/date-time/`.
- SSR segment values in Blade prevent layout shift on first paint; Alpine hydrates from `initialSegments`.
- Playground section **Date & time fields** in `DateTimeFieldPlayground` demonstrates all variants, configuration presets, and bounds/format examples.
- Playground section **Month display** in `DateTimeFieldPlayground` demonstrates `monthDisplay()` (numeric, short, long) across month picker, date, and datetime fields, plus `showYearSegment(false)` month-only mode.
- Rebuild assets after CSS/JS changes — see [Assets & playground](#assets--playground).

---

## FlexVerificationCode

### Summary

OTP / verification code input with grouped digit boxes, paste support, optional auto-submit, loading indicator, and optional account-verify chrome (heading, description, footer link action).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode` |
| **State type** | `string` — normalized code (no separators) |
| **FieldType** | `verification_code` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;

FlexVerificationCode::make('otp')
    ->label('Verification code')
    ->length(6)
    ->groups([3, 3])
    ->groupSeparator('-')
    ->required();

FlexVerificationCode::make('backup_code')
    ->length(8)
    ->allowedCharacters('alphanumeric')
    ->autoSubmit()
    ->autoSubmitMethod('verifyOtp')
    ->loading();
```

Server-side callback on complete code:

```php
FlexVerificationCode::make('code')
    ->submitUsing(function (string $code, $livewire) {
        $livewire->verifyCode($code);
    });
```

Account verification layout (heading, masked destination, resend link):

```php
use Filament\Actions\Action;

FlexVerificationCode::make('otp')
    ->hiddenLabel()
    ->heading('Verify account')
    ->description("We've sent a code to a****@gmail.com")
    ->footer("Didn't receive a code?")
    ->footerAction(
        Action::make('resend')
            ->label('Resend')
            ->link()
            ->action(fn () => /* resend logic */),
    )
    ->length(6)
    ->groups([3, 3])
    ->groupSeparator('-');
```

Shorthand footer action (default **Resend** link label from translations):

```php
FlexVerificationCode::make('otp')
    ->footerAction(fn () => $livewire->resendCode());
```

Pair `hiddenLabel()` with `heading()` so the visible title replaces the standard Filament field label. When a heading is set, it is also used as the digit group `aria-label`.

### State format

Normalized uppercase alphanumeric string of exactly `length()` characters. Separators are display-only.

Default schema config uses `groups: [3, 3]` and `group_separator: '-'` for `123-456` layout.

### Validation

| Check | Detail |
|-------|--------|
| `required()` | Non-empty after normalize |
| Characters | Must match `allowedCharacters` pattern |
| Length | Exactly `length()` when non-empty |

### Configuration API

#### `length(int|Closure $length)`

Total characters `1`–`16`. Default: `6`.

#### `groups(array|Closure|null $groups)` / `groupSizes()`

Group sizes summing to `length()`. `null` = single group. Default in schema: `[3, 3]`.

#### `groupSeparator(string|Closure|null $separator)`

Visual separator between groups (e.g. `-`). Display only.

#### `allowedCharacters(string|Closure $allowedCharacters)`

`numeric` (default) or `alphanumeric`.

#### `color(string|Closure|null $color)`

Filament accent. Default: `primary`.

#### `size(string|ControlSize|Closure $size)`

`sm`, `md`, `lg`. Default: `md`.

#### `autoSubmit(bool|Closure $condition = true)`

Submit when all digits filled.

#### `autoSubmitMethod(string|Closure|null $method)`

Livewire method name; receives normalized code as first argument. Enables `autoSubmit(true)`.

#### `submitUsing(Closure $callback)`

PHP callback with `$state` / `$code` injection. Enables `live(debounce: 250)` if not already live.

#### `loading(bool|Closure $condition = true)` / `validating()`

Spinner during Livewire requests. `validating()` is an alias.

#### `heading(string|Htmlable|Closure|null $heading)`

Optional title above the digit inputs (e.g. **Verify account**). Use with `hiddenLabel()` when the heading should be the primary visible title.

#### `description(string|Htmlable|Closure|null $description)`

Optional supporting copy below the heading (e.g. masked e-mail or phone destination).

#### `footer(string|Htmlable|Closure|null $footer)`

Optional muted text before the footer action (e.g. **Didn't receive a code?**). Translation key: `filament-flex-fields::default.verification_code.footer_prompt`.

#### `footerAction(Action|Closure|null $action)`

Register a **link-style** Filament action beside the footer text (e.g. **Resend**). Pass a `Closure` to create a default link action named `{field}-footer-action` with label `filament-flex-fields::default.verification_code.resend`. Non-link actions passed to this method are automatically converted with `->link()`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getLength()` | `int` | Code length |
| `getResolvedGroups()` | `list<int>` | Group sizes |
| `getGroupSeparator()` | `string\|null` | Separator character |
| `shouldShowSeparators()` | `bool` | Multiple groups |
| `getAllowedCharacters()` | `string` | `numeric` or `alphanumeric` |
| `isNumeric()` | `bool` | Numeric mode |
| `getColor()` | `string\|null` | Accent color |
| `shouldAutoSubmit()` | `bool` | Auto submit on |
| `getAutoSubmitMethod()` | `string\|null` | Livewire method |
| `shouldAutoSubmitUsingServerCallback()` | `bool` | `submitUsing` active |
| `shouldShowLoadingIndicator()` | `bool` | Loading spinner |
| `getLoadingWireTargets()` | `string` | `wire:loading` targets |
| `getInputMode()` | `string` | `numeric` or `text` |
| `getValidationPattern()` | `string` | Full-code regex |
| `getInputValidationPattern()` | `string` | Partial input regex |
| `normalizeState(string $state)` | `string` | Filtered code |
| `getDigitAriaLabel(int $index)` | `string` | Per-digit aria label |
| `getWrapperClasses()` | `list<string>` | `fff-verification-code` |
| `getHeading()` | `string\|Htmlable\|null` | Heading copy |
| `getDescription()` | `string\|Htmlable\|null` | Description copy |
| `getFooter()` | `string\|Htmlable\|null` | Footer prompt copy |
| `getFooterAction()` | `Action\|null` | Registered footer link action |
| `hasHeaderContent()` | `bool` | Heading or description present |
| `hasFooterContent()` | `bool` | Footer text or action present |
| `hasFooterAction()` | `bool` | Footer action registered |
| `hasLayoutChrome()` | `bool` | Any heading/description/footer chrome |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `length` | `length()` |
| `groups` | `groups()` |
| `group_separator` | `groupSeparator()` |
| `allowed_characters` | `allowedCharacters()` |
| `size` | `size()` |
| `color` | `color()` |
| `auto_submit` | `autoSubmit()` |
| `auto_submit_method` | `autoSubmitMethod()` |
| `loading` | `loading()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-verification-code-layout` | Vertical stack when heading/description/footer chrome is present |
| `fff-verification-code-layout__header` | Heading + description block |
| `fff-verification-code-layout__heading` | Title (e.g. Verify account) |
| `fff-verification-code-layout__description` | Supporting copy |
| `fff-verification-code-layout__footer` | Footer prompt + link action row |
| `fff-verification-code-layout__footer-text` | Muted footer prompt |
| `fff-verification-code-layout__footer-action` | Filament link action wrapper |
| `fff-verification-code-shell` | Input row + loading spinner |
| `fff-verification-code` | Digit inputs root |
| `fff-verification-code--{sm\|md\|lg}` | Size modifier |
| `fff-verification-code__input` | Single digit cell |
| `fff-verification-code__separator` | Group separator |

### Implementation notes

- Paste distributes characters across cells; non-allowed characters are stripped.
- Alphanumeric mode uppercases letters in `normalizeState()`.
- Playground section **Verification Code** demonstrates sizes, auto-submit, and the **Verify account** heading/footer/resend layout.
- Footer actions use Filament `Action` with `->link()` styling (`.fi-ac-link-action`).

---

## AudioField

### Summary

Compact audio player with waveform visualization, play/pause control, and optional loop. State is typically a URL string.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField` |
| **State type** | `string\|null` — audio URL (when not using static `src()`) |
| **FieldType** | `audio` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField;

AudioField::make('preview_url')
    ->label('Voice message')
    ->fullWidth()
    ->loop();

AudioField::make('jingle')
    ->src('/audio/notification.mp3')
    ->waveform([20, 45, 80, 60, 35, 90, 50, 30])
    ->size('lg');
```

Display-only (fixed source, state ignored):

```php
AudioField::make('demo_track')
    ->src('https://cdn.example.com/demo.mp3')
    ->readOnly();
```

### State format

| Mode | Behaviour |
|------|-----------|
| Default | Form state string = audio URL |
| `src()` set | Static URL; `resolveAudioSrc()` prefers `src()` over state |
| Empty | No playback; placeholder waveform shown |

### Validation

| Rule | Detail |
|------|--------|
| `nullable` | State may be empty |
| `string` | State must be string when present |

### Configuration API

#### `src(string|Closure|null $src)`

Fixed audio URL. When set, overrides state for playback.

#### `fullWidth(bool|Closure $condition = true)`

Stretch player to container width. Default: `false`.

#### `loop(bool|Closure $condition = true)`

Loop playback. Default: `false`.

#### `waveform(array|Closure|null $waveform)`

Custom peak heights `8`–`100`. When omitted, waveform is generated from URL fingerprint or placeholder.

```php
->waveform([30, 70, 45, 90, 55, 40, 75, 50])
```

#### `playIcon()` / `pauseIcon()`

Custom play/pause icons.

#### `size(string|ControlSize|Closure $size)`

`sm`, `md`, `lg`. Default: `md`.

#### `readOnly(bool|Closure $condition = true)`

Disable interaction.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getSrc()` | `string\|null` | Static src |
| `isFullWidth()` | `bool` | Full width layout |
| `shouldLoop()` | `bool` | Loop enabled |
| `resolveAudioSrc(mixed $state)` | `string\|null` | Effective URL |
| `hasCustomWaveform()` | `bool` | Custom peaks configured |
| `getWaveform()` | `list<int>` | Normalized peaks |
| `resolveWaveform(mixed $state)` | `list<int>` | Peaks for display |
| `getPlayIcon()` / `getPauseIcon()` | `string\|BackedEnum\|Htmlable` | Icons |
| `getWrapperClasses()` | `list<string>` | `fff-audio-field-field` |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `full_width` | `fullWidth()` |
| `src` | `src()` |
| `loop` | `loop()` |
| `waveform` | `waveform()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-audio-field-field` | Root wrapper |
| `fff-audio-field-field--{sm\|md\|lg}` | Size modifier |
| `fff-audio-field__waveform` | Waveform bars |
| `fff-audio-field__play` | Play/pause button |

### Implementation notes

- Waveform without custom peaks uses `AudioWaveformGenerator::fromFingerprint($url)` for stable visuals per URL.
- Non-numeric waveform values throw `InvalidArgumentException`.

---

## VoiceNoteRecorderField

### Summary

In-browser voice recorder with real-time frequency visualizer, inline playback (waveform + play/pause), and Filament `FileUpload` storage integration. Records audio from the microphone, previews locally, then uploads to Livewire temporary storage and persists to disk on form save.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField` |
| **State type** | `string\|null` — stored path on disk after save; keyed `TemporaryUploadedFile` object during upload |
| **FieldType** | — (use the PHP class directly; not mapped via `FieldType`) |
| **Extends** | `FlexFileUpload` → `Filament\Forms\Components\FileUpload` |

### Basic usage

#### Default — upload on form submit

Recording stays in the browser until the form is submitted. A loader is shown while the file uploads before save.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;

VoiceNoteRecorderField::make('voice_note')
    ->label('Voice message')
    ->required()
    ->disk('public')
    ->directory('voice-notes')
    ->maxDuration(120);
```

#### Immediate upload after recording

Uploads to Livewire temporary storage right after recording stops. Delete removes the file from storage (when `deleteFileOnRemove()` is enabled — default in `setUp()`).

```php
VoiceNoteRecorderField::make('voice_note')
    ->uploadImmediately()
    ->disk('public')
    ->directory('voice-notes');
```

Equivalent explicit defer:

```php
VoiceNoteRecorderField::make('voice_note')
    ->uploadOnSubmit(); // default behaviour
```

### Upload flow

| Stage | What happens |
|-------|----------------|
| **Record** | Audio captured in the browser (`MediaRecorder`); playback uses a local blob URL |
| **JS upload** | `$wire.upload('{statePath}.{uuid}', file)` — file lands in **Livewire temp** (`config/livewire.php` → `temporary_file_upload`) |
| **Form save** | Filament `beforeStateDehydrated` → `saveUploadedFiles()` moves/stores the file to **`disk()` + `directory()`** |
| **Persisted state** | Relative path string, e.g. `voice-notes/01H….webm` |

**Temporary path** is configured **globally** for all Livewire uploads (not per field):

```php
// config/livewire.php
'temporary_file_upload' => [
    'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
    'directory' => 'livewire-tmp', // default when null
],
```

**Final path** is configured **per field** with inherited `FileUpload` API:

```php
->disk('public')
->directory('voice-notes/recordings')
->visibility('public')
->moveFiles() // optional: move instead of copy when temp and target share the same disk
```

### State format

| Phase | State shape | Example |
|-------|-------------|---------|
| Empty | `null` | — |
| After JS upload (before save) | Associative array keyed by UUID | `['a1b2…' => TemporaryUploadedFile]` |
| After form dehydrate / save | `string` — path on `disk()` | `'voice-notes/01H….webm'` |
| Existing record (edit) | `string` path | Loaded for playback via `getInitialAudioUrl()` |

### Validation

| Rule | Detail |
|------|--------|
| `required()` | Recording must be present before submit |
| Inherited `FileUpload` | `maxSize()`, `acceptedFileTypes()`, etc. |

Default accepted MIME types (set in `setUp()`): `audio/*`, `audio/mpeg`, `audio/wav`, `audio/webm`, `audio/ogg`, `audio/x-m4a`, `audio/aac`.

### Configuration API

#### `maxDuration(int|Closure $seconds)`

Maximum recording length in seconds. Timer stops recording automatically. Default: `120` (2 minutes).

```php
->maxDuration(30)
```

#### `uploadImmediately(bool|Closure $condition = true)`

Upload to Livewire temp storage immediately after recording. Playback stays visible; background upload progress is shown on the pill.

#### `uploadOnSubmit(bool|Closure $condition = true)`

Defer upload until form submit (default). Shows “Preparing voice note for save…” while uploading on submit.

#### `shouldUploadImmediately(): bool`

Whether immediate upload is active (used by the Blade/Alpine view).

#### Icon overrides

| Method | Default (Gravity UI) | Config key (`filament-flex-fields.ui`) |
|--------|----------------------|----------------------------------------|
| `playIcon()` | `PlayFill` | `audio_play_icon` |
| `pauseIcon()` | `PauseFill` | `audio_pause_icon` |
| `microphoneIcon()` | `Microphone` | `microphone_icon` |
| `stopIcon()` | `Minus` | `stop_icon` |
| `trashIcon()` | `TrashBin` | `trash_icon` |
| `checkmarkIcon()` | `Check` | `checkmark_icon` |

```php
VoiceNoteRecorderField::make('voice_note')
    ->microphoneIcon('heroicon-o-microphone')
    ->maxDuration(60)
    ->size('lg');
```

#### `size(string|ControlSize|Closure $size)`

`sm`, `md`, `lg`. Inherited from `FlexFileUpload` / `HasControlSize`.

### Inherited FileUpload API

`VoiceNoteRecorderField` uses the standard Filament file upload pipeline (not FilePond UI). Common options:

| Method | Description |
|--------|-------------|
| `disk(string\|Closure\|null $name)` | Target filesystem disk for persisted files |
| `directory(string\|Closure\|null $directory)` | Subdirectory on that disk |
| `visibility(string\|Closure\|null $visibility)` | `public` or `private` |
| `maxSize(int\|Closure\|null $size)` | Max file size in KB |
| `required()` / `nullable()` | Validation |
| `deleteFileOnRemove()` | Remove file from disk when user deletes recording (enabled by default) |
| `storeFileNamesIn(string\|Closure\|null $path)` | Sibling state for original filenames |
| `preserveFilenames()` / `moveFiles()` | See [FlexFileUpload](#flexfileupload--fleximageupload) |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getMaxDuration()` | `int` | Max recording seconds |
| `shouldUploadImmediately()` | `bool` | Immediate vs deferred upload |
| `getInitialAudioUrl()` | `string\|null` | Public URL for existing persisted file (edit forms) |
| `getPlayIcon()` / `getPauseIcon()` / … | `string\|BackedEnum\|Htmlable` | Resolved control icons |

### CSS classes

| Class | Role |
|-------|------|
| `fff-voice-recorder` | Root wrapper |
| `fff-voice-recorder--{sm\|md\|lg}` | Size modifier |
| `fff-voice-recorder__record-btn` | Start recording |
| `fff-voice-recorder__recording` | Active recording UI + canvas visualizer |
| `fff-voice-recorder__playback-pill` | Playback bar (play, waveform, time, delete) |
| `fff-voice-recorder__waveform` | Scrubbable waveform bars |
| `fff-voice-recorder__container.is-submitting` | Deferred upload in progress on submit |

Alpine component: `voice-note-recorder-field` (built to `resources/dist/components/voice-note-recorder-field.js`).

### Playground

Registered under **Audio field** playground (`AudioFieldPlayground`):

| Variant key | Description |
|-------------|-------------|
| `voice_note__basic` | Default recorder, deferred upload |
| `voice_note__sm` / `voice_note__lg` | Size variants |
| `voice_note__with_limit` | `maxDuration(30)` |
| `voice_note__immediate` | `uploadImmediately()` |

Use **Dump JSON** in the playground to inspect temp upload state (`livewire-file:…`). Permanent disk storage requires a real form save (playground has no save action).

### Implementation notes

- Requires **microphone permission** and a browser with `MediaRecorder` (Chrome, Safari, Firefox).
- Prefers `audio/mp4` when supported (Safari), falls back to `audio/webm` / `audio/ogg`.
- Local playback uses blob URLs; duration falls back to measured recording time when WebM metadata is missing.
- Deferred upload hooks the parent `<form>` `submit` event (capture phase), uploads via Livewire, then calls `form.requestSubmit()`.
- Delete calls `removeUploadedFile` (temp) or `deleteUploadedFile` (persisted) via `callSchemaComponentMethod` when `schemaComponentKey` is available.
- Translations: `filament-flex-fields::default.audio.*` (`record_label`, `uploading_on_submit`, etc.).

---

## VideoField

### Summary

Custom video player with optional YouTube embed, skip controls, PiP, fullscreen, and compact control layout.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\VideoField` |
| **State type** | `string\|null` — video URL or YouTube link |
| **FieldType** | `video` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VideoField;

VideoField::make('trailer_url')
    ->label('Trailer')
    ->ratio('16:9')
    ->title('Product trailer')
    ->controls()
    ->allowYoutube();

VideoField::make('tutorial')
    ->src('https://cdn.example.com/tutorial.mp4')
    ->poster('/images/tutorial-poster.jpg')
    ->skipSeconds(15)
    ->pictureInPictureable()
    ->compactControls();
```

YouTube URL in state:

```php
VideoField::make('promo_video')
    ->default('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
    ->youtubeNoCookie()
    ->autoHideControls();
```

### State format

| Mode | Behaviour |
|------|-----------|
| Default | State string = direct video URL or YouTube URL |
| `src()` set | Static URL overrides state for playback |
| YouTube | Detected via `VideoSources::youtubeId()` when `allowYoutube()` |

### Validation

| Rule | Detail |
|------|--------|
| `nullable` | State may be empty |
| `string` | State must be string when present |

### Configuration API

#### `src(string|Closure|null $src)`

Fixed video source URL.

#### `poster(string|Closure|null $poster)` / `placeholder()`

Poster image URL. `placeholder()` is an alias for `poster()`.

#### `title(string|Closure|null $title)` / `subtitle(string|Closure|null $subtitle)`

Metadata overlay below player.

#### `ratio(string|Closure|null $ratio)`

Aspect ratio: `16:9`, `4:3`, `auto`, or CSS ratio string. Default: `16:9`.

#### `fullWidth(bool|Closure $condition = true)`

Full container width. Default: `false`.

#### `controls(bool|Closure $condition = true)`

Show custom controls. Default: `true`.

#### `nativeControls(bool|Closure $condition = true)`

Use browser/YouTube native controls instead of custom chrome.

#### `autoplay(bool|Closure $condition = true)`

Autoplay when allowed by browser policy.

#### `loop(bool|Closure $condition = true)`

Loop playback.

#### `muted(bool|Closure $condition = true)`

Start muted (often required for autoplay).

#### `playsInline(bool|Closure $condition = true)`

`playsinline` for mobile. Default: `true`.

#### `skipSeconds(int|Closure $seconds)`

Forward/back skip interval. Minimum `1`. Default: `10`.

#### `fullscreenable(bool|Closure $condition = true)`

Fullscreen button. Default: `true`.

#### `autoHideControls(bool|Closure $condition = true)`

Hide controls after inactivity. Default: `true`.

#### `pictureInPictureable(bool|Closure $condition = true)`

Picture-in-picture button (not available for YouTube). Default: `false`.

#### `volumeControl(bool|Closure $condition = true)`

Volume slider. Default: `true`.

#### `allowYoutube(bool|Closure $condition = true)`

Parse YouTube URLs. Default: `true`.

#### `youtubeNoCookie(bool|Closure $condition = true)`

Use `youtube-nocookie.com` embed domain. Default: `true`.

#### `controlsLayout(string|Closure $layout)` / `compactControls(bool|Closure $condition = true)`

`default` or `compact` control bar. Default from config `filament-flex-fields.ui.video_controls_layout`.

#### Icon overrides

`playIcon()`, `pauseIcon()`, `volumeIcon()`, `muteIcon()`, `fullscreenIcon()`, `exitFullscreenIcon()`, `pictureInPictureIcon()`, `exitPictureInPictureIcon()`, `placeholderIcon()`.

#### `size(string|ControlSize|Closure $size)`

Player size. Default: `md`.

#### `readOnly(bool|Closure $condition = true)`

Disable interaction.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getControlsLayout()` | `string` | `default` or `compact` |
| `usesCompactControls()` | `bool` | Compact layout |
| `getRatio()` / `getAspectRatioStyle()` | `string\|null` | Aspect ratio |
| `resolveVideoSrc(mixed $state)` | `string\|null` | Effective URL |
| `resolveYoutubeId(mixed $state)` | `string\|null` | YouTube video ID |
| `usesYoutubeEmbed(mixed $state)` | `bool` | YouTube mode |
| `resolveProvider(mixed $state)` | `string` | `html5`, `youtube`, etc. |
| `resolveYoutubeEmbedUrl(...)` | `string\|null` | Embed URL |
| `usesYoutubeCustomControls(mixed $state)` | `bool` | Custom YT chrome |
| `usesYoutubeFacade(mixed $state)` | `bool` | Click-to-play facade |
| `getYoutubeIframePlayerVars(...)` | `array` | iframe `playerVars` |
| `resolveYoutubeThumbnail(mixed $state)` | `string\|null` | Poster/thumbnail |
| `supportsPictureInPicture(mixed $state)` | `bool` | PiP available |
| `hasMetadata()` | `bool` | Title or subtitle set |
| `shouldShowControls()` etc. | `bool` | Feature flags |
| `getSkipSeconds()` | `int` | Skip interval |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `size` | `size()` |
| `ratio` | `ratio()` |
| `full_width` | `fullWidth()` |
| `src` | `src()` |
| `poster` / `placeholder` | `poster()` |
| `title` | `title()` |
| `subtitle` | `subtitle()` |
| `controls` | `controls()` |
| `native_controls` | `nativeControls()` |
| `autoplay` | `autoplay()` |
| `loop` | `loop()` |
| `muted` | `muted()` |
| `plays_inline` | `playsInline()` |
| `skip_seconds` | `skipSeconds()` |
| `picture_in_picture` | `pictureInPictureable()` |
| `allow_youtube` | `allowYoutube()` |
| `youtube_no_cookie` | `youtubeNoCookie()` |
| `auto_hide_controls` | `autoHideControls()` |
| `controls_layout` | `controlsLayout()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-video-field-field` | Root wrapper |
| `fff-video-field-field--{sm\|md\|lg}` | Size modifier |
| `fff-video-field__stage` | Video area |
| `fff-video-field__controls` | Control bar |
| `fff-video-field__controls--compact` | Compact layout |

### Implementation notes

- YouTube with custom controls uses iframe API; facade mode shows thumbnail until play.
- Invalid `ratio` strings throw `InvalidArgumentException`.

---

## FlexFileUpload & FlexImageUpload

### Summary

Styled Filament file upload with security defaults, MIME presets, upload summaries, optional metadata sidecars, image optimization hooks, and scoped per-user directories.

| | |
|---|---|
| **FlexFileUpload** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload` |
| **FlexImageUpload** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexImageUpload` (extends `FlexFileUpload`, `imagesOnly()` preset) |
| **State type** | `string\|array\|null` — stored path(s) on disk |
| **FieldType** | `file` → `FlexFileUpload`, `image` → `FlexImageUpload` |
| **Extends** | `Filament\Forms\Components\FileUpload` |

### Basic usage

#### Avatar (image)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexImageUpload;

FlexImageUpload::make('avatar')
    ->label('Profile photo')
    ->withRecommendedDefaults()
    ->avatar()
    ->imageEditor()
    ->circleCropper()
    ->disk('public')
    ->directory('avatars')
    ->maxFiles(1)
    ->optimizeImages()
    ->maxImageWidth(512)
    ->maxImageHeight(512);
```

#### Single document

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;

FlexFileUpload::make('contract')
    ->label('Contract PDF')
    ->withRecommendedDefaults()
    ->documentsOnly()
    ->disk('local')
    ->directory('contracts')
    ->maxFiles(1)
    ->showFileIcon()
    ->uploadSummary()
    ->requireReplaceConfirmation();
```

#### Multiple files with total size guard

```php
FlexFileUpload::make('attachments')
    ->withRecommendedDefaults()
    ->multiple()
    ->maxFiles(5)
    ->maxTotalSizeKb(10240)
    ->remainingSlotsLabel()
    ->uploadSummary()
    ->disk('local')
    ->directory('inquiries/attachments');
```

#### Metadata sidecar

Stores original filename, MIME, size, and image dimensions in a sibling state path:

```php
FlexFileUpload::make('scan')
    ->withRecommendedDefaults()
    ->storeMetadataIn('scan_meta')
    ->disk('local')
    ->directory('scans');
// $data['scan_meta'] => ['original_name' => '...', 'mime' => '...', 'size' => ..., 'width' => ...]
```

### Security & presets

#### `withRecommendedDefaults()` / `applyRecommendedSecurityDefaults()`

Applies:

- `createFormStrategy()` — defer disk writes until form save
- `deleteFileOnRemove()` / `deleteReplacedFiles()`
- `maxSize(5120)` (5 MB)
- `downloadable()` / `openable()`
- `focusOutline()`

`FlexImageUpload::withRecommendedDefaults()` calls `imagesOnly()` instead of `documentsOnly()`.

#### MIME presets

| Method | Accepts |
|--------|---------|
| `documentsOnly()` | PDF, Word, Excel, plain text, etc. |
| `imagesOnly()` | JPEG, PNG, GIF, WebP, SVG, … |
| `spreadsheetsOnly()` | CSV, XLS, XLSX |
| `allowedExtensions(array $extensions)` | Custom extension allow-list |

#### `rejectExecutableFiles(bool $condition = true)`

Blocks dangerous extensions (`.php`, `.sh`, …) with validation message.

#### `scopedDirectory(string $prefix = 'uploads')`

Per-user subdirectory: `{prefix}/{user_id}/…` when authenticated.

### UX & layout

| Method | Description |
|--------|-------------|
| `variant('primary'\|'secondary'\|'flat')` | Visual variant |
| `size('sm'\|'md'\|'lg')` | Control size (from `HasControlSize`) |
| `uploadSummary()` | Show count + total size below field |
| `remainingSlotsLabel()` | “N slots remaining” when `maxFiles` set |
| `emptyStateHint(string)` | Custom empty dropzone hint |
| `dropzoneLabel(string)` | Dropzone label override |
| `compactList()` | Denser file list |
| `showFileIcon()` | File-type icon in list |
| `requireReplaceConfirmation()` | Confirm before replacing single file |

### Image processing

| Method | Description |
|--------|-------------|
| `optimizeImages()` | Resize/compress on save (requires image driver) |
| `optimizeImagesToWebp()` | Convert optimized output to WebP |
| `maxImageWidth()` / `maxImageHeight()` | Resize caps |
| `minImageDimensions($w, $h)` | Minimum dimensions validation |
| `maxImageDimensions($w, $h)` | Maximum dimensions validation |
| `stripExif(bool $condition = true)` | Remove EXIF on save (default: `true`) |

### Lifecycle / disk hygiene

| Method | Description |
|--------|-------------|
| `deleteFileOnRemove()` | Delete from disk when removed in UI |
| `deleteReplacedFiles()` | Delete old file when replaced |
| `pruneOrphanedOnSave()` | Remove files no longer referenced in state |

### Inherited Filament `FileUpload` API

`disk()`, `directory()`, `visibility()`, `multiple()`, `maxFiles()`, `minFiles()`, `maxSize()`, `acceptedFileTypes()`, `imageEditor()`, `avatar()`, `downloadable()`, `openable()`, `previewable()`, `deletable()`, standard validation — all work unchanged.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `disk` | `disk()` |
| `directory` | `directory()` |
| `visibility` | `visibility()` |
| `multiple` | `multiple()` |
| `max_size_kb` / `max_size` | `maxSize()` |
| `max_files` / `min_files` | `maxFiles()` / `minFiles()` |
| `max_total_size_kb` | `maxTotalSizeKb()` |
| `accepted_types` | `acceptedFileTypes()` |
| `documents_only` | `documentsOnly()` |
| `images_only` | `imagesOnly()` |
| `variant` | `variant()` |
| `size` | `size()` |
| `store_metadata_in` | `storeMetadataIn()` |
| `scoped_directory` | `scopedDirectory()` |
| `optimize_images` | `optimizeImages()` |
| `max_image_width` / `max_image_height` | `maxImageWidth()` / `maxImageHeight()` |

### CSS classes

| Class | Meaning |
|-------|---------|
| `fff-flex-file-upload` | Root wrapper |
| `fff-flex-file-upload--{primary\|secondary\|flat}` | Variant |
| `fff-flex-file-upload--{sm\|md\|lg}` | Size |

### Implementation notes

- Requires Livewire temporary uploads; configure `FILESYSTEM_DISK` and disk credentials.
- Playground examples under **File upload** in Flex Fields Playground.
- For Spatie Media Library integration, see package `FlexSpatieMediaLibraryFileUpload` (if installed in app).

---

## ColorSwatchField

### Summary

Preset color picker: horizontal swatch pills with optional section header and tooltips.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\ColorSwatchField` |
| **State type** | `string\|null` — selected color **key** (not hex) |
| **FieldType** | `color_presets` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ColorSwatchField;

ColorSwatchField::make('theme_color')
    ->colors([
        'indigo' => '#6366f1',
        'rose' => '#f43f5e',
        'emerald' => '#10b981',
    ])
    ->sectionLabel('Brand colors')
    ->tooltips(true);

ColorSwatchField::make('accent')
    ->colors(['primary' => '#3b82f6', 'white' => '#ffffff'])
    ->tooltips([
        'primary' => 'Primary blue',
        'white' => 'White',
    ])
    ->size('lg');
```

### State format

Stores the **array key** from `colors()`, e.g. `'indigo'`, not `#6366f1`.

### Validation

| Rule | Detail |
|------|--------|
| `nullable` | No selection allowed |
| `Rule::in(...)` | Key must exist in `colors()` |

### Configuration API

#### `colors(array|Closure $colors)`

Map of `key => hex` (or CSS color). Required for meaningful UI.

#### `sectionLabel(string|Closure|null $label)`

Optional heading above swatches.

#### `sectionIcon(string|BackedEnum|Htmlable|Closure|null $icon)`

Icon next to section label. When label is set and icon omitted, uses config `color_swatch_section_icon` or `GravityIcon::Palette`.

#### `tooltips(bool|array|Closure $tooltips = true)`

`true` = auto labels from keys; `false` = no tooltips; array = per-key labels.

#### `size(string|ControlSize|Closure $size)`

`sm`, `md`, `lg`. Default: `md`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getColors()` | `array<string, string>` | Key → color map |
| `getSectionLabel()` | `string\|null` | Header text |
| `getSectionIcon()` | `string\|BackedEnum\|Htmlable\|null` | Header icon |
| `getDefaultSectionIcon()` | `string\|BackedEnum\|Htmlable` | Fallback icon |
| `hasTooltips()` | `bool` | Tooltips enabled |
| `getColorLabel(string $key)` | `string` | Tooltip for key |
| `isLightSwatch(string $hex)` | `bool` | Light swatch (border adjustment) |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `colors` | `colors()` |
| `section_label` | `sectionLabel()` |
| `section_icon` | `sectionIcon()` |
| `size` | `size()` |
| `tooltips` | `tooltips()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-color-swatch` | Root wrapper |
| `fff-color-swatch--{sm\|md\|lg}` | Size modifier |
| `fff-color-swatch__pill` | Swatch button |
| `fff-color-swatch__pill--light` | Light color border |
| `fff-color-swatch__pill.is-selected` | Selected state |

### Implementation notes

- Keys are persisted; map to hex in your application layer or accessor.
- `isLightSwatch()` detects white swatches for contrast borders.

---

## FlexColorPickerField

### Summary

Full-featured color picker with **advanced** (HSV square + hue/alpha sliders + eyedropper) or **grid** (preset swatches) layouts. Output format is configurable as hex, rgb, hsl, or rgba.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexColorPickerField` |
| **State type** | `string\|null` — color in the configured output format |
| **FieldType** | `flex_color_picker` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexColorPickerField;

FlexColorPickerField::make('brand_color')
    ->label('Brand color')
    ->alpha()
    ->required();

FlexColorPickerField::make('accent')
    ->layout(FlexColorPickerField::LAYOUT_GRID)
    ->gridColumns(17)
    ->gridRows(11)
    ->rgba()
    ->alpha();
```

### State format

Stores a CSS color string in the format selected via `hex()`, `rgb()`, `hsl()`, or `rgba()` (default: hex). When alpha is disabled, rgba output falls back to rgb.

### Configuration API

#### `layout('advanced'|'grid')`

Picker panel layout. **Default: `advanced`.**

#### `hex()` / `rgb()` / `hsl()` / `rgba()`

Shorthand for output `format()`. **Default: hex.**

#### `format(string|Closure $format)`

Explicit format: `hex`, `rgb`, `hsl`, or `rgba`.

#### `alpha(bool|Closure $enabled = true)`

Enable opacity slider and `%` input. **Default: false.**

#### `eyedropper(bool|Closure $enabled = true)`

Show browser EyeDropper button in advanced layout (when supported). **Default: true.**

#### `gridColumns(int|Closure $columns)` / `gridRows(int|Closure $rows)`

Generated palette size when `gridColors()` is not set. **Defaults: 17 × 11** (Tailwind hue palette: lime → yellow, shades 50–950).

#### `gridColors(array|Closure|null $colors)`

Custom hex list for grid layout; when omitted, palette is generated from columns/rows.

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `primary` | Grey pill track. **Default.** |
| `secondary` | Lighter background. |
| `flat` | Transparent background and border. |

Uses the same FlexTextInput shell as phone and address fields.

#### `size(string|ControlSize|Closure $size)`

`sm`, `md`, `lg`. Default: `md`.

### Public helper methods

| Method | Description |
|--------|-------------|
| `getLayout()` | `advanced` or `grid` |
| `getVariant()` | `primary`, `secondary`, or `flat` |
| `getFormat()` | Resolved output format |
| `isAlphaEnabled()` | Whether opacity controls are shown |
| `isEyedropperEnabled()` | Whether eyedropper is configured |
| `getGridColumns()` / `getGridRows()` | Grid dimensions |
| `getGridColors()` | Custom grid palette or `null` |
| `isValidColorString(?string $value)` | PHP-side color validation |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `layout` | `layout()` |
| `format` | `format()` |
| `alpha` | `alpha()` |
| `eyedropper` | `eyedropper()` |
| `grid_columns` | `gridColumns()` |
| `grid_rows` | `gridRows()` |
| `grid_colors` | `gridColors()` |
| `variant` | `variant()` |
| `size` | `size()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-flex-color-picker` | Root wrapper |
| `fff-flex-color-picker__shell` | Trigger + panel container |
| `fff-flex-color-picker__trigger` | Opens picker panel |
| `fff-flex-color-picker__preview` | Color swatch in trigger |
| `fff-flex-color-picker__panel` | Dropdown panel |
| `fff-flex-color-picker__saturation` | S/V square (advanced) |
| `fff-flex-color-picker__eyedropper` | Screen color picker button |
| `fff-flex-color-picker__hue` | Hue slider |
| `fff-flex-color-picker__alpha` | Opacity slider track |
| `fff-flex-color-picker__grid` | Grid swatch container |
| `fff-flex-color-picker__bottom-bar` | Format + value + opacity inputs |

### Assets

Alpine component: `flex-color-picker` (`resources/js/components/flex-color-picker.js`).

---

## NumberStepper

### Summary

Pill-shaped numeric control with **−** / **+** buttons and an animated NumberFlow display.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper` |
| **State type** | `int\|float\|null` when `nullable()` |
| **Model cast** | `'quantity' => 'integer'` or `'decimal:2'` |
| **FieldType** | `number_stepper` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;

NumberStepper::make('quantity')
    ->label('Quantity')
    ->minValue(0)
    ->maxValue(99)
    ->step(1)
    ->suffix('kg')
    ->variant('primary')
    ->size('lg');
```

### Validation

| Method | Effect |
|--------|--------|
| `integer()` | Adds `integer` rule. **Enabled by default.** |
| `minValue($n)` | Adds `min:$n` when not nullable |
| `maxValue($n)` | Adds `max:$n` |

### Configuration API

#### `minValue(scalar|Closure|null $value)` / `maxValue(scalar|Closure|null $value)`

Lower and upper bounds.

#### `step(int|float|Closure $step = 1)`

Increment / decrement step.

#### `integer(bool|Closure $condition = true)`

Restrict to whole numbers.

#### `nullable(bool|Closure $condition = true)`

Allows `null`. Displays `nullLabel` or `—`.

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `default` | White circular buttons on grey track. |
| `primary` | Filled primary buttons. |
| `secondary` | Primary-tinted buttons. |
| `tertiary` | Grey buttons. |
| `outline` | Outlined buttons. |

#### `prefix(string|Closure|null $prefix)` / `suffix(string|Closure|null $suffix)`

Static text before / after the numeric value in the display.

#### `nullLabel(string|Closure|null $label)`

Text shown when the value is `null`.

#### `decrementIcon(string|Closure|null $icon)` / `incrementIcon(string|Closure|null $icon)`

Custom Heroicon for − and + buttons.

#### `icons(array|Closure $icons)`

Shorthand:

```php
->icons([
    'decrement' => 'heroicon-o-minus',
    'increment' => 'heroicon-o-plus',
])
```

#### `reversed(bool|Closure $condition = true)`

Swaps the visual order of decrement and increment buttons.

#### `decimalPlaces(int|Closure|null $places)`

Fixed decimal places in the display.

#### `wheelAnimated(bool|Closure $condition = true)`

NumberFlow wheel animation on value change. **Default: true.**

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `min` | `minValue()` |
| `max` | `maxValue()` |
| `step` | `step()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `prefix` | `prefix()` |
| `suffix` | `suffix()` |
| `null_label` | `nullLabel()` |
| `icons` | `icons()` |
| `decrement_icon` | `decrementIcon()` |
| `increment_icon` | `incrementIcon()` |
| `reversed` | `reversed()` |
| `decimal_places` | `decimalPlaces()` |
| `wheel_animated` | `wheelAnimated()` |
| `integer` | `integer()` |
| `nullable` | `nullable()` |

---

## FlexSlider

### Summary

Styled wrapper around Filament's `Slider` with SaaS-like track, step dots, server-rendered pips, fill segments, and formatted value display.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider` |
| **State type** | `int\|float` or `[min, max]` for range |
| **FieldType** | `flex_slider` |
| **Parent** | `Filament\Forms\Components\Slider` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;

FlexSlider::make('volume')
    ->range(0, 100)
    ->step(5)
    ->showValue()
    ->suffix('%')
    ->color('primary');

FlexSlider::make('price_range')
    ->range(0, 1000)
    ->step(10)
    ->fillTrack([false, true, false])
    ->prefix('$')
    ->trackLabel('Budget')
    ->decimalPlaces(0);
```

Range with auto-fill between handles:

```php
FlexSlider::make('age_range')
    ->range(18, 80)
    ->step(1)
    ->autoFill()
    ->showValue()
    ->valuePosition('start');
```

### State format

Same as Filament Slider: single numeric or array of two values for dual-handle range. Normalized via `normalizeNumeric()` respecting `step()` and `decimalPlaces()`.

### Validation

Inherits Filament Slider rules (`min`, `max`, `step`, etc.).

### Configuration API (FlexSlider-specific)

#### `showValue(bool|Closure $condition = true)`

Show formatted value label. Schema default: `true`.

#### `prefix(string|Closure|null $prefix)` / `suffix(string|Closure|null $suffix)`

Prepended/appended to formatted display (not stored in state).

#### `variant(string|Closure $variant)`

Track variant. Default: `default` (also `secondary` via CSS).

#### `trackLabel(string|Closure|null $label)`

Label above the track.

#### `hideThumbUntilInteraction(bool|Closure $condition = true)`

Hide thumb until user interacts.

#### `valuePosition(string|Closure $position)`

Value label position. Default: `end`.

#### `autoFill(bool|Closure $condition = true)`

Auto-connect fill from min to first handle (single) or between handles (range). Alternative to explicit `fillTrack()`.

#### `color(string|Closure|null $color)`

Filament color class on track. Default: `primary`.

#### `fillColor(string|Closure|null $color)`

Override fill segment color.

#### `size(string|ControlSize|Closure $size)`

`sm`, `md`, `lg`. Default: `md`.

### Inherited Filament Slider API

Also configure via parent methods:

| Method | Purpose |
|--------|---------|
| `range($min, $max)` | Bounds |
| `step($step)` | Step increment |
| `fillTrack(array\|bool)` | Which segments are filled |
| `decimalPlaces(int)` | Fixed decimal display |
| `pips(PipsMode)` | Pip mode: Steps, Positions, Count, etc. |
| `pipsValues(...)` / `pipsDensity(...)` | Pip configuration |
| `vertical()` | Vertical orientation |
| `rangePadding(...)` | Padding beyond min/max |
| `default($value)` | Initial value |
| `disabled()` / `nullable()` | State modifiers |

See [Filament Slider documentation](https://filamentphp.com/docs/forms/fields/slider) for full parent API.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `shouldShowValue()` | `bool` | Value visible |
| `getDisplayPrefix()` / `getDisplaySuffix()` | `string\|null` | Affixes |
| `getVariant()` | `string` | Variant name |
| `getTrackLabel()` | `string\|null` | Header label |
| `shouldHideThumbUntilInteraction()` | `bool` | Thumb hidden initially |
| `getValuePosition()` | `string` | Label position |
| `shouldAutoFill()` | `bool` | Auto fill segments |
| `getColor()` / `getFillColor()` | `string\|null` | Colors |
| `getNormalizedStateValues()` | `list<float>` | Current handle values |
| `isRangeState()` | `bool` | Range mode |
| `valueToPercent()` / `valueToRatio()` | `float` | Position math |
| `getInitialValueRatios()` | `list<float>` | SSR thumb positions |
| `formatDisplayValue(?float $value)` | `string` | Formatted label |
| `shouldShowStepDots()` | `bool` | Step dots visible |
| `getStepDotRatios()` | `list<float>` | Dot positions |
| `shouldRenderServerPips()` | `bool` | SSR pips mode |
| `getServerRenderedPips()` | `list<array>` | Pip markup data |
| `resolveConnectForChrome()` | `array\|false` | Fill connection flags |
| `getInitialFillSegments()` | `list<array>` | SSR fill segments |
| `normalizeNumeric(float\|int $value)` | `float` | Step-aligned value |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `min` / `max` | `range()` |
| `step` | `step()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `show_value` | `showValue()` |
| `prefix` | `prefix()` |
| `suffix` | `suffix()` |
| `track_label` | `trackLabel()` |
| `hide_thumb_until_interaction` | `hideThumbUntilInteraction()` |
| `value_position` | `valuePosition()` |
| `fill_track` / `auto_fill` | `fillTrack()` / `autoFill()` |
| `color` | `color()` |
| `fill_color` | `fillColor()` |
| `decimal_places` | `decimalPlaces()` |

### CSS classes

| Class | Role |
|-------|------|
| `fff-flex-slider` | Root wrapper |
| `fff-flex-slider--{sm\|md\|lg}` | Size modifier |
| `fff-flex-slider--secondary` | Secondary variant |
| `fff-flex-slider__rail` | Track hit area |
| `fff-flex-slider__fill` | Filled segment |
| `fff-flex-slider__thumb` | Draggable handle |
| `fff-flex-slider__pip` | Scale pip |
| `fff-flex-slider__step-dot` | Step indicator dot |

Uses CSS variables `--fff-flex-slider-accent`, `--fff-flex-slider-value-ratio`, etc.

### Implementation notes

- Server-rendered pips and fill segments reduce layout shift before Alpine hydrates.
- Step dots capped at 11 visible dots (`MAX_STEP_DOTS`); excess steps are sampled.
- Vertical sliders disable step dots and server pips.

---

## SegmentTabs

### Summary

Schema layout component: **iOS-style segmented tabs** with per-tab form schemas (same visual language as [SegmentControl](#segmentcontrol)). Used directly and as the base for [TranslatableFields](#translatablefields).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs` |
| **Tab class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab` |
| **State** | Tab panels contain nested fields; tab selection is local UI state (optional query-string persistence) |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

SegmentTabs::make('Account')
    ->tabs([
        SegmentTab::make('General')
            ->icon(GravityIcon::Person)
            ->tooltip('General settings')
            ->schema([
                FlexTextInput::make('name'),
            ]),
        SegmentTab::make('Advanced')
            ->schema([
                FlexTextInput::make('api_key'),
            ]),
    ])
    ->variant('ghost')
    ->fullWidth();
```

### Configuration API

#### `tabs(array|Closure $tabs)`

Array of `SegmentTab` components. Each tab wraps a nested schema.

#### `activeTab(int|Closure $activeTab)`

1-based index of the initially active tab. **Default:** `1`.

#### `persistTabInQueryString(string|Closure|null $key = 'segment-tab')`

Persist the active tab in the URL query string. Pass `null` to disable.

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `default` | Filled track background. **Default.** |
| `ghost` | Transparent track; uses `color()` for selection accent. |

#### `color(string|Closure|null $color)`

Selection accent color. For `ghost`, defaults to `primary` when omitted.

#### `separators(bool|Closure $condition = true)`

Vertical dividers between tab segments. **Default:** `true`.

#### `fullWidth(bool|Closure $condition = true)`

Stretch tabs to the full container width.

#### `iconOnly(bool|Closure $condition = true)`

Hide tab labels; show icons only (requires icons on tabs).

#### `expandSelectedLabel(bool|Closure $condition = true)`

Animate the selected tab to a wider width.

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

### SegmentTab API

| Method | Description |
|--------|-------------|
| `make(string\|Htmlable\|Closure\|null $label)` | Create a tab |
| `icon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Tab icon |
| `tooltip(string\|Closure\|null $tooltip)` | Hover tooltip |
| `schema(array\|Closure $schema)` | Nested form/schema components |
| `badge(...)` | Inherited Filament badge API |
| `visible(...)` / `hidden(...)` | Inherited visibility API |

### Getters

| Method | Returns |
|--------|---------|
| `getVisibleTabs()` | `list<SegmentTab>` — visible tabs only |
| `getActiveTab()` | `int` — 1-based active index |
| `getActiveTabKey()` | `?string` — key of active tab |
| `isTabActive(SegmentTab $tab)` | `bool` |
| `isTabPersistedInQueryString()` | `bool` |
| `getTabQueryStringKey()` | `?string` |

---

## SegmentControl

### Summary

iOS-style **single-select** segmented control.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl` |
| **State type** | `string\|int` — one option key |
| **Model cast** | `'alignment' => 'string'` |
| **FieldType** | `segment_control` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl;

SegmentControl::make('alignment')
    ->label('Alignment')
    ->options([
        'left' => 'Left',
        'center' => [
            'label' => 'Center',
            'icon' => 'heroicon-o-bars-3',
            'tooltip' => 'Centered text',
        ],
        'right' => 'Right',
    ])
    ->variant('ghost')
    ->fullWidth();
```

### Validation

Built-in `Rule::in(...)` against option keys.

### Configuration API

#### `options(array|Closure $options)`

| Key | Type | Description |
|-----|------|-------------|
| `label` | `string` | Segment label |
| `icon` | `string\|null` | Heroicon (or use `icons()`) |
| `tooltip` | `string\|null` | Hover tooltip |
| `disabled` | `bool` | Disables this segment |

Simple form: `'left' => 'Left'`.

#### `icons(array|Closure $icons)`

Map of option key → Heroicon name.

#### `disabledOptions(array|Closure $keys)`

Disable segments by key.

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `default` | Filled track background. **Default.** |
| `ghost` | Transparent track; uses `color()` for selection accent. |

#### `color(string|Closure|null $color)`

Selection accent color. For `ghost`, defaults to `primary` when omitted.

#### `separators(bool|Closure $condition = true)`

Vertical dividers between segments. **Default: true.**

#### `fullWidth(bool|Closure $condition = true)`

Stretch the control to the full field width.

#### `iconOnly(bool|Closure $condition = true)`

Hide labels; show icons only. Requires icons on options.

#### `expandSelectedLabel(bool|Closure $condition = true)`

Animates the selected segment to a wider width (label expansion).

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `options` | `options()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `full_width` | `fullWidth()` |
| `icons` | `icons()` |
| `disabled_options` | `disabledOptions()` |
| `color` | `color()` |
| `separators` | `separators()` |
| `icon_only` | `iconOnly()` |
| `expand_selected_label` | `expandSelectedLabel()` |

---

## TrackSlider

### Summary

Track-based range slider with optional live value output.

Also used internally for `FieldType::Percentage` and `FieldType::RangeSlider`.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrackSlider` |
| **State type** | `int\|float` |
| **Model cast** | `'volume' => 'integer'` or `'float'` |
| **FieldType** | `range_slider`, `percentage` |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrackSlider;

TrackSlider::make('volume')
    ->label('Volume')
    ->min(0)
    ->max(100)
    ->step(5)
    ->suffix('%')
    ->trackLabel('Volume level')
    ->variant('secondary')
    ->showOutput()
    ->size('md');
```

### Validation

No built-in min/max rules. Add manually:

```php
->rule('min:0')
->rule('max:100')
```

Or define rules in `FlexFieldDefinition`.

### Configuration API

#### `min(int|float|Closure $min = 0)` / `max(int|float|Closure $max = 100)`

Range boundaries.

#### `step(int|float|Closure $step = 1)`

Slider step increment.

#### `integer(bool|Closure $condition = true)`

Snap to integer values. **Default: true.**

#### `showOutput(bool|Closure $condition = true)`

Display the current value. **Default: true.**

#### `suffix(string|Closure|null $suffix)`

Text after the value, e.g. `%`, `px`.

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `default` | Standard filled track. |
| `secondary` | Subtle track styling. |

#### `decimalPlaces(int|Closure|null $places)`

Decimal precision for displayed value.

#### `trackLabel(string|Closure|null $label)`

Accessible label / ARIA description for the track.

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `min` | `min()` |
| `max` | `max()` |
| `step` | `step()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `show_output` | `showOutput()` |
| `suffix` | `suffix()` |
| `decimal_places` | `decimalPlaces()` |
| `track_label` | `trackLabel()` |
| `integer` | `integer()` |

---

## TrafficSplit

### Summary

Visual editor for splitting **100%** across 2–5 draggable segments (traffic allocation, weighted distribution).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrafficSplit` |
| **State type** | `array<int, int>` — weights summing to **100** |
| **Model cast** | `'split' => 'array'` or `'json'` |
| **FieldType** | `traffic_split` |

Example state: `[40, 35, 25]`.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrafficSplit;

TrafficSplit::make('traffic_split')
    ->label('Traffic allocation')
    ->segmentCount(4)
    ->minWeight(10)
    ->valueThreshold(15)
    ->labels(['Control', 'Variant A', 'Variant B', 'Variant C'])
    ->lockedSegments([0])
    ->variant('default');
```

### Validation

No automatic Laravel rules. Ensure segment weights sum to 100 in application logic if persisted outside the component.

The component normalizes state on hydration via `normalizeWeights()`.

### Default state

Equal split across segments (`equalSplit()`), e.g. three segments → `[34, 33, 33]`.

### Configuration API

#### `segmentCount(int|Closure $count = 3)`

Number of segments. Range: **2–5**. Default: **3**.

#### `minWeight(int|Closure $weight = 12)`

Minimum weight per segment (1–100). Constraint: `minWeight × segmentCount ≤ 100`.

#### `valueThreshold(int|Closure $threshold = 18)`

Minimum segment width (in percent units) before the numeric label is shown. Must be ≥ `minWeight + 1`.

#### `variant(string|Closure $variant)`

| Value | Description |
|-------|-------------|
| `default` | Standard segment colors. |
| `secondary` | Muted segment styling. |

#### `labels(array|Closure|null $labels)`

Optional zero-indexed labels for each segment. Fallback: `"1"`, `"2"`, `"3"`, …

#### `lockedSegments(array|Closure $indices)`

Zero-indexed segment indices that cannot be resized by dragging.

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size).

### State normalization

On load, weights are passed through `normalizeWeights()`:

- Ensures segment count matches `segmentCount()`
- Enforces `minWeight`
- Respects `lockedSegments`
- Rebalances unlocked segments so the total equals **100**

Throws `InvalidArgumentException` when `minWeight × segmentCount > 100`.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `segment_count` | `segmentCount()` |
| `min_weight` | `minWeight()` |
| `value_threshold` | `valueThreshold()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `labels` | `labels()` |
| `locked_segments` | `lockedSegments()` |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `equalSplit()` | `list<int>` | Equal weight distribution across `segmentCount()` summing to **100** (remainder distributed to first segments). Default state source. |
| `normalizeWeights(?array $weights)` | `list<int>` | Normalizes weights to segment count, enforces `minWeight`, respects `lockedSegments`, rebalances to total **100**. Returns `equalSplit()` when input is invalid. |

### CSS / stacking

Drag handles use a **local** stacking context (`z-index: 2` inside `.fff-traffic-split__track` with `isolation: isolate`) so grey divider bars do not paint above the Filament sticky header when scrolling.

---

## RatingField

### Summary

SaaS-style star (or custom icon) rating input with hover preview, semantic colors, sizes, disabled/required states, and **fractional read-only display**.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField` |
| **State type** | `int|null` (interactive) · `float|null` (read-only display) |
| **FieldType** | `rating` |

Shares rating display configuration with [RatingColumn](#ratingcolumn) via matching fluent API. Fill calculations live in the shared `CalculatesRatingFill` concern.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField;
use Filament\Support\Icons\Heroicon;

RatingField::make('score')
    ->label('How would you rate this product?')
    ->required();

RatingField::make('score')
    ->icon(Heroicon::Heart)
    ->color('danger')
    ->stars(5);

RatingField::make('average_score')
    ->readOnly()
    ->default(3.7);
```

### Validation

| Rule | When |
|------|------|
| `nullable` | Always (unless `required()`) |
| `numeric` | Always |
| `min:0` | Always |
| `max:{stars}` | Matches `stars()` / `max()` |
| `integer` | Interactive mode only |
| `required` | When `->required()` |

### Configuration API

#### `stars(int|Closure $count)` / `max(int|Closure $count)`

Number of rating items. Default: **5**. Minimum: **1**.

#### `size(string|ControlSize|Closure $size)`

See [Control size](#control-size). Default: `md`.

#### `color(string|Closure|null $color)`

Semantic fill color for active icons.

| Value | Use case |
|-------|----------|
| `warning` | Default SaaS amber stars |
| `primary` | Accent / blue |
| `danger` | Error / red hearts |
| `success` | Positive / green |

#### `icon(string|BackedEnum|Htmlable|Closure|null $icon)`

Custom icon for every item. Default: `Heroicon::Star`. Use `Heroicon::Heart` or `Heroicon::OutlinedHeart` for heart variants.

#### `readOnly(bool|Closure $condition = true)`

Display-only mode. Supports **fractional values** (e.g. `3.7`) with partial icon fill.

#### `showValue(bool|Closure $condition = true)`

When read-only, show the numeric value (one decimal) beside the icons. Default: `true`.

#### `extraAlpineAttributes(array|Closure $attributes)`

Extra Alpine bindings on the root element. From Filament `HasExtraAlpineAttributes` (same trait as `SwitchField`).

#### Inherited

`disabled()`, `required()`, `label()`, `helperText()`, `default()`, standard Filament field validation.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getFillPercentageForValue(float\|int\|null $value, int $index)` | `float` | Fill ratio `0.0–1.0` for icon at 1-based `$index`. Used for **read-only fractional display** (e.g. `3.7` fills star 4 to 70%). Returns `0` when value is `null`. |

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `max` | `stars()` |
| `size` | `size()` |
| `color` | `color()` |
| `icon` | `icon()` |
| `show_value` | `showValue()` |
| `read_only` | `readOnly()` |

For **read-only rating display in tables**, use [RatingColumn](#ratingcolumn) instead.

---

## RatingColumn

### Summary

Read-only **table column** for displaying ratings with the same visual language as [RatingField](#ratingfield). Supports fractional values with partial icon fill, custom icons, semantic colors, sizes, and optional numeric value display.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Tables\Columns\RatingColumn` |
| **Context** | Filament tables (`$table->columns([...])`) |
| **State type** | `int|float|string|null` (numeric values only) |
| **Parent** | `Filament\Tables\Columns\TextColumn` |

Shares rating display configuration with RatingField via matching fluent API. Column methods use a `rating*` prefix where they would conflict with `TextColumn` (`ratingSize()`, `ratingColor()`, `ratingIcon()`).

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\RatingColumn;
use Filament\Support\Icons\Heroicon;

RatingColumn::make('score')
    ->label('Rating');

RatingColumn::make('average_score')
    ->stars(10)
    ->ratingColor('success')
    ->ratingSize('lg');

RatingColumn::make('satisfaction')
    ->ratingIcon(Heroicon::Heart)
    ->ratingColor('danger')
    ->showValue(false);
```

Filament resolves `$record->score` as column state from the model attribute or relationship. Fractional values (e.g. `3.7`) render with partial star fill.

### Configuration API (RatingColumn-specific)

No column-only methods beyond inherited table APIs. All visual options come from the shared rating display API below.

### Shared rating display API

These methods are identical to [RatingField](#ratingfield):

| Method | Description |
|--------|-------------|
| `stars(int\|Closure $count)` | Number of rating items. Default: **5** |
| `ratingSize(string\|ControlSize\|Closure $size)` | Icon control size (`sm`, `md`, `lg`). Default: `md` (table scale: 16 / 18 / 20 px) |
| `ratingColor(string\|Closure\|null $color)` | Semantic fill color (`warning`, `primary`, `danger`, `success`) |
| `ratingIcon(string\|BackedEnum\|Htmlable\|Closure\|null $icon)` | Custom icon. Default: `Heroicon::Star` |
| `showValue(bool\|Closure $condition = true)` | Show numeric value (one decimal) beside icons. Default: `true` |

### Inherited TextColumn API

All Filament `TextColumn` methods apply: `label()`, `sortable()`, `searchable()`, `toggleable()`, `alignStart()` / `alignCenter()`, `url()`, `tooltip()`, etc. The column uses `html()` internally — do not call `html(false)` unless you override `formatStateUsing()`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `formatRatingDisplay(mixed $state)` | `string` | Rendered HTML for a state value |
| `normalizeRatingFromState(mixed $state)` | `?float` | Parsed numeric value clamped to `0…max`, or `null` |
| `getFillPercentageForValue(float\|int\|null $value, int $index)` | `float` | Fill ratio `0.0–1.0` for icon at 1-based `$index` |
| `getMax()` | `int` | Star count |
| `getRatingColor()` | `string` | Semantic color name |
| `getRatingIcon()` | `string\|BackedEnum\|Htmlable` | Icon reference |
| `getRatingDisplaySize()` | `string` | Icon control size (`sm`, `md`, `lg`) |
| `shouldShowValue()` | `bool` | Whether numeric value is shown |
| `getItemIndexes()` | `list<int>` | 1-based indexes for each star |

### CSS classes

| Class | Role |
|-------|------|
| `fff-rating-column` | Cell wrapper (inline flex) |
| `fff-rating` | Shared rating root (reused from RatingField) |
| `fff-rating--{size}` | Size variant (`sm`, `md`, `lg`) |
| `fff-rating--with-value` | Layout when numeric value is shown |
| `is-read-only` | Non-interactive display mode |
| `fff-rating__items` | Icon row |
| `fff-rating__icon-clip` | Partial fill clip for fractional values |
| `fff-rating__value` | Numeric value label |

---

## CoverCard

### Summary

SaaS-style **media card** for hero banners, product tiles, and CTA blocks. Supports background image, gradient, or solid color, optional top/footer copy, and a footer action button.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard` |
| **State** | None — display/action component (footer action may use Livewire) |
| **Extends** | `Filament\Schemas\Components\Component` |

### Basic usage

#### Portrait product card

```php
use Bjanczak\FilamentFlexFields\Filament\Actions\Action;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard;
use Filament\Notifications\Notification;

CoverCard::make()
    ->backgroundImage('https://cdn.example.com/yacht.jpg')
    ->backgroundColor('#e4e4e7')
    ->ratio('3:4')
    ->topTitle('Azimut 55 Fly')
    ->topDescription('Mediterranean charter')
    ->footerTitle('From €4,900 / week')
    ->footerDescription('Early booking discount')
    ->footerAction(
        Action::make('book')
            ->label('Book now')
            ->action(fn () => Notification::make()->title('Booking started')->success()->send()),
    );
```

#### Full-width low banner

Use a **wide aspect ratio** with `fullWidth()` — height follows width, not a fixed pixel height.

```php
CoverCard::make()
    ->backgroundImage('https://cdn.example.com/harbor.jpg')
    ->backgroundColor('#0f172a')
    ->ratio('21:9')   // cinematic banner; try '3:1' for a thinner strip
    ->tone('light')
    ->fullWidth()
    ->columnSpanFull()
    ->topTitle('Charter season 2026')
    ->footerTitle('Early booking')
    ->footerDescription('Save 15% before March')
    ->footerAction(Action::make('explore')->label('Explore fleet'));
```

#### Gradient strip (no image)

```php
CoverCard::make()
    ->backgroundGradient('linear-gradient(90deg, rgb(15 23 42) 0%, rgb(30 58 138) 50%, rgb(14 116 144) 100%)')
    ->ratio('3:1')
    ->tone('light')
    ->fullWidth()
    ->footerTitle('Launch week')
    ->footerDescription('Limited offer');
```

### Configuration API

#### Background

| Method | Description |
|--------|-------------|
| `backgroundImage(string\|Closure\|null $url)` | Cover image URL. Sanitized via `SafeMediaUrl` (`javascript:` etc. rejected). Takes precedence over gradient for `background-image`. |
| `backgroundGradient(string\|Closure\|null $gradient)` | CSS gradient string when no image is set. |
| `backgroundColor(string\|Closure\|null $color)` | Fallback / underlay color. |
| `backgroundPosition(string\|Closure $position)` | CSS `background-position`. Default: `center`. |

#### Layout & sizing

| Method | Description |
|--------|-------------|
| `ratio(string\|Closure\|null $ratio)` | Aspect ratio via inline `aspect-ratio`. Formats: `3:4`, `16:9`, `21:9`, `3:1`, `auto` (no fixed ratio). Default: `3:4`. |
| `fullWidth(bool\|Closure $condition = true)` | Removes default `max-width: 20rem` cap (`.is-full-width`). |
| `contentMaxWidth(string\|Closure\|null $width)` | Custom `max-width` when **not** full width (e.g. `'18rem'`). Ignored when `fullWidth()` is true. |

#### Copy blocks

| Method | Description |
|--------|-------------|
| `topTitle()` / `topDescription()` | Header block at top of card (optional). |
| `footerTitle()` / `footerDescription()` | Footer copy block (optional). |
| `footerAction(Action\|Closure\|null $action)` | Filament `Action` rendered as pill CTA in footer. Closure shorthand creates a default labeled action. |

#### Appearance

| Method | Values | Default |
|--------|--------|---------|
| `tone(string\|Closure $tone)` | `dark`, `light` | `dark` |
| `radius(string\|Closure $radius)` | `md`, `lg`, `xl`, `2xl` | `xl` |

#### Content overlays

| Method | Description |
|--------|-------------|
| `contentOverlays(bool\|Closure $condition = true)` | Enables separate top/bottom gradient overlay elements instead of the global scrim. Top overlay renders only when top copy exists; bottom overlay only when footer copy/action exists. |
| `topOverlayGradient(string\|Closure\|null $gradient)` | Custom CSS gradient for the top `::before` overlay. |
| `footerOverlayGradient(string\|Closure\|null $gradient)` | Custom CSS gradient for the bottom `::after` overlay. |

```php
CoverCard::make()
    ->backgroundImage('https://images.pexels.com/photos/33866367/pexels-photo-33866367.jpeg')
    ->contentOverlays()
    ->topTitle('Charter season 2026')
    ->footerTitle('Early booking');
```

### Public helper methods

| Method | Returns |
|--------|---------|
| `getAspectRatioStyle()` | CSS ratio string e.g. `3 / 4`, or `null` when `ratio('auto')` |
| `getBackgroundStyles()` | Inline style fragments for background layer |
| `getFooterAction()` | Registered footer `Action` or `null` |
| `hasTopContent()` / `hasFooterContent()` | Whether copy blocks are filled |
| `hasContentOverlays()` / `shouldShowTopOverlay()` / `shouldShowFooterOverlay()` | Content overlay state |
| `isFullWidth()` | Whether full-width mode is active |

### CSS classes

| Class | Meaning |
|-------|---------|
| `fff-cover-card` | Root card |
| `fff-cover-card--tone-{dark\|light}` | Text/scrim variant |
| `fff-cover-card--radius-{md\|lg\|xl\|2xl}` | Corner radius |
| `is-full-width` | No max-width constraint |
| `fff-cover-card__background` | Background layer |
| `fff-cover-card__scrim` | Global gradient overlay (when `contentOverlays()` is off) |
| `fff-cover-card__overlay--top` / `--bottom` | Separate gradient overlay elements |
| `fff-cover-card__content` | Copy + action container |

### Implementation notes

- Invalid `ratio` or unsupported `tone`/`radius` throws `InvalidArgumentException`.
- Image URLs are escaped in inline styles; unsafe schemes return `null` from `getBackgroundImage()`.
- Playground examples live under **Cover card** in Flex Fields Playground.

---

## ProgressBar

Linear progress indicator for uploads, sync status, stepped delivery trackers, and pill-style multi-segment bars.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressBar` |
| **State** | None — display-only schema component |
| **Playground** | `progress-bar` |
| **Stylesheet** | Lazy `progress-bar` bundle |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressBar;

ProgressBar::make()
    ->label('Upload')
    ->value(60)
    ->max(100)
    ->showValue(true)
    ->size('md')
    ->color('primary');
```

### Configuration API

#### `value(float|int|Closure|null $value)` / `max(float|int|Closure $max)`

Numeric progress. Default `max`: `100`. Ratio = `value / max`.

```php
->value(42)->max(200) // 21%
```

#### `label(string|Closure|null $label)`

Header text above the track.

#### `displayValue(string|Closure|null $value)` / `showValue(bool|Closure $condition = true)`

Custom formatted value string. When `showValue()` is true, shown beside the label (or as badge when `valueBadge()`).

```php
->displayValue('3 of 5 files')
->showValue()
```

#### `valueBadge(bool|Closure $condition = true)`

Render the value as a pill badge in the header instead of inline text.

#### `color(string|Closure|null $color)`

Semantic track/fill color: `primary`, `success`, `warning`, `danger`. Default: `primary`.

#### `indeterminate(bool|Closure $condition = true)`

Animated loading bar when progress is unknown:

```php
ProgressBar::make()->label('Syncing')->indeterminate();
```

#### `variant('default'|'pills')` / `pillCount(int|Closure $count)`

| Variant | Description |
|---------|-------------|
| `default` | Single continuous track |
| `pills` | Segmented pill track; `value` = number of filled pills |

```php
ProgressBar::make()
    ->variant('pills')
    ->pillCount(5)
    ->value(3);
```

When `pillCount` is omitted in pills mode, count is derived from `max`.

#### `gradientFrom(string|Closure|null $color)` / `gradientTo(string|Closure|null $color)`

CSS color stops for gradient fill (pills and default track):

```php
->gradientFrom('rgb(99 102 241)')
->gradientTo('rgb(236 72 153)')
```

#### `segments(array|Closure|null $segments)` / `activeSegment(int|Closure|null $index)`

Stepped **delivery-tracker** mode. Each segment: `label`, optional `icon`, optional `color`.

```php
ProgressBar::make()
    ->segments([
        ['label' => 'Ordered', 'icon' => 'gravityui-check'],
        ['label' => 'Shipped'],
        ['label' => 'Delivered'],
    ])
    ->activeSegment(1)
    ->color('success');
```

#### `segmentThumb(bool|Closure $condition = true)` / `activeSegmentProgress(float|Closure $progress)`

Show a draggable-style thumb on the active segment. `activeSegmentProgress` (0–1) controls partial fill within the active step.

#### `startMarker()` / `currentMarker()` / `endMarker()`

Optional icons at track start, current position, and end (`string|BackedEnum|Htmlable`).

```php
->startMarker('gravityui-circle')
->endMarker('gravityui-check')
->currentMarker('gravityui-pin')
```

#### `remainingTrackStyle('solid'|'dashed')`

Style of the unfilled portion of the track. Default: `solid`.

#### `shell(bool|Closure $condition = true)` / `description()` / `footer()`

Optional card chrome wrapping the bar:

```php
ProgressBar::make()
    ->label('Backup')
    ->value(80)
    ->shell()
    ->description('Daily snapshot in progress…')
    ->footer('Estimated 2 min remaining');
```

#### `size('sm'|'md'|'lg')`

Track height and typography scale. Default: `md`.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getProgressRatio()` | `float` | `value / max` clamped 0–1 |
| `getPercentage()` | `int` | Rounded percent |
| `getFormattedValue()` | `string` | Display string |
| `getNormalizedSegments()` | `array` | Segment metadata |
| `isIndeterminate()` | `bool` | Loading mode |
| `isPillsVariant()` | `bool` | Pills mode |
| `getActivePillCount()` | `int` | Filled pills |
| `hasShell()` / `hasCardChrome()` | `bool` | Card wrapper state |

### CSS classes

| Class | Role |
|-------|------|
| `fff-progress-bar` | Root |
| `fff-progress-bar--{sm\|md\|lg}` | Size |
| `fff-progress-bar--indeterminate` | Loading animation |
| `fff-progress-bar--pills` | Pill variant |
| `fff-progress-bar--segments` | Stepped tracker |
| `fff-progress-bar__track` | Track background |
| `fff-progress-bar__fill` | Filled portion |

---

## ProgressCircle

SVG circular progress with full-circle and semi-circle variants, rounded stroke caps, center or beside content, optional card shell, gap arc, and gradient strokes.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressCircle` |
| **State** | None — display-only schema component |
| **Playground** | `progress-circle` |
| **Stylesheet** | Lazy `progress-circle` bundle |

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressCircle;

ProgressCircle::make()
    ->value(69)
    ->displayValue('69%')
    ->size('md')
    ->color('primary');
```

### Configuration API

#### `value(float|int|Closure|null $value)` / `max(float|int|Closure $max)`

Arc fill ratio. Default `max`: `100`.

#### `displayValue(string|Closure|null $value)`

Primary center text (e.g. `'69%'`). Auto-derived from percent when omitted.

#### `fraction(string|Closure|null $fraction)` / `label(string|Closure|null $label)`

Secondary slots: fraction line (`'124 / 223'`) and label (`'Grade rating'`).

```php
->fraction('124 / 223')
->label('Grade rating')
```

#### `variant('circle'|'semicircle')`

| Variant | Arc | Content |
|---------|-----|---------|
| `circle` (default) | Full or near-full ring | Centered in ring |
| `semicircle` | Wide bottom arc (210° span minus gap) | Percent on arc floor; label below arc |

```php
->variant('semicircle')->gapAngle(24)
```

#### `gapAngle(float|int|Closure $degrees)`

Degrees of empty gap at the bottom of the arc. Used with `circle` (near-full ring) or `semicircle` (narrows arc). Default: `0`.

#### `contentLayout('center'|'left')`

| Layout | Description |
|--------|-------------|
| `center` (default) | Text inside the circle/arc |
| `left` | Text block beside the circle (e.g. completion rate card) |

```php
->contentLayout('left')
->displayValue('72%')
->label('Completion rate')
```

#### `paused(bool|Closure $condition = true)` / `pausedIcon(string|BackedEnum|Htmlable|Closure|null $icon)`

Show paused state with center icon instead of progress text.

#### `gradientFrom()` / `gradientTo()` / `gradientStroke(string|Closure|null $gradient)`

Gradient on the **progress stroke** (fill arc):

```php
->gradientFrom('rgb(99 102 241)')
->gradientTo('rgb(236 72 153)')
// or raw CSS gradient:
->gradientStroke('linear-gradient(90deg, #6366f1, #ec4899)')
```

Track stays gray unless track gradient is set.

#### `trackGradientFrom()` / `trackGradientTo()`

Optional gradient on the **track** (unfilled) stroke:

```php
->trackGradientFrom('rgb(228 228 231)')
->trackGradientTo('rgb(212 212 216)')
```

#### `shell(bool|Closure $condition = true)` / `heading()` / `description()` / `footer()`

Card chrome around the circle:

```php
->shell()
->heading('Completion rate')
->description('Last 30 days')
->footer('Updated hourly')
```

#### `color(string|Closure|null $color)` / `size('sm'|'md'|'lg')`

Semantic stroke color and diameter scale.

### Layout notes

- **Semicircle:** `displayValue` sits on the arc floor; with `label()`, label renders **below** the arc (not inside the gap).
- **Circle + `gapAngle`:** near-full ring with **centered percent only** (no below-label layout).
- Gradient applies to fill by default; track gradient only when `trackGradientFrom/To` are explicit.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getProgressRatio()` | `float` | Fill ratio 0–1 |
| `getPercentage()` | `int` | Rounded percent |
| `getSvgMetrics()` | `array` | ViewBox, radii, arc paths |
| `hasGapArc()` | `bool` | Bottom gap enabled |
| `hasBelowLabel()` | `bool` | Label below semicircle |
| `hasGradientStroke()` | `bool` | Fill gradient active |
| `usesExplicitTrackGradient()` | `bool` | Custom track gradient |
| `hasShell()` / `hasCardChrome()` | `bool` | Card wrapper |

### CSS classes

| Class | Role |
|-------|------|
| `fff-progress-circle` | Root |
| `fff-progress-circle--{sm\|md\|lg}` | Size |
| `fff-progress-circle--semicircle` | Semi variant |
| `fff-progress-circle--has-gap` | Gap arc |
| `fff-progress-circle--has-below-label` | Label under arc |
| `fff-progress-circle--has-gradient` | Gradient fill |
| `fff-progress-circle--has-track-gradient` | Gradient track |
| `fff-progress-circle__svg` | SVG element |
| `fff-progress-circle__content` | Text slots |

---

## ItemCard

modern SaaS-inspired list row / card for settings screens, navigation rows, and mixed action layouts. Renders a horizontal row with optional leading icon, title, description, trailing schema slot (switch, select, actions), and optional chevron.

**Class:** `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard`  
**Extends:** `Filament\Schemas\Components\Component`  
**Traits:** `CanOpenUrl`, `HasDescription`, `HasHeading`, `HasActions` (Filament)

Use inside any Filament schema (`Form`, `Section`, `ItemCardGroup`, `ItemCardStack`, etc.).

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Filament\Support\Icons\Heroicon;

ItemCard::make('Language')
    ->description('Choose your preferred language')
    ->icon(Heroicon::GlobeAlt)
    ->chevron();
```

### Context: `standalone` vs `group`

Rendering mode is controlled by **context**. This changes surface styling (border, shadow, padding, chevron shape).

| Context | When | Visual behaviour |
|---------|------|------------------|
| `auto` (default) | Parent is `ItemCardGroup` → `group`; otherwise → `standalone` | Detected automatically |
| `group` | Row inside a shared group surface | Flat row; no per-row border/shadow |
| `standalone` | Outside `ItemCardGroup` (or forced) | Self-contained card: border, radius, shadow (variant-dependent), chevron in circle |

```php
ItemCard::make('Profile')->standalone(); // force standalone surface
ItemCard::make('Profile')->inGroup();    // force flat group row (even outside a group)
```

### Variants

Set on the **card** (standalone) or inherited visually from the group row context.

| Variant | Standalone appearance |
|---------|---------------------|
| `default` | White surface, border, shadow |
| `secondary` | Light gray surface, no shadow, transparent border |
| `tertiary` | Darker gray surface, no shadow |
| `outline` | Transparent background, visible border, no shadow |
| `transparent` | Transparent background, no border, no shadow |

```php
ItemCard::make('Billing')
    ->variant('outline')
    ->description('Payment methods')
    ->icon(Heroicon::OutlinedCreditCard)
    ->chevron();
```

Invalid variant throws `InvalidArgumentException`.

### Chainable configuration API

#### `make(string|Closure|null $heading = null)`

Factory. Optional heading string (also available via `heading()`).

#### `heading(string|Htmlable|Closure|null $heading)`

Row title rendered in `.item-card__title`. From `HasHeading`.

#### `description(string|Htmlable|Closure|null $description)`

Secondary line under the title. From `HasDescription`. Omit for **title-only** rows.

#### `icon(string|BackedEnum|Htmlable|Closure|null $icon)`

Leading icon inside a rounded square (`.item-card__icon`). Pass `null` or omit for **without-icon** layouts.

```php
->icon(Heroicon::OutlinedUser)
->icon('heroicon-o-user')
```

#### `variant(string|Closure $variant)`

Surface variant. See table above. Default: `default`.

#### `chevron(bool|Closure $condition = true)`

Shows trailing chevron (`Heroicon::ChevronRight`). In **standalone** context the chevron is rendered inside a circular bordered control.

```php
->chevron()
->chevron(fn (): bool => auth()->user()->can('view', $record))
```

#### `context(string|Closure $context)`

Force `auto`, `group`, or `standalone`. Default: `auto`.

#### `standalone()` / `inGroup()`

Shortcuts for `context('standalone')` and `context('group')`.

#### `schema(array|Closure $components)`

Trailing slot content: form fields, `Action`, `ActionGroup`, etc. Rendered in `.item-card__action` on the right.

```php
ItemCard::make('Dark mode')
    ->description('Use dark theme across the app')
    ->icon(Heroicon::Moon)
    ->schema([
        SwitchField::make('dark_mode')->inline()->size('sm'),
    ]),
```

When `schema()` has **any** child components, the row is treated as having **interactive actions** and is **not** pressable (see below).

#### `pressable(bool|Closure $condition = true)`

Marks the row as clickable (`<button>` or `<a>`). Enables hover background and ripple animation.

**Automatic pressable** (when `pressable()` was not called explicitly):

- Parent `ItemCardGroup` has `->pressable()`, **or**
- Row has `->chevron()` and no interactive `schema()` children, **or**
- Row has `->pressableAction()` / `->action()`

**Never pressable** when `schema()` contains fields or actions (switch, select, buttons) — avoids conflicting clicks.

```php
->pressable()        // force on
->pressable(false)   // force off
```

#### `pressableAction(Action|Closure|null $action)`

Registers a Filament `Action` and makes the **entire row** trigger it on click (with ripple). Does **not** render a separate button.

**Closure shorthand** — action name is derived from the heading (`Profile` → `profile`):

```php
ItemCard::make('Profile')
    ->chevron()
    ->pressableAction(fn () => $this->saveProfile());
```

**Full action:**

```php
use Filament\Actions\Action;

ItemCard::make('Security')
    ->chevron()
    ->pressableAction(
        Action::make('openSecurity')
            ->action(fn () => $this->redirect(route('security.settings'))),
    );
```

Component `key` is auto-assigned from the action name (`openSecurity` → `open-security`) unless you set `->key()` manually.

#### `action(?Action $action)`

Low-level action registration (same as Filament `HasActions`). Used internally by `pressableAction()`. Prefer `pressableAction()` for row-click behaviour.

#### `url(string|Closure|null $url, bool|Closure|null $shouldOpenInNewTab = null)`

From `CanOpenUrl`. When set on a pressable row, renders as `<a href="...">` instead of `<button>`. Takes priority over `pressableAction()` for the click target.

```php
ItemCard::make('Documentation')
    ->description('Read the API docs')
    ->icon(Heroicon::OutlinedBookOpen)
    ->chevron()
    ->url('https://example.com/docs', shouldOpenInNewTab: true);
```

#### `openUrlInNewTab(bool|Closure $condition = true)`

From `CanOpenUrl`. Used with `url()`.

### Pressable click priority

When the row is pressable:

1. `url()` → navigation link
2. `action()` / `pressableAction()` → `wire:click` → `mountAction(...)` with `schemaComponent` context
3. Chevron / group `pressable()` only → visual feedback (ripple + hover), no server action unless you add `pressableAction()`

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved variant name |
| `getIcon()` | `string\|BackedEnum\|Htmlable\|null` | Resolved icon |
| `hasChevron()` | `bool` | Whether chevron is shown |
| `getContext()` | `string` | `group` or `standalone` |
| `isPressable()` | `bool` | Whether row renders as button/link |
| `getPressableAction()` | `Action\|null` | Prepared action for row click |
| `hasInteractiveAction()` | `bool` | `true` when `schema()` has children |
| `getUrl()` | `string\|null` | Resolved URL |
| `shouldOpenUrlInNewTab()` | `bool` | New tab flag |

### Form integration

Fields inside `->schema()` are normal Filament form fields. They use the parent form `statePath` (e.g. `data.dark_mode`).

**Validation** runs on parent form submit (`$this->form->validate()`). Rules go on the **field**, not on `ItemCard`. Errors appear **below each field** in the trailing slot (standard Filament wrapper), not under `ItemCardGroup`.

**Autosave / on change:**

```php
SwitchField::make('dark_mode')
    ->live()
    ->afterStateUpdated(fn (bool $state) => $this->saveDarkMode($state)),
```

**Reading values on submit:**

```php
$data = $this->form->getState();
// ['dark_mode' => true, 'event_invites' => 'email', ...]
```

`ItemCard` / `ItemCardGroup` do not add their own state keys — only nested fields do.

### Trailing actions (Filament `Action`)

Use `Action` / `ActionGroup` in `schema()` for visible buttons (not whole-row click):

```php
use Bjanczak\FilamentFlexFields\Filament\Actions\Action;

ItemCard::make('Delete account')
    ->description('Permanently remove your account')
    ->schema([
        Action::make('delete')
            ->label('Delete')
            ->color('danger')
            ->itemCard()           // pill outlined small button style
            ->requiresConfirmation()
            ->action(fn () => $this->deleteAccount()),
    ]),
```

`->itemCard()` is provided by the package `Action` class (`CanStyleItemCardAction`).

### Select & switch in item cards

**Select** — use `SelectField` with `variant('item-card')`:

```php
SelectField::make('language')
    ->options(['en' => 'English', 'pl' => 'Polish'])
    ->variant('item-card')
    ->hiddenLabel(),
```

**Switch** — use `SwitchField` with `inline()` and optional `size('sm')`:

```php
SwitchField::make('dark_mode')
    ->inline()
    ->size('sm'),
```

### Form panel layout (`item-card--form-panel`)

Default `ItemCard` is a **horizontal row** (icon | title | trailing control). For **multi-field form sections**, add the `item-card--form-panel` class via `extraAttributes()`:

- Row 1: icon + title + description
- Row 2: child fields at **full width**

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

ItemCardStack::make()
    ->columns(['default' => 1, 'sm' => 2])
    ->extraAttributes(['class' => 'fff-form-layout fff-form-layout--grid'])
    ->schema([
        ItemCard::make('Profile')
            ->description('How you appear to guests')
            ->icon(GravityIcon::Person)
            ->variant('outline')
            ->standalone()
            ->extraAttributes(['class' => 'item-card--form-panel'])
            ->columns(1)
            ->schema([
                FlexTextInput::make('name')->label('Display name'),
                FlexTextInput::make('email')->label('Email')->email(),
            ]),
        ItemCard::make('Contact')
            ->description('Phone and country')
            ->icon(GravityIcon::Handset)
            ->variant('outline')
            ->standalone()
            ->extraAttributes(['class' => 'item-card--form-panel'])
            ->columns(1)
            ->schema([
                PhoneField::make('phone')->label('Phone'),
            ]),
    ]);
```

| Class | When to use |
|-------|-------------|
| `item-card--form-panel` | Vertical form section inside a card |
| `fff-form-layout--grid` | Two-column card grid on `ItemCardStack` (from `sm` / `640px`) |

> **Do not** nest full-width fields in a plain `ItemCard` without `item-card--form-panel` — controls render in the narrow trailing slot.

### Inherited Filament schema component API

`ItemCard` extends `Filament\Schemas\Components\Component`. These methods work as in core Filament:

| Method | Description |
|--------|-------------|
| `schema()` / `components()` | Child components in the trailing slot |
| `key()` | Explicit schema component key (optional; auto-set for `action()`) |
| `id()` | HTML `id` attribute |
| `hidden()` / `visible()` | Conditional rendering |
| `hiddenOn()` / `visibleOn()` | Hide/show per operation |
| `extraAttributes()` | Extra HTML attributes on root element |
| `columnSpan()` / `columnStart()` | Grid layout when parent uses columns |
| `columns()` / `gap()` | Child grid (default `gap(0)`, `columns(1)` in `setUp()`) |
| `registerActions()` | Register multiple named actions |
| `getAction()` / `getActions()` | Resolve registered actions |
| `actionSchemaModel()` | Model for action forms |
| `hasAction()` | Whether a named action exists |

All configuration methods accept `Closure` with Filament utility injection.

### HTML structure (data slots)

| Slot | Element |
|------|---------|
| `data-slot="item-card"` | Root (`div`, `button`, or `a`) |
| `item-card-icon` | Leading icon container |
| `item-card-content` | Title + description |
| `item-card-action` | Trailing schema (fields, actions) |
| `item-card-chevron` | Chevron indicator |

### CSS classes

| Class | Meaning |
|-------|---------|
| `item-card` | Base row |
| `item-card--{variant}` | Surface variant |
| `item-card--context-standalone` / `--context-group` | Layout context |
| `item-card--pressable` | Interactive row |
| `item-card--form-panel` | Vertical form layout (header row + full-width fields) |

---

## ItemCardGroup

Grouped list surface: multiple `ItemCard` rows share one bordered container (SaaS **item-card-group**). Optional group header, row separators, group-level variant, and group-wide pressable rows.

**Class:** `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardGroup`  
**Extends:** `Filament\Schemas\Components\Component`  
**Traits:** `HasDescription`, `HasHeading`

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardGroup;

ItemCardGroup::make('Settings')
    ->description('Manage your account preferences')
    ->separated()
    ->schema([
        ItemCard::make('Language')
            ->description('Enable automatic language detection')
            ->icon(Heroicon::GlobeAlt)
            ->schema([
                SwitchField::make('language_auto')->inline()->size('sm'),
            ]),
        ItemCard::make('Event Invites')
            ->icon(Heroicon::OutlinedEnvelope)
            ->schema([
                SelectField::make('event_invites')
                    ->options([...])
                    ->variant('item-card')
                    ->hiddenLabel(),
            ]),
    ]);
```

Child `ItemCard` rows automatically use **group** context (`item-card--context-group`).

### Chainable configuration API

#### `make(string|Closure|null $heading = null)`

Factory. Optional group title.

#### `heading(string|Htmlable|Closure|null $heading)`

Group title. Rendered in `.item-card-group__title` (or outside header — see `headerStyle()`).

#### `description(string|Htmlable|Closure|null $description)`

Group subtitle in `.item-card-group__description`.

#### `schema(array|Closure $components)`

Child rows — typically `ItemCard` instances. Aliases `components()`.

#### `variant(string|Closure $variant)`

Surface style for the **group container** (not per-row when inside group).

| Variant | Appearance |
|---------|------------|
| `default` | White background, border, shadow |
| `secondary` | Light gray background |
| `tertiary` | Darker gray background |
| `outline` | Transparent background, border |
| `transparent` | No background, no border |

Default: `default`.

Per-row `ItemCard::variant()` still applies when cards are **standalone**; inside a group, rows are flat and the **group** variant colours the shared surface.

#### `layout(string|Closure $layout)`

| Value | Description |
|-------|-------------|
| `list` (default) | Vertical list of rows |
| `grid` | Grid layout for child cards |

Invalid layout throws `InvalidArgumentException`.

#### `divided(bool|Closure $condition = true)`

Enables horizontal separators between rows. Adds class `item-card-group--divided`.

#### `separated(bool|Closure $condition = true)`

Alias for `divided()`.

#### `withoutSeparators()`

Shortcut for `divided(false)`. Default: separators **off**.

#### `headerStyle(string|Closure $style)`

| Value | Description |
|-------|-------------|
| `embedded` (default) | Title and description inside the bordered group box |
| `outside` | Title and description **above** the box; only rows are inside the surface |

Outside header uses wrapper `.item-card-group-host` and renders header as sibling of `.item-card-group` surface.

```php
ItemCardGroup::make('Source Control')
    ->headerStyle('outside')
    ->separated()
    ->schema([...]);
```

#### `pressable(bool|Closure $condition = true)`

When enabled, **all child rows without interactive `schema()`** become pressable (hover + ripple). Rows with switch/select/action children stay non-pressable.

Does not add actions by itself — combine with `ItemCard::pressableAction()` or `->chevron()` + `->url()` per row.

```php
ItemCardGroup::make('Account')
    ->pressable()
    ->separated()
    ->schema([
        ItemCard::make('Profile')->chevron()->pressableAction(...),
    ]);
```

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getLayout()` | `string` | `list` or `grid` |
| `getVariant()` | `string` | Group variant |
| `isDivided()` | `bool` | Whether row separators are shown |
| `getHeaderStyle()` | `string` | `embedded` or `outside` |
| `areRowsPressable()` | `bool` | Group-level pressable flag |

### Form integration

Same as `ItemCard`: fields nested in child `ItemCard::schema()` participate in the parent form state and validation. `ItemCardGroup` has no form state of its own.

### Validation errors

Displayed **per field** under the control in the row's trailing slot. `ItemCardGroup` does not render a group-level error summary.

### Inherited Filament schema component API

| Method | Description |
|--------|-------------|
| `schema()` / `components()` | Child `ItemCard` rows |
| `key()`, `id()` | Schema identity |
| `hidden()`, `visible()`, `hiddenOn()`, `visibleOn()` | Conditional display |
| `extraAttributes()` | HTML attributes on group root |
| `columnSpan()`, `columns()`, `gap()` | Layout (defaults: `gap(0)`, `columns(1)`) |

### HTML structure (data slots)

| Slot | Element |
|------|---------|
| `data-slot="item-card-group"` | Root or host wrapper |
| `item-card-group-header` | Title + description |
| `item-card-group-surface` | Bordered box (outside header mode) |
| `item-card-group-content` | Child schema grid |

### CSS classes

| Class | Meaning |
|-------|---------|
| `item-card-group` | Group surface |
| `item-card-group-host` | Outside-header wrapper |
| `item-card-group--{layout}` | `list` / `grid` |
| `item-card-group--{variant}` | Surface variant |
| `item-card-group--divided` | Row separators enabled |

---

## ItemCardStack

Vertical stack wrapper for **standalone** `ItemCard` components. Adds consistent **gap** between sibling cards (SaaS vertical stack / pressable list).

**Class:** `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack`  
**Extends:** `Filament\Schemas\Components\Component`

Does not change card styling — children auto-detect `standalone` context when not inside `ItemCardGroup`.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;

ItemCardStack::make()
    ->schema([
        ItemCard::make('Profile')
            ->description('Update your personal information')
            ->icon(Heroicon::OutlinedUser)
            ->chevron()
            ->pressableAction(fn () => $this->openProfile()),
        ItemCard::make('Security')
            ->variant('secondary')
            ->description('Manage passwords and 2FA')
            ->icon(Heroicon::OutlinedKey)
            ->chevron(),
    ]);
```

### Chainable configuration API

#### `make()`

Factory. No heading — use child cards for content.

#### `stackGap(string|Closure $stackGap)`

Spacing between stacked cards.

| Value | Gap |
|-------|-----|
| `sm` | `0.5rem` |
| `md` (default) | `0.75rem` |
| `lg` | `1rem` |

```php
ItemCardStack::make()->stackGap('lg')->schema([...]);
```

> **Note:** Named `stackGap()` to avoid clashing with Filament's grid `gap()` method on `Component`.

#### `schema(array|Closure $components)`

Child `ItemCard` components.

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getStackGap()` | `string` | `sm`, `md`, or `lg` |

### Inherited Filament schema component API

`key()`, `id()`, `hidden()`, `visible()`, `extraAttributes()`, `columnSpan()`, `columns()`, `gap()` (grid gap for children, default `0` in `setUp()`).

### CSS classes

| Class | Meaning |
|-------|---------|
| `fff-item-card-stack` | Stack wrapper |
| `fff-item-card-stack--{sm\|md\|lg}` | Gap size modifier |

---

## Layout components — quick comparison

| Need | Component / pattern |
|------|-------------------|
| Shared box, multiple flat rows | `ItemCardGroup` |
| Separate card per row, gaps | `ItemCard` + `ItemCardStack` |
| Form fields inside a card surface | `ItemCard` + `item-card--form-panel` |
| Two-column form cards | `ItemCardStack` + `fff-form-layout--grid` + `columns(2)` |
| Tabbed form without Filament `Tabs` chrome | `SegmentTabs` + `SegmentTab::schema()` |
| Hero / banner at top of form | `CoverCard` + `fullWidth()` + wide `ratio()` |
| Locale tabs for translatable fields | `TranslatableFields` |
| Row with switch/select | `ItemCard::schema([...])` inside group |
| Whole row navigates | `->chevron()->url()` or `->pressableAction()` |
| Settings list with header above box | `ItemCardGroup::headerStyle('outside')` |

---

## Form layout patterns

Recipes for modern forms **without** heavy Filament `Section` / `Grid` / `Fieldset` chrome. See **Modern form layouts** in Flex Fields Playground.

### 1. Tabbed editor — `SegmentTabs` + `CoverCard`

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ItemCardStack;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;

ItemCardStack::make()
    ->stackGap('lg')
    ->extraAttributes(['class' => 'fff-form-layout fff-form-layout--wide'])
    ->schema([
        CoverCard::make()
            ->backgroundImage('https://cdn.example.com/hero.jpg')
            ->ratio('21:9')
            ->fullWidth()
            ->topTitle('New listing'),
        SegmentTabs::make('Listing')
            ->variant('ghost')
            ->fullWidth()
            ->tabs([
                SegmentTab::make('Details')->schema([
                    FlexTextInput::make('name')->label('Name'),
                    FlexTextareaField::make('description')->label('Description'),
                ]),
                SegmentTab::make('Location')->schema([
                    CountryField::make('country'),
                    PhoneField::make('phone'),
                ]),
            ]),
    ]);
```

### 2. iOS settings — `ItemCardGroup`

```php
ItemCardGroup::make('Publishing')
    ->headerStyle('outside')
    ->variant('secondary')
    ->separated()
    ->schema([
        ItemCard::make('Public listing')
            ->icon(GravityIcon::Eye)
            ->schema([SwitchField::make('public')->inline()->size('sm')]),
        ItemCard::make('Notifications')
            ->icon(GravityIcon::Bell)
            ->schema([
                SelectField::make('channel')
                    ->options(['email' => 'Email', 'push' => 'Push'])
                    ->variant('item-card')
                    ->hiddenLabel(),
            ]),
    ]);
```

### 3. Two-column profile cards — `ItemCardStack` grid

```php
ItemCardStack::make()
    ->columns(['default' => 1, 'sm' => 2])
    ->extraAttributes(['class' => 'fff-form-layout fff-form-layout--grid'])
    ->schema([
        ItemCard::make('Profile')
            ->extraAttributes(['class' => 'item-card--form-panel'])
            ->variant('outline')->standalone()
            ->columns(1)
            ->schema([/* fields */]),
        ItemCard::make('Contact')
            ->extraAttributes(['class' => 'item-card--form-panel'])
            ->variant('outline')->standalone()
            ->columns(1)
            ->schema([/* fields */]),
        ItemCard::make('Visibility')
            ->extraAttributes(['class' => 'item-card--form-panel'])
            ->columnSpanFull()
            ->schema([ChoiceCards::make('status')->gridColumns(3)->options([...])]),
    ]);
```

### Layout CSS helpers

| Class | Purpose |
|-------|---------|
| `fff-form-layout` | Constrains width (`max-width: 42rem`) for single-column forms |
| `fff-form-layout--wide` | Removes width cap (banners, tabbed editors) |
| `fff-form-layout--grid` | Enables two-column `ItemCardStack` grid from `640px` |
| `item-card--form-panel` | Vertical field layout inside `ItemCard` |

---


## SlugField & TitleSlugField

### Summary

Pole permalinku dla Filament: tytuł + slug w jednym bloku, podgląd URL na żywo, edycja inline, przyciski Copy/Visit/Regenerate, walidacja unikalności.

> **Spatie `laravel-sluggable` jest opcjonalne.** Domyślnie slug powstaje z tytułu przez `Str::slug()` w przeglądarce i zapisuje się do bazy jak zwykłe pole formularza. Pakiet Spatie dodajesz tylko wtedy, gdy chcesz te same reguły co przy zapisie modelu (suffixy `-2`, `preventOverwrite`, itd.).

| | |
|---|---|
| **Slug field class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField` |
| **Title + slug factory** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField` |
| **Convenience schema** | `SlugField::withTitle()` → returns a `FusedGroup` (same as `TitleSlugField::make()`) |
| **State type** | `string\|null` (normalized slug; homepage slug is `'/'` when enabled) |
| **FieldType** | `slug` |
| **Spatie** | Opcjonalne — patrz [Integracja ze Spatie](#spatie-laravel-sluggable-integration) |

---

### Start here — integracja bez Spatie (domyślna)

**Nie instalujesz nic poza `filament-flex-fields`.** Model nie potrzebuje traitów ani pakietów slug — wystarczy kolumna w bazie i `fillable`.

#### Kto za co odpowiada

| Co | Kto to robi | Czy musisz coś kodować? |
|----|-------------|-------------------------|
| Podgląd sluga podczas pisania tytułu | `SlugField` (Alpine + `Str::slug`) | Nie — działa automatycznie |
| Zapis wartości `slug` do bazy | Filament + Eloquent | Tak — kolumna + `fillable` |
| Unikalność sluga w formularzu | `SlugField` (reguła `unique`) | Nie — domyślnie włączone |
| Suffix `-2` przy kolizji w bazie | **Tylko Spatie** (`HasSlug`) | Nie — bez Spatie slug musi być unikalny już w formularzu |
| Zachowanie create vs edit | `TitleSlugField` | Nie — domyślnie na edit slug się nie zmienia |

#### Checklist — 4 kroki

```
1. Migracja     → kolumny title + slug (slug zwykle unique)
2. Model        → slug w $fillable (BEZ HasSlug, BEZ Spatie)
3. Formularz    → TitleSlugField::make()
4. Config       → opcjonalnie url_host w config (permalink w UI)
```

#### Krok 1 — migracja

```php
Schema::create('posts', function (Blueprint $table): void {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();  // wymagane: kolumna na slug
    $table->timestamps();
});
```

#### Krok 2 — model (minimalny, bez Spatie)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',   // konieczne — inaczej Filament nie zapisze sluga
    ];
}
```

Model **nie potrzebuje**:
- `use HasSlug` ani `getSlugOptions()`
- observera generującego slug
- mutatora `setSlugAttribute`
- `composer require spatie/laravel-sluggable`

Slug trafia do rekordu tak samo jak `title` — z danych formularza.

#### Krok 3 — formularz Filament (minimum)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        TitleSlugField::make(),   // tyle. Brak parametrów = działa.
        // ...pozostałe pola
    ]);
}
```

To tworzy: pole **title** + ukrytą flagę auto-sync + pole **slug** w jednym `FusedGroup`.

#### Krok 4 — config (opcjonalny, dla paska permalinku)

Jeśli chcesz pod slugiem widzieć `https://twoja-domena.pl/blog/moj-wpis`:

```php
// config/filament-flex-fields.php (po publish)
'slug' => [
    'field_title' => 'title',      // nazwa pola tytułu w formularzu
    'field_slug' => 'slug',        // nazwa pola sluga w formularzu
    'url_host' => env('APP_URL'),  // null = brak pełnego paska URL
],
```

Albo tylko w Resource, bez zmiany configu:

```php
TitleSlugField::make(
    urlHost: config('app.url'),
    urlPath: '/blog/',
),
```

#### Parametry `TitleSlugField::make()` — co jest wymagane?

| Parametr | Wymagany? | Domyślnie | Kiedy go podać |
|----------|-----------|-----------|----------------|
| *(żaden)* | — | — | `TitleSlugField::make()` wystarczy na start |
| `fieldTitle` | Nie | `'title'` | Inna nazwa kolumny/pola tytułu |
| `fieldSlug` | Nie | `'slug'` | Inna nazwa kolumny/pola sluga |
| `urlHost` | Nie | z config lub `null` | Chcesz podgląd pełnego URL |
| `urlPath` | Nie | `null` | Prefix ścieżki, np. `/blog/` |
| `preserveSlugOnEdit` | Nie | `true` | `false` = slug zawsze sync z tytułem |
| `translatableLocales` | Nie | z config lub `null` | Włącza zakładki językowe (`TranslatableFields`) |
| `slugSourceLocale` | Nie | `app.locale` / pierwszy locale | Z którego języka tytułu generować slug |
| `requiredTitleLocales` | Nie | tylko `slugSourceLocale` | `'all'`, `['en']` lub `null` — które locale tytułu są wymagane |
| `spatieTranslatable` | Nie | `false` | Flaga konfiguracyjna dla modeli Spatie — patrz [Translatable titles](#translatable-titles-single-slug) |
| `titleLocaleConfigurator` | Nie | `null` | `fn (FlexTextInput $field, string $locale) => $field` |
| `translatableFieldsConfigurator` | Nie | `null` | `fn (TranslatableFields $fields) => $fields->…` — pełna konfiguracja zakładek tytułu |
| `spatieModel` | Nie | `null` | **Tylko** dla Spatie **Sluggable** (`HasSlug`) — nie mylić z Translatable |

#### Co się dzieje automatycznie (bez Spatie)

| Sytuacja | Zachowanie |
|----------|------------|
| **Create** — użytkownik pisze tytuł | Slug aktualizuje się na żywo (`Hello World` → `hello-world`) |
| **Edit** — zmiana tytułu | Slug **nie** zmienia się (chroni opublikowany URL) |
| **Edit** — ręczna zmiana sluga | Auto-sync wyłącza się; badge **Custom**; pojawia się **Regenerate** |
| **Zapis** | Wartość `slug` z formularza → kolumna `slug` w bazie |
| **Duplikat sluga** | Błąd walidacji w formularzu (przed zapisem) |

#### Generowanie sluga bez Spatie (technicznie)

```
Tytuł (live) → debounce 400ms → Str::slug() → normalizeSlug() → pole slug
```

Nie ma requestów do serwera. Nie musisz nic konfigurować w modelu.

#### Trzy sposoby dodania title + slug

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;

// 1) Zalecane — jedna linia
TitleSlugField::make(),

// 2) To samo, inny import
SlugField::withTitle(),

// 3) Ręcznie — pełna kontrola
SlugField::make('slug')
    ->source('title')
    ->titleField(FlexTextInput::make('title')->required()),
```

#### Kiedy przejść na Spatie?

Dodaj Spatie **dopiero gdy** potrzebujesz logiki przy **zapisie rekordu**, której formularz sam nie załatwi:

- automatyczny suffix `-2`, `-3` przy kolizji w bazie
- `preventOverwrite` — nigdy nie nadpisuj sluga po publikacji
- `skipGenerateWhen`, `extraScope`, wiele pól źródłowych
- ten sam `SlugOptions` w podglądzie formularza i przy `save()`

Do tego: [Spatie `laravel-sluggable` integration](#spatie-laravel-sluggable-integration).

#### Typowe błędy (bez Spatie)

| Objaw | Przyczyna | Fix |
|-------|-----------|-----|
| Slug nie zapisuje się do bazy | Brak `slug` w `$fillable` | Dodaj do `fillable` |
| Brak paska URL pod slugiem | `url_host` = `null` | `APP_URL` w `.env` lub `->urlHost(...)` |
| Slug nie zmienia się z tytułu | Ręczna edycja sluga | Kliknij **Regenerate** |
| Walidacja: slug już istnieje | Duplikat w bazie | Zmień slug lub usuń stary rekord |
| Pola `name` / `handle` zamiast title/slug | Domyślne nazwy | `fieldTitle:` / `fieldSlug:` lub config |

> **Następne sekcje:** [Układ formularza](#default-form-layout-fusedgroup) → [Instalacja](#instalacja-i-assety) → [Config](#konfiguracja-pakietu-configfilament-flex-fieldsphp) → [Pełny przykład](#pełny-przykład-od-zera-migracja--model--resource) → [Spatie](#spatie-laravel-sluggable-integration)

---

### Default form layout (`FusedGroup`)

`TitleSlugField::make()` **zawsze** zwraca `Filament\Schemas\Components\FusedGroup` — ten sam układ z parametrami lub bez:

```php
// Bez Spatie (domyślna ścieżka)
TitleSlugField::make(),

// Z permalinkiem (nadal bez Spatie)
TitleSlugField::make(
    urlHost: config('app.url'),
    urlPath: '/blog/',
),

// Ze Spatie — identyczny wygląd, inna logika generowania podglądu
TitleSlugField::make(spatieModel: Post::class),
```

> **Ważne:** `spatieModel` zmienia **tylko** sposób generowania podglądu sluga (serwer + `SlugOptions`). **Nie zmienia** układu formularza.

#### Co jest wewnątrz `FusedGroup`

| # | Komponent | State path (domyślnie) | Widoczny? | Rola |
|---|-----------|------------------------|-----------|------|
| 1 | `FlexTextInput` | `title` | Tak | Pole tytułu, `live()`, auto-sync do sluga |
| 2 | `Hidden` | `slug_auto_update_disabled` | Nie | Flaga: użytkownik ręcznie edytował slug |
| 3 | `SlugField` | `slug` | Tak | Permalink, inline edit, akcje Copy/Visit/… |

Grupa ma klasę CSS `fff-title-slug-fused-group` (bez standardowego bordera Filament między polami).

#### Domyślny wygląd (ASCII)

Gdy `config('filament-flex-fields.slug.url_host')` jest ustawione (np. `APP_URL`):

```
┌─ Title (FlexTextInput) ─────────────────────────────────────┐
│  Label: "Title"                                              │
│  [ Luxury Yacht Charter in the Mediterranean          ]      │
└──────────────────────────────────────────────────────────────┘

┌─ Slug (SlugField) — etykieta ukryta ────────────────────────┐
│  Permalink                                    [ Auto ]       │
│  🔒 wyachts.test/charters/luxury-yacht-charter              │
│                                                              │
│  [ Edit ]                    [ Regenerate ] [ Copy ] [ Visit ]│
└──────────────────────────────────────────────────────────────┘
```

Gdy `url_host` jest `null` (brak permalinku w config):

```
┌─ Title ─────────────────────────────────────────────────────┐
│  Label: "Title"                                              │
│  [ My post title                                        ]    │
└──────────────────────────────────────────────────────────────┘

┌─ Slug — tryb inline edit ───────────────────────────────────┐
│  wyachts.test/charters/my-post-title   (preview + Edit)      │
│  [ Edit ]                              [ Copy ]              │
└──────────────────────────────────────────────────────────────┘
```

#### Tabela domyślnych wartości wizualnych

| Element | Domyślna wartość | Skąd się bierze |
|---------|------------------|-----------------|
| Nazwa pola title | `title` | `config('filament-flex-fields.slug.field_title')` |
| Nazwa pola slug | `slug` | `config('filament-flex-fields.slug.field_slug')` |
| Label title | `"Title"` | `Str::headline($fieldTitle)` |
| Placeholder title | `"Title"` | j.w. |
| Label slug | ukryty | `slugLabel: null` → `hiddenLabel()` |
| Rozmiar sluga | `md` (40px) | `config('filament-flex-fields.ui.slug_size')` |
| Wariant sluga | `primary` | `config('filament-flex-fields.ui.slug_variant')` |
| Permalink host | `APP_URL` lub `null` | `config('filament-flex-fields.slug.url_host')` |
| Etykiety przycisków | tekst + ikona | `config('filament-flex-fields.slug.action_button_labels')` |
| Ikony | Gravity UI | np. `gravityui-pencil`, `gravityui-copy` |
| Badge | Auto / Custom | Alpine — po ręcznej edycji sluga |

#### Ten sam layout — trzy sposoby wywołania

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;

// A) Fabryka (zalecane) — bez Spatie
TitleSlugField::make(),

// B) Alias — identyczny FusedGroup
SlugField::withTitle(),

// C) Więcej opcji przez slugConfigurator (np. permalink)
TitleSlugField::make(
    slugConfigurator: fn (SlugField $slug) => $slug
        ->urlHost(config('app.url'))
        ->urlPath('/blog/'),
),
```

**Helper do testów Livewire** — nazwa ukrytego pola auto-sync:

```php
TitleSlugField::autoUpdateDisabledFieldName('slug');    // slug_auto_update_disabled
TitleSlugField::autoUpdateDisabledFieldName('permalink'); // permalink_auto_update_disabled
```

---

### Instalacja i assety

Pakiet jest częścią `janczakb/filament-flex-fields`. **Ścieżka bez Spatie wymaga tylko assetów pakietu** — bez dodatkowych `composer require`.

```bash
# W katalogu pakietu / aplikacji
npm run build:js:slug-field
npm run build:css
php artisan filament:assets
```

#### Opcjonalnie: Spatie (dopiero gdy potrzebujesz)

Instaluj **tylko** jeśli model ma `HasSlug` / `getSlugOptions()` i chcesz zgodny podgląd w formularzu:

```bash
composer require spatie/laravel-sluggable
```

Bez tego pakietu `TitleSlugField::make()` działa w pełni — generowanie przez `Str::slug()` w przeglądarce.

---

### Konfiguracja pakietu (`config/filament-flex-fields.php`)

Opublikuj config:

```bash
php artisan vendor:publish --tag=filament-flex-fields-config
```

Klucze dotyczące sluga:

```php
// config/filament-flex-fields.php
return [
    'slug' => [
        // Domyślne nazwy pól w TitleSlugField::make()
        'field_title' => 'title',
        'field_slug' => 'slug',

        // Host w pasku permalink (null = brak paska hosta)
        'url_host' => env('APP_URL'),

        // true = przyciski z tekstem; false = same ikony + tooltip
        'action_button_labels' => true,
    ],

    'ui' => [
        'slug_size' => 'md',      // sm | md | lg
        'slug_variant' => 'primary', // primary | secondary | …
    ],
];
```

**Przykład — blog z polskimi nazwami pól:**

```php
// config/filament-flex-fields.php
'slug' => [
    'field_title' => 'tytul',
    'field_slug' => 'adres',
    'url_host' => 'https://mojblog.pl',
],
```

```php
// W Resource — bez podawania nazw pól
TitleSlugField::make(
    titleLabel: 'Tytuł wpisu',
    urlPath: '/wpisy/',
),
```

---

### Pełny przykład od zera (migracja → model → Resource)

> Rozwinięcie sekcji [Start here — integracja bez Spatie](#start-here--integracja-bez-spatie-domyślna). Kroki 1–3 to minimum; krok 4 (Spatie) jest **opcjonalny**.

#### 1. Migracja

```php
Schema::create('posts', function (Blueprint $table): void {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('body')->nullable();
    $table->timestamps();
});
```

#### 2. Model (bez Spatie — wystarczy na produkcję)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['title', 'slug', 'body'];
}
```

**Nie dodawaj** `HasSlug` ani `getSlugOptions()` — chyba że przechodzisz na krok 4 poniżej.

#### 3. Filament Resource (bez Spatie)

```php
namespace App\Filament\Resources\Posts\Schemas;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TitleSlugField::make(
                urlHost: config('app.url'),
                urlPath: '/blog/',
            ),

            RichEditor::make('body'),
        ]);
    }
}
```

#### 4. (Opcjonalnie) Ten sam Resource ze Spatie

Dodaj ten krok **tylko** gdy potrzebujesz suffixów, `preventOverwrite` lub innych reguł `SlugOptions` przy zapisie.

```php
// app/Models/Post.php
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasSlug;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
```

```php
// Formularz — wygląd identyczny, slug preview zgodny z modelem
TitleSlugField::make(
    spatieModel: Post::class,
    urlHost: config('app.url'),
    urlPath: '/blog/',
),
```

> **Dwie warstwy:** formularz pokazuje podgląd sluga na żywo; przy zapisie rekordu Spatie `HasSlug` może dodać suffix (`-2`) lub zastosować `preventOverwrite` — to normalne.

---

### Książka kucharska — typowe scenariusze

#### Scenariusz 1: Blog — create + edit (domyślne zachowanie)

```php
TitleSlugField::make(
    urlHost: config('app.url'),
    urlPath: '/posts/',
),
```

- **Create:** tytuł → slug na żywo.
- **Edit:** zmiana tytułu **nie** zmienia sluga.

#### Scenariusz 2: Zawsze synchronizuj slug z tytułem

```php
TitleSlugField::make(
    preserveSlugOnEdit: false,
    urlHost: config('app.url'),
),
```

#### Scenariusz 3: Slug tylko do odczytu na edycji

```php
TitleSlugField::make(
    slugConfigurator: fn (SlugField $field) => $field
        ->slugReadOnly(fn (SlugField $component): bool => $component->getOperation() === 'edit'),
),
```

#### Scenariusz 4: Własny tytuł (RichEditor) + slug

```php
use Filament\Forms\Components\RichEditor;

TitleSlugField::make(
    titleField: RichEditor::make('title')
        ->required()
        ->columnSpanFull(),
    slugConfigurator: fn (SlugField $slug) => $slug
        ->urlHost(config('app.url'))
        ->urlPath('/news/'),
),
```

#### Scenariusz 5: Unikalność sluga w obrębie tenanta

```php
TitleSlugField::make(
    slugConfigurator: fn (SlugField $slug) => $slug
        ->slugUniqueModel(Post::class)
        ->slugUniqueScope(fn ($query) => $query->where('tenant_id', filament()->getTenant()->id)),
),
```

#### Scenariusz 6: Bez permalinku — sam slug w formularzu

```php
TitleSlugField::make(
    urlHost: null,
    slugConfigurator: fn (SlugField $slug) => $slug
        ->permalinkPreview(false)
        ->inlineEditing(false),
),
```

#### Scenariusz 7: Strona główna CMS (`/`)

```php
TitleSlugField::make(
    slugConfigurator: fn (SlugField $slug) => $slug
        ->allowHomepageSlug()
        ->slugPattern('/^(\/|[a-z0-9]+(?:-[a-z0-9]+)*)$/'),
),
```

#### Scenariusz 8: Repeater — wiersz z tytułem i slugiem

```php
use Filament\Forms\Components\Repeater;

Repeater::make('sections')
    ->schema([
        FlexTextInput::make('title')->required()->live(),
        SlugField::make('slug')
            ->source('title')
            ->urlHost(config('app.url'))
            ->urlPath('/docs/'),
    ])
    ->columns(1),
```

Ścieżki zagnieżdżone (`sections.0.title` → `sections.0.slug`) są rozwiązywane automatycznie.

#### Scenariusz 9: Sam slug bez tytułu (ręczny)

```php
SlugField::make('slug')
    ->label('URL slug')
    ->autoGenerate(false)
    ->inlineEditing(false)
    ->required(),
```

#### Scenariusz 10: Spatie + wiele pól źródłowych

```php
// Model
public function getSlugOptions(): SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom(['title', 'subtitle'])
        ->saveSlugsTo('slug');
}

// Formularz
TitleSlugField::make(spatieModel: Post::class),
FlexTextInput::make('subtitle')->live(),
```

---


### Quick start — Filament Resource (create + edit)

Skrót sekcji [Start here](#start-here--integracja-bez-spatie-domyślna) — **bez Spatie**:

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        TitleSlugField::make(
            urlHost: config('app.url'),  // opcjonalne — permalink w UI
            urlPath: '/blog/',           // opcjonalne — prefix ścieżki
        ),
        // ...other fields
    ]);
}
```

Wymagania po stronie aplikacji: kolumna `slug` w migracji + `slug` w `$fillable` modelu. Nic więcej.

**What happens automatically:**

| Event | Behaviour |
|-------|-----------|
| User types title on **create** | Slug updates live (`hello-world`) |
| User opens **edit** | Existing slug is preserved when title changes |
| User clicks **Edit** on slug and saves a custom value | Auto-sync stops; badge shows **Custom** |
| User clicks **Regenerate** (after manual edit) | Slug is rebuilt from current title |
| Duplicate slug on save | Validation error (unique rule) |

---

### How slug generation works

```
Title input (live)
       │
       ▼
Debounce (generationDebounce, default 400ms)
       │
       ├── slugifyUsing() set? ──► your Closure
       │
       ├── spatieModel() + Spatie installed? ──► SpatieSlugIntegration
       │         (GenerateSlugAction, SlugOptions, #[Sluggable] attribute)
       │
       └── else ──► SlugGenerator::fromString() (Str::slug + normalize)
       │
       ▼
normalizeSlug() ──► form state + validation
```

**Form preview vs model save:**

| Layer | Responsibility |
|-------|----------------|
| **SlugField (browser / optional server preview)** | Shows what the slug *will* look like while typing |
| **Eloquent model + Spatie `HasSlug`** | Final slug on `create` / `update` (unique suffix, `preventOverwrite`, etc.) |

When Spatie is configured, the field uses the **same `SlugOptions`** as your model so previews match production rules (separator, language, max length, multi-field sources, `extraScope`, suffix start, Closure sources).

---

### Create vs edit

#### Default (preserve slug on edit)

```php
TitleSlugField::make(
    preserveSlugOnEdit: true, // default
),
```

On **edit**, changing the title does **not** change the slug. Good for published URLs.

#### Always sync slug from title

```php
TitleSlugField::make(
    preserveSlugOnEdit: false,
),
```

#### Read-only slug on edit only

```php
TitleSlugField::make(
    slugReadOnly: fn (): bool => true, // always
),

// Or only on edit (Filament operation):
TitleSlugField::make(
    slugConfigurator: fn (SlugField $field) => $field
        ->slugReadOnly(fn (SlugField $component): bool => $component->getOperation() === 'edit'),
),
```

#### Read-only title

```php
TitleSlugField::make(
    titleReadOnly: true,
),
```

---

> **Universal locale tabs:** For any translatable attribute (title, body, metadata, …), use the dedicated [TranslatableFields](#translatablefields) component. The section below covers **TitleSlugField** only — translatable **titles** with a **single shared slug**.

---

### Translatable titles (single slug)

> For generic translatable fields (body, excerpt, metadata, …) without slug coupling, use [TranslatableFields](#translatablefields) instead.

Optional multi-language **title** with **one shared slug**. Locale switching uses package **`TranslatableFields`** (built on `SegmentTabs`).

| Layer | Behaviour |
|-------|-----------|
| **Title form state** | Dot paths: `title.pl`, `title.en`, … |
| **Title DB state** | Nested array / JSON: `{"pl":"…","en":"…"}` |
| **Slug form + DB** | Single string — **not** translatable |
| **Auto-sync** | Only when the **source locale** tab changes |
| **Required title** | By default, only the **source locale** is `required()` |

#### What is implemented today

| Feature | Status |
|---------|--------|
| `TranslatableFields` per locale | Yes |
| `translatableFieldsConfigurator` passthrough | Yes — RTL, badges, `modifyFieldsUsing()`, Spatie flag, etc. |
| `slugSourceLocale` — pick slug source language | Yes |
| Works without Spatie (`array` / `json` cast) | Yes |
| Hydrate edit form from JSON column | Yes |
| Auto-detect `HasTranslations` on record (when package installed) | Yes — `getTranslation($attribute, $locale, false)` per tab |
| `spatieTranslatable: true` config flag | Yes — documents intent for FlexField / config |
| Separate Spatie package dependency in this package | **No** — optional `composer require spatie/laravel-translatable` |
| Per-locale slug | **No** — one slug by design |
| Spatie locale fallback in form tabs | **No** — each tab shows that locale only |
| Deep runtime bridge like `laravel-sluggable` | **No** — compatible state shape + hydrate/save, not a full plugin adapter |

> **`spatieModel` ≠ Spatie Translatable.** `spatieModel` on `TitleSlugField` is for [Spatie Sluggable](#spatie-laravel-sluggable-integration) (`HasSlug`). For translations use `translatableLocales` (+ optional `spatieTranslatable`).

#### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;

TitleSlugField::make(
    translatableLocales: ['pl' => 'PL', 'en' => 'EN', 'fr' => 'FR'],
    slugSourceLocale: 'pl',
    urlHost: config('app.url'),
    urlPath: '/pages/',
);
```

Changing EN/FR titles does **not** change the slug. Only the `slugSourceLocale` tab drives permalink generation.

#### Storage without Spatie (recommended minimum)

```php
class Page extends Model
{
    protected $fillable = ['title', 'slug'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
        ];
    }
}
```

```php
// migration
$table->json('title');
$table->string('slug')->unique();
```

On save, Filament merges `title.pl` / `title.en` into a `title` array. No extra glue code required.

#### Storage with Spatie `laravel-translatable` (optional)

```bash
composer require spatie/laravel-translatable
```

```php
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasTranslations;

    public array $translatable = ['title'];

    protected $fillable = ['title', 'slug'];
    // slug is intentionally NOT in $translatable
}
```

```php
TitleSlugField::make(
    translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
    slugSourceLocale: 'pl',
    spatieTranslatable: true, // optional config marker
);
```

**On edit:** if the record uses `HasTranslations` and the package is installed, each tab is hydrated via `getTranslation()`. Otherwise tabs read the raw JSON attribute.

**On save:** the nested `title` array from the form is assigned to the model; Spatie JSON-encodes translatable attributes automatically.

#### Global defaults (`config/filament-flex-fields.php`)

```php
'slug' => [
    'translatable_locales' => ['pl' => 'PL', 'en' => 'EN'],
    'slug_source_locale' => 'pl',
    'spatie_translatable' => false,
    'required_title_locales' => null, // null | 'all' | ['en']
],
```

When `translatableLocales` is omitted in `TitleSlugField::make()`, locales are read from `config('filament-flex-fields.slug.translatable_locales')`.

#### Required title locales

By default only the slug source locale title is required. Optional locales can stay empty on create/edit.

```php
// Default — only slugSourceLocale required (PL required, EN optional)
TitleSlugField::make(
    translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
    slugSourceLocale: 'pl',
);

// All locales required
TitleSlugField::make(
    translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
    requiredTitleLocales: 'all',
);

// Only specific locales required
TitleSlugField::make(
    translatableLocales: ['pl' => 'PL', 'en' => 'EN'],
    requiredTitleLocales: ['en'],
);
```

FlexField config key: `required_title_locales` (`null`, `'all'`, or list of locale codes).

#### Per-locale title customization

```php
TitleSlugField::make(
    translatableLocales: ['pl', 'en'],
    slugSourceLocale: 'pl',
    titleLocaleConfigurator: fn (FlexTextInput $field, string $locale) => $field
        ->placeholder(match ($locale) {
            'pl' => 'Tytuł po polsku',
            'en' => 'Title in English',
            default => 'Title',
        }),
);
```

#### Full `TranslatableFields` passthrough

When `translatableLocales` is set, title tabs are built with `TranslatableFields` internally. **By default** the factory enables `directionByLocale()` and `emptyBadgeWhenAllFieldsAreEmpty()` (warning `empty` badge on tabs where the title is blank). The active tab stays on `slugSourceLocale`, not `activeTabWithValue()`.

Use `translatableFieldsConfigurator` for further tweaks (`activeTabWithValue()`, bordered panels, custom tab icons, `localeFieldUsing()`, `storageAttributeUsing()`, etc.):

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;

TitleSlugField::make(
    translatableLocales: ['pl' => 'PL', 'en' => 'EN', 'ar' => 'AR'],
    slugSourceLocale: 'pl',
    translatableFieldsConfigurator: fn (TranslatableFields $fields): TranslatableFields => $fields
        ->activeTabWithValue()
        ->modifyTabsUsing(fn ($tab, string $locale) => $tab->icon('heroicon-o-language')),
),
```
```

Per-locale field tweaks remain available via `titleLocaleConfigurator` (applied after default title field setup).

#### Standalone `SlugField` with translatable source

```php
SlugField::make('slug')
    ->translatableTitle()
    ->titleLocales(['pl' => 'PL', 'en' => 'EN'])
    ->slugSourceLocale('pl')
    ->translatableTitleField('title');
```

`getSourceStatePath()` resolves to `title.{slugSourceLocale}` automatically (e.g. `title.pl`).

#### SlugField translatable API

| Method | Description |
|--------|-------------|
| `translatableTitle(bool\|Closure $condition = true)` | Enable translatable source resolution |
| `titleLocales(array\|Closure $locales)` | Locale map or list — also enables translatable mode |
| `slugSourceLocale(string\|Closure $locale)` | Locale used for slug generation |
| `translatableTitleField(string\|Closure $fieldName)` | Base title attribute name (default: `title`) |
| `spatieTranslatable(bool\|Closure $condition = true)` | Config flag (FlexField schema); hydrate auto-detects Spatie when present |
| `usesTranslatableTitle()` | Whether translatable mode is active |
| `getTitleLocales()` | Resolved `locale => label` map |
| `getSlugSourceLocale()` | Effective source locale |
| `shouldUseSpatieTranslatable()` | Evaluated `spatieTranslatable` flag |

#### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `translatable_locales` | `TitleSlugField::make(translatableLocales: …)` / `titleLocales()` |
| `slug_source_locale` | `slugSourceLocale()` |
| `required_title_locales` | `requiredTitleLocales` / `TitleSlugField::make(requiredTitleLocales: …)` |
| `spatie_translatable` | `spatieTranslatable()` |
| `translatable_title_field` | `translatableTitleField()` |

#### Slug generation locale

Translatable titles always use **server-side** slug preview (`generateSlugPreview` / `Str::slug` with `slugSourceLocale`). Alpine receives `serverGenerate: true` and `slugSourceLocale` so live preview matches PHP — including Polish diacritics (`Łódź` → `lodz`), which generic browser ASCII folding cannot handle reliably.

---

### Spatie `laravel-sluggable` integration (v4.x)

> **Nie jest wymagane.** Jeśli wystarczy Ci [ścieżka bez Spatie](#start-here--integracja-bez-spatie-domyślna), pomiń całą tę sekcję.

Pakiet testowany z **`spatie/laravel-sluggable` ^4.0** (aktualnie 4.0.2). Integracja używa oficjalnych klas Spatie v4:
- `Spatie\Sluggable\Actions\GenerateSlugAction` (via `config/sluggable.php` → `actions.generate_slug`)
- `Spatie\Sluggable\Support\SluggableAttributeResolver` dla modeli z `#[Sluggable]` bez `getSlugOptions()`
- `Spatie\Sluggable\Support\Config::getAction()` — ten sam resolver akcji co trait `HasSlug`

Spatie dodaje drugą warstwę: **zapis** sluga z regułami modelu (suffixy, scope, `preventOverwrite`). Formularz może pokazywać ten sam podgląd co model — wtedy podaj `spatieModel`.

#### Izolacja opcjonalnej zależności (technicznie)

| Warstwa | Import `Spatie\…`? | Bez `composer require spatie/laravel-sluggable` |
|---------|-------------------|--------------------------------------------------|
| `SlugField`, `TitleSlugField` | **Nie** | Działa w 100% (`SlugGenerator`, permalink, unique, inline edit) |
| Traity `GeneratesSlugFromSource`, `ConfiguresSlugPermalink` | **Nie** — tylko `SpatieSlugIntegration::isAvailable()` | Guard przed każdym wywołaniem Spatie |
| `Support/Slug/SpatieSlugIntegration.php` | **Tak** — jedyny bridge dla sluga | Ładowany bezpiecznie; `isAvailable()` zwraca `false`, fallback do `SlugGenerator` |

`spatie/laravel-sluggable` jest w `composer.json` → `suggest`, nie w `require`. Pakiet **nie wymusza** instalacji Spatie.

**Optional.** Install when you want model-driven slug rules:

```bash
composer require spatie/laravel-sluggable
```

#### Minimal model (trait — klasycznie)

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    use HasSlug;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
```

#### Minimal model (v4 attribute — bez `getSlugOptions()`)

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'title', to: 'slug')]
class Post extends Model
{
    // Brak HasSlug — SlugField odczytuje opcje przez SluggableAttributeResolver
}
```

Multi-field attribute (v4):

```php
#[Sluggable(from: ['title', 'subtitle'], to: 'slug')]
class Post extends Model {}
```

#### Wire the form (zero extra config)

Spatie w formularzu włącza się **tylko** gdy:
- podasz `spatieModel`, **i**
- model ma `getSlugOptions()` **lub** atrybut `#[Sluggable]` z rozpoznawalnymi opcjami.

Sam fakt, że masz `Post` jako model Resource, **nie włącza** Spatie automatycznie — musi istnieć konfiguracja sluga w modelu.

```php
// Wygląd formularza: IDENTYCZNY FusedGroup w obu przypadkach
TitleSlugField::make(),

// Jawne wskazanie modelu Spatie (zalecane gdy Resource nie binduje modelu)
TitleSlugField::make(spatieModel: Post::class),
```

**Co się zmienia po dodaniu `spatieModel`:**

| Aspekt | Bez Spatie | Ze `spatieModel` |
|--------|------------|------------------|
| Układ UI (`FusedGroup`) | Ten sam | Ten sam |
| Generowanie podglądu | `Str::slug` w JS lub `SlugGenerator` | `GenerateSlugAction` + `SlugOptions` |
| Requesty Livewire | Opcjonalne | Tak (`generateSlugPreview`) |
| Zapis do bazy | Twoja logika / Filament | Spatie `HasSlug` na modelu |

Or on standalone `SlugField`:

```php
SlugField::make('slug')
    ->source('title')
    ->spatieModel(Post::class),
```

#### Explicit Spatie field mapping

When form field names differ from model attributes:

```php
SlugField::make('permalink')
    ->source('name')
    ->spatieModel(Post::class)
    ->spatieSlugField('slug')      // model column Spatie writes to
    ->spatieSourceField('title'),  // model attribute used as primary source
```

#### Supported Spatie `SlugOptions` features in preview

| Spatie option | Supported in live preview |
|---------------|---------------------------|
| `generateSlugsFrom('title')` | Yes |
| `generateSlugsFrom(['title', 'subtitle'])` | Yes — reads sibling form fields |
| `generateSlugsFrom(fn ($model) => ...)` | Yes — Closure receives hydrated model |
| `saveSlugsTo()` | Yes — via `spatieSlugField()` |
| `usingSeparator()` / `usingLanguage()` | Yes |
| `slugsShouldBeNoLongerThan()` | Yes |
| `generateUniqueSlugs` / suffix | Yes — queries DB for collisions |
| `extraScope()` | Yes — hydrates model from full form state (`data.*`, hidden fields, fillable attributes) |
| `startSlugSuffixFrom()` / `useSuffixOnFirstOccurrence()` | Yes |
| `usingSuffixGenerator()` | Yes |
| `skipGenerateWhen()` | Yes — keeps existing slug in preview; reads hydrated form state |
| `preventOverwrite()` | Yes — keeps existing slug in preview |
| `#[Sluggable]` attribute (without `getSlugOptions()`) | Yes — via `SluggableAttributeResolver` (v4) |
| `#[Sluggable(from: ['title', 'subtitle'])` | Yes — sibling form fields |
| `HasTranslatableSlug` + `spatie/laravel-translatable` | Yes — preview for `slugSourceLocale` / `app.locale` |
| `selfHealing()` / route keys | Yes — permalink preview and Visit URL append `{slug}{separator}{id}` on edit |
| `doNotGenerateSlugsOnCreate()` / `OnUpdate()` | Model save only — preview always generates |
| Custom `GenerateSlugAction` | Yes — via `config/sluggable.php` |

#### Multi-field source example

```php
// Model
public function getSlugOptions(): SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom(['title', 'subtitle'])
        ->saveSlugsTo('slug');
}

// Form — include both fields; slug preview concatenates them
TitleSlugField::make(spatieModel: Post::class),
FlexTextInput::make('subtitle')->live(),
```

Formularz musi mieć wypełnione pola używane w `extraScope` / `skipGenerateWhen` (np. `tenant_id`, `status`) — `SlugField` odczytuje je z live form state (`data.*`), nie tylko z pól źródłowych sluga.

#### Scoped unique slugs (Spatie `extraScope`)

```php
public function getSlugOptions(): SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('title')
        ->saveSlugsTo('slug')
        ->extraScope(fn ($query) => $query->where('tenant_id', $this->tenant_id));
}
```

#### Attribute-based model (Spatie v4+)

```php
use Spatie\Sluggable\Attributes\Sluggable;
use Spatie\Sluggable\HasSlug;

#[Sluggable(from: 'title', to: 'slug', separator: '-', unique: true)]
class Post extends Model
{
    use HasSlug;
}
```

No `getSlugOptions()` required — `SlugField` reads the attribute when the method is absent.

#### Override Spatie for preview only

```php
SlugField::make('slug')
    ->source('title')
    ->spatieModel(Post::class)
    ->slugifyUsing(fn (array $state): string => strtoupper($state['source'])),
```

`slugifyUsing()` always wins over Spatie.

#### Force server-side preview

Spatie mode and **translatable titles** already use server-side `generateSlugPreview`. For custom slugifiers or other cases:

```php
SlugField::make('slug')
    ->source('title')
    ->serverSideGeneration(),
```

#### Custom Spatie action class

If you override `config/sluggable.php` → `actions.generate_slug`, the field uses your bound `GenerateSlugAction` implementation automatically.

#### `skipGenerateWhen` — podgląd bez nadpisywania

```php
public function getSlugOptions(): SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('title')
        ->saveSlugsTo('slug')
        ->skipGenerateWhen(fn (): bool => $this->status === 'published');
}
```

W podglądzie formularza, gdy `skipGenerateWhen` zwróci `true`, pole zachowa istniejący slug zamiast generować nowy.

#### `startSlugSuffixFrom` i kolizje w podglądzie

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->startSlugSuffixFrom(5);
```

Jeśli w bazie jest już `my-post`, podgląd może pokazać `my-post-5` (zgodnie z regułami Spatie).

#### Closure jako źródło sluga

```php
SlugOptions::create()
    ->generateSlugsFrom(fn (Post $post): string => "{$post->title}-{$post->edition}")
    ->saveSlugsTo('slug');
```

Formularz musi mieć wypełnione pola `title` i `edition` — `SlugField` odczytuje je z live state rodzeństwa w schemacie.

#### `usingSuffixGenerator` — własny suffix

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->usingSuffixGenerator(fn (string $slug, int $iteration): string => 'v'.($iteration + 1));
```

---

### Permalink preview & URL actions

The permalink bar shows **host** (without `https://`), optional **path prefix**, slug segment, and optional **postfix**. HTTPS hosts display a green lock icon.

#### Basic blog permalink

```php
TitleSlugField::make(
    urlHost: 'https://wyachts.test',
    urlPath: '/blog/',
),
// Preview: wyachts.test/blog/my-post-title
```

#### Subdomain style (host only)

```php
SlugField::make('slug')
    ->source('title')
    ->urlHost('https://acme.example.com')
    ->urlPath(null)
    ->urlHostVisible(true),
```

#### Sandwich URL (prefix + slug + postfix)

```php
TitleSlugField::make(
    urlHost: 'https://shop.test',
    urlPath: '/products/',
    slugLabelPostfix: '/details',
),
// shop.test/products/my-product/details
```

#### Custom visit link (route or absolute URL)

```php
TitleSlugField::make(
    visitUrl: fn (string $slug, string $routeKey, ?\Illuminate\Database\Eloquent\Model $record): string => route('blog.show', $routeKey),
    visitLinkLabel: 'View post',
),

// Lub na SlugField:
SlugField::make('slug')
    ->visitRoute(fn (string $slug, string $routeKey): ?string => filled($slug) ? route('blog.show', $routeKey) : null),
```

Closure `visitUrl` / `visitRoute` otrzymuje wstrzyknięte: `slug` (string), `routeKey` (string — dla self-healing modeli: `hello-world-5`, inaczej jak `slug`) i `record` (?Model).

#### Hide permalink or actions

```php
SlugField::make('slug')
    ->permalinkPreview(false)
    ->showVisitLink(false)
    ->showCopyButton(false)
    ->showRegenerateButton(false),
```

#### Action button layout

Buttons sit **below** the input: **Edit / OK / Cancel** on the left; **Regenerate / Copy / Visit** on the right.

```php
SlugField::make('slug')
    ->actionButtonLabels(true)   // default — text + icon
    ->actionButtonsIconOnly(),  // icons only + tooltips
```

Global default: `config('filament-flex-fields.slug.action_button_labels')`.

---

### Uniqueness validation

Separate from Spatie's DB suffix generation — this is **form validation** before save.

#### Default (unique in table)

```php
SlugField::make('slug'), // unique rule on slug column
```

#### Disable uniqueness check

```php
SlugField::make('slug')->slugUnique(false),
```

#### Scoped uniqueness (tenant, locale, type, …)

```php
SlugField::make('slug')
    ->slugUniqueModel(Post::class)
    ->slugUniqueScope(fn ($query) => $query->where('tenant_id', auth()->user()->tenant_id)),
```

#### Filament-style unique parameters

```php
TitleSlugField::make(
    slugUniqueParameters: [
        'table' => 'posts',
        'column' => 'slug',
        'ignoreRecord' => true,
    ],
),
```

---

### Homepage slug (`/`)

For CMS pages that should live at the site root:

```php
SlugField::make('slug')
    ->source('title')
    ->allowHomepageSlug()
    ->slugPattern('/^(\/|[a-z0-9]+(?:-[a-z0-9]+)*)$/'),
```

Only the exact value `/` is allowed as a special case.

---

### TitleSlugField factory parameters

`TitleSlugField::make()` is a static factory returning a `FusedGroup` (title + hidden auto-sync flag + slug).

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$fieldTitle` | `?string` | `config('filament-flex-fields.slug.field_title')` | Title state path |
| `$fieldSlug` | `?string` | `config('filament-flex-fields.slug.field_slug')` | Slug state path |
| `$titleField` | `?Field` | built-in `FlexTextInput` | Replace default title control |
| `$titleFieldWrapper` | `?Closure` | `null` | Wrap title field: `fn ($field) => $field->columnSpan(2)` |
| `$titleAfterStateUpdated` | `?Closure` | `null` | Hook after title changes |
| `$slugAfterStateUpdated` | `?Closure` | `null` | Hook after slug changes / regenerate |
| `$titleRules` | `array\|Closure` | `['required', 'string']` | Validation on title |
| `$slugRules` | `array\|Closure` | `['required', 'string']` | Validation on slug |
| `$titleAutofocus` | `bool` | `false` | Focus title on create |
| `$titleReadOnly` | `bool\|Closure` | `false` | Read-only title |
| `$slugReadOnly` | `bool\|Closure` | `false` | Read-only slug |
| `$titleLabel` | `?string` | headline of field name | Title label |
| `$titlePlaceholder` | `?string` | headline of field name | Title placeholder |
| `$slugLabel` | `?string` | `null` (hidden) | Slug label; `null` hides it |
| `$titleExtraInputAttributes` | `array` | `[]` | Extra HTML attributes on title input |
| `$slugUniqueParameters` | `?array` | `null` | Passed to `slugUniqueParameters()` |
| `$titleUniqueParameters` | `?array` | `null` | Filament `unique()` na tytule |

**Przykład `titleUniqueParameters` — unikalny tytuł w obrębie tenanta:**

```php
TitleSlugField::make(
    titleUniqueParameters: [
        'table' => 'posts',
        'column' => 'title',
        'ignoreRecord' => true,
        'where' => fn ($query) => $query->where('tenant_id', filament()->getTenant()?->id),
    ],
),
```

| `$urlHost` | `?string` | `config('filament-flex-fields.slug.url_host')` | Permalink host |
| `$urlPath` | `?string` | `null` | Permalink path prefix |
| `$urlHostVisible` | `bool` | `true` | Show host segment |
| `$visitLinkLabel` | `?string` | translated default | Visit button label |
| `$visitUrl` | `string\|Closure\|null` | `null` | Custom visit URL; closure: `slug`, `routeKey`, `record` |
| `$showVisitLink` | `bool` | `true` | Show visit action |
| `$slugLabelPostfix` | `?string` | `null` | Trailing URL segment after slug |
| `$preserveSlugOnEdit` | `bool\|Closure` | `true` | Don't auto-update slug on edit |
| `$translatableLocales` | `array\|Closure\|null` | `config('…slug.translatable_locales')` | Enables `TranslatableFields` title UI; `null` = single-language title |
| `$slugSourceLocale` | `string\|Closure\|null` | `config('…slug.slug_source_locale')` or `app.locale` | Locale whose title drives slug generation |
| `$requiredTitleLocales` | `'all'\|list<string>\|Closure\|null` | `config('…slug.required_title_locales')` or slug source locale only | Which title tabs are required (`null` = source locale only) |
| `$spatieTranslatable` | `bool\|Closure` | `false` | Marks Spatie Translatable intent; hydrate auto-detects `HasTranslations` when package is present |
| `$titleLocaleConfigurator` | `?Closure` | `null` | `fn (FlexTextInput $field, string $locale) => $field` |
| `$translatableFieldsConfigurator` | `?Closure` | `null` | `fn (TranslatableFields $fields) => $fields->…` — full title tabs config |
| `$spatieModel` | `string\|Closure\|null` | `null` | **Spatie Sluggable only** (`HasSlug`) — not Translatable |
| `$slugConfigurator` | `?Closure` | `null` | `fn (SlugField $field) => $field->...` |

**Example — custom title field + slug configurator:**

```php
use Filament\Forms\Components\RichEditor;

TitleSlugField::make(
    titleField: RichEditor::make('title')->required(),
    slugConfigurator: fn (SlugField $slug) => $slug
        ->urlHost(config('app.url'))
        ->urlPath('/news/')
        ->generationDebounce(600)
        ->maxSlugLength(120),
),
```

**Example — custom field names via config:**

```php
// config/filament-flex-fields.php
'slug' => [
    'field_title' => 'name',
    'field_slug' => 'handle',
    'url_host' => env('APP_URL'),
],

TitleSlugField::make(), // uses name + handle
```

#### Przykład dla każdego parametru `TitleSlugField::make()`

```php
TitleSlugField::make(
    // --- nazwy pól ---
    fieldTitle: 'title',
    fieldSlug: 'slug',

    // --- własne pole tytułu zamiast FlexTextInput ---
    titleField: null,

    // --- opakowanie tytułu (np. columnSpan) ---
    titleFieldWrapper: fn ($field) => $field->columnSpanFull(),

    // --- hooki ---
    titleAfterStateUpdated: function ($state, Filament\Schemas\Components\Utilities\Set $set): void {
        // po zmianie tytułu (po logice auto-slug)
    },
    slugAfterStateUpdated: function ($state): void {
        // po zmianie sluga (np. własna walidacja)
    },

    // --- walidacja ---
    titleRules: ['required', 'string', 'min:3'],
    slugRules: ['required', 'string', 'max:255'],

    // --- UX tytułu ---
    titleAutofocus: true,
    titleReadOnly: false,
    titleLabel: 'Post title',
    titlePlaceholder: 'Enter a descriptive title',
    titleExtraInputAttributes: ['data-test' => 'post-title'],

    // --- UX sluga ---
    slugReadOnly: false,
    slugLabel: null, // null = ukryta etykieta

    // --- unikalność ---
    slugUniqueParameters: ['column' => 'slug', 'ignoreRecord' => true],
    titleUniqueParameters: null,

    // --- permalink ---
    urlHost: config('app.url'),
    urlPath: '/blog/',
    urlHostVisible: true,
    slugLabelPostfix: null,
    visitUrl: fn (string $slug, string $routeKey, ?\Illuminate\Database\Eloquent\Model $record): string => route('blog.show', $routeKey),
    visitLinkLabel: 'View on site',
    showVisitLink: true,

    // --- zachowanie ---
    preserveSlugOnEdit: true,

    // --- Spatie (nie zmienia UI!) ---
    spatieModel: Post::class,

    // --- dowolna konfiguracja SlugField ---
    slugConfigurator: fn (SlugField $slug) => $slug
        ->size('lg')
        ->generationDebounce(300)
        ->showCopyButton(true),
),
```

---

### SlugField — configuration API

Each method below is chainable on `SlugField::make('slug')`.

#### `source(string|Closure|null $statePath)`

State path of the field that drives auto-generation (usually `title`).

```php
SlugField::make('slug')->source('title'),
SlugField::make('data.slug')->source('data.title'), // nested
```

#### `sourceLive(bool|Closure $condition = true)`

When `false`, slug does not react to source changes (manual slug only).

```php
SlugField::make('slug')->source('title')->sourceLive(false),
```

#### `translatableTitle(bool|Closure $condition = true)`

Enables translatable title source paths (`title.pl`, …). Usually set via `titleLocales()`.

#### `titleLocales(array|Closure $locales)`

Locale map (`['pl' => 'PL', 'en' => 'EN']`) or list (`['pl', 'en']`). Implies `translatableTitle(true)`.

#### `slugSourceLocale(string|Closure $locale)`

Which locale title drives slug auto-generation. Default: config `slug_source_locale`, then `app.locale`, then first locale.

#### `translatableTitleField(string|Closure $fieldName)`

Base title attribute when resolving source path (default: `title`).

#### `spatieTranslatable(bool|Closure $condition = true)`

Configuration flag for Spatie Translatable models. Hydration auto-detects `HasTranslations` on the record when [spatie/laravel-translatable](https://github.com/spatie/laravel-translatable) is installed — the flag does not need to be `true` for detection to work.

#### `titleField(Field $field)`

Attach a title field for `SlugField::withTitle()` / manual fused layouts.

```php
SlugField::make('slug')
    ->titleField(FlexTextInput::make('title')->required()),
```

#### `titleFieldWrapper(?Closure $wrapper)`

```php
SlugField::make('slug')
    ->titleField(FlexTextInput::make('title'))
    ->titleFieldWrapper(fn (Field $field) => $field->columnSpanFull()),
```

#### `titleAfterStateUpdated(?Closure $callback)`

```php
->titleAfterStateUpdated(function ($state, Filament\Schemas\Components\Utilities\Set $set) {
    // runs after title change logic
}),
```

#### `slugAfterStateUpdated(?Closure $callback)`

```php
->slugAfterStateUpdated(function ($state) {
    // runs when slug state changes
}),
```

#### `titleReadOnly(bool|Closure $condition = true)` / `slugReadOnly(bool|Closure $condition = true)`

Blokuje edycję odpowiedniego pola. Działa z `TitleSlugField` i ręcznym `SlugField::withTitle()`.

```php
// Tytuł tylko do odczytu
TitleSlugField::make(titleReadOnly: true),

// Slug tylko do odczytu
TitleSlugField::make(slugReadOnly: true),

// Slug readonly tylko na edit (zalecane dla opublikowanych URL)
TitleSlugField::make(
    slugConfigurator: fn (SlugField $f) => $f
        ->slugReadOnly(fn (SlugField $c): bool => $c->getOperation() === 'edit'),
),
```

#### `slugifyUsing(?Closure $callback)`

Custom slugifier; receives `['source' => string]`.

```php
->slugifyUsing(fn (array $state): string => str_replace(' ', '.', strtolower($state['source']))),
```

#### `spatieModel(string|Closure|null $modelClass)`

Enable Spatie integration for preview generation.

```php
->spatieModel(Post::class),
->spatieModel(fn () => static::getModel()),
```

#### `spatieSlugField(string|Closure $attribute = 'slug')`

Model attribute Spatie writes to / reads from.

```php
->spatieSlugField('permalink'),
```

#### `spatieSourceField(string|Closure|null $field)`

Primary model attribute for the live source string.

```php
->spatieSourceField('title'),
```

#### `serverSideGeneration(bool|Closure $condition = true)`

Use Livewire `generateSlugPreview` instead of client `Str.slug`. Automatically enabled when Spatie integration is active **or** when translatable titles are used.

```php
->serverSideGeneration(), // explicit; also auto-on for Spatie + translatable titles
```

#### `slugSeparator(string|Closure $separator = '-')`

Normalization separator (also used by fallback `SlugGenerator`). Default validation `slugPattern` is derived from this separator automatically.

```php
->slugSeparator('_'), // validates hello_world without manual slugPattern
```

#### `maxSlugLength(int|Closure|null $length)`

Max length for fallback generator; Spatie uses `slugsShouldBeNoLongerThan` from model.

```php
->maxSlugLength(80),
```

#### `urlHost(string|Closure|null $host)` / `urlPath(string|Closure|null $path)`

Permalink segments. Host may include `https://`; display strips the scheme.

```php
->urlHost('https://example.com')
->urlPath('/docs/'),
```

#### `urlHostVisible(bool|Closure)` / `urlPathVisible(bool|Closure)`

Kontrolują, które segmenty URL są widoczne w podglądzie permalinku.

```php
// Tylko ścieżka (bez hosta) — np. w panelu admina
SlugField::make('slug')
    ->urlHost('https://example.com')
    ->urlPath('/blog/')
    ->urlHostVisible(false),

// Ukryj prefix ścieżki — pokaż tylko host + slug
SlugField::make('slug')
    ->urlPath('/hidden-prefix/')
    ->urlPathVisible(false),
```

#### `permalinkPreview(bool|Closure $condition = true)`

Show or hide the entire permalink chrome.

```php
->permalinkPreview(false),
```

#### `permalinkLabel(string|Closure|null $label)`

```php
->permalinkLabel('Public URL'),
```

#### `visitUrl(string|Closure|null $url)` / `visitRoute(string|Closure|null $route)`

Cel przycisku **Visit**. Closure dostaje wstrzyknięte: `slug`, `routeKey` (self-healing: `{slug}-{id}`) i opcjonalnie `record`.

```php
// Named route (self-healing models need routeKey, not slug alone)
SlugField::make('slug')
    ->visitRoute(fn (string $slug, string $routeKey): ?string => filled($slug)
        ? route('posts.show', $routeKey)
        : null),

// Absolute URL
SlugField::make('slug')
    ->visitUrl(fn (string $slug, string $routeKey): ?string => filled($slug)
        ? url("/preview/{$routeKey}")
        : null),
```

#### `visitLinkLabel(string|Closure|null $label)`

```php
->visitLinkLabel('Open in new tab'),
```

#### `showVisitLink(bool|Closure)` / `showCopyButton(bool|Closure)` / `showRegenerateButton(bool|Closure)`

Przełączniki akcji pod slugiem. Domyślnie wszystkie `true` (oprócz Regenerate — widoczny tylko po ręcznej edycji sluga).

```php
SlugField::make('slug')
    ->showVisitLink(false)        // ukryj "Visit"
    ->showCopyButton(true)       // zostaw "Copy"
    ->showRegenerateButton(true), // pokaż "Regenerate" gdy dotyczy
```

Przykład — tylko Copy, bez Visit:

```php
TitleSlugField::make(
    showVisitLink: false,
    slugConfigurator: fn (SlugField $s) => $s->showRegenerateButton(false),
),
```

#### `actionButtonLabels(bool|Closure)` / `actionButtonsIconOnly(bool|Closure)`

Kontrola tekstu na przyciskach akcji (Hero UI button-group + ikony Gravity).

```php
// Tekst + ikona (domyślnie)
SlugField::make('slug')->actionButtonLabels(true),

// Same ikony + tooltip
SlugField::make('slug')->actionButtonsIconOnly(),

// Globalnie w config
// 'slug' => ['action_button_labels' => false],
```

#### `autoUpdateDisabledField(string|Closure|null $field)`

Hidden boolean field path tracking manual slug edits. `TitleSlugField` sets `{slug}_auto_update_disabled` automatically.

```php
->autoUpdateDisabledField('slug_auto_update_disabled'),
```

#### `autoGenerate(bool|Closure $condition = true)`

Główny przełącznik automatycznego generowania sluga z pola źródłowego.

```php
// Całkowicie ręczny slug (np. import CSV)
SlugField::make('slug')
    ->autoGenerate(false)
    ->inlineEditing(false),
```

#### `preserveSlugOnEdit(bool|Closure $condition = true)`

Na operacji `edit` zatrzymuje auto-sync z tytułu (chroni opublikowany URL). Na `create` zawsze synchronizuje.

```php
// Domyślnie — nie nadpisuj sluga przy edycji tytułu
TitleSlugField::make(), // preserveSlugOnEdit: true

// Zawsze synchronizuj (jak na create)
TitleSlugField::make(preserveSlugOnEdit: false),

SlugField::make('slug')->preserveSlugOnEdit(false),
```

#### `inlineEditing(bool|Closure $condition = true)`

Gdy `true` (domyślnie): podgląd permalinku + przyciski Edit/OK/Cancel/Reset. Gdy `false`: zwykły `TextInput`.

```php
// Prosty input bez trybu inline (np. w Repeaterze)
SlugField::make('slug')
    ->inlineEditing(false)
    ->autoGenerate(false),
```

#### `allowHomepageSlug(bool|Closure $condition = true)`

Pozwala na slug `/` (strona główna CMS). Wymaga dostosowanego wzorca walidacji.

```php
SlugField::make('slug')
    ->allowHomepageSlug()
    ->slugPattern('/^(\/)?[a-z0-9]+(?:-[a-z0-9]+)*$/')
    ->urlHost(config('app.url')),
```

#### `generationDebounce(int|Closure $milliseconds = 400)`

Debounce before regenerating slug from title.

```php
->generationDebounce(250),
```

#### `slugPattern(string|Closure $pattern)` / `regex(string|Closure|null $pattern)`

Optional override. When omitted, pattern is **auto-derived** from `slugSeparator()` (and `allowHomepageSlug()` when enabled). `regex()` is an alias.

```php
// Auto (default) — follows slugSeparator('-')
SlugField::make('slug'), // validates hello-world

// Auto with underscore separator
SlugField::make('slug')->slugSeparator('_'), // validates hello_world

// Manual override
SlugField::make('slug')->slugPattern('/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),

// To samo:
SlugField::make('slug')->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/'),

// Homepage with custom pattern (auto handles this when allowHomepageSlug only)
SlugField::make('slug')
    ->allowHomepageSlug()
    ->regex('/^(\/)?[a-z0-9]+(?:-[a-z0-9]+)*$/'),
```

#### `slugLabelPostfix(string|Closure|null $postfix)`

Trailing path after slug in permalink preview.

```php
->slugLabelPostfix('.html'),
```

#### `recordSlug(string|Closure|null $slug)`

Initial/stored slug for edit preview and visit link before state hydrates.

```php
->recordSlug(fn (?Model $record): ?string => $record?->slug),
```

#### `slugRules(array|Closure $rules)`

Dodatkowe reguły walidacji (oprócz wbudowanego wzorca i `slugUnique`).

```php
SlugField::make('slug')->slugRules(['min:3', 'max:100']),

TitleSlugField::make(
    slugRules: ['required', 'string', 'regex:/^[a-z0-9-]+$/'],
),
```

#### `slugUnique(bool|Closure $condition = true)` / `slugUniqueParameters(array $parameters)` / `slugUniqueScope(?Closure $scope)` / `slugUniqueModel(string|Closure|null $model)`

Walidacja unikalności **w formularzu** (niezależna od suffixów Spatie przy zapisie).

```php
// Wyłącz sprawdzanie unikalności
SlugField::make('slug')->slugUnique(false),

// Scoped — tenant
SlugField::make('slug')
    ->slugUniqueModel(Post::class)
    ->slugUniqueScope(fn ($q) => $q->where('tenant_id', 1)),

// Parametry jak Filament unique()
SlugField::make('slug')->slugUniqueParameters([
    'table' => 'posts',
    'column' => 'slug',
    'ignoreRecord' => true,
]),
```

#### `size(string|Closure $size)` / `variant(string|Closure $variant)`

Rozmiar i wariant wizualny powłoki pola (Hero UI). Domyślnie z `config('filament-flex-fields.ui')`.

```php
SlugField::make('slug')
    ->size('lg')            // sm | md | lg
    ->variant('secondary'), // primary | secondary | …

TitleSlugField::make(
    slugConfigurator: fn (SlugField $s) => $s->size('md')->variant('primary'),
),
```

Defaults: `config('filament-flex-fields.ui.slug_size')`, `slug_variant`.

---

### Dziedziczone API Filament `Field`

`SlugField` dziedzczy standardowe metody Filament — działają jak w innych polach:

```php
SlugField::make('slug')
    ->label('Adres URL')
    ->helperText('Tylko małe litery, cyfry i myślniki.')
    ->hint('Generowany automatycznie z tytułu')
    ->required()
    ->disabled(fn (): bool => auth()->user()?->cannot('edit-slug'))
    ->hidden(fn (): bool => ! auth()->check())
    ->columnSpanFull()
    ->columnSpan(2)
    ->live()
    ->dehydrated(true),
```

`TitleSlugField` konfiguruje tytuł i slug osobno — helper na slug daj przez `slugConfigurator`:

```php
TitleSlugField::make(
    slugConfigurator: fn (SlugField $slug) => $slug
        ->helperText('Ten adres będzie widoczny w URL.'),
),
```

---

### Public helper methods (views, tests, extensions)

Metody używane w Blade, testach i rozszerzeniach:

| Method | Returns | Kiedy użyć |
|--------|---------|------------|
| `getAlpineConfiguration()` | `array` | Debug / custom Blade |
| `getUiLabels()` | `array` | Tłumaczenia UI w testach |
| `getSourceStatePath()` | `?string` | Ścieżka Livewire do pola źródłowego |
| `getOperation()` | `string` | `create` lub `edit` |
| `generateSlugFromSource(string $source)` | `string` | Generowanie po stronie PHP |
| `generateSlugPreview(string $source)` | `string` | Endpoint Livewire (Alpine) |
| `normalizeSlug(string $value)` | `string` | Normalizacja przed zapisem |
| `getFullPermalinkUrl(?string $slug)` | `?string` | Pełny URL do Copy/Visit |
| `getDisplayUrlHost()` | `?string` | Host bez `https://` |
| `usesSpatieIntegration()` | `bool` | Czy Spatie jest aktywne |
| `getSpatieModelClass()` | `?string` | Rozwiązany FQCN modelu |
| `shouldUseServerSideGeneration()` | `bool` | Czy Alpine woła Livewire |
| `getWrapperClasses()` | `list<string>` | Klasy CSS wrappera |

**Przykład w teście:**

```php
$field = SlugField::make('slug')->spatieModel(Post::class);

expect($field->generateSlugFromSource('Hello World'))->toBe('hello-world');
expect($field->getFullPermalinkUrl('hello-world'))
    ->toBe('https://example.com/hello-world');
```

---

### FlexField schema keys (`FieldType::Slug`)

When using `FlexFieldFormBuilder`:

```php
[
    'slug' => 'permalink',
    'label' => 'Permalink',
    'type' => 'slug',
    'config' => [
        'source' => 'title',
        'url_host' => 'https://example.com',
        'url_path' => '/posts/',
        'debounce' => 300,
        'slug_unique' => true,
        'spatie_model' => Post::class,
        'separator' => '-',
        'allow_homepage' => false,
        'preserve_on_edit' => true,
    ],
],
```

| Config key | Maps to method |
|------------|----------------|
| `source` | `source()` |
| `url_host` | `urlHost()` |
| `url_path` | `urlPath()` |
| `debounce` | `generationDebounce()` |
| `slug_unique` | `slugUnique()` |
| `spatie_model` | `spatieModel()` |
| `separator` | `slugSeparator()` |
| `allow_homepage` | `allowHomepageSlug()` |
| `preserve_on_edit` | `preserveSlugOnEdit()` |

---

### Advanced recipes

#### Repeater with per-row title + slug

```php
use Filament\Forms\Components\Repeater;

Repeater::make('sections')
    ->schema([
        FlexTextInput::make('title')->required()->live(),
        SlugField::make('slug')
            ->source('title')
            ->urlHost(config('app.url'))
            ->urlPath('/sections/'),
    ])
    ->columns(1),
```

Nested paths are resolved automatically (`sections.0.title` → `sections.0.slug`).

#### Standalone slug (no title field)

```php
SlugField::make('slug')
    ->label('URL slug')
    ->autoGenerate(false)
    ->inlineEditing(false)
    ->required(),
```

#### Regenerate button behaviour

**Regenerate** pojawia się tylko gdy auto-sync został wyłączony przez ręczną edycję sluga (ukryte pole `{slug}_auto_update_disabled = true`). Podczas normalnej synchronizacji przycisk jest ukryty.

```php
// W teście Livewire — symuluj ręczną edycję:
Livewire::test(EditPost::class, ['record' => $post])
    ->set('data.slug_auto_update_disabled', true)
    ->set('data.title', 'New title'); // slug NIE zmieni się bez Regenerate
```

> **Uwaga:** `SlugField` używa `wire:ignore` na fragmencie Alpine — bezpośrednie `->set('data.slug', 'x')` w testach może nie odzwierciedlać zachowania UI. Testuj przez zmianę tytułu lub flagę `slug_auto_update_disabled`.

### Playground (podgląd na żywo)

Włącz playground w `.env`:

```env
FLEX_FIELDS_PLAYGROUND=true
```

Panel: **Settings & Tools → Flex Fields Playground** — cluster z lewą sub-nawigacją (Filament `SubNavigationPosition::Start`).

- Główny URL: `/admin/flex-fields-playground` (przekierowanie do pierwszego komponentu)
- Podstrony: `/admin/flex-fields-playground/{slug}` — np. `/admin/flex-fields-playground/rating-column`, `/admin/flex-fields-playground/phone-field`
- Rejestracja routów **tylko** gdy `FLEX_FIELDS_PLAYGROUND=true` (lub `filament-flex-fields.playground.enabled`)

Sekcja **Slug field** (`SlugFieldPlayground`) zawiera:

| Przykład w playground | Co demonstruje |
|----------------------|----------------|
| Title (source) + Slug | Auto-sync standalone |
| Title + slug (one-liner) | `TitleSlugField` + permalink |
| Translatable title + slug | `translatableLocales` + `slugSourceLocale` + `SegmentTabs` |
| Title + slug pair | `titleField()` + `recordSlug()` |
| Permalink preview | `urlHost`, `urlPath`, `visitRoute`, debounce |
| URL slug sandwich | `slugLabelPostfix` + złożony visit URL |
| Form readonly | `readOnly()` na całym polu |
| Slug readonly | `slugReadOnly()` |
| Homepage slug | `allowHomepageSlug()` + `/` |

Domyślny stan testowy (np. `slug__title`, `slug__permalink`) jest w `SlugFieldPlayground::defaultState()`.

```php
// Programowe sprawdzenie rejestracji w testach pakietu
$builder = app(\Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder::class);
expect($builder->components())->not->toBeEmpty();
```

#### Translations

Opublikuj pliki językowe:

```bash
php artisan vendor:publish --tag=filament-flex-fields-translations
```

Nadpisuj w `lang/vendor/filament-flex-fields/{locale}/default.php`.

**Klucze UI (`slug.*`):**

| Klucz | EN (domyślnie) | PL |
|-------|----------------|-----|
| `slug.placeholder` | your-permalink-slug | twoj-adres-slug |
| `slug.permalink` | Permalink | Bezposredni link |
| `slug.badge_auto` | Auto | Auto |
| `slug.badge_custom` | Custom | Reczny |
| `slug.edit` | Edit | Edytuj |
| `slug.confirm` | OK | OK |
| `slug.cancel` | Cancel | Anuluj |
| `slug.reset` | Reset | Przywroc |
| `slug.regenerate` | Regenerate | Regeneruj |
| `slug.copy` | Copy | Kopiuj |
| `slug.copied` | Copied | Skopiowano |
| `slug.visit` | Visit | Odwiedz |
| `slug.changed` | Changed | Zmieniono |

**Walidacja (`validation.slug.*`):**

| Klucz | Opis |
|-------|------|
| `validation.slug.invalid` | Ogólny komunikat błędu sluga |
| `validation.slug.pattern` | Komunikat gdy slug nie pasuje do wzorca |

**Własne etykiety bez publikacji — nadpisanie w polu:**

```php
SlugField::make('slug')
    ->placeholder('np. moj-artykul')
    ->permalinkLabel('Link publiczny')
    ->visitLinkLabel('Zobacz wpis'),
```

Ustaw locale aplikacji (`app.locale` = `pl`) aby załadować wbudowane tłumaczenia pakietu.

---

### Troubleshooting

| Problem | Prawdopodobna przyczyna | Rozwiązanie |
|---------|-------------------------|-------------|
| Slug nie aktualizuje się z tytułu | Ręczna edycja wyłączyła auto-sync | Kliknij **Regenerate** lub ustaw `{slug}_auto_update_disabled` na `false` |
| Slug zmienia się na edit mimo że nie powinien | `preserveSlugOnEdit(false)` | `TitleSlugField::make()` lub `preserveSlugOnEdit: true` |
| Podgląd ≠ zapisany slug (bez Spatie) | Duplikat w bazie | Formularz blokuje zapis regułą `unique` — zmień slug |
| Podgląd z suffixem `-2` (ze Spatie) | Kolizja w bazie wykryta w podglądzie | Oczekiwane — podgląd używa tych samych reguł co `HasSlug` |
| Spatie nie działa | Brak pakietu | `composer require spatie/laravel-sluggable` |
| Spatie nie działa | Model bez `getSlugOptions()` / `#[Sluggable]` | Dodaj konfigurację w modelu lub `->spatieModel(Post::class)` |
| `usesSpatieIntegration()` = false | Model formularza ≠ model ze SlugOptions | Jawne `spatieModel(Post::class)` |
| Brak paska permalinku | `url_host` = null | Ustaw `APP_URL` lub `->urlHost(config('app.url'))` |
| Permalink obcina host (`...`) | UX w trybie edit | Normalne — pełny URL w Copy/Visit |
| Unique validation błędnie pada | Brak scope (multi-tenant) | `->slugUniqueScope(fn ($q) => $q->where('tenant_id', ...))` |
| Unique nie działa przy mount | Model record niedostępny | Pakiet odkłada regułę — upewnij się że Resource binduje model |
| `slugifyUsing()` nie działa | Closure nie zwraca stringa lub brak `$state['source']` | `slugifyUsing()` **zawsze wygrywa** nad Spatie, gdy jest ustawiony |
| Test `->set('data.slug')` nie działa | `wire:ignore` + Alpine | Zmieniaj `data.title` lub flagę auto-update |
| `Target [Model] is not instantiable` | Closure z typem `?Model` w mount | Użyj `mixed $record` lub bez hintu Model w closure formularza |
| Regenerate nie widać | Auto-sync nadal aktywny | Najpierw ręcznie edytuj slug (lub ustaw hidden flag) |
| Homepage `/` odrzucony | Domyślny pattern bez `allowHomepageSlug()` | `->allowHomepageSlug()` (auto-pattern includes `/`) lub własny `slugPattern` |
| Pola w Repeaterze nie syncują | Zła ścieżka source | `->source('title')` w tym samym wierszu — ścieżki zagnieżdżone są auto |

**Diagnostyka w tinker / teście:**

```php
$field = SlugField::make('slug')->spatieModel(Post::class);

$field->usesSpatieIntegration();      // true/false
$field->shouldUseServerSideGeneration(); // true gdy Spatie, translatable titles lub serverSideGeneration()
$field->getSourceStatePath();         // np. "data.title"
$field->generateSlugFromSource('Hello World'); // podgląd PHP
```

---

### Comparison with `blendbyte/filament-title-with-slug`

| Feature | Flex Fields `TitleSlugField` | blendbyte |
|---------|------------------------------|-----------|
| Title + slug fused layout | Yes | Yes |
| Permalink preview + HTTPS lock | Yes | Partial |
| Copy URL button | Yes | No |
| Regenerate after manual edit | Yes | Limited |
| Auto / Custom badge | Yes | No |
| Standalone `SlugField` | Yes | No |
| Scoped unique validation | Yes | Basic |
| Full Spatie `SlugOptions` preview | Yes | Partial |
| `#[Sluggable]` attribute support | Yes | No |
| Multi-source `generateSlugsFrom([])` | Yes | No |
| Icon-only action buttons (Hero UI) | Yes | No |
| FlexField / playground integration | Yes | No |
| Gravity icons | Yes | Heroicons |

---

## TranslatableFields

### Summary

Locale-aware **schema layout** built on **SegmentTabs** (iOS-style segmented tabs, same visual language as [SegmentControl](#segmentcontrol)). Clones one or more field templates into per-locale tabs with automatic state paths, JSON hydration, and optional Spatie `laravel-translatable` support.

Designed as a first-party, extensible alternative to third-party translatable tab packages — with explicit extension points (`localeFieldUsing`, `storageAttributeUsing`, tab/field modifiers) and no external plugin dependency.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields` |
| **Extends** | `SegmentTabs` |
| **Legacy alias** | `TranslatableTabs` (drop-in namespace swap from `abdulmajeed-jamaan/filament-translatable-tabs`) |
| **Field macro** | `Field::translatableFields()` / `Field::translatableTabs()` |
| **Component type** | Schema / layout (not a form field) |
| **Form state** | Dot paths per locale: `title.ar`, `title.en`, … |
| **DB storage** | JSON / array column: `{"ar":"…","en":"…"}` or Spatie translatable attribute |

> **Not the same as TitleSlugField translations.** [TitleSlugField](#translatable-titles-single-slug) provides a narrower use case: translatable **titles** with a **single shared slug** and slug-source-locale sync. Use `TranslatableFields` for any generic translatable attribute (body, excerpt, metadata, …).

### Basic usage — standalone component

Register field templates with `->schema()`. Each template is cloned once per locale tab.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;

TranslatableFields::make('Content')
    ->locales(['ar' => 'Arabic', 'en' => 'English'])
    ->withRecommendedDefaults()
    ->schema([
        FlexTextInput::make('title')->hiddenLabel(),
        FlexTextareaField::make('body')->hiddenLabel(),
    ]);
```

On save, Filament merges `title.ar` / `title.en` (and `body.ar` / `body.en`) into nested arrays on the model. On edit, values are hydrated from JSON columns automatically.

### Basic usage — field macro

Wrap a single field in locale tabs without declaring `TranslatableFields` explicitly. The macro returns the `TranslatableFields` component (replace the field in your schema with the return value).

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;

FlexTextInput::make('title')
    ->label('Title')
    ->translatableFields(['ar', 'en']);
```

The macro uses the field's label as the segment-tabs heading and wraps the field as the sole template.

#### Macro with inline modifiers

```php
use Filament\Forms\Components\Field;

FlexTextInput::make('title')
    ->label('Title')
    ->translatableFields(
        locales: ['pl', 'en'],
        modifyTabsUsing: fn ($tab, string $locale) => null,
        modifyFieldsUsing: fn (Field $field, string $locale) => $field
            ->placeholder("Title ({$locale})"),
    );
```

| Macro parameter | Type | Description |
|-----------------|------|-------------|
| `$locales` | `array\|Closure\|null` | Locale codes or `locale => label` map. `null` = read from config. |
| `$modifyTabsUsing` | `Closure(TranslatableTab, string $locale): void\|null` | Applied to each locale tab after build. |
| `$modifyFieldsUsing` | `Closure(Field, string $locale): void\|null` | Applied to each cloned field after build. |

### Legacy aliases

For projects migrating from `abdulmajeed-jamaan/filament-translatable-tabs`:

```php
// Class alias — swap import only
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableTabs;

TranslatableTabs::make('Content')
    ->locales(['de' => 'DE', 'en' => 'EN'])
    ->schema([FlexTextInput::make('title')]);

// Field macro alias
FlexTextInput::make('title')->translatableTabs(['de', 'en']);
```

Third-party preset method names are also aliased on the component:

| Preferred method | Migration alias |
|------------------|-----------------|
| `directionByLocale()` | `addDirectionByLocale()` |
| `emptyBadgeWhenAllFieldsAreEmpty()` | `addEmptyBadgeWhenAllFieldsAreEmpty()` |
| `activeTabWithValue()` | `addSetActiveTabThatHasValue()` |

### Single field vs multiple fields

#### Single field

One template field → one input per locale tab. Ideal for titles, names, short strings.

```php
TranslatableFields::make('Title')
    ->locales(['ar' => 'Arabic', 'en' => 'English'])
    ->directionByLocale()
    ->emptyBadgeWhenAllFieldsAreEmpty()
    ->activeTabWithValue()
    ->schema([
        FlexTextInput::make('title')->label('Title')->hiddenLabel(),
    ]);
```

Or via macro:

```php
FlexTextInput::make('title')->label('Title')->translatableFields(['ar', 'en']);
```

#### Multiple fields (group)

Several templates share the same locale tabs — all fields in a tab belong to that locale.

```php
TranslatableFields::make('Article')
    ->locales(['ar' => 'Arabic', 'en' => 'English'])
    ->withRecommendedDefaults()
    ->schema([
        FlexTextInput::make('title')->hiddenLabel(),
        FlexTextareaField::make('body')->hiddenLabel(),
        FlexTextareaField::make('excerpt')->hiddenLabel(),
    ]);
```

Empty-badge logic considers **all** fields in a tab: the badge appears only when every field in that locale tab is empty.

### Locales configuration

Locales can be supplied inline, split across codes and labels, or read from config.

#### Inline map (`locale => label`)

```php
TranslatableFields::make('Content')
    ->locales(['pl' => 'Polski', 'en' => 'English'])
    ->schema([FlexTextInput::make('title')]);
```

#### List of codes + separate labels

```php
TranslatableFields::make('Content')
    ->locales(['ar', 'en'])
    ->localesLabels([
        'ar' => __('locales.ar'),
        'en' => __('locales.en'),
    ])
    ->schema([FlexTextInput::make('title')]);
```

When only codes are given and no matching label exists, the tab label defaults to the uppercased locale code (`en` → `EN`).

#### From config (omit `->locales()`)

```php
// config/filament-flex-fields.php
'translatable' => [
    'locales' => ['ar', 'en'],
    'locale_labels' => ['ar' => 'Arabic', 'en' => 'English'],
],
```

```php
TranslatableFields::make('Content')
    ->schema([FlexTextInput::make('title')]);
```

If `translatable.locales` is unset, the resolver falls back to `slug.translatable_locales`.

Both `locales()` and `localesLabels()` accept `Closure` for dynamic resolution (e.g. tenant-specific languages).

### Production presets

Bundled helpers for common production UX. Combine individually or use the bundle.

#### `withRecommendedDefaults(?string $emptyBadgeLabel = null)`

Applies all three presets below. Pass a custom empty-badge label or rely on config (`translatable.empty_badge_label`, default `'empty'`).

```php
TranslatableFields::make('Article')
    ->locales(['ar' => 'Arabic', 'en' => 'English'])
    ->withRecommendedDefaults('missing')
    ->schema([FlexTextInput::make('title')]);
```

#### `borderedPanels(bool $condition = true)`

Adds class `fff-translatable-fields--bordered` so the active tab panel renders inside a bordered card (`rounded-xl`, padding). **Off by default** — fields sit flush under the locale tabs. Use when you want a contained content area (e.g. multi-field groups in a Section).

```php
TranslatableFields::make('Article')
    ->locales(['pl' => 'PL', 'en' => 'EN'])
    ->borderedPanels()
    ->schema([
        FlexTextInput::make('title'),
        FlexTextareaField::make('body'),
    ]);
```

#### `directionByLocale()`

Sets `dir="rtl"` on fields for RTL locales. Default list from config:

```php
'translatable' => [
    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
],
```

Locales starting with `ar` (e.g. `ar-SA`) are also treated as RTL.

#### `emptyBadgeWhenAllFieldsAreEmpty(?string $emptyLabel = null)`

Shows a warning badge on locale tabs where **all** schema fields are empty. Useful on edit forms to spot untranslated locales.

```php
TranslatableFields::make('Content')
    ->locales(['ar', 'en'])
    ->emptyBadgeWhenAllFieldsAreEmpty(__('locales.empty'))
    ->schema([FlexTextInput::make('title')]);
```

Tabs use `->live()` so badges update as the user types.

#### `activeTabWithValue()`

On mount, selects the first locale tab that has at least one non-empty field. Falls back to tab 1 when all tabs are empty.

```php
TranslatableFields::make('Content')
    ->locales(['ar', 'en'])
    ->activeTabWithValue()
    ->schema([FlexTextInput::make('title')]);
```

### Storage — JSON / array (no Spatie)

Recommended minimum setup. Works out of the box with Filament's nested state merging.

```php
// migration
$table->json('title');
$table->json('body')->nullable();
```

```php
class Post extends Model
{
    protected $fillable = ['title', 'body'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
        ];
    }
}
```

```php
TranslatableFields::make('Content')
    ->locales(['pl' => 'PL', 'en' => 'EN'])
    ->schema([
        FlexTextInput::make('title')->hiddenLabel(),
        FlexTextareaField::make('body')->hiddenLabel(),
    ]);
```

**State shape:**

| Layer | Shape |
|-------|-------|
| Form state paths | `title.pl`, `title.en`, `body.pl`, … |
| Persisted attribute | `{"pl":"Tytuł","en":"Title"}` per column |

On edit, `TranslatableHydrator` reads the JSON/array attribute and fills each locale field when its state is empty.

### Storage — Spatie `laravel-translatable` (optional)

```bash
composer require spatie/laravel-translatable
```

```php
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasTranslations;

    public array $translatable = ['title', 'body'];

    protected $fillable = ['title', 'body'];
}
```

```php
TranslatableFields::make('Content')
    ->spatieTranslatable(true)
    ->locales(['pl' => 'PL', 'en' => 'EN'])
    ->schema([
        FlexTextInput::make('title')->hiddenLabel(),
        FlexTextareaField::make('body')->hiddenLabel(),
    ]);
```

| Behaviour | Detail |
|-----------|--------|
| **Without Spatie installed** | `spatieTranslatable(true)` is an intent flag; JSON/array hydration still works. |
| **On edit** | When the record uses `HasTranslations`, each field hydrates via `getTranslation($attribute, $locale, false)`. |
| **On save** | With `spatieTranslatable(true)`, empty strings dehydrate to `null` per locale (trimmed). Spatie JSON-encodes translatable attributes. |
| **Package dependency** | Spatie is **optional** — not required by this package. |

Works alongside array/json casts when Spatie is not used on the model.

### Advanced customization

#### `localeFieldUsing(Closure $callback)`

Replace the default field-cloning strategy. Return a custom `Field` instance or `null` to fall back to the default clone.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Filament\Forms\Components\Field;

TranslatableFields::make('Content')
    ->locales(['pl' => 'PL'])
    ->localeFieldUsing(function (FlexTextInput $template, string $locale, TranslatableTab $tab): FlexTextInput {
        return FlexTextInput::make("custom_{$locale}")
            ->label($template->getLabel())
            ->placeholder("Title ({$locale})");
    })
    ->schema([FlexTextInput::make('title')->label('Title')]);
```

Injected closure parameters: `$template`, `$locale`, `$tab`.

#### `storageAttributeUsing(Closure $callback)`

Override the Eloquent attribute used for hydration (default: template field `name`).

```php
TranslatableFields::make('Content')
    ->locales(['en' => 'EN'])
    ->storageAttributeUsing(fn (Field $template, string $locale): string => 'custom_title')
    ->schema([FlexTextInput::make('title')]);
```

Useful when the form field name differs from the database column, or when multiple templates map to custom storage logic.

#### `modifyTabsUsing(Closure $closure, bool $merge = true)`

Run callbacks against each `TranslatableTab` after build. `$merge = false` replaces all previous tab modifiers.

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;

TranslatableFields::make('Content')
    ->locales(['en' => 'English'])
    ->modifyTabsUsing(function (TranslatableTab $tab, string $locale): void {
        $tab->icon('heroicon-o-language');
    })
    ->schema([FlexTextInput::make('title')]);
```

#### `modifyFieldsUsing(Closure $closure, bool $merge = true)`

Run callbacks against each cloned field. `$merge = false` replaces all previous field modifiers.

```php
TranslatableFields::make('Content')
    ->locales(['pl', 'en'])
    ->modifyFieldsUsing(function (Field $field, string $locale): void {
        $field
            ->placeholder("Enter text ({$locale})")
            ->maxLength(255);
    })
    ->schema([FlexTextInput::make('title')]);
```

Presets such as `directionByLocale()` and `emptyBadgeWhenAllFieldsAreEmpty()` are implemented as stacked `modifyTabsUsing` / `modifyFieldsUsing` callbacks.

#### Custom tab badges

Beyond the empty-badge preset, `TranslatableTab` (extends `SegmentTab`) supports Filament badge APIs:

```php
TranslatableFields::make('Content')
    ->locales(['en' => 'English'])
    ->modifyTabsUsing(fn (TranslatableTab $tab) => $tab
        ->badge('draft')
        ->badgeColor('gray'))
    ->schema([FlexTextInput::make('title')]);
```

### State paths and nested attributes

`TranslatableAttributePath` resolves form paths from template field names and optional custom `statePath()`:

| Template | Locale | Form state path |
|----------|--------|-----------------|
| `FlexTextInput::make('title')` | `en` | `title.en` |
| `FlexTextInput::make('title')->statePath('metadata.title')` | `en` | `metadata.title.en` |

Storage attribute for hydration defaults to the field `name` (`title`), not the full state path.

### Custom configuration API

#### `schema(array|Closure $fields)`

One or more `Field` instances used as templates. Only `Field` subclasses are supported — other schema components will throw at build time.

#### `locales(array|Closure $locales)`

Locale codes as a list (`['ar', 'en']`) or map (`['ar' => 'Arabic', 'en' => 'English']`).

#### `localesLabels(array|Closure $localeLabels)`

Labels keyed by locale code. Used when `locales()` is a plain list.

#### `spatieTranslatable(bool|Closure $condition = true)`

Marks fields for Spatie-aware dehydration and documents intent. Hydration auto-detects `HasTranslations` on the record when the package is installed.

#### `localeFieldUsing(Closure $callback)` / `storageAttributeUsing(Closure $callback)`

See [Advanced customization](#advanced-customization).

#### `modifyTabsUsing(Closure $closure, bool $merge = true)` / `modifyFieldsUsing(Closure $closure, bool $merge = true)`

See [Advanced customization](#advanced-customization).

#### `directionByLocale()` / `emptyBadgeWhenAllFieldsAreEmpty(?string $emptyLabel = null)` / `activeTabWithValue()` / `withRecommendedDefaults(?string $emptyBadgeLabel = null)` / `borderedPanels(bool $condition = true)`

See [Production presets](#production-presets).

### Inherited SegmentTabs API

`TranslatableFields` extends `SegmentTabs`. Defaults differ: separators are **off** (`separators(false)` in `setUp()`), CSS class `fff-translatable-fields` is applied, and tab panels are **flat** (no border/padding). Use `borderedPanels()` for a card-style panel. Multiple fields in one tab get vertical spacing only (`mt-4` between field wrappers).

| Method | Description |
|--------|-------------|
| `activeTab(int\|Closure $activeTab)` | 1-based index of the selected tab. `activeTabWithValue()` sets a dynamic closure. |
| `persistTabInQueryString(string\|Closure\|null $key = 'segment-tab')` | Persist selected tab in the URL query string. |
| `variant(string\|Closure $variant)` | `default` (filled track) or `ghost`. **Default:** `default`. |
| `color(string\|Closure\|null $color)` | Selection accent; ghost variant defaults to `primary`. |
| `separators(bool\|Closure $condition = true)` | Vertical dividers between segments. **Default for TranslatableFields:** `false`. |
| `fullWidth(bool\|Closure $condition = true)` | Stretch tabs to full container width. |
| `iconOnly(bool\|Closure $condition = true)` | Hide tab labels; show icons only. |
| `expandSelectedLabel(bool\|Closure $condition = true)` | Animate selected tab to wider width. |
| `size(string\|ControlSize\|Closure $size)` | See [Control size](#control-size). |

### Global defaults

#### `TranslatableFields::configureUsing()`

Filament-native global defaults (same pattern as other Filament components):

```php
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;

TranslatableFields::configureUsing(function (TranslatableFields $component): void {
    $component
        ->locales(['ar', 'en'])
        ->localesLabels(['ar' => 'Arabic', 'en' => 'English'])
        ->withRecommendedDefaults(__('locales.empty'))
        ->separators(true);
});
```

Register in a service provider `boot()` method. Every `TranslatableFields::make()` (including macro-created instances) receives these defaults.

#### Config file

```php
// config/filament-flex-fields.php
'translatable' => [
    'locales' => ['ar' => 'Arabic', 'en' => 'English'], // or ['ar', 'en']
    'locale_labels' => ['ar' => 'Arabic', 'en' => 'English'],
    'empty_badge_label' => 'empty',
    'rtl_locales' => ['ar', 'he', 'fa', 'ur'],
],
```

| Config key | Used by |
|------------|---------|
| `translatable.locales` | Default locales when `->locales()` is omitted |
| `translatable.locale_labels` | Tab labels for list-style locale codes |
| `translatable.empty_badge_label` | `emptyBadgeWhenAllFieldsAreEmpty()` default label |
| `translatable.rtl_locales` | `directionByLocale()` RTL detection |
| `slug.translatable_locales` | Fallback when `translatable.locales` is `null` |

### Architecture overview

Internal services keep the component thin and testable:

| Class / concern | Responsibility |
|-----------------|----------------|
| `TranslatableAttributePath` | Resolves relative state paths (`metadata.title.en`) and storage attribute names |
| `TranslatableHydrator` | Hydrates locale fields from JSON columns or Spatie `getTranslation()` |
| `TranslatableFieldFactory` | Clones template fields into per-locale instances (or delegates to `localeFieldUsing`) |
| `TranslatableTabFactory` | Builds `TranslatableTab` instances from locale list + templates |
| `TranslatableTabState` | Evaluates tab field values for empty badges and active-tab selection |
| `TranslatableLocales` | Resolves locale codes and labels from inline config or `config()` |
| `SpatieTranslatableIntegration` | Detects `HasTranslations` on the edited record |
| `TranslatableFieldBuilder` | Backwards-compatible facade delegating to the services above |
| `TranslatableFields` concerns | Locales, schema templates, modifiers, presets, migration aliases |

Component concerns (under `TranslatableFields/Concerns/`):

| Concern | Methods |
|---------|---------|
| `ConfiguresTranslatableLocales` | `locales()`, `localesLabels()`, `getLocales()` |
| `CustomizesTranslatableComponents` | `schema()`, `modifyTabsUsing()`, `modifyFieldsUsing()`, `spatieTranslatable()`, `localeFieldUsing()`, `storageAttributeUsing()` |
| `BuildsTranslatableTabs` | `buildTranslatableTabs()`, child-schema modifier application |
| `ProvidesTranslatablePresets` | `directionByLocale()`, `emptyBadgeWhenAllFieldsAreEmpty()`, `activeTabWithValue()`, `withRecommendedDefaults()`, `borderedPanels()` |
| `HasTranslatableMigrationAliases` | `addDirectionByLocale()`, `addEmptyBadgeWhenAllFieldsAreEmpty()`, `addSetActiveTabThatHasValue()` |

### Relationship to TitleSlugField

| | `TranslatableFields` | `TitleSlugField` translatable titles |
|---|---------------------|--------------------------------------|
| **Purpose** | Any translatable attribute(s) | Title + single shared slug |
| **Slug** | Not involved | One slug; only `slugSourceLocale` drives auto-sync |
| **Component** | Standalone schema / field macro | `FusedGroup` factory; title tabs use `TranslatableFields` |
| **Locale config** | `translatable.*` config + `->locales()` | `slug.translatable_locales` + `translatableLocales` param |
| **Shared internals** | Full architecture above | Same `TranslatableFields` + `TranslatableHydrator` for title tabs |
| **Extra config** | Direct fluent API | `translatableFieldsConfigurator` + `titleLocaleConfigurator` |
| **Empty tab badges** | Opt-in via `emptyBadgeWhenAllFieldsAreEmpty()` | **On by default** for title locale tabs |
| **Default active tab** | `activeTabWithValue()` in `withRecommendedDefaults()` | `slugSourceLocale` tab |

For translatable titles with permalink generation, see [Translatable titles (single slug)](#translatable-titles-single-slug).

### Playground

The dev playground includes single-field and multi-field variants with RTL, empty badges, and active-tab selection. Enable playground mode in config and open the Flex Fields playground page.

---

# Part III — Appendix

## Deprecated class aliases

| Deprecated class | Replacement | Notes |
|------------------|-------------|-------|
| `CellSwitch` | `SwitchField` | Identical API |
| `CellSlider` | `TrackSlider` | Identical API |
| `TranslatableTabs` | `TranslatableFields` | Drop-in class alias; swap import only |

Update imports and class names; behaviour is unchanged.

---

## Document maintenance

Update this file whenever a component gains a new public method, validation rule, config key, or default value change.

**Last reviewed:** Full component reference including CoverCard, FlexFileUpload, FlexImageUpload, UserColumn, RatingColumn, form layout patterns (`item-card--form-panel`, `fff-form-layout--grid`), and all form fields, table columns, and layout/schema components listed in the table of contents.

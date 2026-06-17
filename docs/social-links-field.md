# SocialLinksField

![SocialLinksField](/art/sc-29.png)

[← Back to Table of Contents](index.md)


### Summary

**Social profile link editor** with a platform picker, one URL row per platform, per-platform hostname validation, optional **custom platforms**, **row reordering**, and **URL auto-formatting** on blur. Client-side validation mirrors server rules and blocks form submit when rows are invalid (same pattern as `ScheduleField`).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SocialLinksField` |
| **State type** | `list<array{platform: string, url: string}>` (also accepts legacy associative map) |
| **Model cast** | `'social_links' => 'array'` or `'json'` |
| **FieldType** | *(no dedicated FieldType mapping yet — use the class directly)* |
| **Playground** | `social-links-field` slug in Flex Fields playground |

---

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SocialLinksField;

SocialLinksField::make('social_links')
    ->label('Social profiles')
    ->required();
```

Filament resource example:

```php
use Filament\Forms\Form;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SocialLinksField;

public static function form(Form $form): Form
{
    return $form->schema([
        SocialLinksField::make('social_links')
            ->label('Broker social links')
            ->helperText('Add each platform once, then paste the profile URL.')
            ->columnSpanFull(),
    ]);
}
```

---

### State format

Each link is a list item with `platform` and `url` keys. Platform values match built-in enum slugs (`instagram`, `x`, `linkedin`, …) or custom platform `value` strings.

#### Minimal example

```json
[
  { "platform": "instagram", "url": "https://instagram.com/laravelphp" },
  { "platform": "linkedin", "url": "https://linkedin.com/company/laravel" }
]
```

#### In-progress row (empty URL kept for validation)

While editing, rows with a selected platform but empty URL remain in Livewire state so server and client validation can report **required** errors:

```json
[
  { "platform": "instagram", "url": "" },
  { "platform": "website", "url": "https://example.com" }
]
```

On successful save (`dehydrate`), rows with empty URLs are **stripped** — only complete links persist.

#### Legacy associative map (enum platforms only)

```php
[
    'instagram' => 'https://instagram.com/laravelphp',
    'website' => 'https://example.com',
]
```

This shape is normalized to the list format. Empty URL values are kept during hydration (not dropped).

---

### Default platforms

When `platforms()` is not set, all built-in platforms from `SocialPlatform::defaults()` are available:

`instagram`, `x`, `linkedin`, `youtube`, `facebook`, `tiktok`, `github`, `telegram`, `whatsapp`, `pinterest`, `threads`, `discord`, `messenger`, `reddit`, `twitch`, `vimeo`, `vk`, `website`

Use `excludePlatforms()` to remove entries from this default set without building a full whitelist.

---

### Configuration API

All methods accept `Closure` for dynamic configuration.

#### `platforms(array|Closure|null $platforms)`

Whitelist which platforms appear in the picker. Accepts `SocialPlatform` enum cases and/or string values (including custom platform values defined via `customPlatforms()`).

Default: **`null`** (use defaults minus exclusions, plus custom platforms).

```php
use Bjanczak\FilamentFlexFields\Data\SocialPlatform;

SocialLinksField::make('footer_links')
    ->platforms([
        SocialPlatform::Instagram,
        SocialPlatform::X,
        SocialPlatform::LinkedIn,
        SocialPlatform::Website,
    ]);
```

Whitelist with a custom platform:

```php
SocialLinksField::make('fediverse')
    ->platforms(['mastodon', SocialPlatform::Website])
    ->customPlatforms([
        [
            'value' => 'mastodon',
            'label' => 'Mastodon',
            'placeholder' => 'https://mastodon.social/@username',
            'hosts' => ['mastodon.social', 'mastodon.online'],
        ],
    ]);
```

#### `excludePlatforms(array|Closure $platforms)`

Exclude platforms from the default set when `platforms()` is **not** configured.

Default: **`[]`**.

```php
SocialLinksField::make('social_links')
    ->excludePlatforms([
        SocialPlatform::Vk,
        SocialPlatform::Twitch,
        SocialPlatform::Reddit,
    ]);
```

#### `customPlatforms(array|Closure $platforms)`

Add or override platform metadata. Each entry is an array (or resolved from a Closure) with:

| Key | Required | Description |
|-----|----------|-------------|
| `value` | yes | Unique platform slug stored in state |
| `label` | no | Display label (defaults to `value`) |
| `placeholder` | no | URL input placeholder |
| `hosts` | no | Allowed URL host patterns; empty = any valid http(s) URL |
| `iconSvg` | no | Raw SVG HTML for brand icon; falls back to link icon partial |

Definitions are represented server-side by `SocialPlatformDefinition`.

```php
SocialLinksField::make('social_links')
    ->customPlatforms([
        [
            'value' => 'bluesky',
            'label' => 'Bluesky',
            'placeholder' => 'https://bsky.app/profile/username',
            'hosts' => ['bsky.app', 'bsky.social'],
            'iconSvg' => '<svg class="fff-social-links__brand-icon" aria-hidden="true">...</svg>',
        ],
    ]);
```

Custom platforms are merged into the default picker list. When `platforms()` is set, only matching custom entries are included.

#### `maxLinks(int|Closure|null $max)`

Cap how many platform rows can be added. When reached, the picker shows a “all platforms added” message.

Default: **`null`** (unlimited, still one row per platform).

```php
SocialLinksField::make('social_links')
    ->maxLinks(5);
```

#### `reorderable(bool|Closure $enabled = true)`

Show up/down chevron buttons on each row to change display order.

Default: **`false`**. Enable explicitly:

```php
SocialLinksField::make('social_links')
    ->reorderable();
```

Disable again (e.g. in a closure):

```php
SocialLinksField::make('social_links')
    ->reorderable(false);
```

#### `autoFormatUrls(bool|Closure $enabled = true)`

On URL input blur: trim whitespace and prepend `https://` when no scheme is present.

Default: **`true`**.

```php
SocialLinksField::make('social_links')
    ->autoFormatUrls(false);
```

#### `variant(string|Closure $variant)`

Visual variant passed to shared flex text input tokens.

Default: **`primary`**. Allowed: `primary`, `secondary`, `soft`, `flat`, `ghost`.

```php
SocialLinksField::make('social_links')
    ->variant('soft');
```

#### `size(string|Closure $size)`

Control density via `HasControlSize`.

Default: **`md`**.

```php
SocialLinksField::make('social_links')
    ->size('sm');
```

#### `readOnly()` / `disabled()`

Standard Filament interaction states — picker, add, remove, reorder, and URL inputs become non-interactive.

#### `required()`

Field is empty when no rows have a non-empty URL after normalization. Rows with platform selected but empty URL fail validation.

#### `focusOutline(bool|Closure $enabled = true)`

Show focus ring on URL input shells (`HasFieldFocusOutline`).

---

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getPlatformValues()` | `list<string>` | Resolved platform slugs |
| `getPlatformDefinitionMap()` | `array<string, SocialPlatformDefinition>` | Full metadata map |
| `getPlatformDefinitions()` | `list<array>` | Alpine-ready definitions including `hosts` |
| `getBrandIconSvgs()` | `array<string, string>` | Rendered SVG map per platform |
| `getAlpineConfiguration()` | `array` | Full JS config blob |
| `getWrapperClasses()` | `array<string, bool>` | BEM wrapper class map |
| `isReorderable()` | `bool` | Whether reorder UI is enabled |
| `shouldAutoFormatUrls()` | `bool` | Whether blur formatting runs |
| `getMaxLinks()` | `?int` | Link cap |
| `getVariant()` / `getSize()` | `string` | Resolved presentation tokens |

---

### Validation

#### Server (`SocialLinkValidator`)

| Rule | Message key |
|------|-------------|
| Empty platform or URL | `social_links.validation.required` |
| Unknown platform (not in definition map) | `social_links.validation.unknown_platform` |
| Platform not in whitelist | `social_links.validation.platform_not_allowed` |
| Invalid URL / non-http(s) scheme | `social_links.validation.invalid_url` |
| Host does not match platform `hosts` | `social_links.validation.platform_mismatch` |

Row errors are wrapped with `social_links.validation.row` (`:platform: :message`).

Built-in host rules mirror `SocialPlatform::hostPatterns()`. Custom platforms use their `hosts` array. `website` and custom entries with empty `hosts` accept any valid http(s) URL.

#### Client (Alpine + `social-link-validation.js`)

Same rules using dynamic `hosts` from `getPlatformDefinitions()`. On form **submit** (capture phase):

1. `showValidationErrors = true`
2. `validateAllRows()`
3. If errors exist: `preventDefault()`, `stopImmediatePropagation()`, sync first error to Livewire via `$wire.addError(statePath, message)`

This matches the `ScheduleField` submit guard pattern.

---

### Recipes

#### CMS footer — small whitelist + cap

```php
SocialLinksField::make('footer_socials')
    ->label('Footer social icons')
    ->platforms([
        SocialPlatform::Instagram,
        SocialPlatform::X,
        SocialPlatform::LinkedIn,
        SocialPlatform::YouTube,
        SocialPlatform::Website,
    ])
    ->maxLinks(4)
    ->variant('secondary')
    ->size('sm');
```

#### Broker profile — required with auto-format

```php
SocialLinksField::make('broker_socials')
    ->label('Public profiles')
    ->required()
    ->autoFormatUrls()
    ->reorderable();
```

#### Exclude niche platforms from defaults

```php
SocialLinksField::make('social_links')
    ->excludePlatforms([
        SocialPlatform::Vk,
        SocialPlatform::Twitch,
        SocialPlatform::Messenger,
    ]);
```

#### Fediverse / custom brand

```php
SocialLinksField::make('social_links')
    ->platforms(['mastodon', 'bluesky', SocialPlatform::Website])
    ->customPlatforms([
        [
            'value' => 'mastodon',
            'label' => 'Mastodon',
            'placeholder' => 'https://mastodon.social/@username',
            'hosts' => ['mastodon.social', 'mastodon.online'],
        ],
        [
            'value' => 'bluesky',
            'label' => 'Bluesky',
            'placeholder' => 'https://bsky.app/profile/handle',
            'hosts' => ['bsky.app', 'bsky.social'],
        ],
    ]);
```

#### Read-only display on view page

```php
SocialLinksField::make('social_links')
    ->readOnly()
    ->default(fn ($record) => $record->social_links);
```

#### Dynamic platform list from tenant settings

```php
SocialLinksField::make('social_links')
    ->platforms(fn (): array => tenant()->allowedSocialPlatforms())
    ->maxLinks(fn (): int => tenant()->socialLinkLimit());
```

#### Eloquent model persistence

```php
// Model
protected $casts = [
    'social_links' => 'array',
];

// Saving — dehydrated state is null or list of complete links
$broker->social_links = $data['social_links'];
```

---

### UI behaviour

| Feature | Detail |
|---------|--------|
| Platform picker | Teleported dropdown listbox; one platform per row maximum |
| Add flow | Select platform → **Add** → focus URL input |
| URL input | `FlexTextInput` shell; per-row inline errors |
| Reorder | Up/down circle buttons when `reorderable(true)` |
| Auto-format | Blur trims and adds `https://` when `autoFormatUrls(true)` |
| Picker a11y | `ArrowUp`/`ArrowDown`/`Home`/`End`/`Enter`/`Escape`; `aria-activedescendant`; `.is-highlighted` option |
| SSR + hydration | Server-rendered list for first paint; Alpine live list after `is-hydrated` |
| State sync | `wire:ignore` + `$entangle` — full links JSON synced to Livewire |
| Submit guard | Blocks native submit when client validation fails; syncs first error to Livewire |

---

### CSS classes

| Class | Role |
|-------|------|
| `fff-social-links` | Root wrapper |
| `fff-social-links-field` | Field wrapper (via `getWrapperClasses`) |
| `fff-social-links-field--{sm\|md\|lg}` | Size modifier |
| `fff-social-links-field--{variant}` | Variant modifier |
| `fff-social-links-field--reorderable` | Reorder feature enabled |
| `fff-social-links__toolbar` | Picker + Add button row |
| `fff-social-links__add-trigger` | Platform dropdown trigger |
| `fff-social-links__add-btn` | Confirm add button |
| `fff-social-links__platform-menu` | Teleported listbox panel |
| `fff-social-links__platform-option` | Picker option |
| `fff-social-links__platform-option.is-highlighted` | Keyboard-highlighted option |
| `fff-social-links__platform-option.is-active` | Currently selected platform |
| `fff-social-links__list` | Link rows container |
| `fff-social-links__item` | Single platform row |
| `fff-social-links__item-actions` | Reorder button group |
| `fff-social-links__reorder-btn` | Up/down circle button |
| `fff-social-links__remove-btn` | Remove row button |
| `fff-social-links__row-error` | Inline validation message |
| `fff-social-links__brand-icon` | SVG icon wrapper in partial |

---

### Assets

| Asset | Purpose |
|-------|---------|
| Stylesheet `social-links-field` | Component layout and states |
| Alpine `social-links-field` | Interactive field logic |
| Dependency `flex-text-input` | URL input shell |
| Dependency `teleported-menu` | Platform dropdown positioning |

Registered in `FlexFieldAssets`. Blade includes `load-stylesheet` partial and `x-load` Alpine component.

---

### PHP support classes

| Class | Role |
|-------|------|
| `SocialPlatform` | Built-in platform enum, labels, placeholders, host patterns |
| `SocialPlatformDefinition` | DTO for built-in + custom platform metadata |
| `SocialLinksNormalizer` | Hydrate/normalize/dehydrate state shapes |
| `SocialLinkValidator` | Server-side URL + host validation |

---

### JavaScript modules

| Module | Role |
|--------|------|
| `resources/js/components/social-links-field.js` | Alpine component, submit guard, reorder, picker a11y |
| `resources/js/support/social-link-validation.js` | Shared validation, normalization, URL formatting helpers |

Exported test helpers: `collectSocialLinkRowErrors`, `hasSocialLinkValidationErrors`, `firstSocialLinkValidationError`, `dehydrateSocialLinksState`, `formatSocialLinkUrl`.

---

### Testing

```bash
# PHP unit + feature
php artisan test --compact tests/Unit/SocialLinksFieldTest.php
php artisan test --compact tests/Feature/SocialLinksFieldRenderTest.php

# JS helpers
node --test tests/js/social-link-validation.test.mjs
node --test tests/js/social-links-field.test.mjs

# Playground E2E (requires FLEX_FIELDS_PLAYGROUND_URL)
npx playwright test tests/e2e/playground-social-links-field.spec.mjs
```

---

### Related components

- **LinkPreviewField** — fetch Open Graph preview for a single URL
- **PhoneField** — similar teleported picker + validation patterns
- **ScheduleField** — submit guard reference implementation

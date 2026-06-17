# LinkPreviewField

![LinkPreviewField](/art/sc-28.png)

[← Back to Table of Contents](/docs/index)


### Summary

URL input with a live Open Graph / HTML meta preview card. The field uses the **FlexTextInput pill shell** for the input track and fetches page metadata through a **server-side scrape endpoint** (cached, rate-limitable). Preview cards support three layouts, optional URL prefix/suffix affixes, and configurable debounce / skeleton timing.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\LinkPreviewField` |
| **State type** | `string\|null` — full URL (including configured prefix) |
| **Model cast** | `'article_url' =&gt; 'string'` or leave uncast |
| **FieldType** | *(no dedicated FieldType mapping yet — use the class directly)* |
| **Playground** | `link-preview-field` slug in Flex Fields playground |

---

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\LinkPreviewField;

LinkPreviewField::make('article_url')
    ->label('Article URL')
    ->placeholder('https://example.com/article')
    ->required();

LinkPreviewField::make('landing_page')
    ->label('Landing page')
    ->previewLayout('card')
    ->previewDebounce(750)
    ->default('https://laravel.com');
```

On a Filament resource:

```php
use Filament\Forms\Form;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\LinkPreviewField;

public static function form(Form $form): Form
{
    return $form->schema([
        LinkPreviewField::make('external_url')
            ->label('Source URL')
            ->helperText('Paste a public URL — title, description, and image are fetched automatically.')
            ->columnSpanFull(),
    ]);
}
```

---

### State format

| Value | Description | Example |
|-------|-------------|---------|
| Valid URL | Trimmed absolute URL stored on save | `https://laravel.com/docs` |
| Empty | `null` after dehydrate | `null` |

**Important:** when `prefix('https://')` is set, the **stored state is always the full URL** (`https://example.com/path`). The visible input shows only the suffix (`example.com/path`) for readability. On blur, pasted full URLs are normalized back to suffix-only display.

```php
LinkPreviewField::make('blog_path')
    ->prefix('https://')
    ->default('https://acme.test/blog/launch');

// Saved state: "https://acme.test/blog/launch"
// Input display: "acme.test/blog/launch"
```

Whitespace is trimmed on hydrate and dehydrate. Empty strings become `null`.

---

### Preview layouts

Three card layouts via `previewLayout()`:

| Layout | Modifier class | Best for |
|--------|----------------|----------|
| `horizontal` (default) | `fff-link-preview__card--horizontal` | Compact rows — square thumb left, title + description + domain right (Twitter / X style) |
| `vertical` | `fff-link-preview__card--vertical` | Narrow columns — wide thumb on top, title + domain below |
| `card` | `fff-link-preview__card--card` | Full-width social cards — 16:9 thumb, title + domain |

```php
LinkPreviewField::make('share_url_horizontal')
    ->label('Share URL')
    ->previewLayout('horizontal');

LinkPreviewField::make('share_url_vertical')
    ->label('Share URL')
    ->previewLayout('vertical');

LinkPreviewField::make('share_url_card')
    ->label('Share URL')
    ->previewLayout('card')
    ->columnSpanFull();
```

The preview card is **hidden when metadata is empty** (no title, description, or image). A **skeleton shimmer** shows while fetching or while preloading the OG image. Errors render in a subtle `role="alert"` region below the card.

---

### Configuration API

Each fluent method accepts a `Closure` for dynamic values (e.g. based on `$get`, `$record`, or `$livewire`).

#### `variant(string|Closure $variant)`

Visual style shared with FlexTextInput.

| Value | Description |
|-------|-------------|
| `primary` | Default filled pill shell |
| `secondary` | Secondary surface tokens |
| `soft` | Softer background / border |
| `flat` | Minimal chrome |
| `ghost` | Transparent shell |

```php
LinkPreviewField::make('url')
    ->variant('soft');

LinkPreviewField::make('url')
    ->variant(fn (): string => 'secondary');
```

#### `size(string|ControlSize|Closure $size)`

Control height. See [Control size](/docs/shared-concepts). Default: **`md`**.

```php
use Bjanczak\FilamentFlexFields\Enums\ControlSize;

LinkPreviewField::make('url')->size('sm');
LinkPreviewField::make('url')->size(ControlSize::Lg);
```

#### `preview(bool|Closure $condition = true)`

Enable or disable the preview card entirely. When `false`, the field behaves as a styled URL input only.

```php
LinkPreviewField::make('internal_path')
    ->preview(false)
    ->rules(['url']);
```

#### `previewDebounce(int|Closure $milliseconds)`

Delay after typing before the client calls the scrape endpoint. Default: **`500`**. Pass **`0`** for immediate fetch (use sparingly).

```php
LinkPreviewField::make('url')->previewDebounce(750);

LinkPreviewField::make('url')->previewDebounce(0); // instant
```

#### `previewMinUrlLength(int|Closure $length)`

Minimum resolved URL character length before scraping starts. Default: **`10`**. Enforced minimum: **`4`**.

```php
LinkPreviewField::make('url')->previewMinUrlLength(12);
```

Useful with prefixes — the check runs against the **full resolved URL**, not the visible suffix alone.

#### `previewMinSkeletonMs(int|Closure $milliseconds)`

Minimum skeleton display time on initial card reveal (SSR-prefilled URLs or first client fetch). Default: **`500`**. Prevents flicker when metadata resolves instantly from cache.

```php
LinkPreviewField::make('url')->previewMinSkeletonMs(300);
LinkPreviewField::make('url')->previewMinSkeletonMs(0); // no minimum
```

#### `previewLayout('horizontal'|'vertical'|'card'|Closure $layout)`

Card layout. Default: **`horizontal`**. Invalid values throw `InvalidArgumentException`.

```php
LinkPreviewField::make('url')->previewLayout('card');
```

#### `resolveInitialPreviewOnServer(bool|Closure $condition = true)`

When **`true`** (default), the Blade view calls `resolveInitialPreview()` during SSR for prefilled URLs so the card can render immediately without waiting for Alpine.

When **`false`**, initial preview is deferred to the client — useful on heavy forms to avoid blocking page render or duplicate scrapes.

```php
LinkPreviewField::make('url')
    ->default('https://laravel.com')
    ->resolveInitialPreviewOnServer(false);
```

#### `showVisitLink(bool|Closure $condition = true)`

When **`true`** (default), the domain row is an `&lt;a&gt;` opening the URL in a new tab (`rel="noopener noreferrer"`).

When **`false`**, the domain is plain text with the same icon styling (`fff-link-preview__domain--text`).

```php
LinkPreviewField::make('url')->showVisitLink(false);
```

#### `visitLabel(string|Closure $label)`

Accessible label for the visit link (`aria-label`). Default: translated `filament-flex-fields::default.link_preview.visit`.

```php
LinkPreviewField::make('url')->visitLabel('Open article in new tab');
```

#### `visitIcon(string|BackedEnum|Htmlable|Closure|null $icon)`

Icon beside the domain row. Default: `GravityIcon::Paperclip`.

```php
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

LinkPreviewField::make('url')->visitIcon(GravityIcon::Link);
LinkPreviewField::make('url')->visitIcon('heroicon-o-arrow-top-right-on-square');
```

#### `prefix(string|Closure|null $label)` / `suffix(string|Closure|null $label)`

Inline affix labels on the FlexTextInput track. Empty strings are treated as no affix.

```php
LinkPreviewField::make('article_path')
    ->prefix('https://')
    ->suffix('.html');

LinkPreviewField::make('cdn_path')
    ->prefix(fn (): string => config('app.cdn_url').'/');
```

#### `placeholder(string|Closure|null $placeholder)`

Inherited from Filament `HasPlaceholder`. Default translation: `filament-flex-fields::default.link_preview.placeholder`.

```php
LinkPreviewField::make('url')->placeholder('https://example.com');
```

#### `readOnly(bool|Closure $condition = true)` / `disabled(bool|Closure $condition = true)`

Inherited from Filament. Read-only still shows preview for the current URL; disabled blocks interaction and scraping triggers.

```php
LinkPreviewField::make('canonical_url')
    ->default($record->canonical_url)
    ->readOnly();

LinkPreviewField::make('url')->disabled();
```

#### `focusOutline(bool|Closure $condition = true)`

Inherited from `HasFieldFocusOutline`. Default: **`false`**. When `true`, shows the shared `--fff-field-focus-*` ring on the input shell.

```php
LinkPreviewField::make('url')->focusOutline();
```

---

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getVariant()` | `string` | Resolved variant |
| `getSize()` | `string` | Resolved size (`sm`, `md`, `lg`) |
| `isPreviewEnabled()` | `bool` | Preview card enabled |
| `getPreviewDebounce()` | `int` | Debounce ms (≥ 0) |
| `getPreviewMinUrlLength()` | `int` | Min URL length (≥ 4) |
| `getPreviewMinSkeletonMs()` | `int` | Min skeleton ms (≥ 0) |
| `getPreviewLayout()` | `string` | `horizontal`, `vertical`, or `card` |
| `shouldResolveInitialPreviewOnServer()` | `bool` | SSR preview resolution |
| `shouldShowVisitLink()` | `bool` | Domain row is a link |
| `getVisitLabel()` | `string` | Visit link aria-label |
| `getVisitIcon()` | `string\|BackedEnum\|Htmlable\|null` | Resolved visit icon |
| `getPrefix()` / `getSuffix()` | `string\|null` | Resolved affixes |
| `getScrapeUrl()` | `string` | Relative scrape route URL for Alpine |
| `resolveInitialPreview(?string $url)` | `array\|null` | Server-side metadata (`title`, `description`, `image`) |
| `getAlpineConfiguration()` | `array` | Config passed to `linkPreviewFieldFormComponent` |
| `getWrapperClasses()` | `array&lt;string, bool&gt;` | Root CSS class map |

`resolveInitialPreview()` returns `null` when preview is disabled, URL is empty, URL is not scrapable, or scrape returns no metadata.

---

### Package configuration

Global scrape behaviour in `config/filament-flex-fields.php`:

| Key | Env variable | Default | Description |
|-----|--------------|---------|-------------|
| `link_preview.cache_ttl_seconds` | `FLEX_FIELDS_LINK_PREVIEW_CACHE_TTL` | `86400` | Server-side scrape cache TTL |
| `link_preview.rate_limit_per_minute` | `FLEX_FIELDS_LINK_PREVIEW_RATE_LIMIT` | `30` | Per-user scrape rate limit |
| `link_preview.timeout_seconds` | `FLEX_FIELDS_LINK_PREVIEW_TIMEOUT` | `8` | HTTP timeout for remote pages |

Publish config:

```bash
php artisan vendor:publish --tag=filament-flex-fields-config
```

Example `.env`:

```env
FLEX_FIELDS_LINK_PREVIEW_CACHE_TTL=43200
FLEX_FIELDS_LINK_PREVIEW_RATE_LIMIT=30
FLEX_FIELDS_LINK_PREVIEW_TIMEOUT=10
```

The client also keeps an **in-memory cache** and **in-flight deduplication** (`url-meta-scrape.js`) so repeated keystrokes do not spam the server.

Scrape endpoint (named route): **`filament-flex-fields.url-meta.scrape`**.

---

### Validation

| Rule | Detail |
|------|--------|
| Built-in | `nullable`, `url` |
| `required()` | Standard Filament required validation |
| Hydrate / dehydrate | Trims whitespace; empty → `null` |

```php
LinkPreviewField::make('website')
    ->required()
    ->rules(['url', 'max:2048']);
```

---

### Model & database examples

```php
// Migration
$table->string('article_url')->nullable();

// Model — no special cast required
protected $fillable = ['article_url'];

// Factory / seeder
'article_url' => 'https://laravel.com',
```

Editing an existing record with SSR preview:

```php
LinkPreviewField::make('article_url')
    ->default(fn (?Article $record): ?string => $record?->article_url)
    ->resolveInitialPreviewOnServer(true);
```

---

### Recipe: CMS external link block

```php
use Filament\Schemas\Components\Grid;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\LinkPreviewField;

Grid::make(1)->schema([
    LinkPreviewField::make('cta_url')
        ->label('Call-to-action URL')
        ->previewLayout('card')
        ->visitLabel('Preview landing page')
        ->previewDebounce(600)
        ->columnSpanFull(),

    LinkPreviewField::make('social_proof_url')
        ->label('Social proof link')
        ->previewLayout('horizontal')
        ->variant('soft')
        ->size('sm'),
]);
```

### Recipe: prefixed marketing domain

```php
LinkPreviewField::make('campaign_path')
    ->label('Campaign page')
    ->prefix('https://go.acme.com/')
    ->placeholder('summer-sale')
    ->previewMinUrlLength(8)
    ->helperText('Enter the path only — https:// is added automatically.');
```

### Recipe: read-only audit display

```php
LinkPreviewField::make('submitted_url')
    ->label('Submitted URL')
    ->default(fn ($record) => $record->submitted_url)
    ->readOnly()
    ->showVisitLink(true)
    ->previewLayout('vertical');
```

### Recipe: heavy admin form — defer SSR scrape

```php
LinkPreviewField::make('reference_url')
    ->resolveInitialPreviewOnServer(false)
    ->previewDebounce(800)
    ->previewMinSkeletonMs(400);
```

---

### Accessibility

- Preview card container uses `aria-live="polite"` for loading and reveal updates
- When revealed, `aria-label` on the card reflects the scraped page title
- Visit link uses `visitLabel()` as `aria-label`
- Scrape errors use `role="alert"`
- Input remains a standard Filament field with label / hint / error association

---

### CSS classes

| Class | Role |
|-------|------|
| `fff-link-preview-field` | Filament wrapper modifier |
| `fff-link-preview-field--{sm\|md\|lg}` | Size modifier |
| `fff-link-preview-field--{variant}` | Variant modifier |
| `fff-link-preview-field--layout-{horizontal\|vertical\|card}` | Layout modifier on wrapper |
| `fff-link-preview` | Alpine root (FlexTextInput shell) |
| `fff-link-preview__card` | Preview card container |
| `fff-link-preview__card--{horizontal\|vertical\|card}` | Layout modifier |
| `fff-link-preview__domain--text` | Non-link domain row when `showVisitLink(false)` |
| `fff-link-preview__error` | Scrape error message |

Shares FlexTextInput shell classes (`fff-flex-text-input`, `fff-flex-text-input__shell`, variant modifiers).

---

### Assets

Lazy-loaded stylesheets (via `FlexFieldAssets::stylesheetsFor('link-preview-field')`):

- `flex-text-input`
- `link-preview-field`

Alpine component: `link-preview-field` (loaded with `x-load`).

Uses `wire:ignore` on the Alpine root — prefer changing Livewire state or sibling fields over direct DOM manipulation in tests.

---

### Implementation notes

- Metadata is scraped server-side via `UrlMetaScraper` (Open Graph + fallback `&lt;title&gt;` / `&lt;meta name="description"&gt;` / `&lt;meta property="og:image"&gt;`).
- Non-scrapable URLs (invalid scheme, localhost, etc.) skip preview quietly.
- Image preload runs client-side before revealing the card to avoid layout pop-in.
- Invalid `variant()` or `previewLayout()` values throw `InvalidArgumentException` at render time.

---

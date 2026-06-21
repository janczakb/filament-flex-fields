---
title: "SlugField & TitleSlugField"
---

![SlugField](/art/sc-22.png)

[← Back to Table of Contents](/docs/index)


### Summary

Permalink field for Filament: title + slug in one block, live URL preview, inline editing, Copy/Visit/Regenerate buttons, and uniqueness validation.

> **Spatie `laravel-sluggable` is optional.** By default, the slug is generated from the title using `Str::slug()` in the browser and saved to the database like a regular form field. You only need to add the Spatie package if you want the same rules as on model saving (such as `-2`, `-3` suffixes, `preventOverwrite`, etc.).

| | |
|---|---|
| **Slug field class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField` |
| **Title + slug factory** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField` |
| **Convenience schema** | `SlugField::withTitle()` → returns a `FusedGroup` (same as `TitleSlugField::make()`) |
| **State type** | `string\|null` (normalized slug; homepage slug is `'/'` when enabled) |
| **FieldType** | `slug` |
| **Spatie** | Optional — see [Spatie `laravel-sluggable` integration](#spatie-laravel-sluggable-integration-v4x) |

---

### Start here — integration without Spatie (default)

**No extra packages required besides `filament-flex-fields`.** The model does not need any traits or slug options—just a database column and `$fillable` configuration.

#### Who is responsible for what

| What | Who does it | Do you need to write code? |
|----|-------------|-------------------------|
| Slug preview while typing the title | `SlugField` (Alpine + `Str::slug`) | No — works automatically |
| Saving the `slug` value to the database | Filament + Eloquent | Yes — database column + `$fillable` |
| Slug uniqueness in the form | `SlugField` (`unique` rule) | No — enabled by default |
| Suffix `-2` on database collision | **Only Spatie** (`HasSlug`) | No — without Spatie, the slug must be unique in the form |
| Create vs Edit behaviour | `TitleSlugField` | No — by default, the slug does not change on edit |

#### Checklist — 4 steps

```
1. Migration     → columns: title + slug (slug usually unique)
2. Model        → slug in $fillable (NO HasSlug, NO Spatie)
3. Form         → TitleSlugField::make()
4. Config       → optional url_host in config (for UI permalink)
```

#### Step 1 — Migration

```php
Schema::create('posts', function (Blueprint $table): void {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();  // required: slug column
    $table->timestamps();
});
```

#### Step 2 — Model (minimal, without Spatie)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'title',
        'slug',   // necessary — otherwise Filament won't save the slug
    ];
}
```

The model **does not need**:
- `use HasSlug` ani `getSlugOptions()`
- an observer generating the slug
- mutatora `setSlugAttribute`
- `composer require spatie/laravel-sluggable`

The slug is saved to the record just like `title`—from the form data.

#### Step 3 — Filament Resource (minimum)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        TitleSlugField::make(),   // that's it. No parameters required.
        // ...pozostałe pola
    ]);
}
```

This creates: a **title** field + a hidden auto-sync flag + a **slug** field in one `FusedGroup`.

#### Step 4 — Config (optional, for permalink path)

If you want to see `https://your-domain.com/blog/my-post` under the slug:

```php
// config/filament-flex-fields.php (after publishing config)
'slug' => [
    'field_title' => 'title',      // title form field name
    'field_slug' => 'slug',        // slug form field name
    'url_host' => env('APP_URL'),  // null = no full URL preview bar
],
```

Or only in the Resource, without changing the config:

```php
TitleSlugField::make(
    urlHost: config('app.url'),
    urlPath: '/blog/',
),
```

#### Parameters of `TitleSlugField::make()` — what is available?

| Parameter | Required? | Default | Description |
|----------|-----------|-----------|----------------|
| *(żaden)* | — | — | `TitleSlugField::make()` wystarczy na start |
| `fieldTitle` | Nie | `'title'` | Alternative title column/field name |
| `fieldSlug` | Nie | `'slug'` | Alternative slug column/field name |
| `urlHost` | Nie | z config lub `null` | Full URL preview host |
| `urlPath` | Nie | `null` | Path prefix, e.g. `/blog/` |
| `preserveSlugOnEdit` | Nie | `true` | `false` = slug always syncs with title |
| `translatableLocales` | Nie | z config lub `null` | Enables multi-language tabs (`TranslatableFields`) |
| `slugSourceLocale` | Nie | `app.locale` / pierwszy locale | Which title language drives slug generation |
| `requiredTitleLocales` | Nie | only `slugSourceLocale` | `'all'`, `['en']` lub `null` — which title locales are required |
| `spatieTranslatable` | Nie | `false` | Config flag for Spatie models — see [Translatable titles](#translatable-titles-single-slug) |
| `titleLocaleConfigurator` | Nie | `null` | `fn (FlexTextInput $field, string $locale) =&gt; $field` |
| `translatableFieldsConfigurator` | Nie | `null` | `fn (TranslatableFields $fields) =&gt; $fields-&gt;…` — custom configuration of title tabs |
| `spatieModel` | Nie | `null` | **Only** for Spatie **Sluggable** (`HasSlug`) — do not confuse with Translatable |

#### What happens automatically (without Spatie)

| Event | Behaviour |
|----------|------------|
| **Create** — user types title | Slug updates live (`Hello World` → `hello-world`) |
| **Edit** — user changes title | Slug **does not** change (preserves published URL) |
| **Edit** — manual slug edit | Auto-sync turns off; badge shows **Custom**; **Regenerate** appears |
| **Save** | Form `slug` value → `slug` database column |
| **Duplicate slug** | Form validation error (before database write) |

#### Slug generation without Spatie (technical)

```
Title (live) → debounce 400ms → Str::slug() → normalizeSlug() → slug field
```

No server requests. No model configuration required.

#### Four ways to add title + slug

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;

// 1) Recommended — one liner
TitleSlugField::make(),

// 2) Same layout, different import
SlugField::withTitle(),

// 3) Manual — full control (title embedded in slug field)
SlugField::make('slug')
    ->source('title')
    ->titleField(FlexTextInput::make('title')->required()),

// 4) Title elsewhere in the schema — slug syncs via ->source() (playground: slug__standalone)
FlexTextInput::make('title')->label('Title')->live(),
SlugField::make('slug')
    ->label('Slug')
    ->source('title')
    ->helperText('Auto-syncs from title until you edit or reset the slug.'),
```

#### When to switch to Spatie?

Only add Spatie when you need **model-level hooks** that the form alone cannot handle:

- automatic `-2`, `-3` suffixes on database collisions
- `preventOverwrite` — nigdy nie nadpisuj sluga po publikacji
- `skipGenerateWhen`, `extraScope`, wiele pól źródłowych
- using the same `SlugOptions` in form preview and on `save()`

Do tego: [Spatie `laravel-sluggable` integration](#spatie-laravel-sluggable-integration).

#### Common issues (without Spatie)

| Symptom | Cause | Fix |
|-------|-----------|-----|
| Slug does not save | Missing `slug` in `$fillable` | Add to `$fillable` |
| No URL preview under slug | `url_host` is `null` | Set `APP_URL` in `.env` or `-&gt;urlHost(...)` |
| Slug does not update from title | Manual edit disabled auto-sync | Click **Regenerate** |
| Validation: slug already exists | Duplicate in database | Change slug or delete the old record |
| `name` / `handle` fields instead of title/slug | Default field names | `fieldTitle:` / `fieldSlug:` lub config |

> **Next sections:** [Default form layout](#default-form-layout-fusedgroup) → [Installation](#installation-and-assets) → [Config](#package-configuration-configfilament-flex-fieldsphp) → [Full Example](#full-example-from-scratch-migration---model---resource) → [Spatie Integration](#spatie-laravel-sluggable-integration-v4x)

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

// With Spatie — identical layout, different preview generation logic
TitleSlugField::make(spatieModel: Post::class),
```

> **Important:** `spatieModel` changes **only** the slug preview generation logic (server + `SlugOptions`). It **does not change** the form layout.

#### Co jest wewnątrz `FusedGroup`

| # | Komponent | State path (domyślnie) | Widoczny? | Rola |
|---|-----------|------------------------|-----------|------|
| 1 | `FlexTextInput` | `title` | Tak | Pole tytułu, `live()`, auto-sync do sluga |
| 2 | `Hidden` | `slug_auto_update_disabled` | Nie | Flaga: użytkownik ręcznie edytował slug |
| 3 | `SlugField` | `slug` | Tak | Permalink, inline edit, akcje Copy/Visit/… |

The group has CSS class `fff-title-slug-fused-group` (without the standard Filament border between fields).

#### Domyślny wygląd (ASCII)

When `config('filament-flex-fields.slug.url_host')` is set (e.g. `APP_URL`):

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

When `url_host` is `null` (no URL preview in config):

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

#### Table of default visual values

| Element | Domyślna wartość | Skąd się bierze |
|---------|------------------|-----------------|
| Title field name | `title` | `config('filament-flex-fields.slug.field_title')` |
| Slug field name | `slug` | `config('filament-flex-fields.slug.field_slug')` |
| Label title | `"Title"` | `Str::headline($fieldTitle)` |
| Placeholder title | `"Title"` | j.w. |
| Label slug | ukryty | `slugLabel: null` → `hiddenLabel()` |
| Slug size | `md` (40px) | `config('filament-flex-fields.ui.slug_size')` |
| Slug variant | `primary` | `config('filament-flex-fields.ui.slug_variant')` |
| Permalink host | `APP_URL` lub `null` | `config('filament-flex-fields.slug.url_host')` |
| Etykiety przycisków | tekst + ikona | `config('filament-flex-fields.slug.action_button_labels')` |
| Ikony | Gravity UI | np. `gravityui-pencil`, `gravityui-copy` |
| Badge | Auto / Custom | Alpine — po ręcznej edycji sluga |

#### The same layout — three ways to call

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;

// A) Factory (recommended) — without Spatie
TitleSlugField::make(),

// B) Alias — identical FusedGroup
SlugField::withTitle(),

// C) More options via slugConfigurator (e.g. permalink)
TitleSlugField::make(
    slugConfigurator: fn (SlugField $slug) => $slug
        ->urlHost(config('app.url'))
        ->urlPath('/blog/'),
),
```

**Livewire test helper** — hidden auto-sync field name:

```php
TitleSlugField::autoUpdateDisabledFieldName('slug');    // slug_auto_update_disabled
TitleSlugField::autoUpdateDisabledFieldName('permalink'); // permalink_auto_update_disabled
```

---

### Instalacja i assety

Pakiet jest częścią `janczakb/filament-flex-fields`. **Ścieżka bez Spatie wymaga tylko assetów pakietu** — bez dodatkowych `composer require`.

```bash
# In the package / application directory
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
        // Default field names pól w TitleSlugField::make()
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

### Full Example from scratch (migration -> model -> Resource)

> Continuation of the [Start here — integration without Spatie](#start-here--integration-without-spatie-default) section. Steps 1–3 are the minimum; step 4 (Spatie) is **optional**.

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

#### 3. Filament Resource (without Spatie)

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

#### 4. (Optional) The same Resource with Spatie

Add this step **only** when you need suffixes, `preventOverwrite`, or other `SlugOptions` rules during save.

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

### Cookbook — typical scenarios

#### Scenario 1: Blog — create + edit (default behaviour)

```php
TitleSlugField::make(
    urlHost: config('app.url'),
    urlPath: '/posts/',
),
```

- **Create:** tytuł → slug na żywo.
- **Edit:** zmiana tytułu **nie** zmienia sluga.

#### Scenario 2: Always sync slug with title

```php
TitleSlugField::make(
    preserveSlugOnEdit: false,
    urlHost: config('app.url'),
),
```

#### Scenario 3: Slug read-only on edit

```php
TitleSlugField::make(
    slugConfigurator: fn (SlugField $field) => $field
        ->slugReadOnly(fn (SlugField $component): bool => $component->getOperation() === 'edit'),
),
```

#### Scenario 4: Custom title (RichEditor) + slug

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

#### Scenario 5: Slug uniqueness within tenant

```php
TitleSlugField::make(
    slugConfigurator: fn (SlugField $slug) => $slug
        ->slugUniqueModel(Post::class)
        ->slugUniqueScope(fn ($query) => $query->where('tenant_id', filament()->getTenant()->id)),
),
```

#### Scenario 6: No permalink — slug field only

```php
TitleSlugField::make(
    urlHost: null,
    slugConfigurator: fn (SlugField $slug) => $slug
        ->permalinkPreview(false)
        ->inlineEditing(false),
),
```

#### Scenario 7: CMS Homepage (`/`)

Standalone `SlugField` (matches playground **Homepage slug**):

```php
SlugField::make('slug')
    ->label('Homepage slug')
    ->allowHomepageSlug()
    ->urlHost('https://wyachts.test')
    ->slugPattern('/^(\/)?[a-z0-9]+(?:-[a-z0-9]+)*$/')
    ->helperText('Supports "/" as homepage slug.'),
```

Inside `TitleSlugField`:

```php
TitleSlugField::make(
    slugConfigurator: fn (SlugField $slug) => $slug
        ->allowHomepageSlug()
        ->slugPattern('/^(\/)?[a-z0-9]+(?:-[a-z0-9]+)*$/'),
),
```

#### Scenario 8: Repeater — row with title and slug

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

#### Scenario 9: Manual slug only (no title, no auto-generate)

Use when there is **no title field** — user types the slug by hand. This is **not** the same as playground `slug__standalone` (that name means “slug field alone in the layout”, but it still uses `->source('title')`).

```php
SlugField::make('slug')
    ->label('URL slug')
    ->autoGenerate(false)
    ->inlineEditing(false)
    ->urlHost(config('app.url'))
    ->urlPath('/posts/')
    ->required(),
```

#### Scenario 10: Form read-only (whole field)

Matches playground **Form readonly**:

```php
SlugField::make('slug')
    ->label('Form readonly')
    ->urlHost('https://wyachts.test')
    ->urlPath('/docs/')
    ->readOnly(),
```

#### Scenario 11: Spatie + multiple source fields (optional package)

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


### Quick Start — Filament Resource (create + edit)

Summary of the [Start here](#start-here--integration-without-spatie-default) section — **without Spatie**:

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

Application-side requirements: slug column in migration + slug in model's $fillable. Nothing more.

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

> **Universal locale tabs:** For any translatable attribute (title, body, metadata, …), use the dedicated [TranslatableFields](/docs/translatablefields) component. The section below covers **TitleSlugField** only — translatable **titles** with a **single shared slug**.

---

### Translatable titles (single slug)

> For generic translatable fields (body, excerpt, metadata, …) without slug coupling, use [TranslatableFields](/docs/translatablefields) instead.

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
            'pl' => 'Title in Polish',
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
| `getTitleLocales()` | Resolved `locale =&gt; label` map |
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

> **Not required.** If the [default path without Spatie](#start-here--integration-without-spatie-default) is sufficient, you can skip this section entirely.

Tested with **`spatie/laravel-sluggable` ^4.0** (currently 4.0.2). The integration uses official Spatie v4 classes:
- `Spatie\Sluggable\Actions\GenerateSlugAction` (via `config/sluggable.php` → `actions.generate_slug`)
- `Spatie\Sluggable\Support\SluggableAttributeResolver` dla modeli z `#[Sluggable]` bez `getSlugOptions()`
- `Spatie\Sluggable\Support\Config::getAction()` — ten sam resolver akcji co trait `HasSlug`

Spatie adds a second layer: saving the slug with model options (suffixes, scope, `preventOverwrite`). The form can display the same preview as the model — just pass `spatieModel`.

#### Optional dependency isolation (technical)

| Warstwa | Import `Spatie\…`? | Bez `composer require spatie/laravel-sluggable` |
|---------|-------------------|--------------------------------------------------|
| `SlugField`, `TitleSlugField` | **No** | Works 100% (`SlugGenerator`, permalink, unique, inline edit) |
| Traits `GeneratesSlugFromSource`, `ConfiguresSlugPermalink` | **No** — only `SpatieSlugIntegration::isAvailable()` | Guard before each Spatie call |
| `Support/Slug/SpatieSlugIntegration.php` | **Yes** — the only slug bridge | Loaded safely; `isAvailable()` returns `false`, falls back to `SlugGenerator` |

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

Spatie in the form is enabled only when:
- podasz `spatieModel`, **i**
- model ma `getSlugOptions()` **lub** atrybut `#[Sluggable]` z rozpoznawalnymi opcjami.

Simply having `Post` as the Resource model does not enable Spatie automatically — the slug configuration must exist on the model.

```php
// Form layout: IDENTICAL FusedGroup in both cases
TitleSlugField::make(),

// Jawne wskazanie modelu Spatie (zalecane gdy Resource nie binduje modelu)
TitleSlugField::make(spatieModel: Post::class),
```

**What changes after adding `spatieModel`:**

| Aspekt | Bez Spatie | Ze `spatieModel` |
|--------|------------|------------------|
| UI Layout (`FusedGroup`) | Same | Same |
| Preview generation | `Str::slug` in JS or `SlugGenerator` | `GenerateSlugAction` + `SlugOptions` |
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
| `generateSlugsFrom(fn ($model) =&gt; ...)` | Yes — Closure receives hydrated model |
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

The form must have the fields used in `extraScope` / `skipGenerateWhen` (e.g., `tenant_id`, `status`) filled out — `SlugField` reads them from the live form state (`data.*`), not just the source fields.

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

#### `skipGenerateWhen` — preview without overwriting

```php
public function getSlugOptions(): SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('title')
        ->saveSlugsTo('slug')
        ->skipGenerateWhen(fn (): bool => $this->status === 'published');
}
```

In the form preview, when `skipGenerateWhen` returns `true`, the field will keep the existing slug instead of generating a new one.

#### `startSlugSuffixFrom` and collisions in preview

```php
SlugOptions::create()
    ->generateSlugsFrom('title')
    ->saveSlugsTo('slug')
    ->startSlugSuffixFrom(5);
```

If `my-post` already exists in the database, the preview may show `my-post-5` (according to Spatie rules).

#### Closure as slug source

```php
SlugOptions::create()
    ->generateSlugsFrom(fn (Post $post): string => "{$post->title}-{$post->edition}")
    ->saveSlugsTo('slug');
```

The form must have the `title` and `edition` fields filled out — `SlugField` reads them from the live state of siblings in the schema.

#### `usingSuffixGenerator` — custom suffix

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
| `$titleFieldWrapper` | `?Closure` | `null` | Wrap title field: `fn ($field) =&gt; $field-&gt;columnSpan(2)` |
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

**Example of `titleUniqueParameters` — unique title within tenant:**

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
| `$requiredTitleLocales` | `'all'\|list&lt;string&gt;\|Closure\|null` | `config('…slug.required_title_locales')` or slug source locale only | Which title tabs are required (`null` = source locale only) |
| `$spatieTranslatable` | `bool\|Closure` | `false` | Marks Spatie Translatable intent; hydrate auto-detects `HasTranslations` when package is present |
| `$titleLocaleConfigurator` | `?Closure` | `null` | `fn (FlexTextInput $field, string $locale) =&gt; $field` |
| `$translatableFieldsConfigurator` | `?Closure` | `null` | `fn (TranslatableFields $fields) =&gt; $fields-&gt;…` — full title tabs config |
| `$spatieModel` | `string\|Closure\|null` | `null` | **Spatie Sluggable only** (`HasSlug`) — not Translatable |
| `$slugConfigurator` | `?Closure` | `null` | `fn (SlugField $field) =&gt; $field-&gt;...` |

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

#### Example for each parameter of `TitleSlugField::make()`

```php
TitleSlugField::make(
    // --- field names ---
    fieldTitle: 'title',
    fieldSlug: 'slug',

    // --- custom title field instead of FlexTextInput ---
    titleField: null,

    // --- title wrapper (e.g. columnSpan) ---
    titleFieldWrapper: fn ($field) => $field->columnSpanFull(),

    // --- hooks ---
    titleAfterStateUpdated: function ($state, Filament\Schemas\Components\Utilities\Set $set): void {
        // after title change (after auto-slug logic)
    },
    slugAfterStateUpdated: function ($state): void {
        // after slug change (e.g. custom validation)
    },

    // --- validation ---
    titleRules: ['required', 'string', 'min:3'],
    slugRules: ['required', 'string', 'max:255'],

    // --- title UX ---
    titleAutofocus: true,
    titleReadOnly: false,
    titleLabel: 'Post title',
    titlePlaceholder: 'Enter a descriptive title',
    titleExtraInputAttributes: ['data-test' => 'post-title'],

    // --- slug UX ---
    slugReadOnly: false,
    slugLabel: null, // null = ukryta etykieta

    // --- uniqueness ---
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

    // --- behaviour ---
    preserveSlugOnEdit: true,

    // --- Spatie (does not change UI!) ---
    spatieModel: Post::class,

    // --- any SlugField configuration ---
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

Locale map (`['pl' =&gt; 'PL', 'en' =&gt; 'EN']`) or list (`['pl', 'en']`). Implies `translatableTitle(true)`.

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

Blocks editing of the respective field. Works with `TitleSlugField` and manual `SlugField::withTitle()`.

```php
// Title read-onlytu
TitleSlugField::make(titleReadOnly: true),

// Slug read-only
TitleSlugField::make(slugReadOnly: true),

// Slug read-only on edit only (recommended for published URLs)
TitleSlugField::make(
    slugConfigurator: fn (SlugField $f) => $f
        ->slugReadOnly(fn (SlugField $c): bool => $c->getOperation() === 'edit'),
),
```

#### `slugifyUsing(?Closure $callback)`

Custom slugifier; receives `['source' =&gt; string]`.

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

Controls which URL segments are visible in the permalink preview.

```php
// Path only (no host) — e.g. in the admin panel
SlugField::make('slug')
    ->urlHost('https://example.com')
    ->urlPath('/blog/')
    ->urlHostVisible(false),

// Hide path prefix — show only host + slug
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

Target of the **Visit** button. The Closure receives injected parameters: `slug`, `routeKey` (self-healing: `{slug}-{id}`), and optionally the `record`.

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

Action toggles below the slug. By default all are `true` (except Regenerate — visible only after manual slug edit).

```php
SlugField::make('slug')
    ->showVisitLink(false)        // ukryj "Visit"
    ->showCopyButton(true)       // zostaw "Copy"
    ->showRegenerateButton(true), // show "Regenerate" when applicable
```

Example — Copy only, no Visit:

```php
TitleSlugField::make(
    showVisitLink: false,
    slugConfigurator: fn (SlugField $s) => $s->showRegenerateButton(false),
),
```

#### `actionButtonLabels(bool|Closure)` / `actionButtonsIconOnly(bool|Closure)`

Kontrola tekstu na przyciskach akcji (Hero UI button-group + ikony Gravity).

```php
// Text + icon (default)
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

Main toggle for auto-generating slug from the source field.

```php
// Completely manual slug (e.g. CSV import)
SlugField::make('slug')
    ->autoGenerate(false)
    ->inlineEditing(false),
```

#### `preserveSlugOnEdit(bool|Closure $condition = true)`

On the `edit` operation, it stops auto-sync from the title (protects published URL). On `create`, it always syncs.

```php
// Default — do not overwrite slug on title edit
TitleSlugField::make(), // preserveSlugOnEdit: true

// Zawsze synchronizuj (jak na create)
TitleSlugField::make(preserveSlugOnEdit: false),

SlugField::make('slug')->preserveSlugOnEdit(false),
```

#### `inlineEditing(bool|Closure $condition = true)`

When `true` (default): permalink preview + Edit/OK/Cancel/Reset buttons. When `false`: standard `TextInput`.

```php
// Prosty input bez trybu inline (np. w Repeaterze)
SlugField::make('slug')
    ->inlineEditing(false)
    ->autoGenerate(false),
```

#### `allowHomepageSlug(bool|Closure $condition = true)`

Allows homepage slug `/` (CMS homepage). Requires custom validation pattern.

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

Additional validation rules (besides built-in pattern and `slugUnique`).

```php
SlugField::make('slug')->slugRules(['min:3', 'max:100']),

TitleSlugField::make(
    slugRules: ['required', 'string', 'regex:/^[a-z0-9-]+$/'],
),
```

#### `slugUnique(bool|Closure $condition = true)` / `slugUniqueParameters(array $parameters)` / `slugUniqueScope(?Closure $scope)` / `slugUniqueModel(string|Closure|null $model)`

Uniqueness validation **in the form** (independent of Spatie suffixes on save).

```php
// Disable uniqueness check
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

Size and visual variant of the field wrapper (Hero UI). Defaults from `config('filament-flex-fields.ui')`.

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

### Inherited Filament `Field` API

`SlugField` inherits standard Filament methods — they work exactly the same as in other fields:

```php
SlugField::make('slug')
    ->label('Adres URL')
    ->helperText('Lowercase letters, numbers, and hyphens only.')
    ->hint('Automatically generated from title')
    ->required()
    ->disabled(fn (): bool => auth()->user()?->cannot('edit-slug'))
    ->hidden(fn (): bool => ! auth()->check())
    ->columnSpanFull()
    ->columnSpan(2)
    ->live()
    ->dehydrated(true),
```

`TitleSlugField` configures title and slug separately — supply slug helper text via the `slugConfigurator`:

```php
TitleSlugField::make(
    slugConfigurator: fn (SlugField $slug) => $slug
        ->helperText('This address will be visible in the URL.'),
),
```

---

### Public helper methods (views, tests, extensions)

Public methods used in Blade templates, tests, and custom field extensions:

| Method | Returns | When to use |
|--------|---------|------------|
| `getAlpineConfiguration()` | `array` | Debug / custom Blade |
| `getUiLabels()` | `array` | UI translations in tests |
| `getSourceStatePath()` | `?string` | Livewire path to source field |
| `getOperation()` | `string` | `create` lub `edit` |
| `generateSlugFromSource(string $source)` | `string` | Server-side slug generation logic |
| `generateSlugPreview(string $source)` | `string` | Livewire component action endpoint |
| `normalizeSlug(string $value)` | `string` | Slug normalization helper before database save |
| `getFullPermalinkUrl(?string $slug)` | `?string` | Full URL for Copy/Visit actions |
| `getDisplayUrlHost()` | `?string` | Domain host without protocol |
| `usesSpatieIntegration()` | `bool` | Whether Spatie sluggable options are resolved |
| `getSpatieModelClass()` | `?string` | Resolved FQCN model class |
| `shouldUseServerSideGeneration()` | `bool` | Whether Alpine triggers Livewire preview requests |
| `getWrapperClasses()` | `list&lt;string&gt;` | Component CSS wrapper classes |

**Example in test:**

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

**Regenerate** only appears when auto-sync has been disabled by a manual slug edit (setting the hidden field `{slug}_auto_update_disabled = true`). During normal syncing, the button is hidden.

```php
// In a Livewire test — simulate manual edit:
Livewire::test(EditPost::class, ['record' => $post])
    ->set('data.slug_auto_update_disabled', true)
    ->set('data.title', 'New title'); // slug will NOT change without calling Regenerate
```

> **Note:** `SlugField` uses `wire:ignore` on the Alpine fragment — changing `data.slug` directly in tests might not reflect UI behavior. Test by updating the title or modifying `slug_auto_update_disabled`.

### Playground (Live Preview)

Enable the playground in `.env`:

```env
FLEX_FIELDS_PLAYGROUND=true
```

Panel navigation: **Settings & Tools → Flex Fields Playground** — cluster with left sub-navigation (Filament `SubNavigationPosition::Start`).

- Root URL: `/admin/flex-fields-playground` (redirects to first component)
- **Slug field page:** `/admin/flex-fields-playground/slug-field`
- Routes are registered **only** when `FLEX_FIELDS_PLAYGROUND=true` (or `filament-flex-fields.playground.enabled` is `true`).

> **Spatie is optional for every recipe below.** All playground demos work with browser `Str::slug()` only. To align preview and save with `laravel-sluggable`, see [Optional Spatie upgrade (playground recipes)](#optional-spatie-upgrade-playground-recipes) at the end of this section.

Source of truth: `SlugFieldPlayground` in the package (`src/Support/Playground/SlugFieldPlayground.php`). Default form state keys (`slug__title`, `slug__standalone`, …) live in `SlugFieldPlayground::defaultState()`.

#### Playground recipes — 1:1 with `SlugFieldPlayground`

Full section schema (copy-paste ready):

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Slug field')
    ->description('Permalink editor with inline Edit/OK/Cancel/Reset, auto-sync, unique validation hooks, Spatie Sluggable integration and FlexFields styling.')
    ->schema([
        // … recipes 1–9 below …
    ]);
```

---

##### Recipe 1 — Shared title source + slug field (`slug__standalone`)

A **separate** title field drives one or more slug fields on the same form. Playground reuses `slug__title` for recipes 1, 5, and 6.

```php
FlexTextInput::make('slug__title') // or 'title' in your app
    ->label('Title (source)')
    ->live()
    ->columnSpanFull(),

SlugField::make('slug__standalone') // or SlugField::make('slug')
    ->label('Slug')
    ->source('slug__title') // or ->source('title')
    ->helperText('Auto-syncs from title until you edit or reset the slug.')
    ->columnSpanFull(),
```

| | |
|---|---|
| **Demonstrates** | `SlugField` + `->source()` pointing at a sibling field (not embedded `titleField()`) |
| **Cookbook** | [Four ways to add title + slug](#four-ways-to-add-title--slug) — pattern **4** |
| **Spatie** | Optional — `->spatieModel(Post::class)` on `SlugField` |

---

##### Recipe 2 — Title + slug one-liner (`TitleSlugField`)

```php
TitleSlugField::make(
    fieldTitle: 'slug__one_liner_title',
    fieldSlug: 'slug__one_liner_slug',
    urlHost: 'https://wyachts.test',
    urlPath: '/posts/',
)
    ->label('Title + slug (one-liner)')
    ->columnSpanFull(),
```

| | |
|---|---|
| **Demonstrates** | `TitleSlugField` fused group, permalink bar, default create/edit behaviour |
| **Cookbook** | [Scenario 1](#scenario-1-blog--create--edit-default-behaviour) |
| **Spatie** | Optional — `TitleSlugField::make(..., spatieModel: Post::class)` |

---

##### Recipe 3 — Translatable title + single slug (`slug__i18n_*`)

```php
TitleSlugField::make(
    fieldTitle: 'slug__i18n_title',
    fieldSlug: 'slug__i18n_slug',
    translatableLocales: ['pl' => 'PL', 'en' => 'EN', 'fr' => 'FR'],
    slugSourceLocale: 'pl',
    urlHost: 'https://wyachts.test',
    urlPath: '/guides/',
)
    ->label('Translatable title + slug')
    ->helperText('Single slug generated from the Polish title tab. Other locales do not change the permalink.')
    ->columnSpanFull(),
```

Default state shape: `'slug__i18n_title' => ['pl' => '…', 'en' => '…']`, `'slug__i18n_slug' => 'przewodnik-po-morzu-srodziemnym'`.

| | |
|---|---|
| **Demonstrates** | `translatableLocales`, `slugSourceLocale`, `TranslatableFields` tabs |
| **Cookbook** | [Translatable titles (single slug)](#translatable-titles-single-slug) |
| **Spatie** | Optional — `spatieTranslatable: true` when using [Spatie Translatable](#storage-with-spatie-laravel-translatable-optional) |

---

##### Recipe 4 — Title + slug pair (`titleField()` + `recordSlug()`)

```php
SlugField::make('slug__pair_slug')
    ->label('Title + slug pair')
    ->titleField(
        FlexTextInput::make('slug__pair_title')
            ->label('Title')
            ->placeholder('Enter a post title…'),
    )
    ->urlHost('https://wyachts.test')
    ->urlPath('/blog/')
    ->recordSlug('premium-catamaran-experience')
    ->columnSpanFull(),
```

| | |
|---|---|
| **Demonstrates** | Embedded title via `->titleField()`, `->recordSlug()` for visit URL on edit |
| **Cookbook** | [Four ways](#four-ways-to-add-title--slug) — pattern **3** |
| **Spatie** | Optional — `->spatieModel(Post::class)` |

---

##### Recipe 5 — Permalink preview + Visit link (`slug__permalink`)

Uses the shared title field from recipe 1 (`slug__title`).

```php
SlugField::make('slug__permalink')
    ->label('Permalink preview')
    ->source('slug__title')
    ->urlHost('https://wyachts.test')
    ->urlPath('/charters/')
    ->visitRoute(fn (?string $slug): ?string => filled($slug) ? "https://wyachts.test/charters/{$slug}" : null)
    ->generationDebounce(250)
    ->columnSpanFull(),
```

| | |
|---|---|
| **Demonstrates** | `urlHost`, `urlPath`, `visitRoute`, `generationDebounce` |
| **Cookbook** | [Permalink preview & URL actions](#permalink-preview--url-actions) |
| **Spatie** | Optional — visit URL still works; preview uses Spatie rules when `->spatieModel()` is set |

---

##### Recipe 6 — URL slug sandwich (`slug__sandwich`)

Uses the shared title field from recipe 1 (`slug__title`).

```php
SlugField::make('slug__sandwich')
    ->label('URL slug sandwich')
    ->source('slug__title')
    ->urlHost('https://wyachts.test')
    ->urlPath('/books/')
    ->slugLabelPostfix('/detail/')
    ->visitRoute(fn (?string $slug): ?string => filled($slug) ? "https://wyachts.test/books/{$slug}/detail" : null)
    ->columnSpanFull(),
```

Preview: `wyachts.test/books/my-slug/detail/`

| | |
|---|---|
| **Demonstrates** | `slugLabelPostfix`, complex `visitRoute` |
| **Cookbook** | [Sandwich URL](#sandwich-url-prefix--slug--postfix) |
| **Spatie** | Optional |

---

##### Recipe 7 — Read-only variants (grid)

```php
Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
    ->schema([
        SlugField::make('slug__readonly')
            ->label('Form readonly')
            ->urlHost('https://wyachts.test')
            ->urlPath('/docs/')
            ->readOnly(),

        SlugField::make('slug__slug_readonly')
            ->label('Slug readonly')
            ->urlHost('https://wyachts.test')
            ->urlPath('/docs/')
            ->slugReadOnly(),

        SlugField::make('slug__homepage')
            ->label('Homepage slug')
            ->allowHomepageSlug()
            ->urlHost('https://wyachts.test')
            ->slugPattern('/^(\/)?[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->helperText('Supports "/" as homepage slug.'),
    ]),
```

| Demo | Method | Cookbook |
|------|--------|----------|
| Form readonly | `readOnly()` | [Scenario 10](#scenario-10-form-read-only-whole-field) |
| Slug readonly | `slugReadOnly()` | [Scenario 3](#scenario-3-slug-read-only-on-edit) |
| Homepage `/` | `allowHomepageSlug()` + `slugPattern()` | [Scenario 7](#scenario-7-cms-homepage-) |

**Spatie:** optional for all three.

---

#### Optional Spatie upgrade (playground recipes)

None of the playground recipes require `composer require spatie/laravel-sluggable`. Add it only when you need model-level suffixes (`-2`), `preventOverwrite`, `extraScope`, or identical rules on save and in the form preview.

**`TitleSlugField` recipes (2, 3):**

```php
TitleSlugField::make(
    fieldTitle: 'title',
    fieldSlug: 'slug',
    urlHost: config('app.url'),
    urlPath: '/posts/',
    spatieModel: Post::class, // optional
),
```

**`SlugField` recipes (1, 4–7):**

```php
SlugField::make('slug')
    ->source('title')
    ->spatieModel(Post::class), // optional
```

Full integration: [Spatie `laravel-sluggable` integration](#spatie-laravel-sluggable-integration-v4x) (install, `HasSlug`, `getSlugOptions()`, `#[Sluggable]`, translatable models).

#### Playground quick reference

| Playground label | State key(s) | Primary API |
|------------------|--------------|-------------|
| Title (source) | `slug__title` | `FlexTextInput` + `live()` |
| Slug | `slug__standalone` | `SlugField` + `->source('slug__title')` |
| Title + slug (one-liner) | `slug__one_liner_*` | `TitleSlugField::make(...)` |
| Translatable title + slug | `slug__i18n_*` | `translatableLocales` + `slugSourceLocale` |
| Title + slug pair | `slug__pair_*` | `->titleField()` + `->recordSlug()` |
| Permalink preview | `slug__permalink` | `visitRoute` + `generationDebounce` |
| URL slug sandwich | `slug__sandwich` | `slugLabelPostfix` + `visitRoute` |
| Form readonly | `slug__readonly` | `readOnly()` |
| Slug readonly | `slug__slug_readonly` | `slugReadOnly()` |
| Homepage slug | `slug__homepage` | `allowHomepageSlug()` |

```php
// Verify playground registration in package tests
$builder = app(\Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder::class);
expect($builder->components())->not->toBeEmpty();
```

#### Translations

Publish translation files:

```bash
php artisan vendor:publish --tag=filament-flex-fields-translations
```

Override in `lang/vendor/filament-flex-fields/{locale}/default.php`.

**UI Keys (`slug.*`):**

| Key | EN (Default) | PL |
|-----|--------------|-----|
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

**Validation (`validation.slug.*`):**

| Key | Description |
|-----|-------------|
| `validation.slug.invalid` | General slug validation error message |
| `validation.slug.pattern` | Validation error message when slug pattern mismatch |

**Custom labels without publishing — override directly on the field:**

```php
SlugField::make('slug')
    ->placeholder('e.g. my-article')
    ->permalinkLabel('Public link')
    ->visitLinkLabel('View post'),
```

Set your application locale (`app.locale` = `pl`) to load built-in translations if desired.

---

### Troubleshooting

| Problem | Likely Cause | Solution |
|---------|--------------|----------|
| Slug does not update from title | Manual edit disabled auto-sync | Click **Regenerate** or set `{slug}_auto_update_disabled` to `false` |
| Slug changes on edit but shouldn't | `preserveSlugOnEdit(false)` | Use default `TitleSlugField::make()` or set `preserveSlugOnEdit(true)` |
| Preview mismatch with saved slug (no Spatie) | Database duplicate | The form unique validation blocks save — change the slug |
| Preview suffix -2 (Spatie) | Database duplicate resolved by Spatie | Expected behavior — preview uses same model configuration as Spatie `HasSlug` |
| Spatie not working | Missing package dependency | `composer require spatie/laravel-sluggable` |
| Spatie not working | Model missing `getSlugOptions()` or Sluggable attribute | Add slug configuration to model or pass class to `spatieModel(Post::class)` |
| `usesSpatieIntegration()` returns false | Form model doesn't match class with SlugOptions | Pass model class explicitly: `spatieModel(Post::class)` |
| Missing permalink bar | `url_host` is null | Set `APP_URL` or `-&gt;urlHost(config('app.url'))` |
| Permalink truncates host (`...`) | UX edit mode limits space | Normal behavior — full URL is copied to clipboard and visited |
| Unique validation fails incorrectly | Missing tenant scope (multi-tenant) | `-&gt;slugUniqueScope(fn ($q) =&gt; $q-&gt;where('tenant_id', ...))` |
| Unique doesn't work on mount | Model record not loaded yet | Package defers validation — ensure the resource binds the record |
| `slugifyUsing()` not working | Closure doesn't return string or missing source state | `slugifyUsing()` **always takes precedence** over Spatie generator |
| Test `-&gt;set('data.slug')` fails | `wire:ignore` + Alpine | Change `data.title` or set `slug_auto_update_disabled` flag |
| `Target [Model] is not instantiable` | Closure maps to model type hint on mount | Use `mixed $record` or remove type hint in closure parameters |
| Regenerate button is hidden | Auto-sync is still active | Manually edit the slug first to show the button (or set update flag) |
| Homepage `/` slug rejected | Default pattern without allowHomepage | Call `-&gt;allowHomepageSlug()` (updates default pattern regex) or custom pattern |
| Repeater rows do not sync | Incorrect source field path | `-&gt;source('title')` in the same schema row resolves relative paths automatically |

**Diagnostics in tinker / test:**

```php
$field = SlugField::make('slug')->spatieModel(Post::class);

$field->usesSpatieIntegration();      // true/false
$field->shouldUseServerSideGeneration(); // true gdy Spatie, translatable titles lub serverSideGeneration()
$field->getSourceStatePath();         // np. "data.title"
$field->generateSlugFromSource('Hello World'); // PHP preview
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

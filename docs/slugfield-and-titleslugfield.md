# SlugField & TitleSlugField

[← Powrót do spisu treści](../README.md)


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

> **Universal locale tabs:** For any translatable attribute (title, body, metadata, …), use the dedicated [TranslatableFields](translatablefields.md) component. The section below covers **TitleSlugField** only — translatable **titles** with a **single shared slug**.

---

### Translatable titles (single slug)

> For generic translatable fields (body, excerpt, metadata, …) without slug coupling, use [TranslatableFields](translatablefields.md) instead.

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

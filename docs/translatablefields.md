# TranslatableFields

[← Powrót do spisu treści](../README.md)


### Summary

Locale-aware **schema layout** built on **SegmentTabs** (iOS-style segmented tabs, same visual language as [SegmentControl](segmentcontrol.md)). Clones one or more field templates into per-locale tabs with automatic state paths, JSON hydration, and optional Spatie `laravel-translatable` support.

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

> **Not the same as TitleSlugField translations.** [TitleSlugField](slugfield-and-titleslugfield.md#translatable-titles-single-slug) provides a narrower use case: translatable **titles** with a **single shared slug** and slug-source-locale sync. Use `TranslatableFields` for any generic translatable attribute (body, excerpt, metadata, …).

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

```php
TranslatableFields::make('field_name')
    ->schema([
        // ... schema components
    ]);
```
#### `locales(array|Closure $locales)`


Locale codes as a list (`['ar', 'en']`) or map (`['ar' => 'Arabic', 'en' => 'English']`).

```php
TranslatableFields::make('field_name')
    ->locales(['pl', 'en', 'de']);
```
#### `localesLabels(array|Closure $localeLabels)`


Labels keyed by locale code. Used when `locales()` is a plain list.

```php
TranslatableFields::make('field_name')
    ->localesLabels([
        'pl' => 'Polski',
        'en' => 'English',
        'de' => 'Deutsch',
    ]);
```
#### `spatieTranslatable(bool|Closure $condition = true)`


Marks fields for Spatie-aware dehydration and documents intent. Hydration auto-detects `HasTranslations` on the record when the package is installed.

```php
TranslatableFields::make('field_name')
    ->spatieTranslatable(true);
```
#### `localeFieldUsing(Closure $callback)` / `storageAttributeUsing(Closure $callback)`


See [Advanced customization](#advanced-customization).

```php
TranslatableFields::make('field_name')
    ->localeFieldUsing()
    ->storageAttributeUsing();
```
#### `modifyTabsUsing(Closure $closure, bool $merge = true)` / `modifyFieldsUsing(Closure $closure, bool $merge = true)`


See [Advanced customization](#advanced-customization).

```php
TranslatableFields::make('field_name')
    ->modifyTabsUsing(true)
    ->modifyFieldsUsing(true);
```
#### `directionByLocale()` / `emptyBadgeWhenAllFieldsAreEmpty(?string $emptyLabel = null)` / `activeTabWithValue()` / `withRecommendedDefaults(?string $emptyBadgeLabel = null)` / `borderedPanels(bool $condition = true)`


See [Production presets](#production-presets).

```php
TranslatableFields::make('field_name')
    ->directionByLocale()
    ->emptyBadgeWhenAllFieldsAreEmpty('Brak danych')
    ->activeTabWithValue()
    ->withRecommendedDefaults('Brak danych')
    ->borderedPanels(true);
```

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
| `size(string\|ControlSize\|Closure $size)` | See [Control size](shared-concepts.md). |

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

For translatable titles with permalink generation, see [Translatable titles (single slug)](slugfield-and-titleslugfield.md#translatable-titles-single-slug).

### Playground

The dev playground includes single-field and multi-field variants with RTL, empty badges, and active-tab selection. Enable playground mode in config and open the Flex Fields playground page.

---

# Part III — Appendix

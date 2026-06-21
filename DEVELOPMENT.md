# Filament Flex Fields — instrukcja dla deweloperów pakietu

Ten dokument opisuje **jak pisać kod w tym pluginie**: ładowanie CSS/JS, współdzielenie stylów między komponentami, unikanie duplikacji i pełny przepływ assetów. Po przeczytaniu powinieneś wiedzieć *dlaczego* coś się ładuje, *kiedy* i *gdzie* to rejestrować.

Dokumentacja użytkownika końcowego jest w `docs/`. Ten plik jest **wyłącznie dla osób rozwijających pakiet**.

**Gdzie szukać poza tym plikiem:**
- API pól i przykłady użycia → `docs/` (np. `shared-concepts.md`)
- Overlay coordinator, `wire:ignore`, chunk inventory → `docs/shared-concepts.md`
- Pełna lista komponentów i metod → `COMPONENTS.md`
- Build, PHPStan, E2E, budżety bundle → `README.md` → Development

---

## Spis treści

1. [Architektura w skrócie](#1-architektura-w-skrocie)
2. [System designu: rozmiary i warianty wizualne](#2-system-designu-rozmiary-i-warianty-wizualne)
3. [Warstwy CSS](#3-warstwy-css)
4. [FlexFieldStylesheetQueue — serce lazy CSS](#4-flexfieldstylesheetqueue--serce-lazy-css)
5. [Dwa sposoby rejestracji CSS](#5-dwa-sposoby-rejestracji-css)
6. [Współdzielenie CSS między komponentami](#6-wspoldzielenie-css-miedzy-komponentami)
7. [emit-assets i data-fff-asset-batch](#7-emit-assets-i-data-fff-asset-batch)
8. [Asset injector (SPA, modale, Livewire morph)](#8-asset-injector-spa-modale-livewire-morph)
9. [JavaScript i FlexFieldAlpineQueue](#9-javascript-i-flexfieldalpinequeue)
10. [Playground — bundle per slug](#10-playground--bundle-per-slug)
11. [Czego NIE robić (antywzorce)](#11-czego-nie-robic-antywzorce)
12. [Checklist: nowy komponent formularza](#12-checklist-nowy-komponent-formularza)
13. [Checklist: nowa kolumna tabeli](#13-checklist-nowa-kolumna-tabeli)
14. [Build i testy](#14-build-i-testy)
15. [Szybka ściągawka](#15-szybka-sciagawka)
16. [Systemy poza assetami](#16-systemy-poza-assetami)
17. [FlexFieldFormBuilder](#17-flexfieldformbuilder)
18. [Filament v5 — hooki i assety specjalne](#18-filament-v5--hooki-i-assety-specjalne)
19. [Serwery, bezpieczeństwo, wydajność](#19-serwery-bezpieczenstwo-wydajnosc)

---

## 1. Architektura w skrócie

```
┌─────────────────────────────────────────────────────────────────┐
│  Filament panel (full page / Livewire partial / modal morph)   │
└────────────────────────────┬────────────────────────────────────┘
                             │
         ┌───────────────────┼───────────────────┐
         ▼                   ▼                   ▼
   core.css (zawsze)   lazy {component}.css   Alpine chunks (ESM)
         │                   │                   │
         │            FlexFieldStylesheetQueue   FlexFieldAlpineQueue
         │            (scoped, per request)      (scoped, per request)
         │                   │                   │
         └───────────────────┴───────────────────┘
                             │
                    emit-assets.blade.php
                    (push <head> lub inline <link>)
                             │
                    flex-field-asset-injector.js
                    (deduplikacja, modale, SPA navigate)
```

**Zasada nr 1:** każdy komponent ładuje **tylko to, czego potrzebuje**. Wspólne fragmenty są **osobnymi plikami CSS/JS**, podpinanymi przez graf zależności — nigdy kopiowane w całości do wielu bundle'i.

**Zasada nr 2:** rejestracja assetów jest **request-scoped** (`app()->scoped()`). W jednym żądaniu pięć pól `PhoneField` rejestruje `phone-field` raz; drugie `enqueueFor('phone-field')` zwraca pustą tablicę.

**Zasada nr 3:** interaktywne kontrolki używają **wspólnej skali rozmiaru** (`sm` / `md` / `lg`) i **z góry zdefiniowanych wariantów wizualnych**. Nowy komponent musi się do nich dopasować — nie wymyślaj piątego rozmiaru ani własnej palety obramowań.

**Zasada nr 4:** kolory tekstu, tła, obramowań i stanów focus bierz z tokenów `--fff-*` w `resources/css/base.css` (lub z dziedziczonych tokenów shella, np. `--fff-flex-text-input-text`). Nie używaj hardcodowanych hex/rgb ani klas Tailwind `text-gray-*` / `dark:text-*` — Filament przełącza dark mode klasą `.dark` na rodzicu, więc warianty `dark:` często **nie działają** w panelu i playground.

---

## 2. System designu: rozmiary i warianty wizualne

Plugin ma **jeden design system** (`--fff-*` w `resources/css/base.css`). Rozmiary i warianty są zdefiniowane w kodzie PHP i CSS — każdy nowy komponent musi je **respektować**, a nie duplikować własne magiczne piksele.

### Rozmiary — `sm`, `md`, `lg`

Enum: `src/Enums/ControlSize.php`  
Trait: `src/Concerns/HasControlSize.php` → metoda `size()` / `getSize()`

| Wartość | Enum | Wysokość tracka (`--fff-track-h-*`) | Typowe użycie |
|---------|------|-------------------------------------|---------------|
| `sm` | `ControlSize::Sm` | **32px** (`2rem`) | Gęste tabele, inline edycja, drugorzędne pola |
| `md` | `ControlSize::Md` | **40px** (`2.5rem`) | **Domyślny** — większość formularzy |
| `lg` | `ControlSize::Lg` | **48px** (`3rem`) | Pola hero, mobile-first, wyraźne CTA |

Tokeny w `base.css` (używaj ich w CSS, nie hardcoduj `height: 40px`):

```css
--fff-track-h-sm / --fff-track-h-md / --fff-track-h-lg   /* zewnętrzna wysokość shell */
--fff-control-h-sm / --fff-control-h-md / --fff-control-h-lg   /* wewnętrzne kontrolki (przyciski, ikony w tracku) */
--fff-text-sm / --fff-text-md / --fff-text-lg
--fff-icon-sm / --fff-icon-md / --fff-icon-lg
```

**Kto implementuje `size()`:** m.in. `FlexTextInput`, `FlexTextareaField`, `PhoneField`, `TagsField`, `SelectField`, `IconPickerField`, `SwitchField`, `SegmentControl`, `ScheduleField`, `IconColumn` (`iconSize()`), `RatingColumn` (`ratingSize()`).

**Wzorzec w PHP** — klasy na root + rozmiar:

```php
// getWrapperClasses() — przykład TagsField
'fff-tags-field--'.$this->getSize(),
'fff-flex-text-input--'.$this->getSize(),
```

**Wzorzec w CSS** — modyfikatory ustawiają zmienne, nie pojedyncze reguły:

```css
.fff-flex-text-input--sm { --fff-flex-text-input-h: var(--fff-track-h-sm); ... }
.fff-flex-text-input--md { --fff-flex-text-input-h: var(--fff-track-h-md); ... }
.fff-flex-text-input--lg { --fff-flex-text-input-h: var(--fff-track-h-lg); ... }
```

Jeśli komponent **wygląda jak input**, dodaj klasy `fff-flex-text-input--{size}` obok własnego prefiksu — wtedy dziedziczy wysokość i typografię bez kopiowania reguł.

### Warianty wizualne — rodziny shelli

Nie ma jednej globalnej listy dla wszystkich komponentów — są **dwie główne rodziny**, z których każda ma własne dozwolone wartości w `getVariant()` (walidacja `in_array` + `InvalidArgumentException`).

#### Rodzina A: pola oparte na `flex-text-input` (pill / track)

Używana przez: `FlexTextInput`, `FlexTextareaField`, `PhoneField`, `CountryField`, `CurrencyField`, `TimezoneField`, `TagsField`, `LinkPreviewField`, `BarcodeScannerField`, `ScheduleField`, `FlexTimeSegmentsField`, pola date/time, i inne z klasą `fff-flex-text-input`.

| Wariant | Znaczenie wizualne | CSS (tokeny) |
|---------|-------------------|--------------|
| `primary` | Domyślny — białe tło, szara obwódka | `--fff-field-bg`, `--fff-field-border` |
| `secondary` | Szary surface, subtelny cień | `--fff-field-secondary-*` |
| `soft` | Miękkie tło, zaokrąglony radius, bez mocnej obwódki | `--fff-field-soft-*` |
| `flat` | Przezroczyste tło i border — „wpisane w layout” | `transparent` |

Niektóre pola rozszerzają rodzinę o `ghost` (np. `LinkPreviewField`, `SocialLinksField`, `BarcodeScannerField`) — sprawdź `getVariant()` w klasie bazowej.

**W PHP:**

```php
->variant('soft')
->size('lg')
```

**W blade / `getWrapperClasses()`:**

```php
'fff-flex-text-input--'.$this->getVariant(),
'fff-phone-field--'.$this->getSize(),  // opcjonalnie własny prefiks + size
```

**W CSS komponentu zależnego** — nadpisuj tylko to, co jest specyficzne dla pola, resztę daje `flex-text-input.css`:

```css
/* currency-field.css — przykład */
.fff-currency-field.fff-flex-text-input--soft .fff-currency-field__currency-trigger {
    /* tylko trigger waluty, nie cały shell */
}
```

#### Rodzina B: select-style (`SelectField`, `IconPickerField`)

Wspólny shell z `select-field.css` + często `teleported-menu.css`.

| Wariant | Opis |
|---------|------|
| `bordered` | Domyślny — obramowany trigger (legacy alias: `primary` → `bordered` w IconPicker) |
| `secondary` | Secondary surface |
| `soft` | Soft background |
| `flat` | Minimal chrome |
| `faded` | Przygaszony / lighter shell |
| `underlined` | Podkreślenie zamiast pełnej ramki |
| `item-card` | Tylko `SelectField` — karty w dropdown |

```php
SelectField::make('status')->variant('underlined')->size('sm');
IconPickerField::make('icon')->variant('faded');
```

Nowe pole **nie powinno** mieszać rodzin A i B na jednym root bez uzasadnienia — jeśli wygląda jak select (trigger + dropdown), dziedzicz warianty B; jeśli jak text track — warianty A.

#### Inne komponenty (własna skala, ten sam duch)

| Komponent | Rozmiary | Warianty / uwagi |
|-----------|----------|------------------|
| `SwitchField` | `sm` / `md` / `lg` | `primary`, `secondary`, `soft` |
| `SegmentControl` | `sm` / `md` / `lg` | `default`, `ghost`, `pills` |
| `ChoiceCards` | `sm` / `md` / `lg` | layout kart, nie `flex-text-input` |
| `RatingField` / `RatingColumn` | `ratingSize()` | kolor semantyczny, nie variant shell |
| `ItemCard` / layout schema | często bez `size()` | własne tokeny layoutu |

Zawsze sprawdź **istniejącą klasę siostrzane** przed dodaniem `variant()` — skopiuj listę dozwolonych wartości i wzorzec `getWrapperClasses()`.

### Kolory i dark mode — tokeny z `base.css`

Wszystkie komponenty muszą wyglądać spójnie w **light i dark mode**. Źródłem prawdy jest `resources/css/base.css`:

- `:root { … }` — wartości domyślne (jasny motyw)
- `.dark { … }` — te same nazwy tokenów, inne wartości (ciemny motyw)

Filament (i playground) włącza dark mode przez **klasę `.dark` na ancestorze**, nie przez media query. Dlatego:

| ❌ Unikaj | ✅ Używaj |
|----------|----------|
| `text-gray-950 dark:text-white` | `color: var(--fff-icon-column-text)` |
| `rgb(24 24 27)` na sztywno w komponencie | token globalny lub dziedziczony |
| `@media (prefers-color-scheme: dark)` | override w `.dark { --fff-*: … }` w `base.css` |
| Osobne reguły `.dark .fff-x` z `@apply text-zinc-100` | najpierw token w `base.css`, potem `color: var(--fff-*)` |

#### Tokeny globalne (najczęstsze)

| Grupa | Przykłady | Zastosowanie |
|-------|-----------|--------------|
| Surface / shell | `--fff-field-bg`, `--fff-field-border`, `--fff-field-secondary-*`, `--fff-field-soft-*` | tła i obramowania pól (rodzina A) |
| Focus | `--fff-field-focus-border`, `--fff-field-focus-ring` | ring przy focusie |
| Surface chrome | `--fff-surface-border`, `--fff-surface-shadow` | karty, segmenty, layout |
| Tekst (shared) | `--fff-flex-slider-label-color`, `--fff-flex-slider-value-color` | etykiety + wartości pomocnicze |
| Kolumny tabeli | `--fff-icon-column-text`, `--fff-icon-column-muted` | `IconColumn` label / slug ikony |

Tokeny **specyficzne dla komponentu** (np. `--fff-schedule-muted`, `--fff-phone-field-menu-text`) definiuj na root klasy komponentu, ale wartości dark ustawiaj przez **nadpisanie w `.dark`** — albo deleguj do globalnego tokena z `base.css`, jeśli semantyka się zgadza.

#### Wzorzec w CSS komponentu

```css
/* icon-column.css — wzorzec zalecany */
.fff-icon-column__label {
    color: var(--fff-icon-column-text);
}

.fff-icon-column__name {
    color: var(--fff-icon-column-muted);
}
```

Tokeny `--fff-icon-column-*` są w `base.css` — komponent tylko **konsumuje**, nie definiuje palety na nowo.

#### Wzorzec dla nowego komponentu z własnymi kolorami

1. Sprawdź, czy wystarczy istniejący token (`--fff-field-bg`, `--fff-flex-text-input-text` z `flex-text-input.css`).
2. Jeśli potrzebujesz nowego semantycznego koloru **używanego w wielu miejscach** → dodaj do `base.css` (`:root` + `.dark`).
3. Jeśli kolor jest **tylko lokalny** dla jednego pola → `--fff-my-field-text` na `.fff-my-field`, z override `.dark .fff-my-field { --fff-my-field-text: … }` (jak `icon-picker-field.css` z `--fff-icon-picker-muted`).
4. W regułach końcowych zawsze `color: var(--fff-…)`, nigdy bezpośredni hex w selektorze elementu.

#### Przykład regresji (IconColumn)

`text-gray-950 dark:text-white` na labelu dawało **czarny tekst w dark mode** w playground — `dark:` nie zadziałał w kontekście Filament. Fix: tokeny w `base.css` + `color: var(--fff-icon-column-text)`.

#### Weryfikacja

- Playground sluga w **dark mode** (przełącznik motywu)
- Test CSS (opcjonalnie): dist bundle zawiera `color:var(--fff-…)` i override `.dark`, bez `dark:text-*`

### Obowiązki przy nowym komponencie

1. **`size()`** — trait `HasControlSize` (lub dedykowany prefix jak `iconSize()` w kolumnach, żeby nie kolidować z `TextColumn`).
2. **`variant()`** — jeśli shell jest trackiem: rodzina A lub B; waliduj tablicę w `getVariant()`.
3. **`getWrapperClasses()`** — zwróć **oba** prefiksy jeśli dziedziczysz po input shell:
   - `fff-{moje-pole}--{size}`
   - `fff-flex-text-input--{size}` + `fff-flex-text-input--{variant}` **albo** `fff-select-field--*`
4. **CSS** — rozmiary przez zmienne CSS (`--fff-track-h-md`), warianty przez modyfikatory rodziny bazowej; w `my-field.css` tylko różnice specyficzne dla pola.
5. **Kolory** — `color` / `background` / `border-color` przez `var(--fff-*)` z `base.css` lub shella; dark mode przez tokeny, nie `dark:` Tailwind (§2).
6. **Playground** — dodaj demo **co najmniej** `md` + `primary` oraz jednej pary `sm`/`lg` lub `soft`/`secondary`, żeby wizualnie zweryfikować spójność (light **i** dark).
7. **Nie** dodawaj `xs` / `xl` bez decyzji architektonicznej i aktualizacji `ControlSize` + `base.css` + wszystkich pól.

### Focus outline

Trait `HasFieldFocusOutline` → `focusOutline()` / `shouldShowFocusOutline()`.  
Klasa `has-focus-outline` na root + reguły w `flex-text-input.css` (wspólny ring `--fff-field-focus-*`). Nowe pola z focusem w shellu powinny **reuse** te selektory lub rozszerzyć je o własny trigger (patrz `.fff-phone-field.has-focus-outline`).

### Gdzie szukać w kodzie

| Temat | Plik |
|-------|------|
| Tokeny rozmiaru i kolorów | `resources/css/base.css` |
| Shell input + warianty A | `resources/css/components/flex-text-input.css` |
| Shell select + warianty B | `resources/css/components/select-field.css` |
| Enum rozmiaru | `src/Enums/ControlSize.php` |
| Trait `size()` | `src/Concerns/HasControlSize.php` |
| Docs użytkownika | `docs/shared-concepts.md` → Control size |

---

## 3. Warstwy CSS

| Warstwa | Plik dist | Kiedy się ładuje | Co zawiera |
|---------|-----------|------------------|------------|
| **Core** | `resources/dist/css/core.css` | Zawsze (Filament asset `flex-fields-core`) | Tokeny `--fff-*`, switch, item-card, hold-confirm, wspólny layout |
| **Lazy component** | `resources/dist/css/{component}.css` | Gdy komponent jest na stronie | Style wyłącznie tego komponentu (+ zależności przez kolejkę) |
| **Shared lazy** | np. `flex-text-input.css`, `tag-chips.css`, `teleported-menu.css` | Gdy komponent z deklaruje zależność | Fragmenty UI używane przez wiele pól |
| **Playground base** | `playground.css` | Strony playground | Chrome playground, wspólne style demo tabel |
| **Playground slug** | `playground-{slug}.css` | `/flex-fields-playground/{slug}` | `playground.css` + sklejone lazy bundle tego sluga |

### Źródła (dev)

```
resources/css/
├── base.css                 # tokeny, zmienne globalne
├── utilities-baseline.css   # wspólny Tailwind @theme + @utility (bez base.css — importuj oba w entry)
├── core.css                 # entry → dist/core.css
├── playground.css           # entry → dist/playground.css
├── entries/{component}.css  # entry → dist/{component}.css (Tailwind v4 @source)
├── components/              # style konkretnych pól
├── core/                    # współdzielone moduły (rating, teleported-menu, tables/)
└── playground/              # style tylko na playground
```

Każdy lazy bundle buduje się z **jednego** pliku `resources/css/entries/{component}.css`. Entry importuje zwykle `@import "tailwindcss/theme"`, `utilities`, `../base.css` — lub eksperymentalnie `../utilities-baseline.css` **oraz** `../base.css` (pilotaż `select-field`).

**Długoterminowa warstwa współdzielona:** wszystkie `resources/css/entries/*.css` importują `../utilities-baseline.css` + `../base.css` zamiast powielać `@import "tailwindcss/theme"` + `utilities`. `select-field` ma wąskie `@source` (tylko blade partials).

---

## 4. FlexFieldStylesheetQueue — serce lazy CSS

Klasa: `src/Support/FlexFieldStylesheetQueue.php`  
Trait: `src/Support/FlexFieldAssetQueue.php` (wspólna logika z `FlexFieldAlpineQueue`)

### Cykl życia klucza (np. `phone-field`)

| Stan | Znaczenie |
|------|-----------|
| **nieznany** | Jeszcze nie wywołano `enqueueFor('phone-field')` |
| **enqueued** | Zarejestrowany w `$enqueued` — wie, że komponent jest na stronie |
| **emitted** | `<link>` już poszedł do DOM — `pending()` go nie zwraca |

### API

```php
// Rejestruje komponent + cały graf STYLESHEET_DEPENDENCIES.
// Zwraca TYLKO nowe stylesheet ID z tej operacji (do natychmiastowego emit).
FlexFieldStylesheetQueue::enqueueFor(string $component): array

// Czy klucz już był rejestrowany w tym requeście?
FlexFieldStylesheetQueue::has(string $component): bool

// Wszystkie zarejestrowane klucze (kolejność rejestracji).
FlexFieldStylesheetQueue::registered(): array

// Enqueued, ale jeszcze nie emitted — do flush na końcu strony.
FlexFieldStylesheetQueue::pending(): array

// Oznacz jako już wysłane (po emit-assets).
FlexFieldStylesheetQueue::markStylesheetsEmitted(array $stylesheets): void

// Playground: oznacz bundle jako „już załadowany”, żeby lazy nie dublował.
FlexFieldStylesheetQueue::suppressForPlaygroundBundle(array $stylesheets): void

// Testy: reset stanu.
FlexFieldStylesheetQueue::reset(): void
```

### Jak `enqueueFor` rozwiązuje zależności

`FlexFieldAssets::stylesheetsFor($component)` robi DFS po `STYLESHEET_DEPENDENCIES` i zwraca **uporządkowaną listę** unikalnych bundle'i, np.:

```php
FlexFieldAssets::stylesheetsFor('phone-field');
// → ['emoji-picker', 'flex-text-input', 'teleported-menu', 'phone-field']
```

`enqueueFor` dodaje każdy klucz do kolejki (pomija duplikaty) i zwraca tylko te, które **po raz pierwszy** trafiły do kolejki w tym wywołaniu.

### Scoped container

W `FilamentFlexFieldsServiceProvider`:

```php
$this->app->scoped(FlexFieldStylesheetQueue::class);
```

Nowa instancja (pusta kolejka) na każde żądanie HTTP. **Nie** używaj statycznych zmiennych globalnych poza testowym `reset()`.

---

## 5. Dwa sposoby rejestracji CSS

### A) Pola formularza, layouty schema, akcje — `load-stylesheet` w Blade

Plik: `resources/views/partials/load-stylesheet.blade.php`

```blade
@include('filament-flex-fields::partials.load-stylesheet', ['component' => 'tags-field'])
```

Co robi:

1. Opcjonalne kolejki danych (`CountryRegistryQueue` dla `country-field` / `phone-field`).
2. `FlexFieldStylesheetQueue::enqueueFor($component)` — rejestruje CSS.
3. `FlexFieldAlpineQueue::enqueueChunksFor($component)` — rejestruje chunki z manifestu.
4. Jeśli coś nowego — **natychmiast** `@include emit-assets` i `markStylesheetsEmitted` / `markChunksEmitted`.

**Dlaczego natychmiast:** pełna strona dostaje `@push('styles')` do `<head>` bez czekania na koniec body. Livewire partial dostaje inline `<link>` od razu w odpowiedzi.

**Wzorzec w blade pola:**

```blade
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'my-field'])

    {{-- opcjonalnie dodatkowe zależności, jeśli blade używa ich DOM bezpośrednio --}}
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'teleported-menu'])

    <div x-load x-load-src="..." x-data="myFieldComponent({ ... })">
        ...
    </div>
</x-dynamic-component>
```

Duplikat `@include load-stylesheet` dla tego samego `component` jest **bezpieczny** — drugie wywołanie zwróci `[]` i nie wyemituje ponownie.

### B) Kolumny tabeli — tylko `setUp()`, bez `load-stylesheet` w komórce

Kolumny (`UserColumn`, `RatingColumn`, `IconColumn`) renderują HTML przez `formatStateUsing()` → blade partial. **Nie ma** sensu `@include load-stylesheet` w każdej komórce (setki duplikatów logicznych).

Zamiast tego w `setUp()`:

```php
protected function setUp(): void
{
    parent::setUp();

    FlexFieldStylesheetQueue::enqueueFor('icon-column');

    $this->html();
    $this->formatStateUsing(fn (mixed $state, IconColumn $column): string => $column->formatIconDisplay($state));
}
```

Flush na końcu requestu robi hook w `FilamentFlexFieldsServiceProvider`:

```php
FilamentView::registerRenderHook(PanelsRenderHook::STYLES_AFTER, ...queued-stylesheets...);
FilamentView::registerRenderHook(PanelsRenderHook::BODY_END, ...queued-stylesheets...);
```

`queued-stylesheets.blade.php` bierze `FlexFieldStylesheetQueue::pending()`, emituje raz, `markStylesheetsEmitted`.

**Dlaczego dwa hooki:** część layoutów Filament renderuje style wcześniej/później; drugi pass zwykle zwraca pusty string (wszystko już `emitted`).

---

## 6. Współdzielenie CSS między komponentami

### Graf zależności — `FlexFieldAssets::STYLESHEET_DEPENDENCIES`

Plik: `src/Support/FlexFieldAssets.php`

```php
public const STYLESHEET_DEPENDENCIES = [
    'tags-field' => ['flex-text-input', 'tag-chips'],
    'user-select' => ['teleported-menu', 'select-field', 'tag-chips', 'user-display'],
    'user-column' => ['user-display'],
    'icon-picker-field' => ['teleported-menu', 'select-field'],
    // ...
];
```

**Reguły:**

1. **Wspólny CSS = osobny bundle** (`tag-chips.css`, `user-display.css`, `flex-text-input.css`).
2. **Nigdy nie importuj** `tag-chips.css` wewnątrz `tags-field.css` entry — zależność idzie tylko przez `STYLESHEET_DEPENDENCIES`.
3. Kolejność w tablicy wynikowej jest deterministyczna: najpierw transitive deps, potem komponent (DFS w `stylesheetsFor`).
4. Jeśli komponent B używa klas z bundle A, dodaj `'b-field' => ['a-shared']` — **nie kopiuj selektorów**.

### Przykład: tagi

| Bundle | Plik źródłowy | Odpowiedzialność |
|--------|---------------|------------------|
| `tag-chips` | `components/tag-chips.css` | Pill chips, remove button — używane przez `TagsField` i `UserSelect` |
| `tags-field` | `components/tags-field.css` | Dropdown sugestii, shell pola |
| `flex-text-input` | `components/flex-text-input.css` | Wspólna powłoka input (track, affixes, warianty) |

`tags-field` entry **nie** zawiera reguł `.fff-tags-field__tag` — one są w `tag-chips`.

### Przykład: user display

`user-display.css` — awatary, inicjały, weryfikacja.  
`user-column.css` — tylko layout kolumny tabeli (stack, overflow).  
`user-select` zależy od obu + `select-field` + `tag-chips`.

### Klasy wrapperów w PHP

Wiele pól dodaje klasy bazowe w `getWrapperClasses()`:

```php
return [
    'fff-tags-field',
    'fff-flex-text-input',           // dziedziczy tokeny input shell
    'fff-flex-text-input--'.$size,
    // ...
];
```

To **nie** ładuje CSS automatycznie — musisz mieć zależność w `STYLESHEET_DEPENDENCIES`.

### `LAZY_COMPONENT_STYLESHEETS`

Każdy nowy bundle musi być na liście w `FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS`, inaczej `hasLazyStylesheet()` zwróci false i nie trafi do rejestracji Filament asset.

---

## 7. emit-assets i data-fff-asset-batch

Plik: `resources/views/partials/emit-assets.blade.php`

Dla każdej paczki assetów renderuje:

1. Ukryty `<span data-fff-asset-batch data-fff-stylesheets="[...]" data-fff-chunks="[...]">` — marker dla injectora przy morph.
2. Rzeczywiste tagi:
   - **Full page:** `@push('styles')` z `<link rel="stylesheet" data-fff-stylesheet="phone-field">`.
   - **Livewire request:** inline `<link>` w odpowiedzi partial.

Atrybuty `data-fff-stylesheet` / `data-fff-alpine-chunk` oznaczają linki **chronione** — injector ich nie usuwa przy deduplikacji.

---

## 8. Asset injector (SPA, modale, Livewire morph)

Plik: `resources/js/core/flex-field-asset-injector.js`  
Rejestracja: Filament JS asset `flex-field-asset-injector` na hooku `SCRIPTS_AFTER`.

Odpowiada za:

- **Normalizację URL** (`normalizeAssetUrl`) — deduplikacja względem `baseURI`.
- **Mapy załadowanych** stylesheetów i chunków + `inflightRequests` (równoległe żądania o ten sam plik).
- **Livewire morph:** skan `data-fff-asset-batch` w nowym DOM, dociąganie brakujących CSS/JS przed pokazaniem modala.
- **FOUC w modalach:** klasa `fff-flex-fields-assets-pending` na `.fi-modal` do czasu załadowania.
- **SPA navigate** (`data-navigate-track` na linkach).

Po zmianie injectora:

```bash
npm run build:js
php artisan filament:assets   # w aplikacji hostującej
```

---

## 9. JavaScript i FlexFieldAlpineQueue

### Struktura

```
resources/js/
├── components/{component}.js   # cienki entry — export factory Alpine
├── core/                       # moduły współdzielone (esbuild splitting)
└── support/                    # integracje (mapbox, itd.)

resources/dist/components/
├── {component}.js              # entry po buildzie
├── flex-fields-{name}-{hash}.js  # współdzielone chunki
└── alpine-manifest.json        # component → lista chunków
```

Build: `scripts/build-component-js.mjs` (esbuild, `splitting: true`).

### Rejestracja Alpine

`FilamentFlexFieldsServiceProvider::registeredAlpineComponents()` rejestruje **tylko entry** z `FlexFieldAssets::alpineEntryNames()` (`resources/dist/components/{name}.js`). Współdzielone chunki `flex-fields-*-{hash}.js` rejestruje osobno `registeredAlpineChunkComponents()` z `loadedOnRequest()` — Filament zna je dla preloadów z `emit-assets`, ale nie ładują się globalnie.

### Blade

```blade
x-load
x-load-src="{{ FilamentAsset::getAlpineComponentSrc('tags-field', package) }}"
x-data="tagsFieldFormComponent({ state: $wire.$entangle('...') })"
```

### FlexFieldAlpineQueue

Analogicznie do CSS:

```php
FlexFieldAlpineQueue::enqueueChunksFor('phone-field'); // z alpine-manifest.json
FlexFieldAlpineQueue::pending();
FlexFieldAlpineQueue::markChunksEmitted($chunks);
```

`load-stylesheet` enqueue'uje chunki razem ze stylesheetami i przechodzi przez ten sam `emit-assets` (`modulepreload`).

### Współdzielenie JS

Importuj z `resources/js/core/` — esbuild sam wyciągnie chunk. **Nie kopiuj** plików między komponentami. Sprawdź `alpine-manifest.json` po buildzie, które chunki są współdzielone.

### `wire:ignore`

Pola z własnym Alpine (mapy, selecty, tagi) używają `wire:ignore` + `$wire.$entangle()` + `wire:key` hashowany od configu. Szczegóły w `docs/shared-concepts.md` (sekcja Livewire).

---

## 10. Playground — bundle per slug

### Rejestracja sluga

Dwa powiązane systemy:

1. **`FlexFieldsPlaygroundRegistry`** — slug → `{ label, playground: XxxPlayground::class, sort, icon: GravityIcon::* }`. Ikony nawigacji: `src/Support/GravityIcon.php` (nie surowe stringi heroicon).
2. **Klasa playground** w `src/Support/Playground/` — kontrakt:
   - `defaultState(): array` — wartości demo
   - `components(): list<Component>` — pola/layout na stronie

Strona Livewire `FlexFieldsPlaygroundComponentPage` rozwiązuje playground przez `app($definition['playground'])`.  
**`FlexFieldsPlaygroundBuilder`** — agregat używany głównie w testach jednostkowych (`defaultState()` skróty), nie przez stronę Livewire.

Nowy slug = klasa playground + wpis w registry + `npm run build:css:playground-bundles` + test nawigacji.

### Bundle CSS

`scripts/export-playground-bundles.php` generuje mapę:

```json
{
  "phone-field": ["emoji-picker", "flex-text-input", "teleported-menu", "phone-field"],
  "icon-column": ["icon-column"]
}
```

`npm run build:css:playground-bundles` skleja `playground.css` + powyższe pliki → `resources/dist/css/playground-{slug}.css`.

### Ładowanie na stronie sluga

`playground-page-stylesheets.blade.php`:

1. Pushuje jeden link `playground-{slug}.css` (`data-fff-playground-bundle`).
2. Wywołuje `suppressForPlaygroundBundle()` dla wszystkich stylesheetów z tego sluga.

**Efekt:** gdy pole na playground woła `load-stylesheet`, `enqueueFor` **nie emituje** ponownie tego samego CSS — unikasz podwójnych reguł i konfliktów specyficzności.

### Aliasy

`PLAYGROUND_STYLESHEET_ALIASES` — gdy slug ≠ id bundle. Alias działa też w `stylesheetsFor()` / `load-stylesheet` (przez `resolveStylesheetComponent()`).

| Slug playground | Bundle CSS |
|-----------------|------------|
| `date-time-fields` | `flex-date-time-field` |
| `file-upload` | `flex-file-upload` |
| `verification-code` | `flex-verification-code` |
| `flex-radiolist` | `flex-checklist` |
| `matrix-choice` | `matrix-choice-field` |
| `rating` | `rating-field` |

**Zasada:** nowy slug z inną nazwą niż `LAZY_COMPONENT_STYLESHEETS` → dodaj alias **przed** buildem bundle. Po buildzie sprawdź, że `playground-{slug}.css` zawiera selektory komponentu (np. `.fff-matrix-choice`), nie tylko chrome playground.

`PLAYGROUND_EXTRA_STYLESHEETS` — dodatkowe bundle ponad domyślne dla sluga (np. `date-time-fields` → `flex-time-segments`).

### SPA navigate na playground

`playground-assets.blade.php` (hook `playground-assets`) podmienia `data-fff-playground-bundle` przy `livewire:navigating` — bundle CSS zmienia się bez pełnego reloadu.

### Critical preload

`critical-stylesheet-preloads` (`HEAD_END`) may preload `CRITICAL_PRELOAD_STYLESHEETS` (currently `teleported-menu` only), but:

- on playground **with bundle sluga** → preload wyłączony;
- on playground **without bundle** → tylko stylesheet'y potrzebne danemu slugowi;
- **poza playground** → preload tylko gdy `FlexFieldStylesheetQueue::hasQueuedTeleportedMenu()` (np. kolumna/tabela zarejestrowała pole z dropdownem przed `HEAD_END`); formularze ładują CSS przez `load-stylesheet` → `emit-assets`.

`HoldConfirmAction` preloaduje swój moduł Alpine przez `@push` + `modulepreload` w `hold-confirm.blade.php` (per action), nie globalnie w `HEAD_END`.

---

## 11. Czego NIE robić (antywzorce)

| ❌ Źle | ✅ Dobrze |
|--------|----------|
| Kopiować `.fff-flex-text-input__shell` do nowego `my-field.css` | Zależność `'my-field' => ['flex-text-input']` |
| `@import '../components/tag-chips.css'` w entry innego pola | Osobny bundle `tag-chips` + `STYLESHEET_DEPENDENCIES` |
| `@include load-stylesheet` w blade każdej komórki tabeli | `enqueueFor()` w `setUp()` kolumny + `queued-stylesheets` |
| Duplikować ten sam CSS w `playground-{slug}.css` i lazy (bez suppress) | `suppressForPlaygroundBundle` na stronie playground |
| Dodawać `<link>` ręcznie w blade | Zawsze przez `load-stylesheet` / kolejkę / hook |
| Statyczna globalna tablica „załadowane CSS” | `FlexFieldStylesheetQueue` (scoped) |
| Wrzucać style komponentu do `core.css` | Lazy bundle — core tylko dla naprawdę globalnych tokenów |
| Importować cały `phone-field.css` w `country-field` entry | Tylko wspólne bundle przez graf |
| Zapomnieć `LAZY_COMPONENT_STYLESHEETS` + entry CSS + `filament:assets` | Pełna checklist poniżej |
| Hardcodować `height: 40px` / własne `xs`/`xl` | Tokeny `--fff-track-h-*` + enum `ControlSize` (§2) |
| Własna paleta obramowań zamiast rodziny A/B | `variant()` + modyfikatory `fff-flex-text-input--*` lub `fff-select-field--*` |
| `text-gray-*` / `dark:text-*` na tekście komponentu | Tokeny `--fff-*` z `base.css` + `color: var(--…)` (§2) |
| Hardcodowany `#18181b` / `rgb(24 24 27)` w regule elementu | Nowy lub istniejący token w `base.css` (`:root` + `.dark`) |

### Duplikacja a specyficzność CSS

Podwójne załadowanie tego samego bundle (np. playground bundle + lazy `icon-picker-field.css`) może powodować **różne kaskady** jeśli pliki się różnią wersją lub kolejnością. Zawsze testuj playground sluga po dodaniu pola.

---

## 12. Checklist: nowy komponent formularza

### PHP

- [ ] Klasa w `src/Filament/Forms/Components/` (lub `Schemas/Components/`)
- [ ] `protected string $view = 'filament-flex-fields::forms.components.my-field'`
- [ ] Opcjonalnie `getWrapperClasses()` z prefiksami `fff-`
- [ ] **Rozmiar:** trait `HasControlSize` + `size()` / `getSize()` (`sm`|`md`|`lg`) — patrz §2
- [ ] **Wariant:** `variant()` z walidacją dozwolonych wartości (rodzina A: `primary|secondary|soft|flat` lub B: `bordered|…`) — patrz §2
- [ ] `getWrapperClasses()` zwraca modyfikatory bazowego shella (`fff-flex-text-input--{size,variant}` **lub** `fff-select-field--*`), nie tylko własny prefiks

### CSS

- [ ] `resources/css/components/my-field.css` — tylko style tego pola
- [ ] `resources/css/entries/my-field.css` — entry z `@source` na blade + PHP
- [ ] Dodaj `'my-field'` do `FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS`
- [ ] Jeśli używasz cudzego shell: wpis w `STYLESHEET_DEPENDENCIES`
- [ ] Rozmiary przez zmienne (`var(--fff-track-h-md)`), nie magiczne piksele; warianty przez modyfikatory rodziny A/B
- [ ] Kolory tekstu/tła/border przez `var(--fff-*)` z `base.css` lub shella — bez `dark:` Tailwind; sprawdź dark mode na playground (§2)
- [ ] `npm run build:css:components`

### Blade

- [ ] `@include load-stylesheet` z poprawnym `component` id
- [ ] Dodatkowe `@include` dla każdej **bezpośredniej** zależności DOM (jeśli nie wystarczy sam graf — patrz `icon-picker-field`: osobno `select-field`, `teleported-menu`)
- [ ] `x-load` + `x-load-src` jeśli jest Alpine
- [ ] `wire:ignore` + `$entangle` jeśli Alpine zarządza DOM

### JS (jeśli interaktywny)

- [ ] `resources/js/components/my-field.js`
- [ ] Wspólna logika → `resources/js/core/`, nie kopiuj
- [ ] `npm run build:js` → sprawdź `alpine-manifest.json`

### Playground

- [ ] `src/Support/Playground/MyFieldPlayground.php`
- [ ] Wpis w `FlexFieldsPlaygroundRegistry`
- [ ] Demo **co najmniej** `md` + domyślny wariant oraz jednej pary `sm`/`lg` lub `soft`/`secondary` (§2)
- [ ] Opcjonalnie `PLAYGROUND_EXTRA_STYLESHEETS` / alias
- [ ] `npm run build:css:playground-bundles`

### Testy

- [ ] `FlexFieldStylesheetQueueTest` — `stylesheetsFor` + `enqueueFor`
- [ ] Blade nie zawiera zbędnych ręcznych `<link>`
- [ ] Test render HTML / playground section

### Aplikacja hostująca

- [ ] `php artisan filament:assets` po zmianie dist

---

## 13. Checklist: nowa kolumna tabeli

- [ ] Klasa extends `TextColumn` (lub `Column`) w `src/Filament/Tables/Columns/`
- [ ] `setUp()`: `FlexFieldStylesheetQueue::enqueueFor('{column-id}')` + `$this->html()` + `formatStateUsing`
- [ ] **Brak** `@include load-stylesheet` w blade komórki
- [ ] Osobny lazy bundle `resources/css/entries/{column-id}.css`
- [ ] `STYLESHEET_DEPENDENCIES` jeśli dziedziczy wizualnie po innym bundle (np. `user-column` → `user-display`)
- [ ] Tekst / muted colors przez tokeny `base.css` (np. `--fff-icon-column-text`), nie `text-gray-* dark:text-*`
- [ ] Playground z mock tabelą (`formatXDisplay()` bez bazy)
- [ ] Test: `registered()` po `make()`, blade bez `load-stylesheet`
- [ ] Test: `queued-stylesheets` emituje raz, drugi pass pusty

---

## 14. Build i testy

```bash
# W katalogu pakietu filament-flex-fields/

npm run build:css:core
npm run build:css:playground
npm run build:css:components      # wszystkie entries → dist/
npm run build:css:playground-bundles
# lub krócej:
npm run build:css
npm run build:js

# Ikony (po zmianie zestawów blade-icons):
php artisan fff:icons:manifest
# commit resources/dist/icon-catalog-manifest.json

# W aplikacji Laravel (monorepo root):
php artisan filament:assets

# PHP
composer test              # Pest — testy pakietu
composer analyse           # PHPStan
composer format            # Pint

# JS / E2E / CI
npm run test:js            # kontrakty Node (icon-picker, schedule, …)
npm run test:e2e           # Playwright — wymaga FLEX_FIELDS_PLAYGROUND_URL
npm run test:assets        # build + test:js + e2e:coordinator
npm run check:budgets      # guard rozmiaru bundle (bundle-metrics.json)

# Pojedynczy test PHP:
php artisan test packages/filament-flex-fields/tests/Unit/FlexFieldStylesheetQueueTest.php
```

### Build JS — szczegóły

- Entry: tylko `resources/js/components/{name}.js`
- Współdzielone: `resources/js/core/`, `resources/js/support/`
- `scripts/build-component-js.mjs` — esbuild splitting + `SEMANTIC_CHUNK_RULES` (nazwy chunków)
- Osobne buildy: `build-asset-injector-js.mjs`, `build-skeleton-demo-js.mjs`
- Po buildzie: `alpine-manifest.json` + `__chunk_modules__` (mapowanie chunk → moduły)
- `generate-bundle-summary.mjs` → tabela bundle w README

### Dev bez `filament:assets`

W **non-production** `publishStalePackageAssets()` kopiuje przestarzałe dist → `public/` (w tym beep MP3 dla barcode). Lokalnie możesz ominąć `filament:assets`, ale w CI/produkcji zawsze go uruchamiaj.

Kluczowe pliki testów assetów:

- `tests/Unit/FlexFieldStylesheetQueueTest.php` — kolejka, zależności, deduplikacja, playground suppress
- `tests/Feature/UserColumnTableTest.php` — hook `queued-stylesheets`
- `tests/Unit/FlexFieldCssAssetsTest.php` — separacja bundle'i (np. rating-column vs core)

---

## 15. Szybka ściągawka

| Chcę… | Zrób… |
|-------|--------|
| Załadować CSS pola | `@include load-stylesheet` w blade |
| Załadować CSS kolumny | `FlexFieldStylesheetQueue::enqueueFor()` w `setUp()` |
| Współdzielić CSS | Osobny bundle + `STYLESHEET_DEPENDENCIES` |
| Współdzielić JS | Import z `resources/js/core/`, build z splitting |
| Preloadować chunki Alpine | Automatycznie przez `load-stylesheet` → `emit-assets` |
| Uniknąć duplikatu na playground | Bundle sluga + `suppressForPlaygroundBundle` |
| Flush CSS kolumn po stronie | Hook `queued-stylesheets` (już zarejestrowany) |
| Sprawdzić co się załaduje | `FlexFieldAssets::stylesheetsFor('my-field')` w tinker |
| Debugować modale / SPA | `flex-field-asset-injector.js`, atrybuty `data-fff-*` |
| Rozmiar / wariant pola | `->size('sm')` + `->variant('soft')` + klasy shell w `getWrapperClasses()` — §2 |
| Kolory (light + dark) | Tokeny `--fff-*` w `base.css`, `color: var(--fff-…)` — nie `dark:text-*` — §2 |
| Manifest ikon | `php artisan fff:icons:manifest` → commit dist JSON |
| Dropdown w modalu | `wireExclusiveFlexDropdown` + `fffOverlays` store — §16 |
| Kraje w phone/country | `CountryRegistryQueue::renderScriptOnce()` w blade — §16 |
| Nowy slug playground | Registry + alias jeśli slug ≠ bundle id — §10 |

---

## 16. Systemy poza assetami

### Icon catalog (IconPickerField, IconColumn)

Warstwa niezależna od lazy CSS — wymaga własnego workflow:

| Element | Plik |
|---------|------|
| Build manifestu | `php artisan fff:icons:manifest` → `BuildIconManifestCommand` |
| Dist manifest | `resources/dist/icon-catalog-manifest.json` |
| Resolver / indeks / SVG cache | `src/Support/Icons/IconCatalogResolver.php`, `IconCatalogIndex.php`, `IconSvgCache.php` |
| Livewire API | `IconPickerField::getIconPickerSearchResults()`, `getIconPickerSvgPreviews()` — `#[ExposedLivewireMethod]` + `#[Renderless]` |
| JS virtual scroll | `resources/js/core/icon-picker-*.js` |
| FormBuilder | `FormBuilder/Configurators/IconPickerFieldConfigurator.php` |

Po zmianie zestawów ikon: `fff:icons:manifest` → commit JSON → `npm run build:js` jeśli zmienił się JS. Docs użytkownika: `docs/icon-picker-field.md`.

**IconColumn** używa tylko `IconSvgCache` + lazy CSS (`enqueueFor` w `setUp()`), bez Alpine ani teleported menu.

### Overlay coordinator i teleported menu

Każde pole z dropdownem teleportowanym do `body` musi używać wspólnego koordynatora — nie własnego `menuOpen` w izolacji.

- `resources/js/core/flex-dropdown-coordinator.js` → `Alpine.store('fffOverlays')`, `wireExclusiveFlexDropdown()`
- `resources/js/core/searchable-select-menu.js` — mixin dla country/timezone/select/icon-picker/map
- CSS: `teleported-menu.css` — z-index w modalach (`:has(.fi-modal.fi-modal-open)`)
- **Specjalna reguła:** `FlexFieldAssets::alpineChunksFor('select-field')` **dokleja** chunk overlay-coordinator nawet gdy nie ma go w manifeście entry (`overlayCoordinatorChunk()`)

Testy: `tests/Feature/SelectFieldCoordinatorRenderTest.php`, `tests/e2e/field-smoke.spec.mjs`. Szczegóły użytkownika: `docs/shared-concepts.md`.

### CountryRegistry (PhoneField, CountryField)

Sam `CountryRegistryQueue::enqueueFor()` w `load-stylesheet` **nie wystarczy**:

1. Blade musi wywołać `CountryRegistryQueue::renderScriptOnce()` — emituje `<template id="fff-country-registry-data">` **raz** na stronę (`phone-field.blade.php`, `country-field.blade.php`).
2. PHP: `CountryRegistryQueue::registerCountryFilter()` gdy pole ogranicza kraje (`allowedCountries()` / `countries()`).
3. JS czyta template przez `resources/js/core/country-registry.js`.

Test: `tests/Unit/CountryRegistryTest.php`.

### SSR i hydratacja

Wiele pól renderuje HTML po stronie serwera, potem Alpine dodaje `is-hydrated` / `displayReady` (schedule, social-links, timezone, link-preview, flex-text-input) — unika layout jump. Nowe interaktywne pole: rozważ ten sam wzorzec zamiast pustego shella + pełny rerender Livewire. Patrz `docs/shared-concepts.md` → Livewire.

### Layout schema i akcje (nie form fields)

- Layout: `src/Filament/Schemas/Components/` (Filament Schemas API) — `SegmentTabs`, `ItemCard`, `ProgressCircle`, …
- `HoldConfirmAction` — Filament Action, nie pole; `@push` `modulepreload` w `hold-confirm.blade.php` + `load-stylesheet` per action
- Cell variants w tabelach: `CellSwitch` → bundle `switch`, `CellSlider` → `track-slider` (reuse CSS pól)

---

## 17. FlexFieldFormBuilder

Warstwa JSON / custom fields dla aplikacji hostujących — osobna od zwykłego form field:

- `FlexFieldFormBuilder`, `FlexFieldSchemaRegistry`, `FieldTypeHandlerRegistry`, `FieldComponentFactory`
- `FieldType` enum + `HasFlexFields` trait na modelu
- Per-type **Configuratory** w `src/Support/FormBuilder/Configurators/`
- Per-type **Handlers** w `src/Support/FormBuilder/Handlers/`

**Dodanie typu do JSON layer:** enum value + handler + configurator + testy (`FlexFieldFormBuilderTest`, `FieldTypeRegistryTest`). Samo dodanie klasy w `Forms/Components/` nie wystarczy.

---

## 18. Filament v5 — hooki i assety specjalne

`FilamentFlexFieldsServiceProvider` rejestruje hooki poza stylesheetami:

| Hook | Cel |
|------|-----|
| `styles` / `scripts` | core CSS, injector JS |
| `HEAD_END` | `critical-stylesheet-preloads` (warunkowy `teleported-menu`) |
| `queued-stylesheets` | flush CSS kolumn tabeli |
| `tooltip-overrides` / `tooltip-glass-script` | tooltip glass |
| `playground-theme` / `playground-assets` | tylko playground |

**Cache busting:** `FlexFieldsCss` / `FlexFieldsAlpineComponent` używają `filemtime()` na ścieżce dist.

**Lazy CSS:** `loadedOnRequest()` — Filament nie ładuje wszystkich bundle przez `@filamentStyles`.

**Dependency-only keys:** np. `segment-tabs` nie ma własnego `entries/segment-tabs.css` — tylko wpis w `STYLESHEET_DEPENDENCIES => ['segment-control']`.

**Plugin:** `FilamentFlexFieldsPlugin::register()` dodaje strony playground przez `PageConfiguration::make(FlexFieldsPlaygroundComponentPage::class, $slug)`.

---

## 19. Serwery, bezpieczeństwo, wydajność

Pola dotykające HTTP muszą respektować istniejące wzorce:

| Obszar | Plik / route |
|--------|----------------|
| Mapbox proxy | `routes/web.php` → `MapboxGeocodingProxyController` |
| URL meta scrape | `UrlMetaScraper` — reguły SSRF (`tests/Unit/UrlMetaScraperTest.php`) |
| HTML sanitization | `HtmlSanitizer` singleton |
| Upload SSRF | `FlexFileUpload` — `tests/Feature/FlexFileUploadSourcesTest.php` |
| User select cache | `UserSelectQueryCache` — scoped dedup per request |
| Config | `config/filament-flex-fields.php` — mapbox, icon picker UI |

Nowy scraper/proxy: kopiuj walidację hostów i timeouty z `UrlMetaScraper` / kontrolerów Mapbox.

---

## Powiązane pliki (mapa kodu)

| Obszar | Plik |
|--------|------|
| Lista lazy CSS + graf deps | `src/Support/FlexFieldAssets.php` |
| Kolejka CSS | `src/Support/FlexFieldStylesheetQueue.php` |
| Kolejka JS chunks | `src/Support/FlexFieldAlpineQueue.php` |
| Emit markup | `resources/views/partials/emit-assets.blade.php` |
| Rejestracja pól | `resources/views/partials/load-stylesheet.blade.php` |
| Flush kolumn | `resources/views/partials/queued-stylesheets.blade.php` |
| Playground bundle | `resources/views/partials/playground-page-stylesheets.blade.php` |
| Hooki Filament | `src/FilamentFlexFieldsServiceProvider.php` |
| Injector | `resources/js/core/flex-field-asset-injector.js` |
| Icon catalog | `src/Support/Icons/`, `src/Console/BuildIconManifestCommand.php` |
| Overlay coordinator | `resources/js/core/flex-dropdown-coordinator.js` |
| Country registry | `src/Support/CountryRegistryQueue.php` |
| FormBuilder | `src/Support/FormBuilder/` |
| Playground registry | `src/Support/FlexFieldsPlaygroundRegistry.php` |
| Docs użytkownika (assets) | `docs/shared-concepts.md` → Assets & playground |

---

## 20. Maintenance checklist

### Limity rozmiaru plików

| Typ | Limit docelowy | Akcja przy przekroczeniu |
|-----|----------------|--------------------------|
| `resources/js/components/{entry}.js` | **400 linii** (entry) | Wyciągnij moduły do `resources/js/core/` lub `components/{name}/` + dynamic `import()` |
| Klasa pola PHP (`src/Filament/Forms/Components/*.php`) | **500 linii** | Support class / trait / presenter (np. `UserSelect` → `Concerns/UserSelect/*`) |

### Checklist optymalizacji (nowy / refaktorowany komponent)

- [ ] **Lazy CSS** — `load-stylesheet` + graf `STYLESHEET_DEPENDENCIES`, nie import w entry CSS
- [ ] **Lazy JS** — cienki entry; współdzielone moduły przez esbuild splitting
- [ ] **Intersect mount** — pola ciężkie (`flex-date-time-field`, `flex-file-upload`, `select-field`, `user-select`, `barcode-scanner-field`, `icon-picker-field`) owijaj w `<x-filament-flex-fields::lazy-alpine-mount>`; disabled → `mountImmediately`
- [ ] **Chunk budget** — po buildzie `npm run check:budgets`; regresje gzip: `npm run compare:budgets`
- [ ] **Critical preload** — tylko `teleported-menu` gdy kolejka tego wymaga; bez globalnego preloadu hold-confirm (per-action `@push`)
- [ ] **Arch test** — `FlexFieldAlpineManifestArchTest` (manifest ↔ dist ↔ LAZY list)
- [ ] **CSS @source** — w entry trzymaj wąskie `@source` (PHP + blade pola); `resources/css/utilities-baseline.css` jako wspólny import tokenów; pełna warstwa shared CSS (jeden request) wymaga zmiany `build-component-css.mjs`

---

*Ostatnia aktualizacja: v2.7.x — world-class maintenance: UserSelect modules, file-upload/date-time splits, fixture E2E smoke, baseline sync.*

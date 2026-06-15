# Changelog

All notable changes to `filament-flex-fields` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.4.0] - 2026-06-16

### Added

- **Mapbox server proxy** — Laravel routes + `MapboxGeocodingClient` keep the Mapbox token server-side; JS uses cached proxy URLs (`geocodeSearchUrl` / `geocodeReverseUrl`).
- **Schema versioning** — `FlexFieldSchema::CURRENT_VERSION`, `version` on schemas, and `FlexFieldSchemaMigrator` (upgrades legacy `field` → `fields`, `name` → `slug`, etc.).
- **Flex field audit trail** — `HasFlexFields` records who/when/what on value changes to configurable `flex_field_audit` JSON column.
- **`SelectField` JS extraction** — ~1000-line inline `x-init` moved to `resources/js/components/select-field.js`, loaded via `x-load`.
- **JS unit tests** — `theme-utils`, `geocoding-cache`, `geocoding-list-keyboard` coverage in `tests/js/`.
- **Playwright E2E** — optional playground geo tests (`tests/e2e/playground-geo.spec.mjs`, set `FLEX_FIELDS_PLAYGROUND_URL`).
- **`docs/tags-field.md`** — full TagsField API reference.
- **Z-index CSS tokens** (`--fff-z-dropdown`, etc.) and `theme-utils.js` for consistent dark-mode menu styling.
- Geocoding keyboard mixin + client-side geocoding cache shared by map and address fields.
- **Geocoding translations** — `geocoding.failed` and `geocoding.rate_limited` in bundled `en` and `pl` language files.

### Changed

- `MapPickerField` / `AddressAutocompleteField` blades document `wire:ignore` + `$entangle` + `wire:key` remount strategy in [shared-concepts.md](docs/shared-concepts.md).
- README compare table, differentiation summary, geo proxy & audit bullets, country registry mention, and CI test counts updated.

## [2.3.0] - 2026-06-09

### Added

- `searchTypes()` on `MapPickerField` and `AddressAutocompleteField` to limit Mapbox geocoding results to specific place types (`address`, `poi`, `place`, etc.) or leave unset for all types.
- `MapboxSearchType` enum with supported Mapbox Geocoding API type values.
- Shared exclusive-dropdown registry (`flex-dropdown-registry.js`) with a `{ isOpen(), close() }` controller contract.
- Alpine overlay store (`Alpine.store('fffOverlays')`) and `wireExclusiveFlexDropdown()` helper that auto-wires each field's open state and closes other menus on open.
- Dedicated overlay-coordinator JS chunk, preloaded for `SelectField` and searchable select menus.
- JavaScript unit tests for the dropdown registry (`tests/js/flex-dropdown-registry.test.mjs`).

### Changed

- Unified exclusive-dropdown behavior across `PhoneField`, `CountryField`, `TimezoneField`, `CurrencyField`, `MapPickerField`, `AddressAutocompleteField`, `FlexColorPickerField`, `FlexDateTimeField`, `VideoField`, `FlexTextInput`, `FlexTextarea`, and `SelectField`.
- `searchable-select-menu.js` now registers through the overlay coordinator instead of per-field manual hooks.
- `SelectField` blade uses `Alpine.store('fffOverlays')` instead of an inline event bus.
- Emoji picker panel restyled toward emoji-mart layout: category tabs with custom SVG icons on top, search field below, 9-column emoji grid.
- Emoji picker category/search icons now use inline `@gravity-ui/icons` SVGs instead of CSS masks (fixes distorted tab icons).
- Emoji picker category tab click no longer shows the library’s circular `:active` flash on top of the custom hover background.
- Emoji picker skin tone menu no longer bleeds into the emoji grid after the search row was moved below category tabs.
- Emoji picker toggle button now closes on second click instead of closing and immediately reopening.
- Mapbox geocoding dropdown (`AddressAutocompleteField`, `MapPickerField`) is now teleported to `body` with the same glass/backdrop-blur menu styling as `SelectField`, including improved dark-mode detection and stronger contrast over map tiles.
- Mapbox geocoding dropdown no longer floats above the Filament topbar on scroll (`z-index` lowered from 80 to 20).

### Removed

- Legacy `fff:flex-dropdown-open` DOM event bus and `window.__fffFlexDropdownCoordinator`.
- Per-instance `prepareExclusiveDropdownOpen()` calls and DOM-scanning close logic.

### Fixed

- Multiple dropdowns opening at once on the same page (e.g. phone country picker and `CountryField` both staying open) caused by a shared unbind function reused across Alpine instances.
- `SwitchField` track color staying primary after toggling OFF when the switch started in the ON state (Alpine `x-bind:class` string merge kept stale `fi-color-*` classes).
- `FlexTextareaField` toolbar actions (`Attach`, `Send`, `More actions`, etc.) flashing or disabling together on any submit — each action now gets a scoped `livewireTarget` via Filament's `getLivewireClickHandler()` instead of global `wire:loading.attr`.
- Livewire console error `Missing closing parenthesis for method "mountAction"` on flex-textarea playground caused by manually built `wire:target` strings with unescaped JSON in HTML attributes.
- Flex Textarea emoji button icon disappearing on hover in dark mode — generic hover `color` matched the dark `bg-zinc-600` background.

## [2.2.0] - 2026-06-09

### Added

- Shared country registry delivered once per page via `<template id="fff-country-registry-data">` with separate `iso` and `phone` pools.
- `CountryRegistry`, `CountryRegistryQueue`, and unit tests for compact registry payloads.
- Lazy country list loading on first menu open instead of embedding full country arrays in every field instance.
- `createCountrySearchMixin()` with debounced search, diacritic normalization, ranking, and result caching.
- Keyboard navigation for country menus: Arrow Up/Down, Home, End, Enter, `aria-activedescendant`, `aria-posinset`, and `aria-setsize`.
- Focus trap inside open country/phone dropdown menus with focus restore on close.
- Virtual list v2 for long country lists: passive scroll, `requestAnimationFrame` batching, threshold of 50 items, and top/bottom spacers.
- Page-level deduplication of custom country filters via `countryFilterKey` in the shared registry payload.
- **Built-in translations** — `en` and `pl` ship in `resources/lang/` (`default.php`, `countries.php`, `currencies.php`, `timezones.php`) and load automatically via the `filament-flex-fields::` namespace.
- **Publishable translations** — `php artisan vendor:publish --tag=filament-flex-fields-translations` copies language files to `lang/vendor/filament-flex-fields/` for customization (optional; overrides merge on top of bundled strings).
- `VERSION` file and this changelog.

### Changed

- `CountryField` uses the `iso` registry pool; `PhoneField` uses the `phone` registry pool.
- Country and phone blades no longer embed `countries: @js($countries)` in Alpine `x-data`.
- Stylesheet loader now enqueues only the pool required by each field type (`iso` for country, `phone` for phone); both pools are still combined automatically when both field types appear on the same page.
- Playground segment CSS is loaded on `livewire:navigating` only; hover/focus preload was removed to avoid unused preload warnings in DevTools.
- Country dropdown menu text colors in dark mode now use dedicated CSS tokens for readable contrast.

### Fixed

- Empty country dropdown after registry refactor (Alpine getter init order, SPA navigation cache reset, and reliable inline `<template>` delivery).
- Virtual list total height freezing at `0` when getters ran before filtered data existed.

## [2.1.0] - 2026-06-08

### Added

- Initial public release on GitHub and Packagist in the **2.x** line — 61 Filament v5 form components, lazy CSS/JS assets, JSON flex fields (`HasFlexFields`), and the developer Playground.

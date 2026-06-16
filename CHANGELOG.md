# Changelog

All notable changes to `filament-flex-fields` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.6.0] - 2026-06-16

### Added

- **`BarcodeScannerField`** — FlexTextInput shell with Filament modal scanner, manual entry fallback, multi-format support (QR, EAN, UPC, Code 128/39, ITF, PDF417, Data Matrix), torch toggle, continuous scanning, success sound, auto-submit, and EAN/UPC checksum validation.
- **Hybrid scan engine** — native `BarcodeDetector` when supported (Chrome/Edge on desktop), automatic **ZXing** fallback elsewhere (including mobile); engine badge shown in the viewport.
- **Barcode configuration** — chainable `formats()`, `continuous()`, `beepOnScan()` (default on), `autoSubmit()`, `cameraFacing()`, `preferredDeviceId()`, `allowCameraSwitch()`, `scanDelay()`, `scanInterval()`, `decodeFps()`, `pauseWhenHidden()`, `storeDetectedFormat()`, `allowManualInput()`, `scanButtonLabel()`, `modalHeading()`, `validateChecksum()`, and `barcodeRule()`.
- **Barcode scanner UX** — toolbar under the preview with **switch camera** and **torch**; decode pauses while the browser tab is hidden (battery-friendly); optional structured state `{ value, format }` from camera symbology.
- **Mobile camera pipeline** — iOS/WebKit video portal (body-mounted preview + synced overlay) so `<video srcObject>` renders inside the Filament modal; camera starts after the modal transition completes.
- **Scan feedback** — rounded scan frame, animated scan line, success flash; bundled MP3 on desktop; **transient Web Audio beep** on mobile (no lock-screen media player).
- **Lazy ZXing assets** — `@zxing/browser` loaded via dynamic import in a separate JS chunk; field CSS/JS lazy-loaded per instance.
- **Playground** — `barcode-scanner-field` page (default, EAN-only, continuous, checksum, scan-only).
- **Documentation** — [docs/barcode-scanner-field.md](docs/barcode-scanner-field.md); entries in [README.md](README.md) (Text & input), [docs/index.md](docs/index.md), and [COMPONENTS.md](COMPONENTS.md).
- **Tests** — PHP unit/feature tests, JS unit tests for barcode validation and engine helpers, Playwright smoke test for barcode scanner playground.
- **i18n** — English and Polish strings under `barcode_scanner.*`.

### Changed

- **Scanner modal** — native `<x-filament::modal>` (teleported, Filament transitions, close button); spacing below modal description before the preview.
- **Mobile camera switch** — toggles `facingMode` (`environment` ↔ `user`) instead of cycling opaque `deviceId` values (reliable front/back on iPhone).
- **Scan controls layout** — switch camera and torch moved from viewport overlay to a **toolbar below the video** (Filament-style buttons, always clickable).
- **Scan overlay** — minimal Apple-style rounded frame and scan line; compact engine badge in the top-left corner.

### Fixed

- **iOS black camera preview** — video preview no longer stays black inside the teleported Filament modal (body portal + overlay sync).
- **Mobile switch / torch taps** — controls no longer use `$dispatch` from a detached portal (events never reached Alpine); direct `switchCamera()` / `toggleTorch()` handlers.
- **Layout on mobile** — portal position resyncs after layout; camera switch no longer hides the toolbar or leaves a black gap above the preview; toolbar stays below the viewport and is not covered by the fixed video layer.

## [2.5.1] - 2026-06-16

Quality, security, accessibility, and test hardening for **LinkPreviewField** and **ScheduleField** (both introduced in 2.4.5), plus new **`SocialLinksField`**.

### Added

- **`SocialLinksField`** — social profile link editor with platform picker (add-from-dropdown UX), one URL row per platform, built-in brand icons, per-platform hostname validation, and optional custom platforms (`customPlatforms()` with label, hosts, icon SVG).
- **Social links configuration** — `platforms()` whitelist, `excludePlatforms()`, `maxLinks()`, variants/sizes (`primary`, `secondary`, `soft`, `flat`, `ghost`; `sm`/`md`/`lg`), `autoFormatUrls()` (trim + prepend `https://` on blur), and opt-in `reorderable()` (default off).
- **Social links validation** — server rules via `SocialLinkValidator`; client-side mirror blocks submit and syncs row errors to Livewire (`$wire.addError`); empty in-progress rows kept in state for validation, stripped on `dehydrate()`; legacy associative map state normalized on hydrate.
- **Social links SSR** — server-rendered list/toolbar fallback to prevent layout jump before Alpine hydration; teleported platform menu with keyboard navigation and click-outside close.
- **Playground** — `social-links-field` page (default, limited platforms, excluded platforms, custom platforms, reorderable, empty).
- **Documentation** — [docs/social-links-field.md](docs/social-links-field.md); entries in [README.md](README.md) (Text & input), [docs/index.md](docs/index.md), and [COMPONENTS.md](COMPONENTS.md).
- **Tests** — PHP unit/feature tests for `SocialLinksField`; JS unit tests for `social-link-validation` and `social-links-field`; Playwright smoke test for social links playground.
- **Link preview & schedule tests** — comprehensive SSRF unit tests for `UrlMetaScraper`; JS unit tests for `link-preview-field`, `url-meta-scrape`, `schedule-field`, and `schedule-validation`; PHP↔JS contract fixtures for overlap/min/max validation; Livewire feature test (`ScheduleFieldRenderTest`); Playwright playground smoke tests for link preview and schedule field.

### Changed

- **`ScheduleField`** default control size is `sm`; timezone selector renders at `md` independent of field size.
- **Schedule toolbar** — compact add-slot / add-break buttons; copy-to-weekdays uses a confirm dialog before overwriting weekday schedules.
- **`flex-time-segments`** — teleported time picker menu styling; `from`/`to` pickers constrain options so start ≤ end within each slot; time options expose `role="option"` and `aria-selected`.
- **Link preview** — image preload timeout (5s) aligned with scrape timeout constants; `@media (prefers-reduced-motion: reduce)` disables card/shimmer transitions.
- **Documentation** — `SocialLinksField` listed under **Text & input** (with `LinkPreviewField`), not Date & time; COMPONENTS.md TOC reordered accordingly.

### Fixed

- **`ScheduleField::required()`** — `isEmptyState()` now treats schedules with no enabled days containing slots as empty (normalization no longer bypasses required validation).
- **Schedule inline validation** — client overlap/range errors block form submit and sync the first error to Livewire via `$wire.addError`.
- **Link preview client scrape** — `isScrapeCandidate()` blocks localhost, loopback, private IPs, and metadata hostnames (matching server-side SSRF rules).
- **Prefixed URL display**, dark-mode card colors, and horizontal skeleton width (carried from 2.4.5 polish).
- **`SocialLinksField` UI** — uniform icon sizing in picker/list, dark-mode legibility on add trigger/button, circular remove/reorder buttons with default gray background, no layout jump on reload (SSR + hydration overlay).

### Security

- **SSRF** — expanded `UrlMetaScraper::isScrapableUrl()` test coverage for localhost, private/link-local IPs, `.local`/`.internal` hostnames, and cloud metadata endpoints; redirect targets re-validated on each hop.
- **Rate limit** — `link_preview.rate_limit_per_minute` default changed from `0` (disabled) to `30` per user per minute.
- **OG images** — `normalizeImageUrl()` rejects `data:` URIs; only `http://` and `https://` images are returned.

## [2.4.5] - 2026-06-16

### Added

- **`ScheduleField`** — weekly schedule editor with day toggles, from/to time slots, add break, copy-to-weekdays, optional timezone selector, and overlap validation.
- **Playground** — `schedule-field` page with variants, sizes, weekdays-only, and split-shift demos.
- **Documentation** — [docs/schedule-field.md](docs/schedule-field.md) and `ScheduleField` section in [COMPONENTS.md](COMPONENTS.md).
- **Tests** — PHP unit tests for validation, API, and config; JS unit tests for schedule overlap validation helpers.

- **`LinkPreviewField`** — URL input with live Open Graph preview cards on the FlexTextInput shell (`primary`, `secondary`, `soft`, `flat`, `ghost` variants; prefix/suffix affixes).
- **Three preview layouts** — `horizontal` (compact row, square edge-to-edge thumb), `vertical` (narrow card), and `card` (full-width social card, max 500px).
- **Server-side URL meta scraper** — `UrlMetaScraper` with SSRF-safe fetching (stream until `</head>`, redirect handling, server cache), `GET /flex-fields/url-meta/scrape` route, and configurable cache TTL / rate limit.
- **Client-side preview pipeline** — debounced scrape, in-memory cache, in-flight deduplication, abort on type, skeleton shimmer, image preload, and minimum skeleton duration on page load (`previewMinSkeletonMs`, default 500ms).
- **Optional SSR initial preview** — `resolveInitialPreviewOnServer()` (default `true`) hydrates prefilled URLs on first render; disable to avoid server scrape on mount.
- **`showVisitLink()`** — toggles domain as link vs plain text; visit icon uses Gravity `paperclip` by default (`visitIcon()` / `visitLabel()` overridable).
- **Playground** — `link-preview-field` page with stacked layout demos.
- **Documentation** — [docs/link-preview-field.md](docs/link-preview-field.md) and `LinkPreviewField` section in [COMPONENTS.md](COMPONENTS.md).
- **Tests** — PHP unit/feature coverage (`LinkPreviewFieldTest`, `UrlMetaScraperTest`, `UrlMetaScrapeControllerTest`) and JS unit tests for `url-meta-scrape.js` (prefix resolution, skeleton timing, visibility helpers).

### Changed

- Link preview stylesheets declare `flex-text-input` as a lazy dependency — input shell CSS is not duplicated in the preview bundle.
- Horizontal preview cards use full available width up to 500px; thumb is 1:1 and flush to the card edge.

### Fixed

- Prefixed URL fields no longer show duplicate `https://` in the input (display value strips the prefix; state stores the full URL).
- Card layout text colors in dark mode use FlexTextInput theme tokens instead of hardcoded light-mode fallbacks.
- Preview skeleton on horizontal / soft + prefix layouts stretches to the card’s max width instead of shrinking to content.

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

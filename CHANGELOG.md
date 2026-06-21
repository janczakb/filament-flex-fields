# Changelog

All notable changes to `filament-flex-fields` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.7.2] - 2026-06-21

### Fixed

- **FlexFileUpload / FlexImageUpload** — fixed an issue where uploaded files were left in the Livewire temporary directory and not moved to the final disk because Filament's `saveUploadedFiles` and `hydrateFiles` lifecycle hooks were accidentally overwritten by the package's internal file storage hooks (#5).

### Fixed

- **AddressAutocompleteField** — fixed hardcoded Mapbox language to properly fallback to `FLEX_FIELDS_MAPBOX_LANGUAGE` (#4).
- **SegmentControl** — server-rendered `data-segment-selected` / `aria-checked` from `$getState()` before Alpine `x-load` hydrates; CSS pre-hydration pill on the selected segment (`:not(.is-hydrated)`) so the white indicator is visible during `x-load` instead of appearing ~0.3s later.
- **UserSelect / SelectField** — `lazy-alpine-mount` `eager` when SSR trigger is shown; `modulepreload` for `select-field` Alpine chunk; SSR layer uses `display: none` once replaced (was `visibility: hidden`, caused double height in grid).
- **IconPickerField** — SSR SVG preview + label before `x-load`; `lazy-alpine-mount` `eager` when a value is selected; `modulepreload` for `icon-picker-field` chunk; `is-trigger-hydrated` handoff (replaces `x-show` on preview).

### Changed

- **FlexFileUpload / FlexImageUpload upload source tabs** — default segment style is now `default` (filled gray track) instead of `ghost` + `primary` accent; use `uploadSourceTabsVariant('ghost')` and `uploadSourceTabsColor('primary')` for the previous look.
- **FlexFileUpload dropzone icons** — empty-state SVG icons use `--fff-flex-file-upload-accent` (`--primary-500`) via CSS masks instead of hardcoded indigo `#6366f1`.
- **FlexFileUpload compact file list** — remove button defaults to the right; with uploaded files, compact layout switches to stacked flow (gap below dropzone + between file cards) instead of broken absolute positioning.
- **VoiceNoteRecorderField** — red microphone record button no longer uses box-shadow.
- **Conditional critical preload** — `criticalPreloadStylesheets()` emits `teleported-menu` only when `FlexFieldStylesheetQueue::hasQueuedTeleportedMenu()`; empty queue on non-playground pages skips HEAD preloads.
- **Hold confirm assets** — Alpine `modulepreload` moved to per-action `hold-confirm.blade.php` (`@push`); removed global `hold-confirm-action-preload` partial.
- **Lazy Alpine mount** — reusable `<x-filament-flex-fields::lazy-alpine-mount>` with `x-intersect` for heavy fields (date/time, file upload, select/user-select, barcode scanner, icon picker).
- **flex-date-time-field** — calendar grid extracted to `calendar-panel.js` with dynamic `import()` on first calendar open.
- **select-field.js** — trigger-label helpers and patch boot logic split into `components/select-field/` modules.
- **UserSelect** — thin Filament class (**46 lines**) + traits `ConfiguresUserSelectColumns`, `ConfiguresUserSelectModel`, `InteractsWithUserSelectPresentation`, `HasUserSelectCollaborators`; services in `UserSelect/*`.
- **flex-file-upload `pond-bridge`** — split into `pond-component-resolver`, `pond-upload-state`, `pond-server-sync`, `pond-file-status` (entry **21 lines**).
- **CSS entries** — all **54** lazy bundles use `utilities-baseline.css` + `base.css` (was Tailwind×54 duplicate imports).
- **InteractsWithFlexFileUpload** — split into `FlexFileUploadSecurity`, `FlexFileUploadStorage`, `FlexFileUploadDisplay` traits; composer trait ~25 lines.
- **flex-date-time-field.js** — further split with lazy `time-panel.js`; main entry ~476 lines.
- **PHPStan** — `Translations::get()` for type-safe `__()`; baseline regenerated (~385 entries); `composer analyse` in CI; `scripts/prune-phpstan-baseline.mjs` for stale-entry cleanup.
- **CI E2E** — always-on unified fixture smoke (`field-smoke`: select coordinator + file-upload + schedule).
- **Docs** — `COMPONENTS.md`, `README.md`, `DEVELOPMENT.md` §3/§20, `docs/shared-concepts.md`, `docs/hold-confirm-action.md` synced with conditional preload, hold-confirm per-action, alpine entry-only, lazy mount.

### Added

- **`Support/Translations.php`** — typed translation helper for PHPStan level 8.
- **`resources/css/utilities-baseline.css`** — shared `@theme` / `@utility` baseline; imported by all **54** lazy CSS entries.
- **`tests/js/flex-file-upload.test.mjs`** — unit tests for file-upload module factories.
- **`tests/e2e/field-smoke.{spec.mjs,html,harness.mjs}`** — fixture smoke without playground URL.
- **`scripts/audit-components.mjs`** + **`npm run audit:components`** — per-component matrix (blade ↔ lazy CSS ↔ dist ↔ Alpine manifest ↔ PHP/JS line budgets); exits non-zero on critical asset gaps.

- **`FlexFieldAlpineManifestArchTest`** — guards LAZY stylesheet ↔ alpine manifest ↔ dist entry alignment.
- **`docs/COMPATIBILITY.md`** — Filament ^5.0, PHP 8.3+, CI link.
- **`scripts/compare-bundle-metrics.mjs`** — prints gzip deltas vs committed `bundle-metrics.json`.

## [2.7.0] - 2026-06-18

### Added

- **`IconPickerField`** — Searchable **blade-icons** picker with SelectField-style teleported dropdown, lazy SVG previews, paginated server search, set-filter pills, whitelist/exclude controls, and virtual scrolling for large catalogs. Stores the full icon name (`heroicon-o-star`, `gravityui-star`, etc.).
- **`IconColumn`** — Read-only table column for blade-icons values stored by `IconPickerField`; optional label, color, size, and per-request render cache. Playground demo and `docs/iconcolumn.md`.
- **Icon catalog pipeline** — `IconCatalogResolver`, `IconCatalogIndex`, and ranked search with server-side caching; optional bundled manifest via `php artisan fff:icons:manifest` for cold-start performance.
- **Icon picker configuration** — `sets()`, `icons()`, `excludeIcons()`, `searchResultsLayout()` (`grid` / `list` / `icons`), `gridColumns()`, `closeOnSelect()`, `preload()`, `limitPerSet()`, `perPage()`, plus shared `SelectField` shell options (`variant`, `size`, `clearable`, affixes).
- **Icon picker performance** — viewport SVG batching (`IntersectionObserver`), client search LRU cache, next-page prefetch, and a DOM virtual window for long result sets.
- **Icon picker UX** — combobox keyboard navigation, debounced search with highlight, horizontal scroll for set pills, full-width results divider, and skeleton states while icons load.
- **`FieldType::IconPicker`** — FlexField form-builder support via `IconPickerFieldConfigurator`.
- **Playground** — `icon-picker-field` page with Heroicons-only, Gravity-only, multi-set pill filter, grid/whitelist, size, variant, and clearable demos.
- **Documentation** — [docs/icon-picker-field.md](docs/icon-picker-field.md) (full API, setup, config, performance); navigation entry under **Choice & Selection** in [docs.json](docs.json) and [docs/index.md](docs/index.md).
- **Tests** — PHP unit/feature tests (`IconPickerFieldTest`, `IconCatalogResolverTest`, `IconPickerFieldRenderTest`, SVG batch limits); JS tests for virtual window, SVG loader, search cache, keyboard highlight, and panel lifecycle.

### Changed

- **Playground bundle dedupe** — `FlexFieldStylesheetQueue::suppressForPlaygroundBundle()` prevents duplicate lazy CSS when a per-slug playground bundle already includes component stylesheets.
- **Critical preload scoping** — `criticalPreloadStylesheets()` skips global preloads on bundled playground slugs and filters preloads to stylesheets the active slug actually uses (fixes unused `flex-text-input` preload warnings on icon-picker pages).
- **Icon picker panel lifecycle** — reopen/refresh no longer calls Alpine `$watch` cleanup (magic `$watch` does not return an unwatcher); pending-flag + `panelReady` watcher handles scroll/SVG resync safely.

### Fixed

- **Icon picker reopen** — empty or frozen results after close/reopen; scroll position preserved on pagination (`preserveScroll`); virtual window getters stay in sync during scroll.
- **Duplicate icon-picker CSS on playground** — bundle + lazy `icon-picker-field.css` no longer both apply conflicting `.fff-icon-picker__toolbar` rules.
- **Icon picker set pills** — symmetric spacing above/below pills; full-width separator; single-row horizontal scroll for many sets.

## [2.6.1] - 2026-06-17

### Added

- **Asset Injector V2 (SPA/Modal Asset Pipeline)** — Re-architected lazy CSS and Alpine chunk loading. Logic extracted into a dedicated, testable `flex-field-asset-injector.js` module, built to `resources/dist/` via `scripts/build-asset-injector-js.mjs`, and registered as a Filament JS asset.
- **Shared asset queue trait** — `FlexFieldAssetQueue` centralizes `enqueued` / `emitted` / `pending()` bookkeeping for `FlexFieldStylesheetQueue` and `FlexFieldAlpineQueue`.
- **Critical stylesheet preloads** — `critical-stylesheet-preloads` partial preloads high-priority flex-field CSS at `HEAD_END` to shorten first-paint latency.
- **FOUC prevention in modals** — `Livewire.hook('morph.updating')` scans unattached morph fragments (`toEl`) for `data-fff-asset-batch` markers (stylesheets and Alpine chunks), then applies `fff-flex-fields-assets-pending` to the **live** DOM target (`el` or its `.fi-modal` ancestor) before morph completes. While assets load, modals show a **skeleton shimmer** over `.fi-modal-content` (shell and backdrop stay visible) instead of hiding the entire window.
- **Background asset preloading** — `preloadBatchesIn()` warms lazy CSS/JS from hidden `data-fff-asset-batch` markers without removing them. Preload runs on `requestIdleCallback` after page load / SPA navigation and on debounced hover over action triggers (`button`, `a`, `[wire:click]`), so modal fields are often styled before the user clicks.
- **Alpine chunk loading in morph batches** — Asset batches now carry `data-fff-chunks`; the injector loads missing `modulepreload` chunks alongside stylesheets.
- **In-flight promise cache** — `inflightRequests` Map deduplicates concurrent downloads; parallel Livewire requests for the same asset share one network request.
- **URL normalization** — Native `URL` constructor normalizes relative, absolute, and mismatched-protocol hrefs before deduplication and load checks.
- **Protected asset links** — Stylesheets and chunks tagged with `data-fff-stylesheet`, `data-fff-alpine-chunk`, or `data-fff-playground-bundle` are never removed during injector dedupe.
- **Injector optimizations** — O(1) `Map` indices for loaded stylesheets/chunks; `link.sheet` check before treating a DOM link as loaded; `Promise.allSettled` so 404s do not block remaining assets; dedupe removes duplicate injected links instead of re-appending them.
- **Playground page stylesheets** — `playground-page-stylesheets` partial pushes per-slug playground bundles via `@push('styles')` with protected `data-fff-playground-bundle` markers.
- **JavaScript test suite** — Unit tests in `tests/js/flex-field-asset-injector.test.mjs` cover URL normalization, in-flight dedupe, morph `el`/`toEl` pending state, conditional `prepareModal`, background preload, protected links, and failed-load recovery.
- **Webcam & URL file import** — New auxiliary upload sources for `flex-file-upload`, opt-in via `allowWebcamUpload()` and `allowUrlUpload()`. Webcam capture supports front/back camera toggle and torch where available; URL import fetches remote images into native `File` objects injected into FilePond, preserving Filament image manipulation hooks and `multiple()` support. Playground demos and [docs/flexfileupload-and-fleximageupload.md](docs/flexfileupload-and-fleximageupload.md) updated.
- **PHP tests** — `FlexFileUploadSourcesTest` feature coverage for webcam/URL sources, staged previews, SSRF rejection, and disabled-source guards.

### Changed

- **`load-stylesheet` always emits** — Field registration immediately includes `emit-assets` (no longer deferred-only), so lazy CSS is not lost on full-page renders.
- **`emit-assets` dual emit path** — Full-page requests push `<link>` tags via `@push('styles')`; Livewire partial updates emit inline `<link>` / `modulepreload` tags for the injector to process.
- **`queued-stylesheets` injector** — Flushes `pending()` stylesheet and chunk queues at `STYLES_AFTER` and `BODY_END` hook points.
- **Morph pending resolution** — `beginPendingMorph({ el, toEl })` scans batches on `toEl` but resolves pending targets from live `el`, keeping modal state attached to the real DOM node through morph.
- **Modal open pending is conditional** — `prepareModal` (`x-modal-opened`) applies skeleton pending only when `rootNeedsAssetLoading()` is true; cached assets open instantly with no pending flash.
- **Teleported menu z-index in modals** — `teleported-menu.css` raises dropdown stacking when a Filament modal is open (`:has(.fi-modal.fi-modal-open)`).

### Fixed

- **Global CSS regression after V2** — Lazy stylesheets load again on full-page Filament views; V2 had registered assets without emitting them until `BODY_END`.
- **Playground missing CSS** — Playground bundle links are protected from injector dedupe; per-slug stylesheets push into the layout `styles` stack.
- **Modal stuck hidden after morph** — Pending state now tracks the live modal (`el`) instead of detached `toEl`, so `morph.updated` reliably releases `fff-flex-fields-assets-pending`.
- **Teleported menu behind modal** — Country/phone/timezone dropdowns render above open modals instead of underneath the overlay.
- **Upload source panel layout gap** — Inactive webcam/URL panels are removed from document flow (`position: absolute`) so switching tabs does not leave empty vertical space above FilePond.
- **Recursive stylesheet dependency resolution** — Depth-first traversal in `FlexFieldAssets::stylesheetsFor()` resolves deeply nested CSS dependencies in correct bottom-up cascade order (e.g. `schedule-field` → `timezone-field` → `flex-time-segments`).

### Security

- **URL import SSRF guard** — Remote file import rejects unsafe URLs (localhost, private IPs, metadata endpoints) before fetch, matching server-side scrape safety patterns.

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

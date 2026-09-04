---
title: "SRE runbook"
---

# SRE runbook

Short operational checklist for Flex Fields asset loading, injector health, and select regressions. Intended for maintainers and advanced self-hosted deployments.

---

## Asset publish

After every package update or dist rebuild:

```bash
composer update janczakb/filament-flex-fields
php artisan filament:assets
```

Automate in `composer.json` post-autoload when possible so CI and local installs stay in sync. If styles look stale, confirm `public/` (or your Filament asset path) contains fresh `janczakb/filament-flex-fields` files and that config cache was cleared.

---

## Injector debug

Lazy CSS and Alpine chunks load through `flex-field-asset-injector.js`. When assets fail after SPA navigation or nested modals:

1. Open DevTools → Network and filter `filament-flex-fields`.
2. Confirm `data-fff-asset-batch` markers appear on morph payloads.
3. Check for duplicate `<link rel="stylesheet">` tags with the same href.
4. Verify protected links (`data-fff-stylesheet`, `data-fff-alpine-chunk`, `data-fff-playground-bundle`) are not removed during dedupe.
5. On playground pages, ensure per-slug bundles load once via `data-fff-playground-bundle`.

Common fixes: hard refresh, re-run `filament:assets`, disable aggressive CDN HTML minifiers that strip data attributes.

---

## Asset Inspector

Use the dev-time Asset Inspector (`flex-field-asset-inspector.js`) to list loaded Flex Fields URLs and detect duplicate hrefs / Alpine chunks after SPA navigation:

```javascript
const inspector = FffAssetInspector?.create?.({ getLoadedUrls: () => [...] })
inspector?.inspect() // { urls, duplicates }
```

**Pass criteria:** zero duplicates after full page load, modal open/close, and `wire:navigate` between two resource pages that use different flex fields. Shared chunks (`flex-fields-select-menu-*`, `flex-fields-search-normalize-*`) and `teleported-menu` CSS must appear once even when select / phone / country / timezone / currency / tags share a page.

Playground: skeleton demo (`localStorage.fff-skeleton-demo = 1` on any flex-fields-playground page).

---

## Headless Select attach

Production SelectField uses the headless combobox Alpine entry (`fffHeadlessSelectField`). Successful init sets `data-fff-select-attached="true"` on `.fff-select-field__shell`.

E2E / SRE checks:

1. Wait for `.fff-select-field__shell[data-fff-select-attached="true"]` before opening menus.
2. Attached shell count should equal total `.fff-select-field__shell` count on the page.
3. Missing attribute after load ⇒ Alpine chunk failed to register or headless init aborted (check Network for `select-field` / select-menu chunks).

Helper: `waitForSelectCoordinatorAttached()` in `tests/e2e/helpers/console-errors.mjs`.

---

## Enterprise observability

When `filament-flex-fields.enterprise.enabled` is true (default), PHP listeners registered via `ObservabilityHooks::on()` receive:

| Event | Fired from |
|-------|------------|
| `field.mount` | `SelectField` `afterStateHydrated` |
| `select.search` | `SelectField::getSearchResults*` cache miss |
| `upload.success` | FlexFileUpload disk persist + Spatie adapter success |
| `upload.fail` | Virus-scan reject (pre/post persist), persist failure, Spatie persist failure |

### Media Capture retention

Schedule (when `media_capture.retention.schedule_enabled=true`):

```bash
php artisan flex-fields:prune-capture-media
php artisan flex-fields:prune-capture-media --category=temp_captures --dry-run
```

Key env vars: `FLEX_FIELDS_REQUIRE_VIRUS_SCAN`, `FLEX_FIELDS_QUARANTINE_DISK`, `FLEX_FIELDS_RETENTION_*`, `FLEX_FIELDS_MEDIA_TENANT_*`.

See [Media & Capture OS](/docs/media-capture-os).

Overlay exclusive open cannot reach PHP; the runtime dispatches a browser CustomEvent instead:

```javascript
window.addEventListener('fff:observability', (e) => {
  // e.detail.event — 'overlay.open' | 'select.search' (client Livewire fetch)
  // e.detail.id / e.detail.query / …
})
```

Set `FLEX_FIELDS_ENTERPRISE_ENABLED=false` to no-op PHP `ObservabilityHooks::emit` / `record`. Browser events still fire for client tooling.

---

## Select race checklist

Run after any SelectField or teleported-menu change:

- [ ] Headless shells expose `data-fff-select-attached="true"` after first paint.
- [ ] Open dropdown → select option → close → reopen: menu opens cleanly (no stuck overlay).
- [ ] Multi-select checklist: selected rows stay visible with checkmarks; menu does not flicker on scroll.
- [ ] Grid layout: selected badge keeps solid circular background in portaled panel.
- [ ] Phone / country / timezone / currency pickers: scrolling inside menu does not replay open animation.
- [ ] Modal action with select: assets retained while modal open; no FOUC on first open.
- [ ] Console: no errors from select coordinator attach/detach.

Playwright: `tests/e2e/playground-select.spec.mjs` and `field-smoke.spec.mjs` when `FLEX_FIELDS_PLAYGROUND_URL` is set.

---

## FFART asset runtime checklist

Run after any change to `flex-field-asset-injector.js`, `flex-field-consumer-graph.js`, `emit-assets`, or modal/slide-over asset hooks:

- [ ] `npm run test:consumer-graph` — logic + property + chaos + inspector (80+ unit tests).
- [ ] No duplicate `link[href*="filament-flex-fields"]` in `<head>` for the same normalized URL (Inspector **I1** / **I33**).
- [ ] `FffAssetInspector.inspect().legacySymbols` is empty (**I34** — no `pageRetainedUrls` / `modalOwnedUrls`).
- [ ] Page field + modal field same `livewireKey` → shared URL `refCount ≥ 1` after modal close (**REQ-3** / **I5**).
- [ ] Modal-only CSS/chunks uninstalled after modal close (**REQ-4** / **I6**).
- [ ] Nested modals: LIFO stack pop only — parent assets survive child open/close (**I7**).
- [ ] `wire:navigate` / Livewire tab swap: page batches resync, modal leftovers gone (**I8**).
- [ ] Slide-over (`.fi-modal-slide-over`) uses the same modal stack as centered modals (**REQ-5** / **I9**).

### Browser inspector (dev)

With playground or admin panel open:

```javascript
FffAssetInspector.inspect()
```

Check `failingInvariants` is `[]`, `duplicates` is `[]`, and `performanceMarks` includes `fff:load` after opening a heavy field.

### E2E (requires `FLEX_FIELDS_PLAYGROUND_URL`)

| Spec | Requirement |
|------|-------------|
| `asset-page-modal-select.spec.mjs` | REQ-3 |
| `asset-modal-only-uninstall.spec.mjs` | REQ-4 |
| `asset-network-dedup.spec.mjs` | REQ-1 |
| `asset-action-select-slideover.spec.mjs` | REQ-5 |
| `asset-action-fillform-select.spec.mjs` | fillForm retain |

---

## Component audit WARN policy

`npm run audit:components` reports three statuses per component:

| Status | Meaning | Release gate |
|--------|---------|--------------|
| **OK** | Within PHP/JS line budgets and asset wiring complete | Required |
| **WARN** | Asset wiring OK but PHP or JS entry exceeds monolith line budget (500 PHP / 400 JS) | Allowed for v3 heavy fields (SelectField, FlexRichEditor, SignatureField) — track in 3.2 split backlog |
| **FAIL** | Missing dist CSS/JS, broken Alpine manifest, or critical asset gap | Blocks release |

WARN rows do **not** fail CI or `QualityGates::releaseChecklist()`. Treat them as refactor signals: prefer splitting Alpine mixins and PHP concerns before adding features to monolith entries. Re-run the audit after JS/CSS splits to confirm status moves to OK.

---

## Export asset registry

For CI audits and release gates:

```bash
php artisan fff:assets:export-registry
```

Output: `resources/dist/asset-registry.json`. Commit after changing `FlexFieldAssets` lazy lists, stylesheet dependencies, or playground aliases. Release checklist: see `QualityGates::releaseChecklist()` in package source.

---

## Escalation

File reproducible steps at [GitHub Issues](https://github.com/janczakb/filament-flex-fields/issues). Include Filament version, browser, whether SPA navigation is enabled, and a HAR or screenshot of duplicate stylesheet loads when asset-related.

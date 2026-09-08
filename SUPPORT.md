# Support & compatibility policy

Filament Flex Fields is tested as a **premium Filament v5 plugin**. This document is the maintainer contract for upstream Filament changes.

## Supported versions

| Dependency | Supported range | CI coverage |
|------------|-----------------|-------------|
| PHP | `^8.3` | 8.3, 8.4, 8.5 |
| Filament | `^5.0` | **5.7.8** (regression floor before `schema(Schema\|…)`) and **^5.8** (current stable line) |
| Filament canary | `5.x-dev` | Weekly / on push — **non-blocking** (`continue-on-error`) |

We aim to keep the plugin green on the **latest Filament 5.x stable** within **1–2 business days** of a breaking upstream release (Dependabot + scheduled `filament-compat` + override signature guards).

## How we catch Filament breaks (class of [#61](https://github.com/janczakb/filament-flex-fields/issues/61))

1. **`composer.lock` is committed** for reproducible CI. It remains `export-ignore` in `.gitattributes` so Composer dist installs for customers stay unconstrained by our lock.
2. **`FilamentOverrideCompatibilityTest`** — reflection LSP checks on critical overrides (`schema()`, Select, RichEditor, …).
3. **`filament-compat` matrix** — Pest compatibility suites on PHP × Filament axes.
4. **`filament-dev` canary** — installs `filament/filament:5.x-dev` to surface breaks before a tag.
5. **Dependabot** groups `filament/*` (weekly).

## Release gate (required checks)

On `main` / release tags, require at least:

- `quality (PHP 8.3)`
- `Filament 5.8.x · PHP 8.3` (or the current `filament-compat` job name for `^5.8` on 8.3)

Optional but recommended: all `quality` PHP axes and both Filament stable matrix legs.

Configure under **Settings → Branches → Branch protection rules → Require status checks**.

## Local commands

```bash
composer install
composer test:filament-compat
```

To simulate CI Filament switch:

```bash
composer require "filament/filament:^5.8" --no-update
composer update "filament/*" --with-all-dependencies
composer test:filament-compat
```

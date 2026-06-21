# Compatibility

## Requirements

| Dependency | Supported |
|------------|-----------|
| PHP | 8.3+ |
| Laravel | 12.x (as used by the host monorepo) |
| Filament | ^5.0 |

## Tested stack

CI runs against:

- PHP 8.3
- Node.js 22
- Pest (package test suite)
- PHPStan (`composer analyse`)
- Playwright field-smoke E2E (`field-smoke.spec.mjs` — select coordinator, file-upload, schedule; no live playground URL required)

Playground E2E specs (`playground-*.spec.mjs`) are available locally when `FLEX_FIELDS_PLAYGROUND_URL` points at a running Filament panel with the playground enabled.

## CI

GitHub Actions workflow: [filament-flex-fields-tests.yml](https://github.com/janczakb/wyachts-super-app/blob/main/.github/workflows/filament-flex-fields-tests.yml)

The workflow builds dist assets, runs Pint, Pest, PHPStan, JavaScript unit tests, bundle budget checks, and field-smoke Playwright tests on every change under `packages/filament-flex-fields/`.

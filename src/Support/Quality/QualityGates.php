<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support\Quality;

/**
 * Release-quality gates for Flex Fields v3 (CI, Playwright hubs, bundle budgets).
 */
final class QualityGates
{
    /**
     * Playground hub ids that must have a matching Playwright E2E spec
     * (`tests/e2e/playground-{hub}.spec.mjs`). Specs skip when
     * `FLEX_FIELDS_PLAYGROUND_URL` is unset.
     *
     * @return list<string>
     */
    public static function requiredPlaywrightHubs(): array
    {
        return [
            'select',
            'flex-rich-editor',
            'calculator-field',
            'skeleton-injector',
            'barcode-scanner',
            'social-links-field',
            'schedule-field',
            'link-preview',
            'geo',
            'v3-hubs',
        ];
    }

    /**
     * Minimum share of tracked JS/CSS chunks that must pass bundle budget checks.
     */
    public static function bundleBudgetCoverageTarget(): float
    {
        return 0.8;
    }

    /**
     * Human-readable release train checklist for maintainers before tagging v3.
     *
     * @return list<string>
     */
    public static function releaseChecklist(): array
    {
        return [
            'Rebuild dist assets (`npm run build`) and confirm dirty-dist gate is clean.',
            'Run Pest, PHPStan, and JavaScript unit tests.',
            'Pass bundle budget checks at or above the coverage target (≥80%).',
            'Run Playwright field-smoke (always-on in CI) and required playground hub E2E specs when FLEX_FIELDS_PLAYGROUND_URL is configured.',
            'Export the asset registry (`php artisan fff:assets:export-registry`).',
            'Run select close↔reopen race regression checklist (see docs/sre-runbook.md).',
            'Update CHANGELOG and customer-facing upgrade guide (docs/upgrade-v2-to-v3.md).',
            'Smoke-test `php artisan filament:assets` on a host panel with playground enabled.',
        ];
    }
}

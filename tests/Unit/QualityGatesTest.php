<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;
use Bjanczak\FilamentFlexFields\Support\Playground\ShowreelMode;
use Bjanczak\FilamentFlexFields\Support\Quality\QualityGates;

it('lists required playwright hub groups for v3 quality gates', function (): void {
    expect(QualityGates::requiredPlaywrightHubs())->toBe([
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
    ]);
});

it('requires a playground e2e spec file for every quality-gate hub', function (): void {
    $e2eDir = dirname(__DIR__).'/e2e';

    foreach (QualityGates::requiredPlaywrightHubs() as $hub) {
        $spec = $e2eDir.'/playground-'.$hub.'.spec.mjs';

        expect($spec)
            ->toBeFile("Missing Playwright E2E spec for required hub [{$hub}] (expected playground-{$hub}.spec.mjs).");
    }
});

it('targets eighty percent bundle budget coverage', function (): void {
    expect(QualityGates::bundleBudgetCoverageTarget())->toBe(0.8);
});

it('documents a non-empty release checklist', function (): void {
    $checklist = QualityGates::releaseChecklist();

    expect($checklist)->toBeArray()
        ->not->toBeEmpty()
        ->and($checklist)->each->toBeString()
        ->and(implode("\n", $checklist))->toContain('fff:assets:export-registry')
        ->and(implode("\n", $checklist))->toContain('audit:components')
        ->and(implode("\n", $checklist))->toContain('prune:phpstan-baseline');
});

it('requires a feature render test file for every quality-gate render spec', function (): void {
    $featureDir = dirname(__DIR__).'/Feature';

    foreach (QualityGates::requiredFeatureRenderTests() as $filename) {
        $path = $featureDir.'/'.$filename;

        expect($path)
            ->toBeFile("Missing feature render test [{$filename}] required by QualityGates.");
    }
});

it('includes calculator field render coverage in required feature render tests', function (): void {
    expect(QualityGates::requiredFeatureRenderTests())->toContain('CalculatorFieldRenderTest.php');
});

it('defines v3 upgrade spot-check hub order', function (): void {
    expect(ShowreelMode::hubOrder())->toBe([
        'schema-conditions',
        'field-intelligence',
        'composition-recipes',
        'select-field',
        'phone-field',
        'country-field',
        'date-time-fields',
        'schedule-field',
        'barcode-scanner-field',
        'choice-cards',
        'segment-tabs',
        'form-layouts',
        'admin-columns',
        'hold-confirm',
        'user-column',
    ]);
});

it('allocates dwell time helpers for upgrade hub checklist', function (): void {
    expect(ShowreelMode::tourDurationSeconds())->toBe(60)
        ->and(ShowreelMode::secondsPerHub())->toBe(4)
        ->and(ShowreelMode::secondsPerHub() * count(ShowreelMode::hubOrder()))->toBeLessThanOrEqual(ShowreelMode::tourDurationSeconds());
});

it('keeps v3 upgrade checklist slugs registered in the playground', function (): void {
    $definitions = FlexFieldsPlaygroundRegistry::definitions();

    foreach (ShowreelMode::hubOrder() as $slug) {
        expect(array_key_exists($slug, $definitions))
            ->toBeTrue("V3 upgrade checklist slug [{$slug}] must exist in playground registry.");
    }
});

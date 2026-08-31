<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\Playground\ShowreelMode;
use Bjanczak\FilamentFlexFields\Support\Upgrade\V3AutoUpgrade;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    $marker = V3AutoUpgrade::markerPath();

    if (is_file($marker)) {
        unlink($marker);
    }

    config()->set('filament-flex-fields.v3.auto_upgrade', true);
    config()->set('filament-flex-fields.v3.migrated', false);
});

afterEach(function (): void {
    $marker = V3AutoUpgrade::markerPath();

    if (is_file($marker)) {
        unlink($marker);
    }

    $commandMarker = storage_path('app/fff-v3-upgrade.json');

    if (is_file($commandMarker)) {
        unlink($commandMarker);
    }
});

it('runs v3 auto upgrade once and marks migrated config', function (): void {
    expect(V3AutoUpgrade::hasCompleted())->toBeFalse();

    V3AutoUpgrade::ensure();

    expect(V3AutoUpgrade::hasCompleted())->toBeTrue()
        ->and(config('filament-flex-fields.v3.migrated'))->toBeTrue()
        ->and(config('filament-flex-fields.intelligence.formulas'))->toBeTrue()
        ->and(is_file(FlexFieldAssets::assetRegistryPath()))->toBeTrue()
        ->and(V3AutoUpgrade::shouldApplyHeadlessEngineAutoDefault())->toBeFalse();

    V3AutoUpgrade::ensure();

    expect(V3AutoUpgrade::hasCompleted())->toBeTrue();
});

it('skips auto upgrade when disabled in config', function (): void {
    config()->set('filament-flex-fields.v3.auto_upgrade', false);

    V3AutoUpgrade::ensure();

    expect(V3AutoUpgrade::hasCompleted())->toBeFalse()
        ->and(config('filament-flex-fields.v3.migrated'))->toBeFalse();
});

it('exposes v3 playground hub checklist aligned with showreel order', function (): void {
    expect(V3AutoUpgrade::playgroundHubChecklist())->toBe(ShowreelMode::hubOrder())
        ->and(V3AutoUpgrade::playgroundHubChecklist())->toContain('schedule-field', 'barcode-scanner-field');
});

it('runs fff v3 upgrade command and writes upgrade marker', function (): void {
    Artisan::call('fff:v3:upgrade', ['--dry-run' => true]);

    expect(Artisan::output())->toContain('Dry run')
        ->and(is_file(storage_path('app/fff-v3-upgrade.json')))->toBeFalse();

    Artisan::call('fff:v3:upgrade');

    expect(is_file(storage_path('app/fff-v3-upgrade.json')))->toBeTrue()
        ->and(is_file(FlexFieldAssets::assetRegistryPath()))->toBeTrue()
        ->and(config('filament-flex-fields.v3.migrated'))->toBeTrue();
});

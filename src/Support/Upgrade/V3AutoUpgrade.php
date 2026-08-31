<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support\Upgrade;

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\Playground\ShowreelMode;
use Illuminate\Support\Facades\App;
use Throwable;

final class V3AutoUpgrade
{
    public const MARKER_FILENAME = 'fff-v3-upgraded.json';

    /**
     * Playground hubs introduced or expanded in v3 that hosts should spot-check after upgrade.
     *
     * @return list<string>
     */
    public static function playgroundHubChecklist(): array
    {
        return ShowreelMode::hubOrder();
    }

    public static function markerPath(): string
    {
        return storage_path('app/'.self::MARKER_FILENAME);
    }

    public static function hasCompleted(): bool
    {
        return is_file(self::markerPath());
    }

    public static function ensure(): void
    {
        if (! config('filament-flex-fields.v3.auto_upgrade', true)) {
            return;
        }

        if (! App::runningInConsole() && ! App::environment('local')) {
            return;
        }

        if (self::hasCompleted()) {
            return;
        }

        if (! is_writable(storage_path('app')) && ! is_dir(storage_path('app'))) {
            return;
        }

        try {
            self::exportAssetRegistryIfWritable();
            $runtimeDefaults = self::applyRuntimeDefaults();
            self::writeMarker([
                'upgraded_at' => now()->toIso8601String(),
                'mode' => 'auto',
                'checklist' => [
                    'asset_registry_exported' => is_file(FlexFieldAssets::assetRegistryPath()),
                    'config_v3_migrated' => true,
                    'runtime_defaults' => $runtimeDefaults,
                ],
            ]);

            config(['filament-flex-fields.v3.migrated' => true]);
        } catch (Throwable) {
            // Silent: host storage or dist may be read-only in production.
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function writeMarker(array $payload): void
    {
        $directory = storage_path('app');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(
            self::markerPath(),
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function applyRuntimeDefaults(): array
    {
        $applied = [
            'intelligence.formulas' => true,
            'enterprise.enabled' => config('filament-flex-fields.enterprise.enabled'),
            'select.use_headless_engine' => config('filament-flex-fields.select.use_headless_engine'),
            'select.auto_migrate' => config('filament-flex-fields.select.auto_migrate'),
        ];

        config(['filament-flex-fields.intelligence.formulas' => true]);

        return $applied;
    }

    public static function shouldApplyHeadlessEngineAutoDefault(): bool
    {
        return false;
    }

    private static function headlessEngineExplicitlyConfigured(): bool
    {
        if (env('FLEX_FIELDS_SELECT_USE_HEADLESS_ENGINE') !== null) {
            return true;
        }

        $publishedPath = config_path('filament-flex-fields.php');

        if (! is_file($publishedPath)) {
            return false;
        }

        /** @var array<string, mixed> $published */
        $published = require $publishedPath;
        $select = is_array($published['select'] ?? null) ? $published['select'] : [];

        return array_key_exists('use_headless_engine', $select);
    }

    public static function exportAssetRegistryIfWritable(): void
    {
        $path = FlexFieldAssets::assetRegistryPath();
        $directory = dirname($path);

        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return;
        }

        if (is_file($path) && ! is_writable($path)) {
            return;
        }

        if (! is_writable($directory)) {
            return;
        }

        file_put_contents(
            $path,
            json_encode(FlexFieldAssets::exportRegistry(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }
}

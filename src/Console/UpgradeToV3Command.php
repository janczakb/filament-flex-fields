<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Console;

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\Upgrade\V3AutoUpgrade;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Throwable;

class UpgradeToV3Command extends Command
{
    protected $signature = 'fff:v3:upgrade {--dry-run : Show steps without writing}';

    protected $description = 'Automatically upgrade Flex Fields host app to v3 foundations';

    public function handle(Filesystem $filesystem): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->components->warn('Dry run — no files will be written.');
        }

        $checklist = [];

        $this->components->twoColumnDetail('Step 1', 'Config & assets');
        $checklist['config_guidance'] = $this->ensureConfigGuidance($dryRun);
        $checklist['asset_registry_exported'] = $this->exportAssetRegistry($dryRun);
        $checklist['stale_assets_published'] = $this->publishStaleAssets($filesystem, $dryRun);

        $this->newLine();
        $this->components->twoColumnDetail('Step 2', 'Runtime defaults');
        $checklist['runtime_defaults'] = $this->applyRuntimeDefaults($dryRun);

        $this->newLine();
        $this->components->twoColumnDetail('Step 3', 'Upgrade marker');
        $checklist['marker_written'] = $this->writeUpgradeMarker($checklist, $dryRun);

        $this->newLine();
        $this->components->twoColumnDetail('Step 4', 'Playground hubs to visit');
        $this->outputPlaygroundChecklist();

        if ($dryRun) {
            $this->newLine();
            $this->components->info('Dry run complete. Re-run without --dry-run to apply changes.');
        } else {
            config(['filament-flex-fields.v3.migrated' => true]);
            $this->newLine();
            $this->components->info('Flex Fields v3 upgrade complete.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, bool>
     */
    protected function ensureConfigGuidance(bool $dryRun): array
    {
        $publishedPath = config_path('filament-flex-fields.php');
        $requiredKeys = ['v3', 'intelligence', 'enterprise', 'schema', 'select'];
        $results = [];

        if (! is_file($publishedPath)) {
            $this->components->bulletList([
                'Published config not found at config/filament-flex-fields.php.',
                'Run: php artisan vendor:publish --tag=filament-flex-fields-config',
            ]);

            foreach ($requiredKeys as $key) {
                $results[$key] = false;
            }

            return $results;
        }

        /** @var array<string, mixed> $published */
        $published = require $publishedPath;

        foreach ($requiredKeys as $key) {
            $present = array_key_exists($key, $published);

            if ($present) {
                $this->line("  <fg=green>✓</> config/filament-flex-fields.php includes [{$key}]");
            } else {
                $this->line("  <fg=yellow>!</> Missing [{$key}] — republish config: php artisan vendor:publish --tag=filament-flex-fields-config --force");
            }

            $results[$key] = $present;
        }

        if ($dryRun) {
            $this->line('  (dry run) Would export asset registry and publish stale package assets when writable.');
        }

        return $results;
    }

    protected function exportAssetRegistry(bool $dryRun): bool
    {
        if ($dryRun) {
            $this->line('  (dry run) Would run fff:assets:export-registry');

            return false;
        }

        try {
            $exitCode = $this->call('fff:assets:export-registry');

            return $exitCode === self::SUCCESS && is_file(FlexFieldAssets::assetRegistryPath());
        } catch (Throwable $throwable) {
            $this->components->warn('Asset registry export skipped: '.$throwable->getMessage());

            return false;
        }
    }

    protected function publishStaleAssets(Filesystem $filesystem, bool $dryRun): bool
    {
        if ($dryRun) {
            $this->line('  (dry run) Would publish stale Filament + static package assets');

            return false;
        }

        if (! FlexFieldAssets::shouldPublishStalePackageAssets()) {
            $this->line('  Stale asset publishing skipped (disabled for this environment).');

            return false;
        }

        try {
            FlexFieldAssets::publishStalePackageAssets($filesystem);
            $this->line('  <fg=green>✓</> Published stale package assets when newer dist files exist');

            return true;
        } catch (Throwable $throwable) {
            $this->components->warn('Stale asset publish skipped: '.$throwable->getMessage());

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function applyRuntimeDefaults(bool $dryRun): array
    {
        $applied = [
            'intelligence.formulas' => true,
        ];

        config(['filament-flex-fields.intelligence.formulas' => true]);
        $this->line('  <fg=green>✓</> FormulaEngine always-on (legacy intelligence.formulas flag kept for compat)');

        $enterpriseEnabled = (bool) config('filament-flex-fields.enterprise.enabled', true);
        $this->line(sprintf(
            '  enterprise.enabled=%s (default true on fresh install — set FLEX_FIELDS_ENTERPRISE_ENABLED=false to slim down).',
            $enterpriseEnabled ? 'true' : 'false',
        ));

        $engine = config('filament-flex-fields.select.use_headless_engine', true);
        $this->line(sprintf(
            '  select.use_headless_engine=%s — headless combobox is the v3 default for eligible SelectFields.',
            is_bool($engine) ? ($engine ? 'true' : 'false') : (string) $engine,
        ));

        if ($dryRun) {
            $this->line('  (dry run) Runtime config()->set calls would apply for this process only.');
        }

        return $applied;
    }

    /**
     * @param  array<string, mixed>  $checklist
     */
    protected function writeUpgradeMarker(array $checklist, bool $dryRun): bool
    {
        $markerPath = storage_path('app/fff-v3-upgrade.json');

        if ($dryRun) {
            $this->line("  (dry run) Would write upgrade marker to {$markerPath}");

            return false;
        }

        $payload = [
            'upgraded_at' => now()->toIso8601String(),
            'mode' => 'command',
            'checklist' => $checklist,
        ];

        file_put_contents(
            $markerPath,
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );

        $this->line("  <fg=green>✓</> Wrote {$markerPath}");

        return true;
    }

    protected function outputPlaygroundChecklist(): void
    {
        if (! config('filament-flex-fields.playground.enabled', false)) {
            $this->line('  Playground disabled — enable FLEX_FIELDS_PLAYGROUND=true locally to visit these hubs.');

            return;
        }

        $hubs = V3AutoUpgrade::playgroundHubChecklist();

        $this->components->bulletList(array_map(
            fn (string $slug): string => "/flex-fields-playground/{$slug}",
            $hubs,
        ));

        $this->line('  Open the Flex Fields Playground and spot-check each hub after upgrading.');
    }
}

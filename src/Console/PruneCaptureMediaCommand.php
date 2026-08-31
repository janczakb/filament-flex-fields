<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Console;

use Bjanczak\FilamentFlexFields\Support\Media\CaptureRetentionAuditor;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureTenantDiskResolver;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\Media\SpatieMediaCaptureAdapter;
use Illuminate\Console\Command;

class PruneCaptureMediaCommand extends Command
{
    protected $signature = 'flex-fields:prune-capture-media
                            {--category=* : Retention category (uploads, voice_notes, signatures, temp_captures, spatie)}
                            {--dry-run : List paths/UUIDs without deleting}';

    protected $description = 'Prune expired capture media based on MediaCaptureOs retention policies';

    public function handle(): int
    {
        $categories = $this->option('category');

        if (! is_array($categories) || $categories === []) {
            $categories = ['temp_captures', 'voice_notes', 'uploads', 'signatures'];
        }

        $policies = MediaCaptureOs::retentionPolicies();
        $disk = MediaCaptureTenantDiskResolver::resolveDisk(
            (string) config('filament-flex-fields.media_capture.disk', config('filesystems.default')),
        );
        $directories = config('filament-flex-fields.media_capture.directories', []);
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach ($categories as $category) {
            if ($category === 'spatie') {
                $days = (int) config('filament-flex-fields.media_capture.retention.uploads.days', 0);

                if ($days <= 0) {
                    $this->components->warn('Spatie prune skipped — set media_capture.retention.uploads.days.');

                    continue;
                }

                foreach (config('filament-flex-fields.media_capture.spatie.prune_collections', []) as $collection) {
                    $deleted = SpatieMediaCaptureAdapter::pruneSpatieMedia($days, is_string($collection) ? $collection : null, $dryRun);
                    $total += count($deleted);
                    $this->reportDeleted("spatie:{$collection}", $deleted, $dryRun);
                }

                continue;
            }

            $policy = $policies[$category] ?? null;

            if (! is_array($policy) || ! ($policy['enabled'] ?? false)) {
                $this->line("Skipping [{$category}] — retention disabled.");

                continue;
            }

            $days = (int) ($policy['days'] ?? 0);

            if ($days <= 0) {
                $this->components->warn("Skipping [{$category}] — days not configured.");

                continue;
            }

            $paths = SpatieMediaCaptureAdapter::pruneDiskDirectories(
                $disk,
                is_array($directories[$category] ?? null) ? $directories[$category] : [],
                $days,
                $dryRun,
            );

            $total += count($paths);
            $this->reportDeleted($category, $paths, $dryRun);
        }

        $this->components->info($dryRun
            ? "Dry run complete — {$total} item(s) would be pruned."
            : "Pruned {$total} item(s).");

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $items
     */
    private function reportDeleted(string $category, array $items, bool $dryRun): void
    {
        if ($items === []) {
            return;
        }

        $verb = $dryRun ? 'Would prune' : 'Pruned';
        $this->components->info("{$verb} ".count($items)." [{$category}] item(s).");

        foreach (array_slice($items, 0, 10) as $item) {
            $this->line("  - {$item}");
        }

        if (count($items) > 10) {
            $this->line('  ...');
        }

        CaptureRetentionAuditor::record($category, $items, $dryRun);
    }
}

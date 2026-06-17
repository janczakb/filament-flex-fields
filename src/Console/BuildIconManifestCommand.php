<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Console;

use Bjanczak\FilamentFlexFields\Support\Icons\IconCatalogResolver;
use BladeUI\Icons\Factory;
use Illuminate\Console\Command;

class BuildIconManifestCommand extends Command
{
    protected $signature = 'fff:icons:manifest
                            {--sets=* : Limit manifest to specific blade-icons set names}';

    protected $description = 'Build the blade-icons catalog manifest for IconPickerField cold-start performance';

    public function handle(Factory $factory, IconCatalogResolver $resolver): int
    {
        $sets = $this->option('sets');

        if (! is_array($sets) || $sets === []) {
            $sets = array_keys($factory->all());
        }

        $sets = $resolver->resolveSetNames($sets);
        $catalog = $resolver->buildCatalogFromManifest($sets);

        $path = dirname(__DIR__, 2).'/resources/dist/icon-catalog-manifest.json';
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode($catalog, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $iconCount = array_sum(array_map(
            static fn (array $set): int => count($set['icons'] ?? []),
            $catalog,
        ));

        $this->components->info(sprintf(
            'Wrote %d sets (%d icons) to %s',
            count($catalog),
            $iconCount,
            $path,
        ));

        return self::SUCCESS;
    }
}

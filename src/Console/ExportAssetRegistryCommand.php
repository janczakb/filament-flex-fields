<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Console;

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Illuminate\Console\Command;

class ExportAssetRegistryCommand extends Command
{
    protected $signature = 'fff:assets:export-registry';

    protected $description = 'Export the Flex Fields asset registry JSON for tooling and CI audits';

    public function handle(): int
    {
        $registry = FlexFieldAssets::exportRegistry();
        $path = FlexFieldAssets::assetRegistryPath();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(
            $path,
            json_encode($registry, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );

        $this->components->info(sprintf(
            'Wrote asset registry (%d lazy stylesheets) to %s',
            count($registry['lazy_stylesheets']),
            $path,
        ));

        return self::SUCCESS;
    }
}

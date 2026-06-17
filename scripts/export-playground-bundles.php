<?php

declare(strict_types=1);

/**
 * Outputs JSON map of playground slug => component stylesheets for bundle builds.
 *
 * Usage (from monorepo root):
 *   php packages/filament-flex-fields/scripts/export-playground-bundles.php
 */
$autoloadCandidates = [
    __DIR__.'/../../../vendor/autoload.php',
    __DIR__.'/../vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require $autoload;

        break;
    }
}

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;

$map = [];

foreach (FlexFieldsPlaygroundRegistry::definitions() as $slug => $definition) {
    unset($definition);

    $map[$slug] = FlexFieldAssets::playgroundStylesheetsFor($slug);
}

echo json_encode($map, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

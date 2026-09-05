<?php

declare(strict_types=1);

$autoloadCandidates = [
    __DIR__.'/../../../../../vendor/autoload.php',
    __DIR__.'/../../vendor/autoload.php',
    __DIR__.'/../../../vendor/autoload.php',
];

$autoload = null;

foreach ($autoloadCandidates as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;

        break;
    }
}

if ($autoload === null) {
    fwrite(STDERR, "Could not locate Composer autoload for export-asset-registry.\n");
    exit(1);
}

require $autoload;

$bootstrapCandidates = [
    dirname($autoload).'/../bootstrap/app.php',
    __DIR__.'/../../../../../bootstrap/app.php',
];

$bootstrapped = false;

foreach ($bootstrapCandidates as $bootstrap) {
    if (! is_file($bootstrap)) {
        continue;
    }

    $app = require $bootstrap;
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $bootstrapped = true;

    break;
}

if (! $bootstrapped) {
    fwrite(STDERR, "Could not bootstrap Laravel for export-asset-registry.\n");
    exit(1);
}

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

echo json_encode(FlexFieldAssets::exportRegistry(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

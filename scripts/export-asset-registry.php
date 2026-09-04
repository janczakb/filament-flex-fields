<?php

declare(strict_types=1);

$autoloadCandidates = [
    __DIR__.'/../../../../../vendor/autoload.php',
    __DIR__.'/../../vendor/autoload.php',
    __DIR__.'/../../../vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require $autoload;

        break;
    }
}

if (! class_exists(FlexFieldAssets::class)) {
    require_once __DIR__.'/../src/Support/FlexFieldAssets.php';
}

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;

echo json_encode(FlexFieldAssets::exportRegistry(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

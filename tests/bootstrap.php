<?php

declare(strict_types=1);

$candidates = [
    // Standalone package / GitHub Actions (`composer install` in package root).
    dirname(__DIR__).'/vendor/autoload.php',
    // Local monorepo: packages/filament-flex-fields → app vendor.
    dirname(__DIR__, 3).'/vendor/autoload.php',
];

foreach ($candidates as $autoload) {
    if (is_file($autoload)) {
        require $autoload;

        return;
    }
}

fwrite(STDERR, "Cannot open Composer autoload. Tried:\n");

foreach ($candidates as $autoload) {
    fwrite(STDERR, "  - {$autoload}\n");
}

fwrite(STDERR, "Run `composer install` in the package root (CI) or the application root (monorepo).\n");

exit(1);

#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Token-aware SelectField migration (v2 → v3 headless).
 *
 * Usage:
 *   php scripts/codemods/v2-to-v3-select-ast.php /path/to/app
 *   php scripts/codemods/v2-to-v3-select-ast.php /path/to/app --write
 */
$targetRoot = $argv[1] ?? getcwd();
$shouldWrite = in_array('--write', $argv, true);

/**
 * @return list<string>
 */
function walkPhpFiles(string $root): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $path = $file->getPathname();

        if (str_contains($path, '/vendor/')
            || str_contains($path, '/node_modules/')
            || str_contains($path, '/.git/')) {
            continue;
        }

        if (preg_match('/\.(php|blade\.php)$/', $path) !== 1) {
            continue;
        }

        $files[] = $path;
    }

    return $files;
}

/**
 * @return array{content: string, changed: bool, hints: list<string>}
 */
function transformSelectFieldChains(string $content): array
{
    $hints = [];
    $changed = false;
    $next = $content;

    if (preg_match('/SelectField::make\s*\(/', $content) === 1
        && preg_match('/relationship\s*\(/', $content) === 1
        && ! str_contains($content, 'fff:v3:relationship')) {
        $hints[] = 'Review relationship() selects — headless engine supports async search natively.';
    }

    if (preg_match_all('/->native\s*\(\s*false\s*\)/', $next, $matches) && $matches[0] !== []) {
        $next = preg_replace('/->native\s*\(\s*false\s*\)/', '', $next) ?? $next;
        $changed = true;
        $hints[] = 'Removed ->native(false) after SelectField chains.';
    }

    if (preg_match('/fffSelectFieldCoordinator/', $next) === 1) {
        $hints[] = 'Remove fffSelectFieldCoordinator — production uses fffHeadlessSelectField.';
    }

    if (preg_match('/SelectField::make\s*\(/', $next) === 1
        && ! str_contains($next, 'fff:v3:headless')
        && str_contains($next, '<?php')) {
        $replaced = preg_replace(
            '/(SelectField::make\([^\n]+\)\n)/',
            "$1            // fff:v3:headless — verify config select.use_headless_engine\n",
            $next,
            1,
        );

        if (is_string($replaced) && $replaced !== $next) {
            $next = $replaced;
            $changed = true;
            $hints[] = 'Inserted fff:v3:headless verification comment.';
        }
    }

    return [
        'content' => $next,
        'changed' => $changed,
        'hints' => $hints,
    ];
}

$files = walkPhpFiles($targetRoot);
$report = [];

foreach ($files as $file) {
    $original = file_get_contents($file);

    if ($original === false || ! str_contains($original, 'SelectField::make')) {
        continue;
    }

    $result = transformSelectFieldChains($original);

    if ($result['hints'] === []) {
        continue;
    }

    $report[] = [
        'file' => $file,
        'hints' => $result['hints'],
        'changed' => $result['changed'],
    ];

    if ($shouldWrite && $result['changed']) {
        file_put_contents($file, $result['content']);
    }
}

echo 'Scanned '.count($files)." files under {$targetRoot}\n";

foreach ($report as $entry) {
    echo "\n{$entry['file']}\n";

    foreach ($entry['hints'] as $hint) {
        echo "  - {$hint}\n";
    }
}

if ($report === []) {
    echo "\nNo SelectField AST migration hints found.\n";
} elseif (! $shouldWrite) {
    echo "\nRe-run with --write to apply token-aware transforms.\n";
}

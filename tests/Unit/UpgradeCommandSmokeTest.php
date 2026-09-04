<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Console\UpgradeToV3Command;

it('runs fff upgrade command registration smoke test', function (): void {
    $command = app(UpgradeToV3Command::class);

    expect($command->getName())->toBe('fff:v3:upgrade');
});

it('codemod scripts exist for v2 to v3 select migration', function (): void {
    $root = dirname(__DIR__, 2);

    expect(is_file($root.'/scripts/codemods/v2-to-v3-select-ast.php'))->toBeTrue()
        ->and(is_file($root.'/scripts/codemods/v2-to-v3-select.mjs'))->toBeTrue();
});

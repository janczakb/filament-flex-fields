<?php

declare(strict_types=1);

it('writes bundle metrics with css and js sizes', function () {
    $metricsPath = __DIR__.'/../../resources/dist/bundle-metrics.json';

    expect(is_file($metricsPath))->toBeTrue();

    $metrics = json_decode((string) file_get_contents($metricsPath), true);

    expect($metrics)
        ->toHaveKeys(['generatedAt', 'css', 'js'])
        ->and($metrics['css']['core.css'] ?? null)
        ->toHaveKeys(['bytes', 'kb', 'gzipKb']);
});

it('passes bundle budget checks', function () {
    $packageRoot = dirname(__DIR__, 2);
    $command = 'node '.escapeshellarg($packageRoot.'/scripts/check-bundle-budgets.mjs');

    exec($command, $output, $exitCode);

    expect($exitCode)->toBe(0, implode("\n", $output));
});

it('keeps the phone lib chunk under budget in bundle metrics', function () {
    $metrics = json_decode(
        (string) file_get_contents(__DIR__.'/../../resources/dist/bundle-metrics.json'),
        true,
    );

    $phoneLibKb = null;

    foreach ($metrics['js'] ?? [] as $file => $sizes) {
        if (str_starts_with($file, 'flex-fields-phone-lib')) {
            $phoneLibKb = $sizes['kb'];
            break;
        }
    }

    expect($phoneLibKb)->not->toBeNull()
        ->and($phoneLibKb)->toBeLessThan(195);
});

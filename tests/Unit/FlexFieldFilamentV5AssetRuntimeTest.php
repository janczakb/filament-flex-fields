<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Illuminate\Support\Facades\File;

it('REQ-8 / I38: package source uses Action::schema not deprecated Action::form', function (): void {
    $srcRoot = dirname(__DIR__, 2).'/src';
    $violations = [];

    foreach (File::allFiles($srcRoot) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $contents = File::get($file->getPathname());

        if (preg_match('/->form\s*\(\s*\[/', $contents) === 1) {
            $violations[] = str_replace($srcRoot.'/', '', $file->getPathname());
        }
    }

    expect($violations)->toBeEmpty('Deprecated Action::form([ usage found: '.implode(', ', $violations));
});

it('I37: BatchConsumerBridge module is not shipped', function (): void {
    $bridgePath = dirname(__DIR__, 2).'/resources/js/core/flex-field-batch-consumer-bridge.js';

    expect(file_exists($bridgePath))->toBeFalse();
});

it('FlexFieldAssets exposes consumer bundle API for CRG registry export', function (): void {
    expect(method_exists(FlexFieldAssets::class, 'assetBundleFor'))->toBeTrue()
        ->and(method_exists(FlexFieldAssets::class, 'consumerAttributesFor'))->toBeTrue()
        ->and(method_exists(FlexFieldAssets::class, 'resolveCanonicalComponent'))->toBeTrue();
});

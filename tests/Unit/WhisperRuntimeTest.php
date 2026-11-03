<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\WhisperRuntime;
use Bjanczak\FilamentFlexFields\Support\WhisperRuntimeManifest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    WhisperRuntime::clearFakes();
});

afterEach(function (): void {
    WhisperRuntime::clearFakes();
});

it('pins whisper runtime files with sha256 checksums and no source maps', function (): void {
    expect(WhisperRuntimeManifest::FILES)
        ->toHaveKey('transformers.min.js')
        ->not->toHaveKey('transformers.min.js.map')
        ->toHaveKeys(['ort-wasm-simd-threaded.mjs', 'ort-wasm-simd-threaded.wasm'])
        ->and(WhisperRuntimeManifest::VERSION)->toBe('4.3.2')
        ->and(WhisperRuntimeManifest::downloadUrl('transformers.min.js'))
        ->toContain('@huggingface/transformers@4.3.2/dist/transformers.min.js');
});

it('reports whisper runtime as missing when public files are absent', function (): void {
    $directory = sys_get_temp_dir().'/fff-whisper-missing-'.uniqid();
    WhisperRuntime::usePublicDirectory($directory);

    $result = WhisperRuntime::verify();

    expect($result['ok'])->toBeFalse()
        ->and($result['missing'])->toContain('transformers.min.js')
        ->and(WhisperRuntime::isInstalled())->toBeFalse();
});

it('rejects whisper installs when a download fails sha256 verification', function (): void {
    $directory = sys_get_temp_dir().'/fff-whisper-bad-'.uniqid();
    $filesystem = app(Filesystem::class);
    $filesystem->ensureDirectoryExists($directory);
    WhisperRuntime::usePublicDirectory($directory);

    $fake = [];

    foreach (WhisperRuntimeManifest::FILES as $filename => $expectedHash) {
        $fake[WhisperRuntimeManifest::downloadUrl($filename)] = Http::response('not-the-real-bytes', 200);
    }

    Http::fake($fake);

    expect(fn () => WhisperRuntime::install($filesystem))
        ->toThrow(RuntimeException::class, 'SHA-256 mismatch');
});

it('does not auto-publish stale package assets on staging http requests', function (): void {
    app()->detectEnvironment(fn (): string => 'staging');

    expect(FlexFieldAssets::shouldPublishStalePackageAssets(runningInConsole: false))->toBeFalse()
        ->and(FlexFieldAssets::shouldPublishStalePackageAssets(runningInConsole: true))->toBeTrue();
});

it('still auto-publishes stale package assets on local http requests', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    expect(FlexFieldAssets::shouldPublishStalePackageAssets(runningInConsole: false))->toBeTrue();
});

it('pins ONNX loaders and binaries to the same resolved runtime release', function (): void {
    foreach (['ort-wasm-simd-threaded.mjs', 'ort-wasm-simd-threaded.wasm'] as $filename) {
        expect(WhisperRuntimeManifest::downloadUrl($filename))
            ->toBe('https://cdn.jsdelivr.net/npm/onnxruntime-web@'.WhisperRuntimeManifest::ONNX_VERSION.'/dist/'.$filename);
    }
    foreach (WhisperRuntimeManifest::FILES as $hash) {
        expect($hash)->toMatch('/^[a-f0-9]{64}$/');
    }
    expect(fn () => WhisperRuntimeManifest::downloadUrl('../unexpected.js'))
        ->toThrow(InvalidArgumentException::class);
});

it('isolates installed runtime versions without changing explicit directory overrides', function (): void {
    expect(WhisperRuntime::publicDirectory())->toEndWith('/whisper/'.WhisperRuntimeManifest::VERSION)
        ->and(FlexFieldAssets::whisperRuntimeModuleSrc())->toContain('/whisper/'.WhisperRuntimeManifest::VERSION.'/transformers.min.js');
    WhisperRuntime::usePublicDirectory('/tmp/explicit-runtime');
    expect(WhisperRuntime::publicDirectory())->toBe('/tmp/explicit-runtime');
});

it('verifies real installed bytes and rejects a corrupted ONNX loader', function (): void {
    $root = dirname(__DIR__, 2).'/node_modules/@huggingface/transformers';
    $onnx = is_dir($root.'/node_modules/onnxruntime-web')
        ? $root.'/node_modules/onnxruntime-web'
        : dirname(__DIR__, 2).'/node_modules/onnxruntime-web';
    $sources = [
        'transformers.min.js' => $root.'/dist/transformers.min.js',
        'ort-wasm-simd-threaded.mjs' => $onnx.'/dist/ort-wasm-simd-threaded.mjs',
        'ort-wasm-simd-threaded.wasm' => $onnx.'/dist/ort-wasm-simd-threaded.wasm',
    ];
    foreach ($sources as $source) {
        if (! is_file($source)) {
            $this->markTestSkipped('The pinned npm runtime must be installed for the real-byte verification probe.');
        }
    }
    $directory = sys_get_temp_dir().'/fff-whisper-valid-'.bin2hex(random_bytes(8));
    $filesystem = app(Filesystem::class);
    WhisperRuntime::usePublicDirectory($directory);
    $responses = [];
    foreach ($sources as $filename => $source) {
        $responses[WhisperRuntimeManifest::downloadUrl($filename)] = Http::response((string) file_get_contents($source));
    }
    Http::fake($responses);
    try {
        WhisperRuntime::install($filesystem);
        expect(WhisperRuntime::verify()['ok'])->toBeTrue()
            ->and(WhisperRuntime::verify()['version'])->toBe(WhisperRuntimeManifest::VERSION);
        Http::assertSentCount(3);
        $filesystem->put($directory.'/ort-wasm-simd-threaded.mjs', 'corrupt loader');
        expect(WhisperRuntime::verify()['ok'])->toBeFalse()
            ->and(WhisperRuntime::verify()['mismatched'])->toBe(['ort-wasm-simd-threaded.mjs']);
    } finally {
        $filesystem->deleteDirectory($directory);
    }
});

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
        ->and(WhisperRuntimeManifest::VERSION)->toBe('2.17.2')
        ->and(WhisperRuntimeManifest::downloadUrl('transformers.min.js'))
        ->toContain('@xenova/transformers@2.17.2/dist/transformers.min.js');
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

it('installs whisper runtime when downloads match the manifest hashes', function (): void {
    $backup = null;
    $candidates = [
        dirname(__DIR__, 3).'/.cursor/backups',
        '/Users/bartek/STRONY LOKALNE LARAVEL/wyachts-super-app/.cursor/backups',
    ];

    foreach ($candidates as $dir) {
        if (! is_dir($dir)) {
            continue;
        }

        $matches = glob($dir.'/packages-filament-flex-fields-resources-dist-assets-whisper.*.tar.gz') ?: [];

        if ($matches !== []) {
            rsort($matches);
            $backup = $matches[0];
            break;
        }
    }

    if ($backup === null) {
        $this->markTestSkipped('Whisper backup tarball not available for golden install test.');
    }

    $filesystem = app(Filesystem::class);
    $extract = sys_get_temp_dir().'/fff-whisper-golden-'.uniqid();
    $directory = sys_get_temp_dir().'/fff-whisper-install-ok-'.uniqid();
    $filesystem->ensureDirectoryExists($extract);
    $filesystem->ensureDirectoryExists($directory);
    WhisperRuntime::usePublicDirectory($directory);

    exec('tar xzf '.escapeshellarg($backup).' -C '.escapeshellarg($extract), $out, $code);
    expect($code)->toBe(0);

    $golden = $extract.'/whisper';
    $fake = [];

    foreach (WhisperRuntimeManifest::FILES as $filename => $expectedHash) {
        $path = $golden.'/'.$filename;
        expect(is_file($path))->toBeTrue()
            ->and(hash_file('sha256', $path))->toBe($expectedHash);

        $fake[WhisperRuntimeManifest::downloadUrl($filename)] = Http::response((string) file_get_contents($path), 200);
    }

    Http::fake($fake);

    WhisperRuntime::install($filesystem);

    expect(WhisperRuntime::verify()['ok'])->toBeTrue()
        ->and(is_file(WhisperRuntime::lockPath()))->toBeTrue()
        ->and(is_file($directory.'/transformers.min.js.map'))->toBeFalse();
});

it('never publishes whisper paths from the static asset publisher', function (): void {
    $source = FlexFieldAssets::staticAssetsSourceDirectory().'/whisper';
    $filesystem = app(Filesystem::class);
    $filesystem->ensureDirectoryExists($source);
    $filesystem->put($source.'/should-not-copy.txt', 'nope');

    $destination = FlexFieldAssets::staticAssetsPublicDirectory().'/whisper/should-not-copy.txt';

    if (is_file($destination)) {
        unlink($destination);
    }

    FlexFieldAssets::publishStaticAssetsIfStale($filesystem);

    expect(is_file($destination))->toBeFalse();

    $filesystem->deleteDirectory($source);
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

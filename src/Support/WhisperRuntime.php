<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Host-installed Whisper browser runtime (public/…/whisper), verified against WhisperRuntimeManifest.
 */
final class WhisperRuntime
{
    public const LOCK_FILENAME = '.installed.json';

    private static ?bool $installedOverride = null;

    private static ?string $publicDirectoryOverride = null;

    public static function fakeInstalled(?bool $installed): void
    {
        self::$installedOverride = $installed;
    }

    public static function usePublicDirectory(?string $directory): void
    {
        self::$publicDirectoryOverride = $directory;
    }

    public static function clearFakes(): void
    {
        self::$installedOverride = null;
        self::$publicDirectoryOverride = null;
    }

    public static function publicDirectory(): string
    {
        return self::$publicDirectoryOverride
            ?? FlexFieldAssets::staticAssetsPublicDirectory().'/whisper';
    }

    public static function lockPath(): string
    {
        return self::publicDirectory().'/'.self::LOCK_FILENAME;
    }

    public static function modulePath(): string
    {
        return self::publicDirectory().'/transformers.min.js';
    }

    public static function isInstalled(): bool
    {
        if (self::$installedOverride !== null) {
            return self::$installedOverride;
        }

        return self::verify()['ok'];
    }

    /**
     * @return array{
     *     ok: bool,
     *     installed: bool,
     *     version: ?string,
     *     missing: list<string>,
     *     mismatched: list<string>,
     *     unexpected: list<string>,
     *     message: string
     * }
     */
    public static function verify(): array
    {
        $directory = self::publicDirectory();
        $missing = [];
        $mismatched = [];

        foreach (WhisperRuntimeManifest::FILES as $filename => $expectedHash) {
            $path = $directory.'/'.$filename;

            if (! is_file($path)) {
                $missing[] = $filename;

                continue;
            }

            $actual = hash_file('sha256', $path);

            if ($actual !== $expectedHash) {
                $mismatched[] = $filename;
            }
        }

        $lock = self::readLock();
        $ok = $missing === [] && $mismatched === [];

        $unexpected = [];

        if (is_file($directory.'/transformers.min.js.map')) {
            $unexpected[] = 'transformers.min.js.map';
        }

        $message = match (true) {
            $ok => 'Whisper runtime is installed and matches the pinned manifest.',
            $missing !== [] && $mismatched !== [] => 'Whisper runtime is incomplete and has corrupted files.',
            $missing !== [] => 'Whisper runtime is not installed (missing files).',
            default => 'Whisper runtime files failed SHA-256 verification.',
        };

        return [
            'ok' => $ok,
            'installed' => $ok,
            'version' => is_string($lock['version'] ?? null) ? $lock['version'] : ($ok ? WhisperRuntimeManifest::VERSION : null),
            'missing' => $missing,
            'mismatched' => $mismatched,
            'unexpected' => $unexpected,
            'message' => $message,
        ];
    }

    /**
     * @param  (callable(string, int, int): void)|null  $onProgress
     */
    public static function install(?Filesystem $filesystem = null, ?callable $onProgress = null): void
    {
        $filesystem ??= app(Filesystem::class);
        $directory = self::publicDirectory();
        $filesystem->ensureDirectoryExists($directory);

        $total = count(WhisperRuntimeManifest::FILES);
        $index = 0;

        foreach (WhisperRuntimeManifest::FILES as $filename => $expectedHash) {
            $index++;
            $onProgress?->__invoke($filename, $index, $total);

            $url = WhisperRuntimeManifest::downloadUrl($filename);
            $target = $directory.'/'.$filename;
            $temporary = $target.'.download';

            try {
                $response = Http::timeout(120)->get($url);
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    "Failed to download Whisper runtime file [{$filename}] from [{$url}]: {$exception->getMessage()}",
                    previous: $exception,
                );
            }

            if (! $response->successful()) {
                throw new RuntimeException("Failed to download Whisper runtime file [{$filename}] (HTTP {$response->status()}).");
            }

            $filesystem->put($temporary, $response->body());

            if (! is_file($temporary)) {
                throw new RuntimeException("Failed to write Whisper runtime file [{$filename}] to temporary path.");
            }

            $actual = hash_file('sha256', $temporary);

            if ($actual !== $expectedHash) {
                @unlink($temporary);

                throw new RuntimeException(
                    "SHA-256 mismatch for [{$filename}]: expected {$expectedHash}, got {$actual}.",
                );
            }

            $filesystem->move($temporary, $target);
        }

        // Never keep source maps on customer installs.
        if (is_file($directory.'/transformers.min.js.map')) {
            $filesystem->delete($directory.'/transformers.min.js.map');
        }

        $lock = [
            'package' => WhisperRuntimeManifest::PACKAGE,
            'version' => WhisperRuntimeManifest::VERSION,
            'installed_at' => now()->toIso8601String(),
            'files' => WhisperRuntimeManifest::FILES,
        ];

        $filesystem->put(
            self::lockPath(),
            json_encode($lock, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function readLock(): ?array
    {
        $path = self::lockPath();

        if (! is_file($path)) {
            return null;
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (Throwable) {
            return null;
        }
    }
}

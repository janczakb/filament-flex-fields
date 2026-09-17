<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Console;

use Bjanczak\FilamentFlexFields\Support\WhisperRuntime;
use Bjanczak\FilamentFlexFields\Support\WhisperRuntimeManifest;
use Illuminate\Console\Command;
use Throwable;

class InstallWhisperRuntimeCommand extends Command
{
    protected $signature = 'fff:whisper:install
                            {--force : Re-download even when the runtime already verifies}
                            {--check : Only verify the installed runtime (CI gate; exit 1 if missing/corrupt)}';

    protected $description = 'Install or verify the optional Whisper browser runtime for AudioField transcription';

    public function handle(): int
    {
        if ($this->option('check')) {
            return $this->check();
        }

        return $this->install();
    }

    protected function check(): int
    {
        $result = WhisperRuntime::verify();

        if ($result['ok']) {
            $this->components->info($result['message']);

            return self::SUCCESS;
        }

        $this->components->error($result['message']);
        $this->components->info('Fix with: '.WhisperRuntimeManifest::INSTALL_COMMAND);

        return self::FAILURE;
    }

    protected function install(): int
    {
        $result = WhisperRuntime::verify();

        if ($result['ok'] && ! $this->option('force')) {
            $this->components->info('Whisper runtime already installed. Use --force to re-download.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf(
            'Installing %s@%s (%d files, SHA-256 verified)…',
            WhisperRuntimeManifest::PACKAGE,
            WhisperRuntimeManifest::VERSION,
            count(WhisperRuntimeManifest::FILES),
        ));

        try {
            WhisperRuntime::install(null, function (string $filename, int $index, int $total): void {
                $this->components->twoColumnDetail("[{$index}/{$total}]", $filename);
            });
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $verify = WhisperRuntime::verify();

        if (! $verify['ok']) {
            $this->components->error('Install finished but verification failed: '.$verify['message']);

            return self::FAILURE;
        }

        $this->components->info('Whisper runtime installed at '.WhisperRuntime::publicDirectory());

        return self::SUCCESS;
    }
}

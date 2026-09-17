<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Console;

use Bjanczak\FilamentFlexFields\Support\WhisperRuntime;
use Bjanczak\FilamentFlexFields\Support\WhisperRuntimeManifest;
use Illuminate\Console\Command;

class WhisperRuntimeStatusCommand extends Command
{
    protected $signature = 'fff:whisper:status';

    protected $description = 'Show whether the optional Whisper browser runtime is installed and valid';

    public function handle(): int
    {
        $result = WhisperRuntime::verify();

        $this->components->twoColumnDetail('Package', WhisperRuntimeManifest::PACKAGE.'@'.WhisperRuntimeManifest::VERSION);
        $this->components->twoColumnDetail('Directory', WhisperRuntime::publicDirectory());
        $this->components->twoColumnDetail('Status', $result['ok'] ? 'installed' : 'missing / invalid');
        $this->components->twoColumnDetail('Version lock', $result['version'] ?? '—');
        $this->line($result['message']);

        if ($result['missing'] !== []) {
            $this->components->bulletList($result['missing']);
        }

        if ($result['mismatched'] !== []) {
            $this->components->error('SHA-256 mismatches:');
            $this->components->bulletList($result['mismatched']);
        }

        if (! $result['ok']) {
            $this->newLine();
            $this->components->info('Install with: '.WhisperRuntimeManifest::INSTALL_COMMAND);
        }

        return self::SUCCESS;
    }
}

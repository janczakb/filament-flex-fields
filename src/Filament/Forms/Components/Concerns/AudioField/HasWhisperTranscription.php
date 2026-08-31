<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\AudioField;

use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\WhisperLanguageCatalog;
use Bjanczak\FilamentFlexFields\Support\WhisperModelCatalog;
use Closure;
use InvalidArgumentException;

trait HasWhisperTranscription
{
    protected bool|Closure $transcriptionEnabled = false;

    protected bool|Closure $transcriptionSettingsVisible = true;

    protected string|Closure|null $whisperModel = null;

    protected bool|Closure|null $whisperQuantized = null;

    protected bool|Closure $whisperMultilingual = true;

    protected string|Closure|null $whisperLanguage = null;

    protected string|Closure $whisperTask = 'transcribe';

    public function transcription(bool|Closure $condition = true): static
    {
        $this->transcriptionEnabled = $condition;

        return $this;
    }

    public function transcriptionSettings(bool|Closure $condition = true): static
    {
        $this->transcriptionSettingsVisible = $condition;

        return $this;
    }

    public function whisperModel(string|Closure|null $model): static
    {
        $this->whisperModel = $model;

        return $this;
    }

    public function whisperQuantized(bool|Closure $condition = true): static
    {
        $this->whisperQuantized = $condition;

        return $this;
    }

    public function whisperMultilingual(bool|Closure $condition = true): static
    {
        $this->whisperMultilingual = $condition;

        return $this;
    }

    public function whisperLanguage(string|Closure|null $language): static
    {
        $this->whisperLanguage = $language;

        return $this;
    }

    public function whisperTask(string|Closure $task): static
    {
        $this->whisperTask = $task;

        return $this;
    }

    public function isTranscriptionEnabled(): bool
    {
        return (bool) $this->evaluate($this->transcriptionEnabled);
    }

    public function isTranscriptionSettingsVisible(): bool
    {
        return (bool) $this->evaluate($this->transcriptionSettingsVisible);
    }

    public function getWhisperModel(): string
    {
        $model = $this->evaluate($this->whisperModel);

        if (is_string($model) && filled($model)) {
            return $model;
        }

        return (string) config('filament-flex-fields.audio.transcription.default_model', 'Xenova/whisper-tiny');
    }

    public function isWhisperQuantized(): bool
    {
        $quantized = $this->evaluate($this->whisperQuantized);

        if ($quantized !== null) {
            return (bool) $quantized;
        }

        return (bool) config('filament-flex-fields.audio.transcription.default_quantized', true);
    }

    public function isWhisperMultilingual(): bool
    {
        return (bool) $this->evaluate($this->whisperMultilingual);
    }

    public function getWhisperLanguage(): ?string
    {
        $language = $this->evaluate($this->whisperLanguage);

        if ($language === null || $language === '') {
            return null;
        }

        return strtolower((string) $language);
    }

    public function getWhisperTask(): string
    {
        $task = strtolower((string) $this->evaluate($this->whisperTask));

        if (! in_array($task, ['transcribe', 'translate'], true)) {
            throw new InvalidArgumentException("Whisper task [{$task}] must be transcribe or translate.");
        }

        return $task;
    }

    /**
     * @return array{
     *     model: string,
     *     quantized: bool,
     *     multilingual: bool,
     *     language: ?string,
     *     task: string,
     *     settingsVisible: bool,
     *     runtimeModuleUrl: string,
     *     runtimeWasmBaseUrl: string,
     *     models: list<array{id: string, multilingual: bool, distil: bool, sizes: list<int>}>,
     *     languages: list<array{code: ?string, label: string}>
     * }
     */
    public function getTranscriptionAlpineConfig(): array
    {
        if (! $this->isTranscriptionEnabled()) {
            return [];
        }

        /** @var array<string, mixed> $config */
        $config = config('filament-flex-fields.audio.transcription', []);

        return [
            'model' => $this->getWhisperModel(),
            'quantized' => $this->isWhisperQuantized(),
            'multilingual' => $this->isWhisperMultilingual(),
            'language' => $this->getWhisperLanguage(),
            'task' => $this->getWhisperTask(),
            'settingsVisible' => $this->isTranscriptionSettingsVisible(),
            'runtimeModuleUrl' => FlexFieldAssets::whisperRuntimeModuleSrc(),
            'runtimeWasmBaseUrl' => FlexFieldAssets::whisperRuntimeWasmBaseSrc(),
            'models' => WhisperModelCatalog::models(),
            'languages' => WhisperLanguageCatalog::options(),
        ];
    }
}

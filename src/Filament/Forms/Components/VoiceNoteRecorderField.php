<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\ResolvesConfiguredIcons;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Illuminate\Contracts\Support\Htmlable;

class VoiceNoteRecorderField extends FlexFileUpload
{
    use ResolvesConfiguredIcons;

    protected string $view = 'filament-flex-fields::forms.components.voice-note-recorder-field';

    protected int|Closure $maxDuration = 120; // Default 2 minutes

    protected bool|Closure $uploadImmediately = false;

    protected string|BackedEnum|Htmlable|Closure|null $playIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $pauseIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $microphoneIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $stopIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $trashIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $checkmarkIcon = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->acceptedFileTypes([
            'audio/*',
            'audio/mpeg',
            'audio/wav',
            'audio/webm',
            'audio/ogg',
            'audio/x-m4a',
            'audio/aac',
        ]);

        $this->deleteFileOnRemove();
    }

    public function maxDuration(int|Closure $seconds): static
    {
        $this->maxDuration = $seconds;

        return $this;
    }

    public function getMaxDuration(): int
    {
        return (int) $this->evaluate($this->maxDuration);
    }

    public function uploadImmediately(bool|Closure $condition = true): static
    {
        $this->uploadImmediately = $condition;

        return $this;
    }

    public function uploadOnSubmit(bool|Closure $condition = true): static
    {
        $this->uploadImmediately = fn (): bool => ! $this->evaluate($condition);

        return $this;
    }

    public function shouldUploadImmediately(): bool
    {
        return (bool) $this->evaluate($this->uploadImmediately);
    }

    public function playIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->playIcon = $icon;

        return $this;
    }

    public function pauseIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->pauseIcon = $icon;

        return $this;
    }

    public function microphoneIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->microphoneIcon = $icon;

        return $this;
    }

    public function stopIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->stopIcon = $icon;

        return $this;
    }

    public function trashIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->trashIcon = $icon;

        return $this;
    }

    public function checkmarkIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->checkmarkIcon = $icon;

        return $this;
    }

    public function getPlayIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->playIcon, 'audio_play_icon', GravityIcon::PlayFill);
    }

    public function getPauseIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->pauseIcon, 'audio_pause_icon', GravityIcon::PauseFill);
    }

    public function getMicrophoneIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->microphoneIcon, 'microphone_icon', GravityIcon::Microphone);
    }

    public function getStopIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->stopIcon, 'stop_icon', GravityIcon::Minus);
    }

    public function getTrashIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->trashIcon, 'trash_icon', GravityIcon::TrashBin);
    }

    public function getCheckmarkIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->checkmarkIcon, 'checkmark_icon', GravityIcon::Check);
    }

    public function getInitialAudioUrl(): ?string
    {
        $state = $this->getState();

        if (blank($state)) {
            return null;
        }

        if (is_array($state)) {
            $state = reset($state);
        }

        if (blank($state)) {
            return null;
        }

        $disk = $this->getDisk();

        if (! method_exists($disk, 'url')) {
            return null;
        }

        try {
            return $disk->url($state);
        } catch (\Throwable) {
            return null;
        }
    }
}

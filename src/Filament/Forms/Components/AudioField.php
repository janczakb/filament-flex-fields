<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\ResolvesConfiguredIcons;
use Bjanczak\FilamentFlexFields\Support\AudioWaveformGenerator;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Security\SafeMediaUrl;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class AudioField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use ResolvesConfiguredIcons;

    protected string $view = 'filament-flex-fields::forms.components.audio-field';

    protected string|Closure|null $src = null;

    protected bool|Closure $isFullWidth = false;

    protected bool|Closure $loop = false;

    /** @var list<int>|Closure|null */
    protected array|Closure|null $waveform = null;

    protected string|BackedEnum|Htmlable|Closure|null $playIcon = null;

    protected string|BackedEnum|Htmlable|Closure|null $pauseIcon = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);

        $this->rules(['nullable', 'string']);

        $this->rule(function (AudioField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                if (! is_string($value)) {
                    $fail(__('filament-flex-fields::default.validation.media.invalid_url'));

                    return;
                }

                if (SafeMediaUrl::sanitize($value) === null) {
                    $fail(__('filament-flex-fields::default.validation.media.invalid_url'));
                }
            };
        });
    }

    public function src(string|Closure|null $src): static
    {
        $this->src = $src;

        return $this;
    }

    public function fullWidth(bool|Closure $condition = true): static
    {
        $this->isFullWidth = $condition;

        return $this;
    }

    public function loop(bool|Closure $condition = true): static
    {
        $this->loop = $condition;

        return $this;
    }

    /**
     * @param  list<int>|Closure|null  $waveform
     */
    public function waveform(array|Closure|null $waveform): static
    {
        $this->waveform = $waveform;

        return $this;
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

    public function getPlayIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->playIcon, 'audio_play_icon', GravityIcon::PlayFill);
    }

    public function getPauseIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveFieldIcon($this->pauseIcon, 'audio_pause_icon', GravityIcon::PauseFill);
    }

    public function getSrc(): ?string
    {
        $src = $this->evaluate($this->src);

        if (! filled($src)) {
            return null;
        }

        return SafeMediaUrl::sanitize((string) $src);
    }

    public function isFullWidth(): bool
    {
        return (bool) $this->evaluate($this->isFullWidth);
    }

    public function shouldLoop(): bool
    {
        return (bool) $this->evaluate($this->loop);
    }

    public function resolveAudioSrc(mixed $state): ?string
    {
        $staticSrc = $this->getSrc();

        if ($staticSrc !== null) {
            return $staticSrc;
        }

        if (is_string($state) && filled($state)) {
            return SafeMediaUrl::sanitize($state);
        }

        return null;
    }

    public function hasCustomWaveform(): bool
    {
        if ($this->waveform === null) {
            return false;
        }

        $waveform = $this->evaluate($this->waveform);

        return is_array($waveform) && $waveform !== [];
    }

    /**
     * @return list<int>
     */
    public function getWaveform(): array
    {
        $waveform = $this->evaluate($this->waveform);

        if (! is_array($waveform) || $waveform === []) {
            return AudioWaveformGenerator::placeholderWaveform();
        }

        return $this->normalizeWaveformPeaks($waveform);
    }

    /**
     * @return list<int>
     */
    public function resolveWaveform(mixed $state): array
    {
        if ($this->hasCustomWaveform()) {
            return $this->getWaveform();
        }

        $audioSrc = $this->resolveAudioSrc($state);

        if ($audioSrc !== null) {
            return AudioWaveformGenerator::fromFingerprint($audioSrc);
        }

        return AudioWaveformGenerator::placeholderWaveform();
    }

    /**
     * @param  list<mixed>  $waveform
     * @return list<int>
     */
    protected function normalizeWaveformPeaks(array $waveform): array
    {
        $normalized = [];

        foreach ($waveform as $peak) {
            if (! is_numeric($peak)) {
                throw new InvalidArgumentException('Audio waveform peaks must be numeric values between 8 and 100.');
            }

            $normalized[] = max(8, min(100, (int) $peak));
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-audio-field-field',
            'fff-audio-field-field--'.$this->getSize(),
        ];
    }
}

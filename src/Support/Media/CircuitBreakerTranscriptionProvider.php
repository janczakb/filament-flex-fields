<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

use Illuminate\Support\Facades\Cache;

final class CircuitBreakerTranscriptionProvider implements VoiceNoteTranscriptionInterface
{
    public function __construct(
        private readonly VoiceNoteTranscriptionInterface $provider,
        private readonly int $failureThreshold = 3,
        private readonly int $openSeconds = 30,
    ) {}

    public function transcribe(string $disk, string $path, array $context = []): ?string
    {
        if ($this->isOpen()) {
            return null;
        }

        try {
            $result = $this->provider->transcribe($disk, $path, $context);
            $this->recordSuccess();

            return $result;
        } catch (\Throwable) {
            $this->recordFailure();

            return null;
        }
    }

    private function isOpen(): bool
    {
        return (bool) Cache::get($this->openKey(), false);
    }

    private function recordFailure(): void
    {
        $key = $this->failureKey();
        $failures = (int) Cache::get($key, 0) + 1;

        Cache::put($key, $failures, now()->addMinutes(5));

        if ($failures >= $this->failureThreshold) {
            Cache::put($this->openKey(), true, now()->addSeconds($this->openSeconds));
            Cache::forget($key);
        }
    }

    private function recordSuccess(): void
    {
        Cache::forget($this->failureKey());
        Cache::forget($this->openKey());
    }

    private function failureKey(): string
    {
        return 'fff-transcription:circuit:failures';
    }

    private function openKey(): string
    {
        return 'fff-transcription:circuit:open';
    }
}

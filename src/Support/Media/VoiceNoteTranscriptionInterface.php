<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

interface VoiceNoteTranscriptionInterface
{
    /**
     * Transcribe a stored voice-note audio file to plain text.
     *
     * @param  array<string, mixed>  $context
     */
    public function transcribe(string $disk, string $path, array $context = []): ?string;
}

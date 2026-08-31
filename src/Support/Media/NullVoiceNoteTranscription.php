<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

final class NullVoiceNoteTranscription implements VoiceNoteTranscriptionInterface
{
    public function transcribe(string $disk, string $path, array $context = []): ?string
    {
        return null;
    }
}

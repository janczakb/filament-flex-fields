<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline;

/**
 * Maps MediaKind → retention policy / prune directory category keys.
 */
final class MediaKindRetention
{
    public static function policyKey(MediaKind $kind): string
    {
        return match ($kind) {
            MediaKind::Upload, MediaKind::Image, MediaKind::RichAttachment => 'uploads',
            MediaKind::VoiceNote => 'voice_notes',
            MediaKind::Signature => 'signatures',
            MediaKind::TempCapture => 'temp_captures',
        };
    }

    /**
     * @return list<string>
     */
    public static function directoriesFor(MediaKind $kind): array
    {
        $key = self::policyKey($kind);
        $directories = config('filament-flex-fields.media_capture.directories.'.$key, []);

        return is_array($directories) ? array_values(array_filter($directories, 'is_string')) : [];
    }
}

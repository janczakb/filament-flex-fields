<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline;

enum MediaKind: string
{
    case Upload = 'upload';
    case Image = 'image';
    case VoiceNote = 'voice_note';
    case Signature = 'signature';
    case RichAttachment = 'rich_attachment';
    case TempCapture = 'temp_capture';
}

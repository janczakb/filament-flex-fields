<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum FileUploadSource: string
{
    case File = 'file';
    case Url = 'url';
    case Webcam = 'webcam';
}

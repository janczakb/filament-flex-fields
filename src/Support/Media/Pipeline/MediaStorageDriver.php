<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline;

enum MediaStorageDriver: string
{
    case Disk = 'disk';
    case Spatie = 'spatie';
}

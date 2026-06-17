<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FileUpload;

final readonly class RemoteFilePayload
{
    public function __construct(
        public string $contents,
        public string $mimeType,
        public string $filename,
        public int $size,
    ) {}
}

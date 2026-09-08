<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs;

use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaStorageDriver;

final class SpatieMediaRef implements MediaRef
{
    public function __construct(
        public readonly string $uuid,
        public readonly object $media,
        public readonly ?string $disk = null,
        public readonly ?string $path = null,
    ) {}

    public function driver(): MediaStorageDriver
    {
        return MediaStorageDriver::Spatie;
    }

    public function identifier(): string
    {
        return $this->uuid;
    }

    public function diskName(): ?string
    {
        return $this->disk;
    }

    public function relativePath(): ?string
    {
        return $this->path;
    }

    public function mediaUuid(): string
    {
        return $this->uuid;
    }

    public function media(): object
    {
        return $this->media;
    }
}

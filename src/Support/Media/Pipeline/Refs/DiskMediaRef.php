<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs;

use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaStorageDriver;

final class DiskMediaRef implements MediaRef
{
    public function __construct(
        public readonly string $disk,
        public readonly string $path,
    ) {}

    public function driver(): MediaStorageDriver
    {
        return MediaStorageDriver::Disk;
    }

    public function identifier(): string
    {
        return $this->path;
    }

    public function diskName(): ?string
    {
        return $this->disk;
    }

    public function relativePath(): ?string
    {
        return $this->path;
    }

    public function mediaUuid(): ?string
    {
        return null;
    }

    public function media(): ?object
    {
        return null;
    }
}

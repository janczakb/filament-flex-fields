<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs;

use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaStorageDriver;

interface MediaRef
{
    public function driver(): MediaStorageDriver;

    public function identifier(): string;

    public function diskName(): ?string;

    public function relativePath(): ?string;

    public function mediaUuid(): ?string;

    public function media(): ?object;
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Bjanczak\FilamentFlexFields\Support\RichEditor\RichEditorSpatieMediaRepository;

class FakeRichEditorSpatieMediaRepository extends RichEditorSpatieMediaRepository
{
    /**
     * @var array<string, object>
     */
    private array $mediaByUuid = [];

    public function seed(string $uuid, object $media): static
    {
        $this->mediaByUuid[$uuid] = $media;

        return $this;
    }

    public function findByUuid(string $uuid): ?object
    {
        return $this->mediaByUuid[$uuid] ?? null;
    }
}

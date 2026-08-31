<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground\Contracts;

interface PlaygroundWithPersistence
{
    public function playgroundSlug(): string;

    /**
     * @return list<string>|null Persist only these state keys, or null for the full form state.
     */
    public function persistedStateKeys(): ?array;
}

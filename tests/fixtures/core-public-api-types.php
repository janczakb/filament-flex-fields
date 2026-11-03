<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\PublicApiVersions;

class ParentType {}

class ChildType extends ParentType
{
    public ?self $child = null;

    public ?parent $parent = null;

    public function copy(?parent $value = null): ?self
    {
        return null;
    }

    public function combine(self|\DateTimeInterface|null $value): self|\DateTimeInterface|null
    {
        return $value;
    }

    public function late(): static
    {
        return $this;
    }
}

class GrandchildType extends ChildType {}

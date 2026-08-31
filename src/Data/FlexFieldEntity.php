<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Data;

readonly class FlexFieldEntity
{
    /**
     * @param  class-string  $modelClass
     * @param  class-string|null  $resourceClass
     */
    public function __construct(
        public string $modelClass,
        public string $label,
        public ?string $icon = null,
        public ?string $resourceClass = null,
        public int $sort = 0,
    ) {}

    public function key(): string
    {
        return $this->modelClass;
    }
}

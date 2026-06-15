<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Data;

class FlexFieldSchema
{
    public const CURRENT_VERSION = 1;

    /** @var list<FlexFieldDefinition> */
    protected array $fields = [];

    public function __construct(
        public string $key,
        public string $targetType,
        public ?string $label = null,
        public bool $isActive = true,
        public int $sort = 0,
        public int $version = self::CURRENT_VERSION,
    ) {}

    public static function make(string $key, string $targetType): self
    {
        return new self($key, $targetType);
    }

    public function label(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function active(bool $active = true): static
    {
        $this->isActive = $active;

        return $this;
    }

    public function sort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function version(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @param  list<FlexFieldDefinition|array<string, mixed>>  $fields
     */
    public function fields(array $fields): static
    {
        $this->fields = array_map(
            fn (FlexFieldDefinition|array $field): FlexFieldDefinition => $field instanceof FlexFieldDefinition
                ? $field
                : FlexFieldDefinition::fromArray($field),
            $fields,
        );

        return $this;
    }

    public function addField(FlexFieldDefinition|array $field): static
    {
        $this->fields[] = $field instanceof FlexFieldDefinition
            ? $field
            : FlexFieldDefinition::fromArray($field);

        return $this;
    }

    /**
     * @return list<FlexFieldDefinition>
     */
    public function getFields(): array
    {
        return collect($this->fields)
            ->filter(fn (FlexFieldDefinition $field): bool => $field->isActive && $field->isVisible)
            ->sortBy('sort')
            ->values()
            ->all();
    }
}

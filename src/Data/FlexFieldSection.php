<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Data;

use Bjanczak\FilamentFlexFields\Enums\FlexFieldSectionType;

readonly class FlexFieldSection
{
    /**
     * @param  array<string, mixed>|list<array<string, mixed>>|null  $visibleWhen
     */
    public function __construct(
        public string $id,
        public string $label,
        public FlexFieldSectionType $type = FlexFieldSectionType::Section,
        public int $sort = 0,
        public ?string $description = null,
        public ?array $visibleWhen = null,
        public bool $isActive = true,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $type = $attributes['type'] ?? FlexFieldSectionType::Section->value;

        return new self(
            id: (string) ($attributes['id'] ?? $attributes['slug'] ?? ''),
            label: (string) ($attributes['label'] ?? $attributes['name'] ?? ''),
            type: $type instanceof FlexFieldSectionType ? $type : FlexFieldSectionType::from((string) $type),
            sort: (int) ($attributes['sort'] ?? 0),
            description: isset($attributes['description']) && is_string($attributes['description']) ? $attributes['description'] : null,
            visibleWhen: is_array($attributes['visible_when'] ?? $attributes['visibleWhen'] ?? null) ? ($attributes['visible_when'] ?? $attributes['visibleWhen']) : null,
            isActive: (bool) ($attributes['is_active'] ?? $attributes['isActive'] ?? true),
        );
    }
}

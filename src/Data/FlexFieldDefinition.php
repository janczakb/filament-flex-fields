<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Data;

use Bjanczak\FilamentFlexFields\Enums\FieldType;

readonly class FlexFieldDefinition
{
    /**
     * @param  array<string, mixed>  $config
     * @param  list<string>  $validation
     */
    public function __construct(
        public string $slug,
        public string $label,
        public FieldType $type,
        public array $config = [],
        public array $validation = [],
        public mixed $defaultValue = null,
        public ?string $helpText = null,
        public ?string $placeholder = null,
        public bool $isRequired = false,
        public bool $isActive = true,
        public bool $isVisible = true,
        public int $sort = 0,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $type = $attributes['type'] instanceof FieldType
            ? $attributes['type']
            : FieldType::from((string) $attributes['type']);

        return new self(
            slug: (string) $attributes['slug'],
            label: (string) $attributes['label'],
            type: $type,
            config: array_merge($type->defaultConfig(), $attributes['config'] ?? []),
            validation: $attributes['validation'] ?? [],
            defaultValue: $attributes['default_value'] ?? $attributes['defaultValue'] ?? null,
            helpText: $attributes['help_text'] ?? $attributes['helpText'] ?? null,
            placeholder: $attributes['placeholder'] ?? null,
            isRequired: (bool) ($attributes['is_required'] ?? $attributes['isRequired'] ?? false),
            isActive: (bool) ($attributes['is_active'] ?? $attributes['isActive'] ?? true),
            isVisible: (bool) ($attributes['is_visible'] ?? $attributes['isVisible'] ?? true),
            sort: (int) ($attributes['sort'] ?? 0),
        );
    }

    public function stateKey(): string
    {
        return $this->slug;
    }
}

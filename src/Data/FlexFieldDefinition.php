<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Data;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Enums\FlexFieldWidth;

readonly class FlexFieldDefinition
{
    /**
     * @param  array<string, mixed>  $config
     * @param  list<string>  $validation
     * @param  array<string, mixed>|list<array<string, mixed>>|null  $visibleWhen
     * @param  array<string, mixed>|list<array<string, mixed>>|null  $requiredWhen
     * @param  array<string, mixed>|list<array<string, mixed>>|null  $disabledWhen
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
        public bool $hiddenLabel = false,
        public int $sort = 0,
        public ?array $visibleWhen = null,
        public ?array $requiredWhen = null,
        public ?array $disabledWhen = null,
        public ?string $formula = null,
        public ?string $sectionId = null,
        public FlexFieldWidth $width = FlexFieldWidth::Full,
        public bool $isEncrypted = false,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $type = $attributes['type'] instanceof FieldType
            ? $attributes['type']
            : FieldType::from((string) $attributes['type']);

        $helpText = $attributes['help_text'] ?? $attributes['helpText'] ?? null;
        $config = array_merge($type->defaultConfig(), $attributes['config'] ?? []);
        $formula = self::resolveFormulaExpression($attributes, $config);
        $width = self::resolveWidth($attributes);

        return new self(
            slug: (string) $attributes['slug'],
            label: (string) $attributes['label'],
            type: $type,
            config: $config,
            validation: $attributes['validation'] ?? [],
            defaultValue: $attributes['default_value'] ?? $attributes['defaultValue'] ?? null,
            helpText: is_string($helpText) && trim($helpText) !== '' ? trim($helpText) : null,
            placeholder: $attributes['placeholder'] ?? null,
            isRequired: (bool) ($attributes['is_required'] ?? $attributes['isRequired'] ?? false),
            isActive: (bool) ($attributes['is_active'] ?? $attributes['isActive'] ?? true),
            isVisible: (bool) ($attributes['is_visible'] ?? $attributes['isVisible'] ?? true),
            hiddenLabel: (bool) ($attributes['hidden_label'] ?? $attributes['hiddenLabel'] ?? false),
            sort: (int) ($attributes['sort'] ?? 0),
            visibleWhen: self::parseConditionRules($attributes['visible_when'] ?? $attributes['visibleWhen'] ?? null),
            requiredWhen: self::parseConditionRules($attributes['required_when'] ?? $attributes['requiredWhen'] ?? null),
            disabledWhen: self::parseConditionRules($attributes['disabled_when'] ?? $attributes['disabledWhen'] ?? null),
            formula: $formula,
            sectionId: isset($attributes['section_id']) && filled($attributes['section_id']) ? (string) $attributes['section_id'] : null,
            width: $width,
            isEncrypted: (bool) ($attributes['is_encrypted'] ?? $attributes['isEncrypted'] ?? $config['encrypted'] ?? false),
        );
    }

    public function hasDynamicVisibility(): bool
    {
        return $this->visibleWhen !== null;
    }

    public function hasFormula(): bool
    {
        return filled($this->formula);
    }

    /**
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private static function parseConditionRules(mixed $value): ?array
    {
        if (! is_array($value) || $value === []) {
            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $config
     */
    private static function resolveFormulaExpression(array $attributes, array $config): ?string
    {
        $candidates = [
            $attributes['formula'] ?? null,
            $attributes['calculated'] ?? null,
            $config['formula'] ?? null,
            $config['calculated'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function resolveWidth(array $attributes): FlexFieldWidth
    {
        $width = $attributes['width'] ?? $attributes['config']['width'] ?? FlexFieldWidth::Full->value;

        if ($width instanceof FlexFieldWidth) {
            return $width;
        }

        return FlexFieldWidth::tryFrom((string) $width) ?? FlexFieldWidth::Full;
    }

    public function stateKey(): string
    {
        return $this->slug;
    }
}

<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;

class FlexFieldSchemaRegistry
{
    /** @var array<string, FlexFieldSchema> */
    protected array $schemas = [];

    /** @var array<string, list<FlexFieldSchema>> */
    protected array $targetSchemasCache = [];

    /** @var array<string, list<FlexFieldDefinition>> */
    protected array $targetFieldsCache = [];

    public function register(FlexFieldSchema $schema): self
    {
        $this->schemas[$schema->key] = $schema;

        $this->targetSchemasCache = [];
        $this->targetFieldsCache = [];

        return $this;
    }

    /**
     * @param  array<string, array<string, mixed>>  $schemas
     */
    public function registerFromConfig(array $schemas): self
    {
        foreach ($schemas as $key => $schema) {
            $this->register(
                FlexFieldSchema::make($key, (string) $schema['target'])
                    ->label($schema['label'] ?? null)
                    ->active((bool) ($schema['active'] ?? true))
                    ->sort((int) ($schema['sort'] ?? 0))
                    ->fields($schema['fields'] ?? []),
            );
        }

        return $this;
    }

    public function find(string $key): ?FlexFieldSchema
    {
        return $this->schemas[$key] ?? null;
    }

    /**
     * @return list<FlexFieldSchema>
     */
    public function forTarget(string $targetType): array
    {
        if (isset($this->targetSchemasCache[$targetType])) {
            return $this->targetSchemasCache[$targetType];
        }

        return $this->targetSchemasCache[$targetType] = array_values(collect($this->schemas)
            ->filter(fn (FlexFieldSchema $schema): bool => $schema->isActive && $schema->targetType === $targetType)
            ->sortBy('sort')
            ->values()
            ->all());
    }

    /**
     * @return list<FlexFieldDefinition>
     */
    public function fieldsForTarget(string $targetType): array
    {
        if (isset($this->targetFieldsCache[$targetType])) {
            return $this->targetFieldsCache[$targetType];
        }

        return $this->targetFieldsCache[$targetType] = array_values(collect($this->forTarget($targetType))
            ->flatMap(fn (FlexFieldSchema $schema): array => $schema->getFields())
            ->sortBy('sort')
            ->values()
            ->all());
    }
}

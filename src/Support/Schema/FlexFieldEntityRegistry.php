<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Data\FlexFieldEntity;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Illuminate\Support\Str;

final class FlexFieldEntityRegistry
{
    /** @var list<FlexFieldEntity>|null */
    private ?array $cached = null;

    /** @var array<string, FlexFieldEntity> */
    private array $manual = [];

    public function __construct(
        private readonly FlexFieldEntityDiscovery $discovery,
    ) {}

    public function register(FlexFieldEntity $entity): self
    {
        $this->manual[$entity->key()] = $entity;
        $this->cached = null;

        return $this;
    }

    /**
     * @return list<FlexFieldEntity>
     */
    public function all(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        /** @var array<string, FlexFieldEntity> $merged */
        $merged = [];

        foreach ($this->discovery->discover() as $entity) {
            $merged[$entity->key()] = $entity;
        }

        foreach ($this->manual as $entity) {
            $merged[$entity->key()] = $entity;
        }

        return $this->cached = array_values(collect($merged)
            ->sortBy(fn (FlexFieldEntity $entity): int => $entity->sort)
            ->values()
            ->all());
    }

    public function find(string $modelClass): ?FlexFieldEntity
    {
        foreach ($this->all() as $entity) {
            if ($entity->modelClass === $modelClass) {
                return $entity;
            }
        }

        return null;
    }

    public function forgetCache(): void
    {
        $this->cached = null;
    }

    /**
     * @return array<string, string> modelClass => label
     */
    public function selectOptions(?string $includeModelClass = null): array
    {
        $options = [];

        foreach ($this->all() as $entity) {
            $options[$entity->modelClass] = $entity->label;
        }

        if (
            is_string($includeModelClass)
            && filled($includeModelClass)
            && ! array_key_exists($includeModelClass, $options)
        ) {
            $options[$includeModelClass] = class_basename($includeModelClass);
        }

        return $options;
    }

    public function defaultModelClass(): string
    {
        $fromQuery = request()->query('target_type');

        if (is_string($fromQuery) && filled($fromQuery)) {
            return $fromQuery;
        }

        $entities = $this->all();

        if ($entities !== []) {
            return $entities[0]->modelClass;
        }

        return FlexFieldsConfig::getSchemaDefaultTargetType();
    }

    /**
     * @return array<string, string> attribute => label
     */
    public function modelAttributeOptionsFor(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $model = app($modelClass);
        } catch (\Throwable) {
            return [];
        }

        $attributes = [];

        if (method_exists($model, 'getFillable')) {
            $attributes = array_values(array_filter(
                $model->getFillable(),
                fn (mixed $attribute): bool => is_string($attribute) && filled($attribute),
            ));
        }

        if ($attributes === [] && property_exists($model, 'fillable') && is_array($model->fillable)) {
            $attributes = $model->fillable;
        }

        $options = [];

        foreach ($attributes as $attribute) {
            if (! is_string($attribute) || ! filled($attribute)) {
                continue;
            }

            $options[$attribute] = Str::headline($attribute);
        }

        return $options;
    }
}

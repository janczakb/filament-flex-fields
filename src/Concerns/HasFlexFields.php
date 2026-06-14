<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Illuminate\Database\Eloquent\Builder;

trait HasFlexFields
{
    public static function bootHasFlexFields(): void
    {
        static::retrieved(function ($model): void {
            if (! isset($model->attributes[static::flexFieldsColumn()])) {
                $model->setAttribute(static::flexFieldsColumn(), []);
            }
        });
    }

    public function initializeHasFlexFields(): void
    {
        $column = static::flexFieldsColumn();

        if (! isset($this->casts[$column])) {
            $this->casts[$column] = 'array';
        }
    }

    public static function flexFieldsColumn(): string
    {
        return FlexFieldsConfig::getValuesColumn();
    }

    /**
     * @return array<string, mixed>
     */
    public function getFlexFieldValues(): array
    {
        return $this->getAttribute(static::flexFieldsColumn()) ?? [];
    }

    public function getFlexFieldValue(string $slug, mixed $default = null): mixed
    {
        return data_get($this->getFlexFieldValues(), $slug, $default);
    }

    public function setFlexFieldValue(string $slug, mixed $value): static
    {
        $values = $this->getFlexFieldValues();
        data_set($values, $slug, $value);
        $this->setAttribute(static::flexFieldsColumn(), $values);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setFlexFieldValues(array $values): static
    {
        $this->setAttribute(static::flexFieldsColumn(), $values);

        return $this;
    }

    /**
     * @return list<FlexFieldSchema>
     */
    public function flexFieldSchemas(): array
    {
        return app(FlexFieldSchemaRegistry::class)->forTarget(static::class);
    }

    /**
     * @return list<FlexFieldDefinition>
     */
    public function flexFieldDefinitions(): array
    {
        return app(FlexFieldSchemaRegistry::class)->fieldsForTarget(static::class);
    }

    /**
     * Scope a query to filter models by a flex field value.
     */
    public function scopeWhereFlexField(Builder $query, string $slug, mixed $operator, mixed $value = null): Builder
    {
        $column = static::flexFieldsColumn();

        return $query->where("{$column}->{$slug}", $operator, $value);
    }

    /**
     * Scope a query to filter models by a flex field value with OR logic.
     */
    public function scopeOrWhereFlexField(Builder $query, string $slug, mixed $operator, mixed $value = null): Builder
    {
        $column = static::flexFieldsColumn();

        return $query->orWhere("{$column}->{$slug}", $operator, $value);
    }

    /**
     * Scope a query to filter models by a flex field value within an array of values.
     *
     * @param  array<int|string, mixed>  $values
     */
    public function scopeWhereFlexFieldIn(Builder $query, string $slug, array $values): Builder
    {
        $column = static::flexFieldsColumn();

        return $query->whereIn("{$column}->{$slug}", $values);
    }

    /**
     * Scope a query to filter models where a flex field is null.
     */
    public function scopeWhereFlexFieldNull(Builder $query, string $slug): Builder
    {
        $column = static::flexFieldsColumn();

        return $query->whereNull("{$column}->{$slug}");
    }

    /**
     * Scope a query to filter models where a flex field is not null.
     */
    public function scopeWhereFlexFieldNotNull(Builder $query, string $slug): Builder
    {
        $column = static::flexFieldsColumn();

        return $query->whereNotNull("{$column}->{$slug}");
    }
}

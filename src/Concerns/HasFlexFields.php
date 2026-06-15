<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Concerns;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;
use Bjanczak\FilamentFlexFields\Data\FlexFieldValueChange;
use Bjanczak\FilamentFlexFields\Support\FlexFieldSchemaRegistry;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

trait HasFlexFields
{
    /** @var array<string, mixed>|null */
    protected ?array $flexFieldValuesBeforeChange = null;

    public static function bootHasFlexFields(): void
    {
        static::retrieved(function ($model): void {
            if (! isset($model->attributes[static::flexFieldsColumn()])) {
                $model->setAttribute(static::flexFieldsColumn(), []);
            }
        });

        static::saving(function ($model): void {
            if (! FlexFieldsConfig::isAuditEnabled()) {
                return;
            }

            $column = static::flexFieldsColumn();

            if (! $model->isDirty($column)) {
                return;
            }

            $before = $model->flexFieldValuesBeforeChange ?? $model->getOriginal($column) ?? [];
            $after = $model->getAttribute($column) ?? [];

            if (! is_array($before)) {
                $before = [];
            }

            if (! is_array($after)) {
                $after = [];
            }

            $changes = $model->diffFlexFieldValues($before, $after);

            foreach ($changes as $change) {
                $model->recordFlexFieldValueChange($change);
            }

            $model->flexFieldValuesBeforeChange = null;
        });
    }

    public function initializeHasFlexFields(): void
    {
        $column = static::flexFieldsColumn();

        if (! isset($this->casts[$column])) {
            $this->casts[$column] = 'array';
        }

        if (FlexFieldsConfig::isAuditEnabled()) {
            $auditColumn = static::flexFieldAuditColumn();

            if (! isset($this->casts[$auditColumn])) {
                $this->casts[$auditColumn] = 'array';
            }
        }
    }

    public static function flexFieldsColumn(): string
    {
        return FlexFieldsConfig::getValuesColumn();
    }

    public static function flexFieldAuditColumn(): string
    {
        return FlexFieldsConfig::getAuditColumn();
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
        $this->rememberFlexFieldValuesBeforeChange();

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
        $this->rememberFlexFieldValuesBeforeChange();
        $this->setAttribute(static::flexFieldsColumn(), $values);

        return $this;
    }

    /**
     * @return list<FlexFieldValueChange>
     */
    public function getFlexFieldAuditTrail(): array
    {
        if (! FlexFieldsConfig::isAuditEnabled()) {
            return [];
        }

        $entries = $this->getAttribute(static::flexFieldAuditColumn()) ?? [];

        if (! is_array($entries)) {
            return [];
        }

        return array_map(
            fn (array $entry): FlexFieldValueChange => FlexFieldValueChange::fromArray($entry),
            $entries,
        );
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

    protected function rememberFlexFieldValuesBeforeChange(): void
    {
        if ($this->flexFieldValuesBeforeChange !== null) {
            return;
        }

        $this->flexFieldValuesBeforeChange = $this->getFlexFieldValues();
    }

    protected function recordFlexFieldValueChange(FlexFieldValueChange $change): void
    {
        if (! FlexFieldsConfig::isAuditEnabled()) {
            return;
        }

        $column = static::flexFieldAuditColumn();
        $entries = $this->getAttribute($column) ?? [];

        if (! is_array($entries)) {
            $entries = [];
        }

        $entries[] = $change->toArray();

        $maxEntries = FlexFieldsConfig::getAuditMaxEntries();

        if (count($entries) > $maxEntries) {
            $entries = array_slice($entries, count($entries) - $maxEntries);
        }

        $this->setAttribute($column, array_values($entries));
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<FlexFieldValueChange>
     */
    protected function diffFlexFieldValues(array $before, array $after): array
    {
        $changes = [];
        $keys = array_unique([...array_keys(Arr::dot($before)), ...array_keys(Arr::dot($after))]);

        foreach ($keys as $slug) {
            $oldValue = data_get($before, $slug);
            $newValue = data_get($after, $slug);

            if ($oldValue === $newValue) {
                continue;
            }

            $changes[] = new FlexFieldValueChange(
                slug: $slug,
                oldValue: $oldValue,
                newValue: $newValue,
                userId: $this->resolveFlexFieldAuditUserId(),
                userName: $this->resolveFlexFieldAuditUserName(),
                changedAt: now()->toIso8601String(),
            );
        }

        return $changes;
    }

    protected function resolveFlexFieldAuditUserId(): ?int
    {
        $user = auth()->user();

        return $user ? (int) $user->getAuthIdentifier() : null;
    }

    protected function resolveFlexFieldAuditUserName(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        foreach (['name', 'email'] as $attribute) {
            if (isset($user->{$attribute}) && is_string($user->{$attribute}) && filled($user->{$attribute})) {
                return $user->{$attribute};
            }
        }

        return null;
    }
}

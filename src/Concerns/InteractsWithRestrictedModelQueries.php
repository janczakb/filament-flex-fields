<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait InteractsWithRestrictedModelQueries
{
    protected function restrictRelationshipQueryColumns(Builder $query): Builder
    {
        if ($this->hasOptionLabelFromRecordUsingCallback()) {
            return $query;
        }

        $columns = array_values(array_unique(array_filter(
            $this->resolveRelationshipSelectColumns(),
            fn (?string $column): bool => filled($column),
        )));

        if ($columns === []) {
            return $query;
        }

        return $query->select($columns);
    }

    /**
     * @return list<string>
     */
    protected function resolveRelationshipSelectColumns(): array
    {
        if (! $this->hasRelationship()) {
            return [];
        }

        $relationship = $this->getRelationship();
        $related = $relationship->getRelated();
        $columns = [$related->getQualifiedKeyName()];

        $titleAttribute = $this->getRelationshipTitleAttribute();

        if (filled($titleAttribute) && ! str_contains($titleAttribute, '->')) {
            $columns[] = $this->qualifyRelationshipColumn($titleAttribute);
        }

        foreach ($this->getSearchColumns() ?? [] as $searchColumn) {
            if (filled($searchColumn) && ! str_contains($searchColumn, '->')) {
                $columns[] = $this->qualifyRelationshipColumn($searchColumn);
            }
        }

        return array_values(array_unique($columns));
    }

    protected function qualifyRelationshipColumn(string $column): string
    {
        if (str_contains($column, '.')) {
            return $column;
        }

        $relationship = $this->getRelationship();

        return $relationship->getRelated()->qualifyColumn($column);
    }

    protected function wrapRelationshipQueryModifier(?\Closure $modifyQueryUsing): \Closure
    {
        return function ($component, Builder $query, ?string $search = null) use ($modifyQueryUsing) {
            $query = $component->restrictRelationshipQueryColumns($query);

            if ($modifyQueryUsing === null) {
                return $query;
            }

            return $component->evaluate($modifyQueryUsing, [
                'query' => $query,
                'search' => $search,
            ]) ?? $query;
        };
    }
}

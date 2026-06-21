<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Support\UserSelectQueryCache;
use Filament\Support\Services\RelationshipJoiner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class UserSelectQueryEngine
{
    public function __construct(
        protected UserSelect $select,
        protected UserSelectRecordMapper $mapper,
        protected UserSelectRuntimeState $state,
        protected UserSelectQueryCache $queryCache,
    ) {}

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function searchRecords(?string $search): array
    {
        if ($this->select->getUserModel() === null) {
            return [];
        }

        if (blank($search)) {
            return $this->fetchDefaultSuggestions();
        }

        $term = trim($search);

        if (mb_strlen($term) < $this->select->getMinSearchLength()) {
            return [];
        }

        $cacheKey = $this->searchCacheKey($term);

        if (isset($this->state->searchResultsCache[$cacheKey])) {
            return $this->state->searchResultsCache[$cacheKey];
        }

        $limit = $this->select->getOptionsLimit();
        $query = $this->buildModelQuery();
        $this->applySearchToQuery($query, $term);
        $this->applySearchRelevanceOrdering($query, $term);

        $results = $this->mapper->mapQueryRecordsToOptions(
            $this->rememberQuery($query->limit($limit)),
        );

        $tokens = $this->extractSearchTokens($term);

        if (count($results) < $limit && count($tokens) > 1) {
            $fallbackQuery = $this->buildModelQuery();
            $this->applyMultiTokenSearchToQuery($fallbackQuery, $tokens, array_keys($results));
            $this->applySearchRelevanceOrdering($fallbackQuery, $term);

            $additionalResults = $this->mapper->mapQueryRecordsToOptions(
                $this->rememberQuery($fallbackQuery->limit($limit - count($results))),
            );

            $results = $results + $additionalResults;
        }

        return $this->state->searchResultsCache[$cacheKey] = $results;
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function fetchDefaultSuggestions(): array
    {
        $cacheKey = $this->searchCacheKey(null);

        if (isset($this->state->searchResultsCache[$cacheKey])) {
            return $this->state->searchResultsCache[$cacheKey];
        }

        $keyName = $this->mapper->resolveModelKeyName();

        $results = $this->mapper->mapQueryRecordsToOptions(
            $this->rememberQuery(
                $this->buildModelQuery()
                    ->orderByDesc($keyName)
                    ->limit($this->select->getDefaultSuggestionsLimit())
            ),
        );

        return $this->state->searchResultsCache[$cacheKey] = $results;
    }

    /**
     * @param  array<int|string, mixed>  $values
     */
    public function resolveRecordsForValues(array $values): void
    {
        $uncachedValues = [];

        foreach ($values as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $key = (string) $value;

            if (! isset($this->state->resolvedRecordCache[$key])) {
                $uncachedValues[] = $value;
            }
        }

        if ($uncachedValues === []) {
            return;
        }

        if ($this->select->hasRelationship()) {
            $this->resolveRelationshipRecordsForValues($uncachedValues);

            return;
        }

        $modelClass = $this->select->getUserModel();

        if ($modelClass === null) {
            return;
        }

        $keyName = $this->mapper->resolveModelKeyName();

        $this->rememberQuery(
            $this->buildModelQuery()->whereIn($keyName, $uncachedValues)
        )->each(function (Model $record) use ($keyName): void {
            $key = (string) $record->getAttribute($keyName);
            $this->state->resolvedRecordCache[$key] = $record;
        });
    }

    /**
     * @param  list<mixed>  $values
     */
    public function resolveRelationshipRecordsForValues(array $values): void
    {
        $relationship = Relation::noConstraints(fn () => $this->select->getRelationship());
        $relationshipQuery = app(RelationshipJoiner::class)
            ->prepareQueryForNoConstraints($relationship);

        $qualifiedRelatedKeyName = $this->select->getQualifiedRelatedKeyForRelationship($relationship);

        $relationshipQuery->whereIn($qualifiedRelatedKeyName, $values);

        $modifyQueryUsing = $this->select->getModifyQueryUsing();

        if ($modifyQueryUsing !== null) {
            $relationshipQuery = $this->select->evaluate($modifyQueryUsing, [
                'query' => $relationshipQuery,
                'search' => null,
            ]) ?? $relationshipQuery;
        }

        $keyName = Str::afterLast($qualifiedRelatedKeyName, '.');

        $this->rememberQuery($relationshipQuery)
            ->each(function (Model $record) use ($keyName): void {
                $key = (string) $record->getAttribute($keyName);
                $this->state->resolvedRecordCache[$key] = $record;
            });
    }

    public function resolveRecordForValue(mixed $value): ?Model
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        $key = (string) $value;

        if (isset($this->state->resolvedRecordCache[$key])) {
            return $this->state->resolvedRecordCache[$key];
        }

        if ($this->select->hasRelationship()) {
            $record = $this->select->evaluate($this->select->getSelectedRecordResolver(), [
                'state' => $value,
            ]);

            if ($record instanceof Model) {
                $this->state->resolvedRecordCache[$key] = $record;
            }

            return $record instanceof Model ? $record : null;
        }

        $this->resolveRecordsForValues([$value]);

        return $this->state->resolvedRecordCache[$key] ?? null;
    }

    public function buildModelQuery(): Builder
    {
        $modelClass = $this->select->getUserModel();

        if ($modelClass === null || ! class_exists($modelClass)) {
            throw new InvalidArgumentException('UserSelect requires a valid Eloquent model class via optionModel().');
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException("UserSelect model [{$modelClass}] must extend [".Model::class.'].');
        }

        /** @var Builder $query */
        $query = $modelClass::query();

        $modifyQueryUsing = $this->select->getModifyQueryUsing();

        if ($modifyQueryUsing !== null) {
            $query = $this->select->evaluate($modifyQueryUsing, [
                'query' => $query,
            ]) ?? $query;
        }

        return $this->restrictModelQueryColumns($query);
    }

  protected function applySearchToQuery(Builder $query, string $search): void
    {
        $applySearchUsing = $this->select->getApplySearchUsing();

        if ($applySearchUsing !== null) {
            $this->select->evaluate($applySearchUsing, [
                'query' => $query,
                'search' => trim($search),
            ]);

            return;
        }

        $term = trim($search);
        $nameColumn = $this->qualifySearchColumn($query, $this->select->getNameColumn());
        $emailColumn = $this->select->getEmailColumn();
        $qualifiedEmailColumn = $emailColumn !== null
            ? $this->qualifySearchColumn($query, $emailColumn)
            : null;
        $escaped = addcslashes($term, '%_\\');
        $prefixPattern = $escaped.'%';

        $query->where(function (Builder $inner) use ($nameColumn, $qualifiedEmailColumn, $prefixPattern, $escaped): void {
            $inner->where($nameColumn, 'like', $prefixPattern);

            if ($qualifiedEmailColumn !== null) {
                $inner
                    ->orWhere($qualifiedEmailColumn, 'like', $prefixPattern)
                    ->orWhere($qualifiedEmailColumn, 'like', $escaped.'@%');
            }
        });
    }

    protected function applySearchRelevanceOrdering(Builder $query, string $search): void
    {
        $term = trim($search);
        $escaped = addcslashes($term, '%_\\');
        $prefixPattern = $escaped.'%';
        $nameColumn = $this->qualifySearchColumn($query, $this->select->getNameColumn());
        $emailColumn = $this->select->getEmailColumn();

        if ($emailColumn === null) {
            $query
                ->orderByRaw("CASE WHEN {$nameColumn} LIKE ? THEN 0 ELSE 1 END", [$prefixPattern])
                ->orderBy($nameColumn);

            return;
        }

        $qualifiedEmailColumn = $this->qualifySearchColumn($query, $emailColumn);

        $query
            ->orderByRaw(
                "CASE WHEN {$nameColumn} LIKE ? THEN 0 WHEN {$qualifiedEmailColumn} LIKE ? THEN 1 ELSE 2 END",
                [$prefixPattern, $prefixPattern],
            )
            ->orderBy($nameColumn);
    }

    /**
     * @param  list<string>  $tokens
     * @param  list<int|string>  $excludeValues
     */
    protected function applyMultiTokenSearchToQuery(Builder $query, array $tokens, array $excludeValues): void
    {
        $nameColumn = $this->qualifySearchColumn($query, $this->select->getNameColumn());
        $emailColumn = $this->select->getEmailColumn();
        $qualifiedEmailColumn = $emailColumn !== null
            ? $this->qualifySearchColumn($query, $emailColumn)
            : null;

        $query->where(function (Builder $inner) use ($nameColumn, $qualifiedEmailColumn, $tokens): void {
            foreach ($tokens as $token) {
                $escaped = addcslashes($token, '%_\\');
                $prefixPattern = $escaped.'%';
                $wordPattern = '% '.$escaped.'%';

                $inner->where(function (Builder $tokenQuery) use ($nameColumn, $qualifiedEmailColumn, $prefixPattern, $wordPattern, $escaped): void {
                    $tokenQuery
                        ->where($nameColumn, 'like', $prefixPattern)
                        ->orWhere($nameColumn, 'like', $wordPattern);

                    if ($qualifiedEmailColumn !== null) {
                        $tokenQuery
                            ->orWhere($qualifiedEmailColumn, 'like', $prefixPattern)
                            ->orWhere($qualifiedEmailColumn, 'like', $escaped.'@%');
                    }
                });
            }
        });

        if ($excludeValues !== []) {
            $query->whereNotIn($this->mapper->resolveModelKeyName(), $excludeValues);
        }
    }

    protected function restrictModelQueryColumns(Builder $query): Builder
    {
        if ($this->mapper->needsFullModelForResolvers()) {
            return $query;
        }

        $columns = array_values(array_unique(array_filter([
            $this->mapper->resolveModelKeyName(),
            $this->select->getNameColumn(),
            $this->select->getEmailColumn(),
            $this->select->getAvatarColumn(),
            $this->select->getVerificationColumn(),
        ], fn (?string $column): bool => filled($column))));

        if ($columns === []) {
            return $query;
        }

        return $query->select($columns);
    }

    protected function searchCacheKey(?string $search): string
    {
        return hash('xxh128', implode('|', [
            $this->select->getUserModel() ?? '',
            $search ?? '',
            (string) $this->select->getOptionsLimit(),
            (string) $this->select->getDefaultSuggestionsLimit(),
            $this->select->getNameColumn(),
            $this->select->getEmailColumn() ?? '',
        ]));
    }

    /**
     * @return list<string>
     */
    protected function extractSearchTokens(string $search): array
    {
        $tokens = preg_split('/\s+/u', trim($search)) ?: [];

        return array_values(array_filter(
            $tokens,
            fn (string $token): bool => mb_strlen($token) >= $this->select->getMinSearchLength(),
        ));
    }

    protected function qualifySearchColumn(Builder $query, string $column): string
    {
        if (str_contains($column, '.')) {
            return $column;
        }

        return $query->qualifyColumn($column);
    }

    /**
     * @param  Builder<*>|Relation<*, *, *>|QueryBuilder  $query
     * @return Collection<int, Model>
     */
    protected function rememberQuery(Builder|Relation|QueryBuilder $query): Collection
    {
        return $this->queryCache->remember($query);
    }
}

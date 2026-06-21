<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class UserSelectQueryCache
{
    /** @var array<string, Collection<int, Model>> */
    public array $cache = [];

    /**
     * @param  Builder<*>|Relation<*, *, *>|QueryBuilder  $query
     * @return Collection<int, Model>
     */
    public function remember(Builder|Relation|QueryBuilder $query): Collection
    {
        $cacheKey = hash('xxh128', $query->toSql().'|'.serialize($query->getBindings()));

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        /** @var Collection<int, Model> $result */
        $result = $query->get();

        return $this->cache[$cacheKey] = $result;
    }
}

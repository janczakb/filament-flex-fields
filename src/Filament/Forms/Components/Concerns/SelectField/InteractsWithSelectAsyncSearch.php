<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks;
use Bjanczak\FilamentFlexFields\Support\Select\EntityMentionQuery;
use Bjanczak\FilamentFlexFields\Support\Select\SelectSearchRateLimiter;
use Closure;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Arr;
use Livewire\Attributes\Renderless;

/**
 * @mixin SelectField
 */
trait InteractsWithSelectAsyncSearch
{
    /**
     * @var array<string, mixed>
     */
    protected array $searchResultsCache = [];

    protected bool|Closure $optionGroupSeparators = true;

    protected bool|Closure $paginatedSearchResults = false;

    protected int|Closure $searchResultsPageSize = 50;

    protected ?Closure $getSearchResultsPageUsing = null;

    public function getSearchResults(string $search): array
    {
        $search = $this->resolveEntityMentionSearchQuery($search);

        $cacheKey = $this->searchCacheKey($search);

        if (isset($this->searchResultsCache[$cacheKey])) {
            return $this->searchResultsCache[$cacheKey];
        }

        ObservabilityHooks::record(ObservabilityHooks::EVENT_SELECT_SEARCH, [
            'field' => $this->getName(),
            'query_hash' => $this->hashSearchQueryForObservability($search),
            'query_len' => mb_strlen(trim($search)),
            'source' => 'options',
        ]);

        return $this->searchResultsCache[$cacheKey] = parent::getSearchResults($search);
    }

    public function getSearchResultsFromRelationship(?string $search): array
    {
        $search = $this->resolveEntityMentionSearchQuery((string) $search);

        $cacheKey = $this->searchCacheKey($search);

        if (isset($this->searchResultsCache[$cacheKey])) {
            return $this->searchResultsCache[$cacheKey];
        }

        ObservabilityHooks::record(ObservabilityHooks::EVENT_SELECT_SEARCH, [
            'field' => $this->getName(),
            'query_hash' => $this->hashSearchQueryForObservability((string) $search),
            'query_len' => mb_strlen(trim((string) $search)),
            'source' => 'relationship',
        ]);

        return $this->searchResultsCache[$cacheKey] = parent::getSearchResultsFromRelationship($search);
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function getSearchResultsForJs(string $search): array
    {
        if (! $this->allowSelectSearchRequest()) {
            return [];
        }

        if ($this->hasPaginatedSearchResults()) {
            return array_map(
                fn (array $item): array => [
                    'label' => (string) ($item['label'] ?? ''),
                    'value' => (string) ($item['value'] ?? ''),
                ],
                $this->resolveSearchResultsPageForJs($search)['items'],
            );
        }

        return parent::getSearchResultsForJs($this->resolveEntityMentionSearchQuery($search));
    }

    /**
     * @param  array<int|string>|Closure  $keys
     */
    public function disabledOptions(array|Closure $keys): static
    {
        return $this->disableOptionWhen(function (string $value) use ($keys): bool {
            $disabledKeys = array_map('strval', Arr::wrap($this->evaluate($keys)));

            return in_array((string) $value, $disabledKeys, true);
        }, merge: true);
    }

    public function optionGroupSeparators(bool|Closure $condition = true): static
    {
        $this->optionGroupSeparators = $condition;

        return $this;
    }

    public function hasOptionGroupSeparators(): bool
    {
        return (bool) $this->evaluate($this->optionGroupSeparators);
    }

    public function paginatedSearchResults(bool|Closure $condition = true): static
    {
        $this->paginatedSearchResults = $condition;

        return $this;
    }

    public function hasPaginatedSearchResults(): bool
    {
        if (! $this->hasDynamicSearchResults()) {
            return false;
        }

        return (bool) $this->evaluate($this->paginatedSearchResults);
    }

    public function searchResultsPageSize(int|Closure $size = 50): static
    {
        $this->searchResultsPageSize = $size;

        return $this;
    }

    public function getSearchResultsPageSize(): int
    {
        return max(1, (int) $this->evaluate($this->searchResultsPageSize));
    }

    /**
     * @param  Closure(array{search: string, cursor: ?string, pageSize: int}): (array{items: array<int, array<string, mixed>>, cursor: ?string, hasMore: bool}|array<int, array<string, mixed>>)  $callback
     */
    public function getSearchResultsPageUsing(?Closure $callback): static
    {
        $this->getSearchResultsPageUsing = $callback;

        return $this;
    }

    /**
     * @return array{items: list<array<string, mixed>>, cursor: ?string, hasMore: bool}
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getSearchResultsPageForJs(string $search, ?string $cursor = null): array
    {
        if (! $this->allowSelectSearchRequest()) {
            return [
                'items' => [],
                'cursor' => null,
                'hasMore' => false,
            ];
        }

        return $this->resolveSearchResultsPageForJs($search, $cursor);
    }

    /**
     * @return array{items: list<array<string, mixed>>, cursor: ?string, hasMore: bool}
     */
    protected function resolveSearchResultsPageForJs(string $search, ?string $cursor = null): array
    {
        $search = $this->resolveEntityMentionSearchQuery($search);
        $pageSize = $this->getSearchResultsPageSize();
        $cacheKey = $this->searchCacheKey($search.'|'.($cursor ?? '0').'|'.$pageSize);

        if (isset($this->searchResultsCache[$cacheKey])) {
            $cached = $this->searchResultsCache[$cacheKey];

            if (is_array($cached) && array_key_exists('items', $cached)) {
                /** @var array{items: list<array<string, mixed>>, cursor: ?string, hasMore: bool} $cached */
                return $cached;
            }
        }

        ObservabilityHooks::record(ObservabilityHooks::EVENT_SELECT_SEARCH, [
            'field' => $this->getName(),
            'query_hash' => $this->hashSearchQueryForObservability($search),
            'query_len' => mb_strlen(trim($search)),
            'source' => $this->getSearchResultsPageUsing instanceof Closure ? 'page' : 'page-slice-capped',
            'cursor' => $cursor,
        ]);

        if ($this->getSearchResultsPageUsing instanceof Closure) {
            $result = $this->evaluate($this->getSearchResultsPageUsing, [
                'search' => $search,
                'cursor' => $cursor,
                'pageSize' => $pageSize,
            ]);

            return $this->searchResultsCache[$cacheKey] = $this->normalizeSearchResultsPagePayload($result);
        }

        // Without getSearchResultsPageUsing, never load+slice an unbounded result set.
        // Return at most one page from parent search and stop (hasMore=false).
        if ($cursor !== null && $cursor !== '' && $cursor !== '0') {
            return $this->searchResultsCache[$cacheKey] = [
                'items' => [],
                'cursor' => null,
                'hasMore' => false,
            ];
        }

        $allResults = parent::getSearchResultsForJs($search);
        $items = array_slice($allResults, 0, $pageSize);

        return $this->searchResultsCache[$cacheKey] = [
            'items' => array_values($items),
            'cursor' => null,
            'hasMore' => false,
        ];
    }

    /**
     * @param  array{items?: array<int, array<string, mixed>>, cursor?: ?string, hasMore?: bool}|list<array<string, mixed>>  $payload
     * @return array{items: list<array<string, mixed>>, cursor: ?string, hasMore: bool}
     */
    protected function normalizeSearchResultsPagePayload(array $payload): array
    {
        if (array_is_list($payload)) {
            /** @var list<array<string, mixed>> $items */
            $items = array_values(array_filter(
                $payload,
                fn (mixed $item): bool => is_array($item),
            ));

            return [
                'items' => $items,
                'cursor' => null,
                'hasMore' => false,
            ];
        }

        $rawItems = $payload['items'] ?? [];
        /** @var list<array<string, mixed>> $items */
        $items = array_values(array_filter(
            is_array($rawItems) ? $rawItems : [],
            fn (mixed $item): bool => is_array($item),
        ));
        $cursor = null;

        if (isset($payload['cursor'])) {
            if (is_string($payload['cursor'])) {
                $cursor = $payload['cursor'];
            } elseif (is_int($payload['cursor']) || is_float($payload['cursor'])) {
                $cursor = (string) $payload['cursor'];
            }
        }

        $hasMore = (bool) ($payload['hasMore'] ?? ($cursor !== null && $cursor !== ''));

        if ($cursor === '') {
            $cursor = null;
        }

        if (! $hasMore) {
            $cursor = null;
        }

        return [
            'items' => $items,
            'cursor' => $cursor,
            'hasMore' => $hasMore,
        ];
    }

    protected function resolveEntityMentionSearchQuery(string $search): string
    {
        if (! $this->hasEntityMentions()) {
            return $search;
        }

        $mention = EntityMentionQuery::parse($search, $this->getEntityMentionTrigger());

        return $mention['active'] ? $mention['search'] : $search;
    }

    protected function searchCacheKey(?string $search): string
    {
        return md5($this->getName().'|'.trim((string) $search));
    }

    protected function allowSelectSearchRequest(): bool
    {
        return app(SelectSearchRateLimiter::class)->attempt((string) $this->getName());
    }

    protected function hashSearchQueryForObservability(string $search): string
    {
        return hash('sha256', trim($search));
    }
}

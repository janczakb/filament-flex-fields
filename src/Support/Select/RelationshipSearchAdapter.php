<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Select;

/**
 * Formal contract for async relationship / search adapters used by headless SelectField.
 *
 * Host apps implement search via Livewire {@see \Filament\Forms\Components\Select::getSearchResultsFromRelationship()}
 * or custom {@see \Filament\Forms\Components\Select::getSearchResults()}. The JS side mirrors this with
 * {@see \Bjanczak\FilamentFlexFields\Support\Select\RelationshipSearchAdapter::jsContract()} (createRelationshipSearchAdapter).
 */
final class RelationshipSearchAdapter
{
    public const SOURCE_RELATIONSHIP = 'relationship';

    public const SOURCE_OPTIONS = 'options';

    /**
     * @return array{
     *     fetch: string,
     *     cancel: string,
     *     debounceMs: int,
     *     minSearchLength: int,
     *     warnLargeResultCount: int,
     *     observabilityEvent: string,
     * }
     */
    public static function jsContract(): array
    {
        return [
            'fetch' => 'fetchResults(search, signal?) => Promise<option[]>',
            'cancel' => 'cancel() aborts in-flight fetch',
            'debounceMs' => 1000,
            'minSearchLength' => 0,
            'warnLargeResultCount' => 5000,
            'observabilityEvent' => 'select.search',
        ];
    }

    /**
     * @param  positive-int  $count
     * @param  positive-int  $threshold
     * @return array{type: string, count: int, threshold: int}
     */
    public static function largeResultWarning(int $count, int $threshold = 5000): array
    {
        return [
            'type' => 'large_result_set',
            'count' => $count,
            'threshold' => $threshold,
        ];
    }
}

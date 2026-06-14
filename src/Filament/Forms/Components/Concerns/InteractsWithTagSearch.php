<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Closure;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Livewire\Attributes\Renderless;

trait InteractsWithTagSearch
{
    protected ?Closure $getSearchResultsUsing = null;

    protected int|Closure $minSearchLength = 2;

    public function getSearchResultsUsing(?Closure $callback): static
    {
        $this->getSearchResultsUsing = $callback;

        return $this;
    }

    public function minSearchLength(int|Closure $length): static
    {
        $this->minSearchLength = $length;

        return $this;
    }

    public function shouldSearchSuggestions(): bool
    {
        return $this->getSearchResultsUsing !== null;
    }

    public function getMinSearchLength(): int
    {
        return max(0, (int) $this->evaluate($this->minSearchLength));
    }

    /**
     * @return list<string>
     */
    public function getSuggestionsForJs(): array
    {
        if ($this->shouldSearchSuggestions()) {
            return [];
        }

        return $this->getSuggestions();
    }

    /**
     * @return list<string>
     */
    public function searchTagSuggestions(string $search): array
    {
        if (! $this->shouldSearchSuggestions()) {
            return $this->getSuggestions();
        }

        $term = trim($search);

        if ($term === '' || mb_strlen($term) < $this->getMinSearchLength()) {
            return [];
        }

        $results = $this->evaluate($this->getSearchResultsUsing, [
            'search' => $term,
        ]);

        if (! is_array($results)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $value): string => (string) $value, $results),
            static fn (string $value): bool => $value !== '',
        ));
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function getTagSearchResults(string $search): array
    {
        return $this->searchTagSuggestions($search);
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Icons;

/**
 * Pre-indexed, filter-scoped icon pool for O(1) lookups and ranked search.
 *
 * @phpstan-type IconEntry array{name: string, label: string, set: string, nameLower: string, labelLower: string}
 * @phpstan-type SetSummary array{key: string, prefix: string, label: string, count: int}
 */
final class IconCatalogIndex
{
    /**
     * @param  list<IconEntry>  $entries
     * @param  array<string, list<IconEntry>>  $entriesBySet
     * @param  array<string, true>  $allowedLookup
     * @param  list<SetSummary>  $setSummaries
     */
    public function __construct(
        private readonly array $entries,
        private readonly array $entriesBySet,
        private readonly array $allowedLookup,
        private readonly array $setSummaries,
    ) {}

    public function isAllowed(string $icon): bool
    {
        return isset($this->allowedLookup[trim($icon)]);
    }

    /**
     * @return list<SetSummary>
     */
    public function setSummaries(): array
    {
        return $this->setSummaries;
    }

    /**
     * @return array{
     *     icons: list<array{name: string, label: string}>,
     *     total: int,
     *     page: int,
     *     perPage: int,
     *     hasMore: bool,
     *     sets: list<SetSummary>
     * }
     */
    public function search(
        ?string $query = null,
        ?string $set = null,
        int $page = 1,
        int $perPage = IconCatalogResolver::DEFAULT_PER_PAGE,
        bool $includeSetSummaries = false,
    ): array {
        $perPage = max(1, min($perPage, IconCatalogResolver::MAX_PER_PAGE));
        $page = max(1, $page);
        $term = mb_strtolower(trim((string) $query));

        $pool = $set !== null
            ? ($this->entriesBySet[$set] ?? [])
            : $this->entries;

        if ($term !== '') {
            $tokens = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            $scored = [];

            foreach ($pool as $entry) {
                $score = $this->scoreEntry($entry, $tokens, $term);

                if ($score > 0) {
                    $scored[] = ['score' => $score, 'entry' => $entry];
                }
            }

            usort($scored, static function (array $left, array $right): int {
                if ($left['score'] !== $right['score']) {
                    return $right['score'] <=> $left['score'];
                }

                return strnatcasecmp($left['entry']['name'], $right['entry']['name']);
            });

            $pool = array_map(static fn (array $row): array => $row['entry'], $scored);
        }

        $total = count($pool);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($pool, $offset, $perPage);

        return [
            'icons' => array_map(
                static fn (array $entry): array => [
                    'name' => $entry['name'],
                    'label' => $entry['label'],
                ],
                $slice,
            ),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'hasMore' => ($offset + count($slice)) < $total,
            'sets' => $includeSetSummaries ? $this->setSummaries : [],
        ];
    }

    /**
     * @param  IconEntry  $entry
     * @param  list<string>  $tokens
     */
    private function scoreEntry(array $entry, array $tokens, string $fullTerm): int
    {
        $name = $entry['nameLower'];
        $label = $entry['labelLower'];
        $score = 0;

        if ($name === $fullTerm) {
            $score += 200;
        } elseif (str_starts_with($name, $fullTerm)) {
            $score += 120;
        } elseif (str_contains($name, $fullTerm)) {
            $score += 60;
        }

        if (str_starts_with($label, $fullTerm)) {
            $score += 80;
        } elseif (str_contains($label, $fullTerm)) {
            $score += 40;
        }

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            if ($name === $token) {
                $score += 100;
            } elseif (str_starts_with($name, $token)) {
                $score += 50;
            } elseif (str_contains($name, $token)) {
                $score += 25;
            }

            if (str_starts_with($label, $token)) {
                $score += 35;
            } elseif (str_contains($label, $token)) {
                $score += 15;
            }
        }

        return $score;
    }
}

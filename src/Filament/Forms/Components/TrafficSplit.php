<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Livewire\Partials\PartialsComponentHook;
use InvalidArgumentException;
use LogicException;

class TrafficSplit extends Field
{
    use HasControlSize;

    public const int MAX_LINKED_REPEATER_ITEMS = 5;

    protected string $view = 'filament-flex-fields::forms.components.traffic-split';

    protected int|Closure $segmentCount = 3;

    protected int|Closure $minWeight = 12;

    protected int|Closure $valueThreshold = 18;

    protected string|Closure $variant = 'default';

    protected string|Closure|null $linkedRepeaterPath = null;

    /**
     * @var array<int, string> | Closure | null
     */
    protected array|Closure|null $labels = null;

    /**
     * @var array<int, int> | Closure
     */
    protected array|Closure $lockedSegments = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(fn (TrafficSplit $component): array => $component->equalSplit());

        $this->afterStateHydrated(function (TrafficSplit $component, mixed $state): void {
            $normalized = $component->normalizeWeights(is_array($state) ? $state : null);

            if ($component->weightsMatchState($normalized, is_array($state) ? $state : null)) {
                return;
            }

            $component->state($normalized);
        });
    }

    public function segmentCount(int|Closure $count): static
    {
        $this->segmentCount = $count;

        return $this;
    }

    public function minWeight(int|Closure $weight): static
    {
        $this->minWeight = $weight;

        return $this;
    }

    public function valueThreshold(int|Closure $threshold): static
    {
        $this->valueThreshold = $threshold;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * @param  array<int, string> | Closure | null  $labels
     */
    public function labels(array|Closure|null $labels): static
    {
        $this->labels = $labels;

        return $this;
    }

    /**
     * Sync segment count (and optional numeric labels) with a Filament Repeater / Builder list.
     *
     * The repeater should call {@see Repeater::partiallyRenderAfterActionsCalled()}
     * and {@see Repeater::partiallyRenderComponentsAfterStateUpdated()} with this field's name,
     * plus {@see Repeater::afterStateUpdated()} with {@see TrafficSplit::repeaterSyncCallback()}.
     * Do not mark the repeater {@see Field::live()}.
     * When the item count changes, weights are rebalanced to an equal split automatically.
     *
     * @param  string|Closure  $repeaterStatePath  Relative state path of the repeater (e.g. `testing_urls`).
     * @param  bool  $numericLabels  Use `1`, `2`, `3`… as segment labels (Dub-style).
     * @param  int  $minimumItems  Hide the split until the repeater has at least this many items.
     */
    public function linkedToRepeater(
        string|Closure $repeaterStatePath,
        bool $numericLabels = true,
        int $minimumItems = 2,
    ): static {
        $this->linkedRepeaterPath = $repeaterStatePath;

        $this->segmentCount(function (Get $get) use ($repeaterStatePath): int {
            $count = $this->resolveLinkedRepeaterItemCount($get, $repeaterStatePath);

            return max(2, min(self::MAX_LINKED_REPEATER_ITEMS, $count));
        });

        $this->visible(function (Get $get) use ($repeaterStatePath, $minimumItems): bool {
            return $this->resolveLinkedRepeaterItemCount($get, $repeaterStatePath) >= $minimumItems;
        });

        if ($numericLabels) {
            $this->labels(function (Get $get) use ($repeaterStatePath): array {
                $count = max(2, min(self::MAX_LINKED_REPEATER_ITEMS, $this->resolveLinkedRepeaterItemCount($get, $repeaterStatePath)));

                return array_map(
                    fn (int $index): string => (string) ($index + 1),
                    range(0, $count - 1),
                );
            });
        }

        $this->live(false);

        return $this;
    }

    public function getLinkedRepeaterPath(): ?string
    {
        $path = $this->evaluate($this->linkedRepeaterPath);

        return is_string($path) && filled($path) ? $path : null;
    }

    public function getLinkedRepeaterWirePath(): ?string
    {
        $repeaterPath = $this->getLinkedRepeaterPath();

        if ($repeaterPath === null) {
            return null;
        }

        $containerPath = $this->getContainer()->getStatePath();

        if (filled($containerPath)) {
            return "{$containerPath}.{$repeaterPath}";
        }

        return $repeaterPath;
    }

    /**
     * Keep linked weights in sync when the repeater row count changes.
     */
    public function repeaterSyncCallback(): Closure
    {
        $repeaterPath = $this->getLinkedRepeaterPath();
        $statePath = $this->getName();

        return function (Get $get, Set $set) use ($repeaterPath, $statePath): void {
            if ($repeaterPath === null) {
                return;
            }

            $items = $get($repeaterPath);
            $count = is_array($items) ? count($items) : 0;

            $set($statePath, self::equalSplitForCount(min($count, self::MAX_LINKED_REPEATER_ITEMS)));
        };
    }

    public function isLinkedToRepeater(): bool
    {
        return $this->getLinkedRepeaterPath() !== null;
    }

    /**
     * Rebalance stored weights when the linked repeater changes segment count.
     */
    public function syncWeightsToSegmentCount(): void
    {
        $state = $this->getState();
        $normalized = $this->normalizeWeights(is_array($state) ? $state : null);

        if ($this->weightsMatchState($normalized, is_array($state) ? $state : null)) {
            return;
        }

        $this->state($normalized);
    }

    public function partiallyRender(): void
    {
        if ($this->isLinkedToRepeater()) {
            $this->syncWeightsToSegmentCount();
        }

        app(PartialsComponentHook::class)->renderPartial($this->getLivewire(), function (): array {
            $key = $this->getKey();

            if (blank($key)) {
                throw new LogicException('A [key()] or [statePath()] is required to partially render a component.');
            }

            return [
                "schema-component::{$key}" => $this->toSchemaHtml(...),
            ];
        });
    }

    protected function resolveLinkedRepeaterItemCount(Get $get, string|Closure $repeaterStatePath): int
    {
        $path = is_string($repeaterStatePath)
            ? $repeaterStatePath
            : (string) $this->evaluate($repeaterStatePath);

        $items = $get($path);

        if (! is_array($items)) {
            return 0;
        }

        return count($items);
    }

    /**
     * @param  array<int, int> | Closure  $indices
     */
    public function lockedSegments(array|Closure $indices): static
    {
        $this->lockedSegments = $indices;

        return $this;
    }

    public function getSegmentCount(): int
    {
        $count = (int) $this->evaluate($this->segmentCount);

        return max(2, min(self::MAX_LINKED_REPEATER_ITEMS, $count));
    }

    public function getMinWeight(): int
    {
        $minWeight = (int) $this->evaluate($this->minWeight);

        return max(1, min(100, $minWeight));
    }

    public function getValueThreshold(): int
    {
        $threshold = (int) $this->evaluate($this->valueThreshold);

        return max($this->getMinWeight() + 1, min(100, $threshold));
    }

    public function getVariant(): string
    {
        return $this->evaluate($this->variant);
    }

    /**
     * @return array<int, string> | null
     */
    public function getLabels(): ?array
    {
        $labels = $this->evaluate($this->labels);

        if ($labels === null) {
            return null;
        }

        return array_values($labels);
    }

    public function getSegmentLabel(int $index): string
    {
        $labels = $this->getLabels();

        if ($labels !== null && array_key_exists($index, $labels)) {
            return $labels[$index];
        }

        return (string) ($index + 1);
    }

    /**
     * @return list<int>
     */
    public function getLockedSegments(): array
    {
        /** @var array<int, int> $indices */
        $indices = $this->evaluate($this->lockedSegments);
        $count = $this->getSegmentCount();

        $normalized = array_values(array_unique(array_map(
            fn (mixed $index): int => (int) $index,
            $indices,
        )));

        sort($normalized);

        return array_values(array_filter(
            $normalized,
            fn (int $index): bool => $index >= 0 && $index < $count,
        ));
    }

    public function isSegmentLocked(int $index): bool
    {
        return in_array($index, $this->getLockedSegments(), true);
    }

    /**
     * @return list<int>
     */
    public function equalSplit(): array
    {
        return self::equalSplitForCount($this->getSegmentCount());
    }

    /**
     * @return list<int>
     */
    public static function equalSplitForCount(int $count): array
    {
        $count = max(2, min(self::MAX_LINKED_REPEATER_ITEMS, $count));
        $base = intdiv(100, $count);
        $remainder = 100 % $count;

        return array_map(
            fn (int $index): int => $base + ($index < $remainder ? 1 : 0),
            range(0, $count - 1),
        );
    }

    /**
     * @param  list<int>  $normalized
     * @param  array<int, int> | null  $state
     */
    public function weightsMatchState(array $normalized, ?array $state): bool
    {
        if ($state === null) {
            return false;
        }

        $current = array_values(array_map(
            fn (mixed $weight): int => (int) $weight,
            $state,
        ));

        return $current === $normalized;
    }

    /**
     * @param  array<int, int> | null  $weights
     * @return list<int>
     */
    public function normalizeWeights(?array $weights): array
    {
        $count = $this->getSegmentCount();
        $minWeight = $this->getMinWeight();

        if ($minWeight * $count > 100) {
            throw new InvalidArgumentException('Traffic split minWeight * segmentCount cannot exceed 100.');
        }

        if ($weights === null || count($weights) !== $count) {
            return $this->equalSplit();
        }

        $weights = array_map(
            fn (mixed $weight): int => max($minWeight, (int) $weight),
            array_values($weights),
        );

        $locked = $this->getLockedSegments();

        if ($locked === []) {
            return array_sum($weights) === 100
                ? $weights
                : $this->equalSplit();
        }

        foreach ($locked as $index) {
            $weights[$index] = max($minWeight, $weights[$index]);
        }

        if (array_sum($weights) === 100) {
            return $weights;
        }

        return $this->redistributeUnlocked($weights, $locked);
    }

    /**
     * @param  list<int>  $weights
     * @param  list<int>  $locked
     * @return list<int>
     */
    protected function redistributeUnlocked(array $weights, array $locked): array
    {
        $count = count($weights);
        $minWeight = $this->getMinWeight();
        $unlocked = array_values(array_diff(range(0, $count - 1), $locked));

        if ($unlocked === []) {
            return array_sum($weights) === 100 ? $weights : $this->equalSplit();
        }

        $lockedSum = array_sum(array_map(
            fn (int $index): int => $weights[$index],
            $locked,
        ));

        $remaining = 100 - $lockedSum;

        if ($remaining < $minWeight * count($unlocked)) {
            return $this->equalSplit();
        }

        $base = intdiv($remaining, count($unlocked));
        $remainder = $remaining % count($unlocked);

        $result = $weights;

        foreach ($unlocked as $position => $index) {
            $result[$index] = $base + ($position < $remainder ? 1 : 0);
        }

        return $result;
    }
}

# TrafficSplit

[← Back to Table of Contents](index.md)


### Summary

Visual editor for splitting **100%** across 2–5 draggable segments (traffic allocation, weighted distribution).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrafficSplit` |
| **State type** | `array&lt;int, int&gt;` — weights summing to **100** |
| **Model cast** | `'split' =&gt; 'array'` or `'json'` |
| **FieldType** | `traffic_split` |

Example state: `[40, 35, 25]`.

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrafficSplit;

TrafficSplit::make('traffic_split')
    ->label('Traffic allocation')
    ->segmentCount(4)
    ->minWeight(10)
    ->valueThreshold(15)
    ->labels(['Control', 'Variant A', 'Variant B', 'Variant C'])
    ->lockedSegments([0])
    ->variant('default');
```

### Validation

No automatic Laravel rules. Ensure segment weights sum to 100 in application logic if persisted outside the component.

The component normalizes state on hydration via `normalizeWeights()`.

### Default state

Equal split across segments (`equalSplit()`), e.g. three segments → `[34, 33, 33]`.

### Configuration API

#### `linkedToRepeater(string|Closure $repeaterPath)`


Links the split control dynamically to a Repeater state path. The number of split segments will automatically match the items in the Repeater, syncing weights.

```php
TrafficSplit::make('weights')
    ->linkedToRepeater('variants');
```
### State normalization

On load, weights are passed through `normalizeWeights()`:

- Ensures segment count matches `segmentCount()`
- Enforces `minWeight`
- Respects `lockedSegments`
- Rebalances unlocked segments so the total equals **100**

Throws `InvalidArgumentException` when `minWeight × segmentCount &gt; 100`.

### FlexField schema config

| Config key | Maps to |
|------------|---------|
| `segment_count` | `segmentCount()` |
| `min_weight` | `minWeight()` |
| `value_threshold` | `valueThreshold()` |
| `size` | `size()` |
| `variant` | `variant()` |
| `labels` | `labels()` |
| `locked_segments` | `lockedSegments()` |

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `equalSplit()` | `list&lt;int&gt;` | Equal weight distribution across `segmentCount()` summing to **100** (remainder distributed to first segments). Default state source. |
| `normalizeWeights(?array $weights)` | `list&lt;int&gt;` | Normalizes weights to segment count, enforces `minWeight`, respects `lockedSegments`, rebalances to total **100**. Returns `equalSplit()` when input is invalid. |

### CSS / stacking

Drag handles use a **local** stacking context (`z-index: 2` inside `.fff-traffic-split__track` with `isolation: isolate`) so grey divider bars do not paint above the Filament sticky header when scrolling.

---

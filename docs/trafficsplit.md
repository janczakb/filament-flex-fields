---
title: "TrafficSplit"
description: Interactive percentage-based weight distributor for A/B testing and traffic allocation.
---

[← Back to Table of Contents](/docs/index)

### Summary

A specialized input for distributing 100% across multiple segments. Features interactive sliders, numeric inputs, and "locking" capabilities. Commonly used for A/B test traffic allocation, budget distribution, or weighting algorithms.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrafficSplit` |
| **State type** | `array<int, int>` — list of weights summing to 100 |
| **Model cast** | `'traffic_weights' => 'array'` |
| **FieldType** | `traffic-split` |
| **Playground** | `traffic-split` slug in Flex Fields playground |
| **Default segments** | `3` |
| **Default state** | Equal split (e.g. `[34, 33, 33]`) |

---

### Basic usage

#### Standard 3-way split
```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrafficSplit;

TrafficSplit::make('weights')
    ->label('Traffic Allocation')
    ->segmentCount(3)
    ->labels(['Variant A', 'Variant B', 'Control']);
```

#### Locked segments
Prevent users from changing specific weights while allowing others to rebalance.
```php
TrafficSplit::make('weights')
    ->segmentCount(4)
    ->lockedSegments([0, 3]); // First and last segments are locked
```

---

### State & validation

#### Stored value
The field stores an array of integers. The component automatically ensures that the sum of all integers is exactly **100**.

```php
$record->weights; // [50, 25, 25]
```

#### Automatic normalization
When the component is hydrated or the segment count changes, it automatically rebalances weights to reach 100%. If a state is invalid (e.g. sums to 90%), it will be normalized to an equal split or adjusted based on locked segments.

---

### Advanced Features

#### Linking to a Repeater
`TrafficSplit` can dynamically sync its segment count with a Filament Repeater. This is perfect for split-testing URLs or features where the number of variants is dynamic.

```php
Repeater::make('variants')
    ->schema([
        TextInput::make('url')->required(),
    ])
    ->afterStateUpdated(fn (TrafficSplit $component) => $component->repeaterSyncCallback())
    ->live(),

TrafficSplit::make('allocation')
    ->linkedToRepeater('variants');
```

#### Minimum Weight
Ensure no segment falls below a certain percentage (e.g. for statistical significance).

```php
TrafficSplit::make('weights')
    ->minWeight(10); // No slider can go below 10%
```

---

### Configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `segmentCount(int\|Closure $count)` | Setup | `3` | Number of segments (max 5) |
| `minWeight(int\|Closure $weight)` | Setup | `12` | Minimum allowed weight per segment |
| `valueThreshold(int\|Closure $val)` | Setup | `18` | Visual threshold for label display |
| `variant(string\|Closure $variant)` | Setup | `'default'` | Visual variant |
| `labels(array\|Closure\|null $labels)` | Setup | `null` | Custom segment labels |
| `lockedSegments(array\|Closure $ids)` | Setup | `[]` | Indices of segments to lock |
| `linkedToRepeater(...)` | Setup | — | Sync with a Repeater state path |
| `size(string\|Closure $size)` | Setup | `'md'` | `sm`, `md`, `lg` |

---

### Real-world examples

#### A/B Test Configuration
```php
Section::make('Experiment Settings')
    ->schema([
        TextInput::make('name')->required(),
        TrafficSplit::make('traffic_split')
            ->label('Traffic Distribution')
            ->segmentCount(2)
            ->labels(['Original', 'Challenger'])
            ->minWeight(5)
            ->required(),
    ]);
```

---

### Playground

`/admin/flex-fields-playground/traffic-split`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [FlexSlider](/docs/flexslider) | Single value selection on a range |
| [TrackSlider](/docs/trackslider) | Range selection (min/max) |
| [ProgressBar](/docs/progressbar) | Read-only percentage display |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-traffic-split` | Root wrapper |
| `fff-traffic-split__track` | The horizontal slider track |
| `fff-traffic-split__segment` | Individual color segment |
| `fff-traffic-split__handle` | Draggable divider |
| `fff-traffic-split__inputs` | Grid of numeric inputs |
| `fff-traffic-split__label` | Segment label text |
| `fff-traffic-split__value` | Percentage value display |

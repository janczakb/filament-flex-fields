---
title: "BubbleChoiceField"
description: Bubble multi-select with center magnification, fringe shrink on pan, and optional scalloped selection.
---

![BubbleChoiceField](/art/bubble.webp)

[← Back to Table of Contents](/docs/index)

### Summary

Phrase options render as bubbles in a pannable arena. Bubbles near the center stay full size; bubbles toward the edges shrink smoothly as you scroll. Scrollbars are hidden. Selecting a bubble applies the scalloped flower mask and a lighter primary fill. The arena defaults to `#ebebec` (charcoal `#2a2a2c` in dark mode). Idle bubbles use a darker primary; selected bubbles use a lighter primary. Hover darkens the bubble’s own color slightly.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\BubbleChoiceField` |
| **State type** | `array<string\|int>` — list of selected keys |
| **Model cast** | `'habits' => 'array'` |
| **FieldType** | `bubble_choice` |
| **Playground** | `bubble-choice` |

---

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BubbleChoiceField;

BubbleChoiceField::make('habits')
    ->options([
        'water' => 'Water',
        'yoga' => 'Yoga',
        'journal' => 'Journal',
        'workout' => 'Workout',
    ])
    ->layoutOptions([
        'size' => 160,
        'minSize' => 25,
        'gutter' => 8,
        'numCols' => 6,
        'fringeWidth' => 160,
        'yRadius' => 130,
        'xRadius' => 220,
        'cornerRadius' => 50,
        'compact' => true,
        'gravitation' => 5,
        'provideProps' => true,
    ])
    ->minItems(1)
    ->maxItems(4);
```

#### Colors

```php
BubbleChoiceField::make('habits')
    ->options([...])
    ->arenaColor('#0f172a')
    ->bubbleColor('#1e40af')
    ->selectedBubbleColor('#93c5fd');
```

#### Per-option rich data

```php
BubbleChoiceField::make('mood')
    ->options([
        'calm' => [
            'label' => 'Calm',
            'description' => 'CLM',
            'color' => '#1e3a8a',
            'selectedColor' => '#93c5fd',
            'image' => asset('images/calm.jpg'),
            'imageMode' => 'icon', // or 'background'
        ],
        'focus' => [
            'label' => 'Focus',
            'description' => 'FCS',
            'image' => asset('images/focus.jpg'),
            'imageMode' => 'background',
        ],
    ]);
```

---

### Layout API

| Option | Method | Default | Description |
|--------|--------|---------|-------------|
| `size` | `bubbleSize()` | `160` | Max bubble diameter (px) |
| `minSize` | `minSize()` | `25` | Min diameter at the outer fringe |
| `gutter` | `gutter()` | `8` | Gap between bubbles |
| `numCols` | `numCols()` | `6` | Columns in the staggered packing |
| `fringeWidth` | `fringeWidth()` | `160` | Width of the shrink zone |
| `yRadius` | `yRadius()` | `130` | Half-height of the full-size center |
| `xRadius` | `xRadius()` | `220` | Half-width of the full-size center |
| `cornerRadius` | `cornerRadius()` | `50` | Rounded corner of the center region |
| `compact` | `compact()` | `true` | Pull fringe bubbles inward while shrinking |
| `gravitation` | `gravitation()` | `5` | Extra pull outside the fringe |
| `provideProps` | `provideProps()` | `true` | Expose runtime size CSS variables on cells |

Or set them together with `layoutOptions([...])`.

---

### Other configuration

| Method | Default | Description |
|--------|---------|-------------|
| `selectedShape()` | `'scallop'` | `scallop`, `grow`, `circle` |
| `arenaHeight()` | `'500px'` | Visible arena height (default 500px) |
| `description` on options | — | Secondary line under the label |
| `imageMode` on options | `'background'` | `background` or `icon` (center circle) |
| `arenaColor()` | `#ebebec` / `#2a2a2c` | Box background (light / dark) |
| `minItems()` / `maxItems()` / `exactItems()` | `null` | Selection count validation |
| `required()` | Filament default | Requires at least 1 selection when `minItems` / `exactItems` are not set |
| `variant()` | `'soft'` | `soft`, `solid`, `outline` |
| `size()` | `'md'` | Label size token `sm`/`md`/`lg` |

---

### Playground

`/admin/flex-fields-playground/bubble-choice`

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [ChoiceCheckboxCards](/docs/choicecheckboxcards) | Structured multi-select cards |
| [ImageChoiceCards](/docs/imagechoicecards) | Image-forward card grid |
| [TagsField](/docs/tags-field) | Freeform multi values |

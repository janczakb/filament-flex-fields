---
title: "NpsField"
description: NPS, CSAT, and Likert scale inputs with three visual variants.
---

![NpsField](/art/sc-33.png)

[← Back to Table of Contents](/docs/index)

### Summary

Single-select **survey scale** for Net Promoter Score (0–10), CSAT, satisfaction ratings, and textual Likert scales. Three visual variants share one API: **pills** (sliding segment control), **segments** (full-width bordered bar), and **emojis** (icon or image rings with labels).

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\NpsField` |
| **State type** | `string\|int\|null` — one option key, or `null` when unset |
| **Model cast** | `'nps_score' => 'integer'` · `'feedback_agreement' => 'string'` |
| **FieldType** | *(no dedicated FieldType mapping yet — use the class directly)* |
| **Playground** | `nps-field` slug in Flex Fields playground |
| **Default variant** | `pills` |
| **Default scale** | `0` through `10` (numeric keys and labels) |
| **Default state** | `null` (no pre-selection) |

Works with all standard Filament field APIs: `required()`, `disabled()`, `hidden()`, `live()`, `afterStateUpdated()`, validation rules, etc.

---

### Variants at a glance

| Variant | Value | Best for |
|---------|-------|----------|
| **Pills** | `'pills'` (default) | Classic NPS 0–10, compact scales, sliding primary indicator |
| **Segments** | `'segments'` | Full-width 0–10 bar, experience ratings, Likert with many options |
| **Emojis** | `'emojis'` | Mood / satisfaction with icons or bundled emoji images |

```php
NpsField::make('nps')->variant('pills');     // default
NpsField::make('nps')->variant('segments');
NpsField::make('mood')->variant('emojis');
```

---

### Basic usage

#### Standard NPS (0–10)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NpsField;

NpsField::make('nps_score')
    ->label('How likely are you to recommend us to a friend or colleague?')
    ->minLabel('Not at all likely')
    ->maxLabel('Extremely likely')
    ->required();
```

#### Filament resource form

```php
use Filament\Forms\Form;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NpsField;

public static function form(Form $form): Form
{
    return $form->schema([
        NpsField::make('nps_score')
            ->label('Recommendation score')
            ->minLabel('Unlikely')
            ->maxLabel('Very likely')
            ->colorCoded()
            ->columnSpanFull()
            ->required(),
    ]);
}
```

#### Color-coded NPS (Detractor / Passive / Promoter)

```php
NpsField::make('nps_score')
    ->label('Recommendation')
    ->minLabel('Detractor')
    ->maxLabel('Promoter')
    ->colorCoded();
```

| Score | Tone | Selected color |
|-------|------|----------------|
| 0–6 | Detractor | Red (`danger`) |
| 7–8 | Passive | Yellow (`warning`) |
| 9–10 | Promoter | Green (`success`) |

---

### State & validation

#### Stored value

State is the **option key** from `options()` — not the display label.

```php
// Default 0–10 scale
$record->nps_score; // int|null — e.g. 9

// Likert scale with string keys
$record->agreement; // string|null — e.g. 'sa'
```

#### Default: empty initial state

The field defaults to **`null`**. Nothing is selected until the user picks an option. All variants support this.

```php
NpsField::make('nps_score')
    ->label('Optional feedback')
    // default is null — no ->default() needed
```

Pre-fill when editing:

```php
NpsField::make('nps_score')
    ->default(8);
```

#### Validation rules (built-in)

| Rule | When |
|------|------|
| `nullable` | Always (unless `required()`) |
| `Rule::in(...)` | Value must match a configured option key |
| `required` | When `->required()` |

```php
NpsField::make('nps_score')
    ->options(array_combine(range(1, 5), range(1, 5)))
    ->required();

// Custom rules stack on top
NpsField::make('nps_score')
    ->rules(['integer', 'min:1']);
```

#### Optional fields — deselect on second click

When the field is **not** `required()`, clicking the **already selected** option clears the value back to `null`. Required fields always keep one selection.

```php
NpsField::make('nps_score')
    ->label('Optional NPS')
    ->minLabel('Low')
    ->maxLabel('High');
// User can click 7, then click 7 again to clear
```

---

### Custom scales

#### 5-point CSAT (1–5)

```php
NpsField::make('csat')
    ->label('Overall satisfaction')
    ->options(array_combine(range(1, 5), range(1, 5)))
    ->minLabel('Very dissatisfied')
    ->maxLabel('Very satisfied')
    ->required();
```

#### 3-point quick rating

```php
NpsField::make('quick_rating')
    ->options([
        1 => 'Bad',
        2 => 'Okay',
        3 => 'Good',
    ])
    ->size('sm');
```

#### Textual Likert scale (string keys)

```php
NpsField::make('agreement')
    ->label('I find this product easy to use')
    ->variant('segments')
    ->options([
        'sd' => 'Strongly Disagree',
        'd' => 'Disagree',
        'n' => 'Neutral',
        'a' => 'Agree',
        'sa' => 'Strongly Agree',
    ])
    ->required();
```

#### Dynamic options with a closure

```php
NpsField::make('priority')
    ->options(fn (): array => [
        'low' => __('Low'),
        'medium' => __('Medium'),
        'high' => __('High'),
    ]);
```

---

### Variant: Pills (default)

Sliding pill indicator on a gray track — same visual language as [SegmentControl](/docs/segmentcontrol). Best for numeric scales and compact layouts.

```php
NpsField::make('nps_score')
    ->variant('pills')
    ->minLabel('Not likely')
    ->maxLabel('Very likely');
```

#### Sizes

```php
NpsField::make('nps_score')->size('sm'); // default: md
NpsField::make('nps_score')->size('lg');
```

See [Control size](/docs/shared-concepts) (`sm`, `md`, `lg`).

#### Rounding

```php
NpsField::make('nps_score')
    ->rounding('full'); // 'default', 'native', 'md', 'lg', 'xl', 'full'
```

Per-field `rounding()` overrides the global default from `config/filament-flex-fields.php` (`ui.field_rounding`).

---

### Variant: Segments

Full-width bordered bar with vertical dividers between options. Ideal for 0–10 NPS and multi-option Likert rows.

```php
NpsField::make('experience')
    ->label('How would you rate your experience?')
    ->variant('segments')
    ->size('md')
    ->rounding('full')
    ->required();
```

#### Segments with sizes and rounding

```php
NpsField::make('experience_sm')
    ->variant('segments')
    ->size('sm')
    ->rounding('full');

NpsField::make('experience_lg')
    ->variant('segments')
    ->size('lg')
    ->rounding('md');
```

---

### Variant: Emojis

Circular rings with a visual inside each option and a text label below. Three ways to supply visuals (priority order):

1. **`icons()`** — Filament icon strings (Heroicon, Gravity, Blade Icons, …)
2. **`emojiImages()`** — custom image URLs
3. **Bundled webp** — for numeric keys `0`–`4` when neither of the above is set

#### Bundled emoji images (5-point mood scale)

```php
NpsField::make('mood')
    ->label('How are you feeling today?')
    ->variant('emojis')
    ->options([
        0 => 'Awful',
        1 => 'Poor',
        2 => 'Neutral',
        3 => 'Good',
        4 => 'Excellent',
    ])
    ->required();
```

Bundled assets ship in the package (`resources/dist/assets/nps-field/emojis/0.webp` … `4.webp`) and are published to `public/filament-flex-fields-assets/` via `php artisan filament:assets`.

#### Custom Gravity / Heroicon icons

```php
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

NpsField::make('mood')
    ->variant('emojis')
    ->options([
        0 => 'Awful',
        1 => 'Poor',
        2 => 'Neutral',
        3 => 'Good',
        4 => 'Excellent',
    ])
    ->icons([
        0 => GravityIcon::CircleXmark,
        1 => GravityIcon::Hand,
        2 => GravityIcon::FaceSmile,
        3 => GravityIcon::Heart,
        4 => GravityIcon::Star,
    ]);
```

Heroicon example:

```php
NpsField::make('mood')
    ->variant('emojis')
    ->options([0 => 'Bad', 1 => 'Good'])
    ->icons([
        0 => 'heroicon-o-face-frown',
        1 => 'heroicon-o-face-smile',
    ]);
```

When `icons()` is set for a key, it **overrides** bundled webp for that key.

#### Custom image URLs

```php
NpsField::make('mood')
    ->variant('emojis')
    ->options([
        0 => 'Awful',
        1 => 'Poor',
        2 => 'Neutral',
        3 => 'Good',
        4 => 'Excellent',
    ])
    ->emojiImages([
        0 => asset('images/survey/awful.svg'),
        1 => asset('images/survey/poor.svg'),
        2 => asset('images/survey/neutral.svg'),
        3 => asset('images/survey/good.svg'),
        4 => asset('images/survey/excellent.svg'),
    ]);
```

#### Emoji sizes

```php
NpsField::make('mood')->variant('emojis')->size('sm');
NpsField::make('mood')->variant('emojis')->size('lg');
```

---

### Color coding & custom colors

#### Built-in NPS color coding

```php
NpsField::make('nps_score')
    ->colorCoded()
    ->variant('pills'); // works on all variants
```

#### Custom per-option background colors

Map Filament semantic names, hex, or rgb to option keys:

```php
NpsField::make('risk')
    ->options(array_combine(range(1, 5), range(1, 5)))
    ->colors([
        'danger' => [1, 2],
        'warning' => [3],
        'success' => [4, 5],
    ]);
```

With hex:

```php
NpsField::make('heat')
    ->options(['cold' => 'Cold', 'warm' => 'Warm', 'hot' => 'Hot'])
    ->colors([
        '#3b82f6' => 'cold',
        '#f59e0b' => 'warm',
        '#ef4444' => 'hot',
    ]);
```

#### Custom selected text colors

```php
NpsField::make('nps_score')
    ->options(array_combine(range(0, 10), range(0, 10)))
    ->colors(['danger' => range(0, 6), 'success' => range(9, 10)])
    ->textColors([
        '#ffffff' => range(0, 6),
        '#ffffff' => range(9, 10),
    ]);
```

When `colorCoded()` is enabled, built-in detractor/passive/promoter colors apply unless you override with `colors()` / `textColors()`.

---

### Disabled options

Disable individual keys without disabling the whole field:

```php
NpsField::make('nps_score')
    ->disabledOptions([0, 1])
    ->helperText('Scores 0 and 1 are not available for this survey.');
```

Dynamic:

```php
NpsField::make('nps_score')
    ->disabledOptions(fn (): array => auth()->user()?->isDemo() ? range(0, 5) : []);
```

Combine with field-level `disabled()`:

```php
NpsField::make('nps_score')
    ->disabled()
    ->default(8);
```

---

### Edge labels

Show helper text under the left and right ends of the scale:

```php
NpsField::make('nps_score')
    ->minLabel('Not at all likely')
    ->maxLabel('Extremely likely');
```

Only the extremes are labeled — option labels come from `options()` values.

---

### Complete configuration API

All methods accept `Closure` unless noted.

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `variant(string\|Closure $variant)` | Setup | `'pills'` | Visual variant: `pills`, `segments`, `emojis` |
| `options(array\|Closure $options)` | Setup | `0`–`10` | Option keys => display labels |
| `minLabel(string\|Closure\|null $label)` | Setup | `null` | Left extreme caption |
| `maxLabel(string\|Closure\|null $label)` | Setup | `null` | Right extreme caption |
| `colorCoded(bool\|Closure $condition = true)` | Setup | `false` | NPS detractor/passive/promoter colors |
| `colors(array\|Closure\|null $colors)` | Setup | `[]` | Custom selected background colors per key |
| `textColors(array\|Closure\|null $textColors)` | Setup | `[]` | Custom selected text colors per key |
| `icons(array\|Closure $icons)` | Setup | `[]` | Emoji variant: key => Filament icon string |
| `emojiImages(array\|Closure\|null $images)` | Setup | `null` | Emoji variant: key => image URL |
| `disabledOptions(array\|Closure $keys)` | Setup | `[]` | Keys that cannot be selected |
| `size(string\|ControlSize\|Closure $size)` | Setup | `'md'` | Control size: `sm`, `md`, `lg` |
| `rounding(string\|Closure\|null $rounding)` | Setup | config | Border radius token |
| `default(mixed $state)` | Setup | `null` | Initial state (Filament API) |
| `required(bool\|Closure $condition = true)` | Setup | optional | Require a selection; disables deselect |
| `disabled(bool\|Closure $condition = true)` | Setup | — | Disable entire field (Filament API) |

#### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getOptions()` | `array<string\|int, string>` | Resolved options |
| `getOptionKeys()` | `list<string\|int>` | Option keys for validation |
| `getVariant()` | `string` | Current variant |
| `isColorCoded()` | `bool` | Whether color coding is active |
| `getOptionIcon(mixed $value)` | `?string` | Icon string for emoji variant key |
| `getEmojiImage(mixed $value)` | `?string` | Image URL for emoji variant key |
| `getOptionTone(mixed $value)` | `?string` | `detractor`, `passive`, `promoter`, or `null` |
| `isDetractor(mixed $key)` | `bool` | NPS 0–6 |
| `isPassive(mixed $key)` | `bool` | NPS 7–8 |
| `isPromoter(mixed $key)` | `bool` | NPS 9–10 |
| `isOptionDisabled(string\|int $key)` | `bool` | Whether key is disabled |

---

### Real-world examples

#### Post-purchase survey (CreateRecord)

```php
use Filament\Resources\Pages\CreateRecord;

protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['survey_completed_at'] = now();

    return $data;
}

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Feedback')->schema([
            NpsField::make('nps_score')
                ->label('How likely are you to recommend us?')
                ->colorCoded()
                ->minLabel('Not likely')
                ->maxLabel('Very likely')
                ->required(),

            NpsField::make('csat')
                ->label('Overall satisfaction')
                ->options(array_combine(range(1, 5), range(1, 5)))
                ->variant('segments')
                ->minLabel('Poor')
                ->maxLabel('Excellent'),

            NpsField::make('mood')
                ->label('How did we make you feel?')
                ->variant('emojis')
                ->options([
                    0 => 'Awful',
                    1 => 'Poor',
                    2 => 'Neutral',
                    3 => 'Good',
                    4 => 'Excellent',
                ]),
        ]),
    ]);
}
```

#### Wizard step — optional NPS

```php
Wizard\Step::make('Feedback')->schema([
    NpsField::make('nps_score')
        ->label('Recommendation score (optional)')
        ->helperText('Click your score again to clear.')
        ->columnSpanFull(),
]),
```

#### Live reactive form

```php
NpsField::make('nps_score')
    ->live()
    ->afterStateUpdated(function (?int $state, Set $set): void {
        if ($state !== null && $state <= 6) {
            $set('follow_up_reason', null);
        }
    }),

Textarea::make('follow_up_reason')
    ->visible(fn (Get $get): bool => filled($get('nps_score')) && (int) $get('nps_score') <= 6)
    ->required();
```

#### Infolist / table display (manual)

`NpsField` is a form component. Display stored values in tables with `TextColumn` or a custom column:

```php
TextColumn::make('nps_score')
    ->label('NPS')
    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : (string) $state)
    ->color(fn (?int $state): ?string => match (true) {
        $state === null => null,
        $state <= 6 => 'danger',
        $state <= 8 => 'warning',
        default => 'success',
    });
```

---

### Database & Eloquent

#### Migration

```php
Schema::create('survey_responses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->unsignedTinyInteger('nps_score')->nullable();
    $table->unsignedTinyInteger('csat')->nullable();
    $table->string('agreement', 2)->nullable(); // Likert keys: sd, d, n, a, sa
    $table->timestamps();
});
```

#### Model

```php
class SurveyResponse extends Model
{
    protected $fillable = ['user_id', 'nps_score', 'csat', 'agreement'];

    protected function casts(): array
    {
        return [
            'nps_score' => 'integer',
            'csat' => 'integer',
        ];
    }

    public function isPromoter(): bool
    {
        return $this->nps_score !== null && $this->nps_score >= 9;
    }
}
```

---

### Assets & deployment

| Asset type | Location after publish | How |
|------------|------------------------|-----|
| CSS / JS / Alpine | Filament public paths | `php artisan filament:assets` |
| Emoji webp (bundled) | `public/filament-flex-fields-assets/nps-field/emojis/` | Same command |

Fields resolve bundled emoji URLs via `FlexFieldAssets::assetUrl()` with automatic `?v=filemtime` cache busting.

On upgrade, run `php artisan filament:assets` (or use a Composer `post-autoload-dump` hook). See the main README [Upgrading](https://github.com/janczakb/filament-flex-fields#upgrading) section.

---

### Accessibility

- Root element uses `role="radiogroup"` with `aria-label` from the field label.
- Each option is a `<label role="radio">` with `aria-checked` and keyboard support (`Enter` / `Space`).
- Hidden native `<input type="radio">` elements preserve form semantics.
- Focus visible outline on keyboard navigation.
- Disabled options expose `aria-disabled="true"`.

---

### Performance

| Mechanism | What it does |
|-----------|--------------|
| **Lazy CSS** | Loads `segment-control` + `nps-field` bundles only when the field renders |
| **Lazy Alpine** | `nps-field.js` loaded on demand via Filament `x-load` |
| **SSR pre-selection** | `data-segment-selected` on pills prevents layout flash before Alpine hydrates |

Stylesheet dependency graph: `nps-field` → `segment-control` (pills variant only).

---

### Playground

`/admin/flex-fields-playground/nps-field`

See [Playground](/docs/index#playground) for setup.

---

### Related components

| Component | When to use instead |
|-----------|---------------------|
| [SegmentControl](/docs/segmentcontrol) | Generic single-select segments without survey semantics |
| [RatingField](/docs/ratingfield) | Star/icon ratings with fractional display |
| [MatrixChoiceField](/docs/matrixchoicefield) | Grid of radio/checkbox choices |
| [ChoiceCards](/docs/choicecards) | Large card-style single or multi select |

---

### CSS classes (reference)

| Class | Role |
|-------|------|
| `fff-nps-field` | Root wrapper |
| `fff-nps-field--variant-pills` | Pills variant |
| `fff-nps-field--variant-segments` | Segments variant |
| `fff-nps-field--variant-emojis` | Emojis variant |
| `fff-nps-field--color-coded` | NPS color coding active |
| `fff-nps-field--{sm\|md\|lg}` | Size variant |
| `fff-nps-field__track` | Options container |
| `fff-nps-field__segment` | Segment variant option |
| `fff-nps-field__emoji-ring` | Emoji variant circle |
| `fff-nps-field__extremes` | min/max label row |

Pills variant reuses `fff-segment-control`, `fff-segment-track`, `fff-segment-indicator`, and `fff-segment-item` from [SegmentControl](/docs/segmentcontrol).

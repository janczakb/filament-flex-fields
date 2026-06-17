---
title: "ScheduleField"
---

![ScheduleField](/art/sc-27.png)

[← Back to Table of Contents](/docs/index)


### Summary

Weekly **availability / opening-hours editor** with per-day toggles, **from/to time slots**, optional **break** entries, **copy-to-weekdays**, searchable **timezone selector**, and **locked days**. Time pickers use the compact **FlexTimeSegmentsField** dropdown (hour + minute columns). Default control size is **`sm`**; the timezone selector always renders at **`md`**.

| | |
|---|---|
| **Class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\ScheduleField` |
| **State type** | `array&lt;timezone?: string, days: array&lt;string, array{enabled: bool, slots: list&lt;array{from: string, to: string, type?: string&gt;&gt;}&gt;}` |
| **Model cast** | `'opening_hours' =&gt; 'array'` or `'json'` |
| **FieldType** | *(no dedicated FieldType mapping yet — use the class directly)* |
| **Playground** | `schedule-field` slug in Flex Fields playground |

---

### Basic usage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ScheduleField;

ScheduleField::make('opening_hours')
    ->label('Opening hours')
    ->timezone('Europe/Warsaw')
    ->required();
```

Filament resource example:

```php
use Filament\Forms\Form;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ScheduleField;

public static function form(Form $form): Form
{
    return $form->schema([
        ScheduleField::make('opening_hours')
            ->label('Business hours')
            ->timezone('Europe/Warsaw')
            ->timeStep(15)
            ->minSlots(1)
            ->maxSlots(4)
            ->columnSpanFull(),
    ]);
}
```

---

### State format

Day keys use three-letter English abbreviations: **`mon`**, **`tue`**, **`wed`**, **`thu`**, **`fri`**, **`sat`**, **`sun`**.

Times are **`HH:MM`** strings in 24-hour format (`09:00`, `17:30`). On save, invalid slot entries are dropped and times are zero-padded.

#### Minimal example

```json
{
  "timezone": "Europe/Warsaw",
  "days": {
    "mon": {
      "enabled": true,
      "slots": [
        { "from": "09:00", "to": "17:00", "type": "slot" }
      ]
    },
    "sat": {
      "enabled": false,
      "slots": []
    }
  }
}
```

#### Split shift with lunch break

```json
{
  "timezone": "Europe/Warsaw",
  "days": {
    "mon": {
      "enabled": true,
      "slots": [
        { "from": "09:00", "to": "12:00", "type": "slot" },
        { "from": "12:00", "to": "13:00", "type": "break" },
        { "from": "13:00", "to": "17:00", "type": "slot" }
      ]
    }
  }
}
```

| Slot `type` | UI | Notes |
|-------------|-----|-------|
| `slot` (default) | Work slot row with briefcase icon | Counted toward min/max slot limits |
| `break` | Dashed break row with clock icon | Also validated for time order and overlap |

When `timezone(null)` hides the selector, omit the `timezone` key from state — it is not required or validated.

---

### Default state

The field default calls `ScheduleField::defaultSchedule()`:

```php
ScheduleField::defaultSchedule('Europe/Warsaw');
// Mon–Fri enabled 09:00–17:00, Sat/Sun disabled with empty slots
```

Static helper signature:

```php
/**
 * @param  list<string>|null  $days  Which days to include (default: all seven)
 * @return array<timezone?: string, days: array<string, array{enabled: bool, slots: list<array{from: string, to: string>>}>}
 */
ScheduleField::defaultSchedule(?string $timezone = null, ?array $days = null): array
```

Examples:

```php
// Weekdays only in state shape
ScheduleField::defaultSchedule('UTC', ['mon', 'tue', 'wed', 'thu', 'fri']);

// No timezone key (when selector hidden)
ScheduleField::defaultSchedule(null);

// Override field default explicitly
ScheduleField::make('hours')
    ->default(ScheduleField::defaultSchedule('America/New_York'));
```

---

### Configuration API

All methods accept `Closure` for dynamic configuration.

#### `days(array|Closure $days)`

Which days to render. Invalid day codes are ignored. Empty array falls back to all seven days.

Default: **`['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']`**.

```php
ScheduleField::make('hours')
    ->days(['mon', 'tue', 'wed', 'thu', 'fri']);

ScheduleField::make('hours')
    ->days(['mon', 'wed', 'fri']); // Mon / Wed / Fri only
```

#### `timezone(string|Closure|null $timezone)`

Default timezone identifier when the selector is shown, or **`null`** to hide the timezone block entirely.

Default: **`'UTC'`** (selector visible).

```php
ScheduleField::make('hours')->timezone('Europe/Warsaw');

ScheduleField::make('hours')->timezone(null); // local / implicit timezone

ScheduleField::make('hours')
    ->timezone(fn (): string => config('app.timezone'));
```

When shown, the selector uses the shared **TimezoneField** UI (search, UTC offset badge). It always renders at **`md`** size regardless of the schedule field `size()`.

#### `timeStep(int|Closure $minutes)`

Minute step for **FlexTimeSegmentsField** dropdowns (`From` / `To`). Clamped to **`1`–`60`**.

Default: **`5`**.

```php
ScheduleField::make('hours')->timeStep(15);
ScheduleField::make('hours')->timeStep(1);  // every minute
```

#### `minSlots(int|Closure $count)` / `maxSlots(int|Closure $count)`

Per-day slot limits (includes both work slots and breaks).

Defaults: **`1`** / **`10`**.

```php
ScheduleField::make('hours')
    ->minSlots(1)
    ->maxSlots(6);
```

#### `requireSlotsForEnabledDays(bool|Closure $condition = true)`

When **`true`** (default), each **enabled** day must have at least `minSlots()` valid entries.

When **`false`**, an enabled day may have zero slots (useful for “open but hours TBD” flows — use carefully).

```php
ScheduleField::make('hours')->requireSlotsForEnabledDays(false);
```

#### `allowCopyToWeekdays(bool|Closure $condition = true)`

Shows **Copy to weekdays** on the copy source day.

Default: **`true`**.

```php
ScheduleField::make('hours')->allowCopyToWeekdays(false);
```

#### `copySourceDay(string|Closure $day = 'mon')`

Which day row displays the copy button. Must be one of `mon` … `sun`.

Default: **`mon`**.

```php
ScheduleField::make('hours')->copySourceDay('tue');
```

#### `workdays(array|Closure $days)`

Target days for **Copy to weekdays**. Invalid entries are filtered. Empty after filter falls back to Mon–Fri.

Default: **`['mon', 'tue', 'wed', 'thu', 'fri']`**.

```php
ScheduleField::make('hours')
    ->copySourceDay('mon')
    ->workdays(['mon', 'tue', 'wed', 'thu', 'fri']);

ScheduleField::make('hours')
    ->workdays(['mon', 'wed', 'fri']); // copy only to these days
```

Copy replaces slots on target workdays with a clone of the source day's slots. It does not change `enabled` flags.

#### `lockedDays(array|Closure $days)`

Days that cannot be toggled on/off. Shows a **lock icon** instead of the switch. Locked days still display their schedule when enabled in state.

Only days present in `days()` are kept.

```php
ScheduleField::make('hours')->lockedDays(['sat', 'sun']);

ScheduleField::make('hours')
    ->days(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])
    ->lockedDays(['sat', 'sun']);
```

#### `variant(string|Closure $variant)`

Visual container style.

| Value | Description |
|-------|-------------|
| `primary` | Default |
| `secondary` | Secondary surface |
| `soft` | Soft background tokens |
| `flat` | No shadow / minimal chrome |

```php
ScheduleField::make('hours')->variant('soft');
```

#### `size(string|ControlSize|Closure $size)`

Size of **time inputs** and day rows. Default: **`sm`**.

Timezone selector remains **`md`**.

```php
ScheduleField::make('hours')->size('md');
ScheduleField::make('hours')->size('lg');
```

#### `readOnly(bool|Closure $condition = true)` / `disabled(bool|Closure $condition = true)`

Inherited from Filament. Disables toggles, time pickers, copy, and slot toolbar actions.

```php
ScheduleField::make('hours')->readOnly();
ScheduleField::make('hours')->disabled();
```

#### `focusOutline(bool|Closure $condition = true)`

Inherited from `HasFieldFocusOutline`. Default: **`false`**. Adds focus ring on time input shells when enabled.

```php
ScheduleField::make('hours')->focusOutline();
```

---

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getRequiredValidationRule()` | `string\|Closure` | Returns `'nullable'` — emptiness checked inside schedule validator when `required()` is set |
| `getDays()` | `list&lt;string&gt;` | Normalized day codes to render |
| `showsTimezoneSelector()` | `bool` | Timezone block visible |
| `getDefaultTimezoneIdentifier()` | `string\|null` | Default / fallback timezone |
| `getTimeStep()` | `int` | Minute step (1–60) |
| `getMinSlots()` / `getMaxSlots()` | `int` | Slot limits |
| `shouldAllowCopyToWeekdays()` | `bool` | Copy button enabled |
| `getCopySourceDay()` | `string` | Copy source day code |
| `getWorkdays()` | `list&lt;string&gt;` | Copy target weekdays |
| `shouldRequireSlotsForEnabledDays()` | `bool` | Min slots enforced |
| `getLockedDays()` | `list&lt;string&gt;` | Locked day codes |
| `isDayLocked(string $day)` | `bool` | Single day lock check |
| `getVariant()` / `getSize()` | `string` | Resolved styling |
| `defaultSchedule(?string $timezone, ?array $days)` | `array` | Static default state builder |
| `normalizeState(mixed $state)` | `array` | Canonical schedule array |
| `isEmptyState(mixed $state)` | `bool` | True when no day keys |
| `stateMatches(array $normalized, mixed $state)` | `bool` | Compare normalized JSON |
| `getResolvedTimezoneIdentifiers()` | `list&lt;string&gt;` | Allowed IANA identifiers |
| `getTimezoneOptionsForJs()` | `list&lt;array&lt;id,label,offset&gt;&gt;` | Timezone dropdown data |
| `getAlpineConfiguration()` | `array` | Alpine + validation config |
| `getWrapperClasses()` | `array&lt;string, bool&gt;` | Root CSS class map |

---

### Validation

Built-in rule runs on submit (and when Livewire validates). Custom Filament `required` is mapped to **`nullable`** at rule level — emptiness is checked inside the schedule validator when `required()` is set.

| Check | When |
|-------|------|
| Required field | All configured days missing from state |
| Timezone required | Selector shown but value empty / invalid |
| Timezone in list | Must be in PHP `timezone_identifiers_list()` |
| Missing day | Configured day absent from state |
| Invalid slot shape | Non-array slot entry |
| Invalid time | Not parseable as `HH:MM` |
| `from` before `to` | End time must be after start |
| Min slots | Enabled day has fewer than `minSlots()` |
| Max slots | Enabled day exceeds `maxSlots()` |
| Overlap | Two slots on the same day overlap in time |

Real-time UI validation mirrors server messages via Alpine (`from_before_to`, `min_slots`, `max_slots`, `overlap`).

Disabled days skip slot validation entirely.

---

### Model & persistence

```php
// Migration
$table->json('opening_hours')->nullable();

// Model
protected $casts = [
    'opening_hours' => 'array',
];

protected $fillable = ['opening_hours'];
```

Loading existing state:

```php
ScheduleField::make('opening_hours')
    ->default(fn (?Location $record): array => $record?->opening_hours
        ?? ScheduleField::defaultSchedule('Europe/Warsaw'));
```

Reading normalized state in PHP:

```php
$normalized = ScheduleField::make('opening_hours')->normalizeState($rawState);
```

---

### Recipe: restaurant — weekdays + lunch break

```php
ScheduleField::make('opening_hours')
    ->label('Restaurant hours')
    ->timezone('Europe/Warsaw')
    ->days(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'])
    ->timeStep(15)
    ->minSlots(1)
    ->maxSlots(4)
    ->default([
        'timezone' => 'Europe/Warsaw',
        'days' => [
            'mon' => [
                'enabled' => true,
                'slots' => [
                    ['from' => '11:00', 'to' => '15:00', 'type' => 'slot'],
                    ['from' => '15:00', 'to' => '16:00', 'type' => 'break'],
                    ['from' => '16:00', 'to' => '22:00', 'type' => 'slot'],
                ],
            ],
            // ... other days
        ],
    ]);
```

Users can add breaks in the UI via **Add break**.

### Recipe: support desk — weekdays only, no weekends in UI

```php
ScheduleField::make('support_hours')
    ->label('Support availability')
    ->timezone('UTC')
    ->days(['mon', 'tue', 'wed', 'thu', 'fri'])
    ->copySourceDay('mon')
    ->workdays(['mon', 'tue', 'wed', 'thu', 'fri'])
    ->maxSlots(3);
```

### Recipe: always-closed weekends (locked)

```php
ScheduleField::make('store_hours')
    ->timezone('Europe/London')
    ->lockedDays(['sat', 'sun'])
    ->default(function (): array {
        $schedule = ScheduleField::defaultSchedule('Europe/London');
        $schedule['days']['sat']['enabled'] = false;
        $schedule['days']['sun']['enabled'] = false;

        return $schedule;
    });
```

### Recipe: no timezone selector (local hours)

```php
ScheduleField::make('local_hours')
    ->label('Local schedule')
    ->timezone(null)
    ->days(['mon', 'tue', 'wed', 'thu', 'fri']);
```

### Recipe: 30-minute steps, larger time inputs

```php
ScheduleField::make('clinic_hours')
    ->timeStep(30)
    ->size('md')
    ->variant('secondary');
```

### Recipe: read-only preview on view page

```php
ScheduleField::make('opening_hours')
    ->readOnly()
    ->default(fn ($record) => $record->opening_hours);
```

---

### UI behaviour

| Feature | Detail |
|---------|--------|
| Day toggle | Switch per day — expands/collapses slot editor with animation (`is-day-animated`) |
| Time pickers | FlexTimeSegments dropdown — separate hour and minute columns |
| Add slot / Add break | Compact toolbar buttons per day |
| Remove slot | Trash icon when more than `minSlots()` or day would still satisfy rules |
| Copy to weekdays | Visible only on `copySourceDay()` when `allowCopyToWeekdays(true)` and workdays overlap |
| SSR + hydration | Server-rendered markup for enabled days; Alpine replaces with live UI (`displayReady`) |
| State sync | `wire:ignore` + `$entangle` — full schedule JSON synced to Livewire |

---

### CSS classes

| Class | Role |
|-------|------|
| `fff-schedule-field` | Root wrapper |
| `fff-schedule-field--{sm\|md\|lg}` | Size modifier (time inputs) |
| `fff-schedule-field--{variant}` | Variant modifier |
| `fff-schedule-field--has-copy-column` | Grid includes copy column |
| `fff-schedule-field__days` | Bordered week container |
| `fff-schedule-field__day` | Single day block |
| `fff-schedule-field__day-header` | Day label + status + copy + switch |
| `fff-schedule-field__slot` | Work slot row |
| `fff-schedule-field__slot--break` | Break slot row (dashed) |
| `fff-schedule-field__time-shell` | FlexTimeSegments shell |
| `fff-schedule-field__action-btn` | Add slot / Add break buttons |
| `fff-schedule-field__day-switch` | Inline switch wrapper |

Timezone block reuses **TimezoneField** classes (`fff-timezone-field`, teleported menu classes).

---

### Assets

Lazy-loaded bundles (`FlexFieldAssets::stylesheetsFor('schedule-field')`):

- `flex-text-input`
- `switch`
- `teleported-menu`
- `timezone-field`
- `flex-time-segments`

Alpine components:

- `schedule-field` (main coordinator)
- `flex-time-segments` (preloaded per slot — shared chunk)

Uses `wire:ignore` on the field root. After deploy, run:

```bash
cd packages/filament-flex-fields && npm run build
php artisan filament:assets
```

---

### Playground

Slug: **`schedule-field`**

| Demo field | Shows |
|------------|-------|
| Default block | Weekday 09:00–17:00 default, timezone selector |
| Copy-to-weekdays | `copySourceDay('mon')` + workday targets |
| Locked weekends | `lockedDays(['sat', 'sun'])` |
| No timezone | `timezone(null)` — local hours only |
| Size / variant | `size('md')`, `variant('soft')` |

Enable the playground (`FLEX_FIELDS_PLAYGROUND=true`) and open **Flex Fields Playground → Schedule field**.

---

### Implementation notes

- Normalization: `ScheduleNormalizer` — pads times, drops invalid slots, coerces `type` to `slot` or `break`.
- Validation: `ScheduleValidator` — overlap detection sorts slots by start time.
- Day constants: `ScheduleDays::ALL`, `ScheduleDays::WEEKDAYS`.
- Click-outside on the field closes open time menus and the timezone dropdown.
- Mobile layout reflows day header (copy button moves below label row).

---

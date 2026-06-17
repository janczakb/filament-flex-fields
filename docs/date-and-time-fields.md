# Date & time fields

![FlexDateRangeField](/art/sc-26.png)

[← Back to Table of Contents](/docs/index)


Spectrum-style segmented date and time inputs powered by [`@internationalized/date`](https://react-spectrum.adobe.com/internationalized/date/). All variants share one Alpine component (`flex-date-time-field`), one Blade view, and the `InteractsWithDateTimeConfiguration` trait.

### Summary

| Component | Class | Mode | Calendar | Typical state |
|-----------|-------|------|----------|---------------|
| **FlexDateField** | `FlexDateField` | `date` | No | `string` — e.g. `2026-06-15` |
| **FlexDatePicker** | `FlexDatePicker` | `date` | Yes (popover) | `string` — e.g. `2026-06-15` |
| **FlexTimeField** | `FlexTimeField` | `time` | No | `string` — e.g. `14:30:00` |
| **FlexTimeSegmentsField** | `FlexTimeSegmentsField` | — | No (dropdown) | `string` — e.g. `09:30` (`HH:MM`, 24h) |
| **ScheduleField** | `ScheduleField` | — | No (embedded) | `array` — weekly schedule with timezone, days, slots — see [schedule-field.md](/docs/schedule-field) |
| **FlexDateTimePicker** | `FlexDateTimePicker` | `dateTime` | Yes (popover) | `string` — e.g. `2026-06-15T14:30:00` |
| **FlexDateRangeField** | `FlexDateRangeField` | `dateRange` | Yes (range UI) | `array&lt;start: string\|null, end: string\|null&gt;` |
| **FlexDurationField** | `FlexDurationField` | `duration` | No | `string` — e.g. `02:30:00` |
| **FlexTimeRangeField** | `FlexTimeRangeField` | `timeRange` | No | `array&lt;start: string\|null, end: string\|null&gt;` |
| **FlexMonthPicker** | `FlexMonthPicker` | `month` | Yes (month grid) | `string` — e.g. `2026-06` |
| **FlexYearPicker** | `FlexYearPicker` | `year` | Yes (year grid) | `string` — e.g. `2026` |

| | |
|---|---|
| **Base class** | `Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimeField` (abstract) |
| **Granularity enum** | `Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity` — `Day`, `Hour`, `Minute`, `Second` |
| **Month display enum** | `Bjanczak\FilamentFlexFields\Enums\MonthDisplay` — `Numeric`, `Short`, `Long` |
| **FlexField `FieldType`** | `date` → `FlexDatePicker`, `time` → `FlexTimeField`, `date_time` → `FlexDateTimePicker`, `date_range` → `FlexDateRangeField`, `duration` → `FlexDurationField`, `time_range` → `FlexTimeRangeField`, `month` → `FlexMonthPicker`, `year` → `FlexYearPicker`, `timezone` → `TimezoneField` |

`FlexFieldFormBuilder::configureDateTimeField()` applies JSON config keys (`granularity`, `hour_cycle`, `show_seconds`, `min_value`/`min_date`, `max_value`/`max_date`, `display_format`, `storage_format`, `locale`, `time_zone`, `force_leading_zeros`, `hide_time_zone`, `hide_time_section`, `close_on_select`, `allow_same_day`, `range_separator`, `variant`, `size`, `first_day_of_week`, `unavailable_dates`, `show_year_segment`, `month_display`) to all date/time field types.

Use `FlexDateField::make()` explicitly when you need segmented date input **without** a calendar popover.

### Basic usage

#### FlexDateField — segmented date, no calendar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Carbon\Carbon;

FlexDateField::make('starts_on')
    ->label('Start date')
    ->required()
    ->default('2026-06-15')
    ->minValue(Carbon::today())
    ->withRecommendedDefaults();
```

#### FlexDatePicker — segmented date with calendar popover

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDatePicker;

FlexDatePicker::make('published_on')
    ->label('Publish date')
    ->highlightToday()
    ->closeOnSelect()
    ->withRecommendedDefaults();
```

#### FlexTimeField — segmented time (12h or 24h)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeField;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

// 12-hour (default recommended preset)
FlexTimeField::make('opens_at')
    ->label('Opening time')
    ->hourCycle(12)
    ->minValue('09:00')
    ->maxValue('18:00')
    ->withRecommendedDefaults();

// 24-hour with minute precision
FlexTimeField::make('starts_at')
    ->label('Start time')
    ->hourCycle(24)
    ->granularity(DateTimeGranularity::Minute)
    ->hideTimeZone()
    ->default('14:30');
```

#### FlexTimeSegmentsField — dropdown hour / minute (24h)

Use when you need a compact **dropdown** time control (two scroll columns) instead of segmented text input — e.g. schedule slots, opening hours. Shares the flex-text-input **shell** (`variant`, `size`) but **not** the full `FlexTextInput` API (no prefix/suffix, masks, dictation, etc.).

State is always normalized to **`HH:MM`** (24-hour). Configure minute granularity with `minuteStep()` (default `15`).

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeSegmentsField;

FlexTimeSegmentsField::make('opens_at')
    ->label('Opening time')
    ->minuteStep(15)
    ->variant('primary')
    ->default('09:00');

FlexTimeSegmentsField::make('slot_start')
    ->minuteStep(5)
    ->size('sm')
    ->variant('soft')
    ->hourCycle(12)
    ->minValue('09:00')
    ->maxValue('18:00')
    ->locale('pl_PL');
```

Form builder JSON (`time_picker: dropdown` or `segments`):

```json
{
  "type": "time",
  "config": {
    "time_picker": "dropdown",
    "minute_step": 15,
    "hour_cycle": 24,
    "min_value": "09:00",
    "max_value": "18:00"
  }
}
```

Default `time_picker` is `segmented` (`FlexTimeField`).

#### FlexDateTimePicker — date + time with calendar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

FlexDateTimePicker::make('scheduled_at')
    ->label('Scheduled at')
    ->granularity(DateTimeGranularity::Minute)
    ->hourCycle(24)
    ->hideTimeZone()
    ->default('2026-06-15T14:30:00')
    ->withRecommendedDefaults();
```

#### FlexDateRangeField — start/end range with optional time under calendar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

FlexDateRangeField::make('booking_range')
    ->label('Booking period')
    ->granularity(DateTimeGranularity::Minute)
    ->allowSameDay(false)
    ->default([
        'start' => '2026-06-10T09:00:00',
        'end' => '2026-06-14T17:00:00',
    ])
    ->withRecommendedDefaults();
```

#### FlexMonthPicker — month + year with calendar

Calendar opens on the **years** grid; pick a year, then the **months** grid.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMonthPicker;

FlexMonthPicker::make('period')
    ->locale('pl_PL')
    ->withRecommendedDefaults();
```

#### FlexMonthPicker — month only (no year segment)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMonthPicker;
use Bjanczak\FilamentFlexFields\Enums\MonthDisplay;

FlexMonthPicker::make('season')
    ->showYearSegment(false)
    ->monthDisplay(MonthDisplay::Long)
    ->locale('pl_PL');
```

State is still stored as `Y-m` (e.g. `2026-06`); when the year segment is hidden, the current calendar year is used when composing the stored value.

#### FlexYearPicker — year with calendar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexYearPicker;

FlexYearPicker::make('fiscal_year')
    ->label('Fiscal year')
    ->default('2026')
    ->withRecommendedDefaults();
```

#### Full form example

```php
use Filament\Schemas\Components\Section;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDatePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeField;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

Section::make('Schedule')
    ->schema([
        FlexDateField::make('date')
            ->required()
            ->withRecommendedDefaults(),

        FlexDatePicker::make('date_with_calendar')
            ->withRecommendedDefaults(),

        FlexTimeField::make('time')
            ->hourCycle(24)
            ->withRecommendedDefaults(),

        FlexDateTimePicker::make('date_time')
            ->granularity(DateTimeGranularity::Minute)
            ->withRecommendedDefaults(),

        FlexDateRangeField::make('range')
            ->withRecommendedDefaults(),
    ]);
```

### State format

Values are **normalized on hydrate and dehydrate** via `DateTimeFieldValue` and the configured `storageFormat()`.

#### Single value fields (`FlexDateField`, `FlexDatePicker`, `FlexTimeField`, `FlexDateTimePicker`)

| Mode | Default storage format | Example stored value |
|------|------------------------|----------------------|
| Date | `Y-m-d` | `2026-06-15` |
| Time | `H:i` (or `H:i:s` with `showSeconds()`) | `14:30` / `14:30:00` |
| DateTime — Hour | `Y-m-d\TH:00:00` | `2026-06-15T14:00:00` |
| DateTime — Minute | `Y-m-d\TH:i:00` | `2026-06-15T14:30:00` |
| DateTime — Second | `Y-m-d\TH:i:s` | `2026-06-15T14:30:45` |
| Month (`FlexMonthPicker`) | `Y-m` | `2026-06` |
| Year (`FlexYearPicker`) | `Y` | `2026` |

`normalizeState()` accepts strings, numeric strings, and `CarbonInterface` instances. Invalid values become `null` and fail validation when the field is required or non-empty.

For `FlexMonthPicker` with `showYearSegment(false)`, storage format remains `Y-m`; the year defaults to the current calendar year when only the month segment is filled.

#### Range field (`FlexDateRangeField`)

```php
[
    'start' => '2026-06-10T09:00:00',
    'end' => '2026-06-14T17:00:00',
]
```

When `granularity` is `Day`, start/end are date-only strings (`Y-m-d`). With time granularity, ISO-like datetime strings are used (see defaults above).

### Display format vs storage format

Use **`displayFormat()`** for how values are formatted for display helpers / SSR `initialDisplay`, and **`storageFormat()`** for what is persisted in form state and the database.

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;

FlexDateField::make('event_date')
    ->displayFormat('d/m/Y')       // display helper: 15/06/2026
    ->storageFormat('Y-m-d')       // database: 2026-06-15
    ->default('2026-06-15');

// Custom dotted storage
FlexDateField::make('invoice_date')
    ->storageFormat('d.m.Y')
    ->default('2026-06-15');
// normalizeState('2026-06-15') → '15.06.2026'
```

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

FlexDateTimePicker::make('logged_at')
    ->granularity(DateTimeGranularity::Second)
    ->showSeconds()
    ->displayFormat('d/m/Y H:i:s')
    ->storageFormat('Y-m-d H:i:s')
    ->default('2026-06-15T14:30:45');
```

**Segment order is locale-aware** via `DateTimeLocaleOrder` (PHP `IntlDateFormatter` + JS `Intl`). Examples:

| Locale | Date segment order | Example |
|--------|-------------------|---------|
| `pl_PL`, `en_GB`, `de_DE` | day-first | `dd · mm · yyyy` |
| `en_US` | month-first | `mm · dd · yyyy` |

Locale strings such as `pl_PL` are normalized to BCP 47 (`pl-PL`) for `Intl` in JS calendar labels and month display.

`displayFormat()` affects server-side display helpers only (`initialDisplay`, `formatForDisplay`) — it does **not** change segment order in the UI.

### Granularity and time precision

`granularity()` controls which time segments appear and how values are stored.

| `DateTimeGranularity` | Date segments | Time segments | Use case |
|----------------------|---------------|---------------|----------|
| `Day` | month, day, year | — | Date only |
| `Hour` | month, day, year | hour | Date + hour |
| `Minute` | month, day, year | hour, minute | Date + hour:minute (default for DateTime) |
| `Second` | month, day, year | hour, minute, second | Full precision |

```php
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

// Hour only (minutes zeroed in storage)
FlexDateTimePicker::make('slot')
    ->granularity(DateTimeGranularity::Hour)
    ->hourCycle(24)
    ->default('2026-06-15T08:00:00');

// Minute precision (recommended default for date/time)
FlexDateTimePicker::make('appointment')
    ->granularity(DateTimeGranularity::Minute);

// Seconds — use granularity Second or showSeconds() on time fields
FlexDateTimePicker::make('timestamp')
    ->granularity(DateTimeGranularity::Second)
    ->showSeconds()
    ->default('2026-06-15T14:30:45');

FlexTimeField::make('lap_time')
    ->granularity(DateTimeGranularity::Second)
    ->showSeconds()
    ->hourCycle(24)
    ->default('00:01:23');
```

For **range fields**, when `granularity` is not `Day` and `hideTimeSection()` is false, time rows appear under the calendar popover (start/end time segments).

For **date/time picker**, when time is enabled, a single **Time** row appears under the calendar for editing hour/minute/second without closing the popover (`closeOnSelect(false)` in recommended defaults).

### Validation

Built-in validation runs through a custom `rule()` on `FlexDateTimeField`. Filament's default `required` rule is overridden to `nullable` — emptiness and constraints are handled by the custom rule.

| Check | When | Message key |
|-------|------|-------------|
| Required | `required()` and empty state | `validation.required` |
| Invalid / unparsable | `normalizeState()` returns `null` | `date_time.validation.invalid` |
| Below minimum | `minValue()` | `date_time.validation.before_min` |
| Above maximum | `maxValue()` | `date_time.validation.after_max` |
| Unavailable date | `isDateUnavailable()` callback | `date_time.validation.unavailable` |
| Incomplete range | Missing `start` or `end` | `date_time.validation.incomplete_range` |
| Range order | `end` before `start` | `date_time.validation.range_order` |
| Same day | `allowSameDay(false)` and equal dates | `date_time.validation.same_day_not_allowed` |

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Carbon\Carbon;

FlexDateField::make('starts_on')
    ->required()
    ->minValue('2026-06-01')
    ->maxValue(Carbon::parse('2026-12-31'))
    ->isDateUnavailable(fn (Carbon $date) => $date->isWeekend());
```

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTimeField;

FlexTimeField::make('business_hours')
    ->hourCycle(24)
    ->minValue('09:00')
    ->maxValue('17:00')
    ->required();
```

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;

FlexDateRangeField::make('leave')
    ->required()
    ->allowSameDay(false)
    ->minValue('2026-01-01')
    ->maxValue('2026-12-31');
```

**Client-side:** calendar days outside `minValue` / `maxValue` are disabled. `isDateUnavailable()` is validated on submit but unavailable days are **not** yet disabled in the calendar UI.

On segment **blur**, client-side validation sets `segmentInvalid` when segments are incomplete or out of bounds. The message appears **below** the input shell (`.fff-date-time-field__segment-error`, `role="alert"`), not inside individual segments. Text comes from `config.segmentInvalidMessage` / translation key `date_time.validation.invalid`. Server-side validation (form submit) is unchanged.

### Preset bundle: `withRecommendedDefaults()`

Applies sensible defaults per mode:

| Mode | Preset |
|------|--------|
| Date | `granularity(Day)`, `closeOnSelect()`, `highlightToday()` |
| Time | `granularity(Minute)`, `hourCycle(12)`, `hideTimeZone()` |
| DateTime | `granularity(Minute)`, `hourCycle(24)`, `closeOnSelect(false)` |
| DateRange | `granularity(Day)`, `allowSameDay()`, `highlightToday()`, `closeOnSelect(false)` |
| Month | `closeOnSelect()`, `highlightToday()` |
| Year | `closeOnSelect()`, `highlightToday()` |

```php
FlexDatePicker::make('date')->withRecommendedDefaults();
FlexTimeField::make('time')->withRecommendedDefaults();
FlexDateTimePicker::make('date_time')->withRecommendedDefaults();
FlexDateRangeField::make('range')->withRecommendedDefaults();
FlexMonthPicker::make('period')->withRecommendedDefaults();
FlexYearPicker::make('year')->withRecommendedDefaults();
```

### Configuration API

All date/time components share these chainable methods (from `InteractsWithDateTimeConfiguration` + `HasControlSize` + `HasFieldFocusOutline`).

#### `variant(string|Closure $variant)`


Visual shell style. Values: `primary` (default), `secondary`, `flat`. Shared with FlexTextInput tokens.

```php
->variant('secondary')
->variant('flat')
```

#### `size(string|ControlSize|Closure $size)`


Control height. See [Control size](/docs/shared-concepts). Default: `md`.

```php
use Bjanczak\FilamentFlexFields\Enums\ControlSize;

->size('sm')
->size(ControlSize::Lg)
```

#### `granularity(DateTimeGranularity|string|Closure $granularity)`


Time precision. Default: `Day` for date/range date-only; use `Minute` or `Second` for datetime/time. See [Granularity and time precision](#granularity-and-time-precision).

```php
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

->granularity(DateTimeGranularity::Day)
->granularity(DateTimeGranularity::Hour)
->granularity(DateTimeGranularity::Minute)
->granularity(DateTimeGranularity::Second)
```

#### `locale(string|Closure|null $locale)`


BCP 47 locale for segment order, placeholders, separators, and calendar labels. Default: `app()-&gt;getLocale()`. Laravel-style tags (`pl_PL`) are normalized to BCP 47 (`pl-PL`) for JS `Intl` APIs. Segment order follows locale via `DateTimeLocaleOrder` — see [Display format vs storage format](#display-format-vs-storage-format).

```php
->locale('en_US')
->locale('pl_PL')
```

#### `timeZone(string|Closure|null $timeZone)`


IANA timezone identifier passed to Alpine (`config.timeZone`). Default: `config('app.timezone')`. Affects calendar "today" and datetime parsing context.

```php
->timeZone('Europe/Warsaw')
->timeZone('UTC')
```

#### `hourCycle(int|Closure $hourCycle)`


`12` or `24`. `12` adds an AM/PM (`dayPeriod`) segment on time and datetime fields. Invalid values throw `InvalidArgumentException`.

```php
->hourCycle(24)
->hourCycle(12)
```

#### `displayFormat(string|Closure|null $format)`


PHP date format for display helpers (`initialDisplay`, `formatForDisplay`). When omitted, mode-specific defaults apply (e.g. `m/d/Y`, `m/d/Y H:i:s`). Does not affect segment order — that is driven by `locale()`. See [Display format vs storage format](#display-format-vs-storage-format).

```php
->displayFormat('d/m/Y')
->displayFormat('Y-m-d H:i')
->displayFormat('m/d/Y g:i A')  // 12-hour display
```

#### `storageFormat(string|Closure|null $format)`


Format used when normalizing state for storage. When omitted, mode/granularity defaults apply. See [State format](/docs/flexslider#state-format).

```php
->storageFormat('Y-m-d')
->storageFormat('d.m.Y')
->storageFormat('Y-m-d H:i:s')
->storageFormat('H:i:s')
```

#### `forceLeadingZeros(bool|Closure $condition = true)`


Pad segment values with leading zeros (`06` vs `6`). Default: `true`.

```php
->forceLeadingZeros()
->forceLeadingZeros(false)
```

#### `showYearSegment(bool|Closure $condition = true)`


Default: `true`. When `false`, only the month segment is shown (no year segment). Used by `FlexMonthPicker` for month-only fields. Storage format remains `Y-m`; the current calendar year is used when composing the stored value from a month-only segment.

```php
FlexMonthPicker::make('season')
    ->showYearSegment(false)
    ->monthDisplay(MonthDisplay::Long)
    ->locale('pl_PL');
```

#### `monthDisplay(MonthDisplay|string|Closure $display)`


Controls how the month segment is rendered. Enum `Bjanczak\FilamentFlexFields\Enums\MonthDisplay`:

| Value | Segment display | Example (`pl_PL`) |
|-------|-----------------|-------------------|
| `Numeric` (default) | zero-padded number | `06` |
| `Short` | abbreviated name | `cze` |
| `Long` | full name | `czerwiec` |

Works on any field with a month segment (date, datetime, month picker). Textual months are left-aligned with width fitted to content (`field-sizing: content`); CSS variable `--fff-date-time-month-ch` sets character width. The calendar month grid always uses **short** month labels regardless of `monthDisplay()`.

```php
use Bjanczak\FilamentFlexFields\Enums\MonthDisplay;

FlexMonthPicker::make('billing_month')
    ->showYearSegment(false)
    ->monthDisplay(MonthDisplay::Long)
    ->locale('pl_PL');

FlexDatePicker::make('event_date')
    ->locale('pl_PL')
    ->monthDisplay(MonthDisplay::Long);
```

#### `minValue(string|CarbonInterface|Closure|null $value)`


Minimum allowed value. Accepts date/time strings or `Carbon` instances. Enforced on submit; calendar disables earlier **days** (date portion).

```php
use Carbon\Carbon;

->minValue('2026-06-01')
->minValue(Carbon::today())
->minValue('09:00')  // time fields
```

Alias for Filament-style naming (identical to `minValue()`):

```php
->minDate(now()->subYears(150))
->minDate(Carbon::today())
```

#### `maxValue(string|CarbonInterface|Closure|null $value)`


Maximum allowed value. Same types as `minValue()`.

```php
->maxValue('2026-12-31')
->maxValue(Carbon::parse('2026-12-31'))
->maxValue('18:00')
```

Alias for Filament-style naming (identical to `maxValue()`):

```php
->maxDate(now())
->maxDate(Carbon::parse('2026-12-31'))
```

#### Example: date of birth bounds


```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDatePicker;

FlexDatePicker::make('birth_date')
    ->minDate(now()->subYears(150))
    ->maxDate(now())
    ->required();
```

#### `isDateUnavailable(Closure $callback)`


Mark specific dates as invalid. Callback receives `Carbon` at start of day; return `true` to reject.

```php
use Carbon\Carbon;

->isDateUnavailable(fn (Carbon $date) => $date->isWeekend())
->isDateUnavailable(fn (Carbon $date) => $date->isPast())
->isDateUnavailable(function (Carbon $date): bool {
    return in_array($date->format('Y-m-d'), ['2026-12-24', '2026-12-25'], true);
})
```

#### `rangeSeparator(string|Closure $separator)`


Text between start and end segments in range fields. Default: `' - '`.

```php
->rangeSeparator(' – ')
->rangeSeparator(' to ')
```

#### `allowSameDay(bool|Closure $condition = true)`


Whether start and end can be the same calendar day in range mode. Default: `true`. `FlexDateRangeField` recommended preset keeps `true`; set `false` for multi-day-only ranges.

```php
->allowSameDay()
->allowSameDay(false)
```

#### `highlightToday(bool|Closure $condition = true)`


Show a dot on today's date in the calendar. Default: `true`.

```php
->highlightToday()
->highlightToday(false)
```

#### `showCalendar(bool|Closure $condition)` / `showCalendarButton(bool|Closure $condition)`


Enable calendar popover and trailing calendar trigger button. Set automatically per component class (`FlexDatePicker`, `FlexDateTimePicker`, `FlexDateRangeField`, `FlexMonthPicker`, `FlexYearPicker` = on; `FlexDateField`, `FlexTimeField` = off).

```php
// Rare: override on a date field subclass
FlexDateField::make('custom')
    ->showCalendar(true)
    ->showCalendarButton(true);
```

#### `closeOnSelect(bool|Closure $condition = true)`


Close calendar popover after selecting a date. Default: `true` for date picker, `false` for date/time and range (recommended) so users can adjust time under the calendar.

```php
->closeOnSelect()       // close immediately
->closeOnSelect(false)  // keep open for time editing
```

#### `firstDayOfWeek(int|Closure $day)`


Week start for calendar grid. `0` = Sunday, `1` = Monday, … `6` = Saturday. Default: `0`.

```php
->firstDayOfWeek(1)  // Monday-first (EU)
```

#### `hideTimeZone(bool|Closure $condition = true)`


Hide timezone label segment on time-only fields. Default: `false`; recommended time preset enables it.

```php
->hideTimeZone()
```

#### `hideTimeSection(bool|Closure $condition = true)`


Hide time rows under the calendar (range / date-time). When hidden, only date segments are used in the main input.

```php
FlexDateRangeField::make('nights')
    ->granularity(DateTimeGranularity::Day)
    ->hideTimeSection();
```

#### `showSeconds(bool|Closure $condition = true)`


Include second segment on time/datetime fields. Implied when `granularity(Second)`.

```php
->showSeconds()
->granularity(DateTimeGranularity::Second)
    ->showSeconds()
```

#### `withRecommendedDefaults()`


Mode-specific preset bundle. See [Preset bundle](#preset-bundle-withrecommendeddefaults).

```php
Component::make('field_name')
    ->withRecommendedDefaults('Brak danych');
```
#### `focusOutline(bool|Closure $condition = true)`


Inherited from `HasFieldFocusOutline`. Show focus ring on the outer shell.

```php
->focusOutline()
```

#### Inherited Filament `Field` API


`label()`, `helperText()`, `hint()`, `placeholder()`, `required()`, `disabled()`, `readOnly()`, `default()`, `live()`, `dehydrated()`, `hidden()`, `visible()`, `rule()`, `rules()`, `afterStateUpdated()` — all work as usual. See [Inherited Filament field API](/docs/shared-concepts).

Default placeholders (translation keys under `filament-flex-fields::default.date_time`):

| Mode | Key |
|------|-----|
| Date | `placeholder_date` |
| Time | `placeholder_time` |
| DateTime | `placeholder_date_time` |
| DateRange | `placeholder_date_range` |

```php
Component::make('field_name')
    ->Field();
```

### Recipe examples

#### Booking form with bounds and blocked weekends

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;
use Carbon\Carbon;

FlexDateRangeField::make('stay')
    ->label('Stay dates')
    ->required()
    ->granularity(DateTimeGranularity::Day)
    ->minValue(Carbon::today())
    ->maxValue(Carbon::today()->addYear())
    ->isDateUnavailable(fn (Carbon $date) => $date->isWeekend())
    ->allowSameDay(false)
    ->rangeSeparator(' – ')
    ->withRecommendedDefaults();
```

#### Event scheduling with 12-hour clock and seconds

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

FlexDateTimePicker::make('starts_at')
    ->label('Event start')
    ->granularity(DateTimeGranularity::Second)
    ->showSeconds()
    ->hourCycle(12)
    ->default('2026-07-04T18:30:00')
    ->minValue('2026-01-01')
    ->closeOnSelect(false)
    ->size('lg');
```

#### EU display, ISO storage

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Carbon\Carbon;

FlexDateField::make('birth_date')
    ->label('Date of birth')
    ->displayFormat('d.m.Y')
    ->storageFormat('Y-m-d')
    ->maxValue(Carbon::today())
    ->forceLeadingZeros()
    ->required();
```

#### Dynamic min date from another field (Closure)

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Carbon\Carbon;
use Filament\Schemas\Components\Utilities\Get;

FlexDateField::make('ends_on')
    ->minValue(fn (Get $get): string => $get('starts_on') ?? '2026-01-01')
    ->required();
```

#### Read-only review field

```php
FlexDateTimePicker::make('confirmed_at')
    ->default('2026-06-15T10:00:00')
    ->disabled()
    ->hideTimeZone();
```

#### Custom validation rule alongside built-in

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateField;
use Carbon\Carbon;

FlexDateField::make('cutoff')
    ->required()
    ->rule('after:today')
    ->minValue(Carbon::today());
```

#### Hydrate from Eloquent datetime cast

```php
// Model: protected $casts = ['scheduled_at' => 'datetime'];

FlexDateTimePicker::make('scheduled_at')
    ->granularity(DateTimeGranularity::Minute)
    ->withRecommendedDefaults();
// Carbon from model is normalized to storage format on dehydrate
```

#### Store only date in MySQL `DATE` column

```php
FlexDateField::make('published_on')
    ->storageFormat('Y-m-d')
    ->required();

// In migration: $table->date('published_on');
```

#### Range with per-day time windows under calendar

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateRangeField;
use Bjanczak\FilamentFlexFields\Enums\DateTimeGranularity;

FlexDateRangeField::make('rental')
    ->label('Rental period')
    ->granularity(DateTimeGranularity::Minute)
    ->default([
        'start' => '2026-06-10T09:30:00',
        'end' => '2026-06-14T17:00:00',
    ])
    ->closeOnSelect(false)
    ->withRecommendedDefaults();
```

### Calendar UX

Components with `showCalendar()` expose a teleported popover calendar:

- **Month navigation** — chevron previous/next; header label click drills down: days → months → years (Spectrum-style).
- **FlexMonthPicker (with year segment)** — opens on the **years** grid; pick a year → **months** grid.
- **FlexMonthPicker with `showYearSegment(false)`** — opens directly on the **months** grid (no year selection step).
- **FlexYearPicker** — year grid only.
- **Locale** — calendar labels use the configured locale; month names in the grid are always short form.
- **Range selection** — continuous pill highlight between start and end; hover preview while selecting end.
- **Today** — optional dot via `highlightToday()`.
- **Primary color** — selected day uses theme `--primary-500`.
- **Time under calendar** — when time granularity is enabled, editable time segments appear below the grid (single row for `FlexDateTimePicker`, start/end rows for `FlexDateRangeField`).

### FlexField schema config

When using `FlexFieldFormBuilder`, these `FieldType` values map to components with `withRecommendedDefaults()`:

| `FieldType` | Component |
|-------------|-----------|
| `date` | `FlexDateField` |
| `time` | `FlexTimeField` |
| `date_time` | `FlexDateTimePicker` |
| `date_range` | `FlexDateRangeField` |
| `month` | `FlexMonthPicker` |
| `year` | `FlexYearPicker` |

```php
use Bjanczak\FilamentFlexFields\Enums\FieldType;

// FlexField definition config (snake_case keys map to chainable methods when wired)
[
    'type' => FieldType::DateTime,
    'name' => 'scheduled_at',
    'label' => 'Scheduled at',
    'config' => [
        'granularity' => 'minute',
        'hour_cycle' => 24,
        'min_value' => '2026-01-01',
        'storage_format' => 'Y-m-d H:i:s',
    ],
]

// Month picker with textual month display
[
    'type' => FieldType::Month,
    'name' => 'billing_month',
    'label' => 'Billing month',
    'config' => [
        'locale' => 'pl_PL',
        'show_year_segment' => false,
        'month_display' => 'long',
    ],
]

// Year picker
[
    'type' => FieldType::Year,
    'name' => 'fiscal_year',
    'label' => 'Fiscal year',
    'config' => [
        'min_value' => '2020',
        'max_value' => '2030',
    ],
]
```

Configure fields manually in Filament schemas for full API access (`FlexDatePicker`, custom `displayFormat`, etc.).

### Public helper methods

| Method | Returns | Description |
|--------|---------|-------------|
| `getMode()` | `DateTimeFieldMode` | `date`, `time`, `dateTime`, `dateRange`, `month`, `year`, `duration`, `timeRange` |
| `getVariant()` | `string` | `primary`, `secondary`, `flat` |
| `getGranularity()` | `DateTimeGranularity` | Resolved granularity |
| `getLocale()` | `string` | Effective locale |
| `getTimeZone()` | `string` | IANA timezone |
| `getHourCycle()` | `int` | `12` or `24` |
| `getDisplayFormat()` | `string` | Resolved display format |
| `getStorageFormat()` | `string` | Resolved storage format |
| `shouldForceLeadingZeros()` | `bool` | Leading zero padding |
| `getMinValue()` / `getMaxValue()` | mixed | Raw configured bounds |
| `getRangeSeparator()` | `string` | Range text separator |
| `shouldAllowSameDay()` | `bool` | Same-day range allowed |
| `shouldHighlightToday()` | `bool` | Today dot in calendar |
| `shouldShowCalendar()` | `bool` | Calendar popover enabled |
| `shouldShowCalendarButton()` | `bool` | Trailing calendar button |
| `shouldCloseOnSelect()` | `bool` | Auto-close on date pick |
| `getFirstDayOfWeek()` | `int` | Calendar week start |
| `shouldHideTimeZone()` | `bool` | Timezone segment hidden |
| `shouldHideTimeSection()` | `bool` | Time rows under calendar hidden |
| `shouldShowSeconds()` | `bool` | Second segment visible |
| `shouldShowYearSegment()` | `bool` | Year segment visible (month picker) |
| `getMonthDisplay()` | `MonthDisplay` | Month segment display mode |
| `normalizeState(mixed $state)` | `string\|array\|null` | Canonical stored value |
| `isEmptyState(mixed $state)` | `bool` | Whether value is considered empty |
| `getAlpineConfiguration()` | `array` | Full Alpine bootstrap payload |
| `getViewSegments()` | `array` | SSR segment hydration |
| `getWrapperClasses()` | `list&lt;string&gt;` | CSS class list |

### CSS classes

| Class | Role |
|-------|------|
| `fff-date-time-field` | Root wrapper |
| `fff-date-time-field--{date\|time\|dateTime\|dateRange\|month\|year}` | Mode modifier |
| `fff-date-time-field--{sm\|md\|lg}` | Size modifier |
| `fff-date-time-field--{primary\|secondary\|flat}` | Variant modifier |
| `fff-date-time-field--show-seconds` | Wider shell when seconds visible |
| `fff-date-time-field--textual-month` | Month segment uses short/long text (not numeric) |
| `fff-date-time-field--textual-month-short` | Short month labels in segment (`cze`) |
| `fff-date-time-field--textual-month-long` | Long month labels in segment (`czerwiec`) |
| `fff-date-time-field__shell` | Pill input border (FlexTextInput shell) |
| `fff-date-time-field__segments` | Segment group |
| `fff-date-time-field__segment` | Individual segment input |
| `fff-date-time-field__segment-error` | Client-side validation message below shell (`role="alert"`) |
| `fff-date-time-field__suffix` | Calendar button column |
| `fff-date-time-field__calendar` | Teleported popover (`is-positioned` when placed) |
| `fff-date-time-field__day.is-selected` | Selected calendar day (primary background) |
| `fff-date-time-field__time-rows` | Time editor under calendar |

Textual month segments set CSS variable `--fff-date-time-month-ch` on the root wrapper for character-width sizing.

Segment focus uses theme primary (`--primary-500`) for active background and ring.

### Implementation notes

- Client logic: `resources/js/components/flex-date-time-field.js` (Alpine), bundled to `resources/dist/components/flex-date-time-field.js`.
- Date math: `@internationalized/date` via `resources/js/core/date-time/`.
- SSR segment values in Blade prevent layout shift on first paint; Alpine hydrates from `initialSegments`.
- Playground section **Date & time fields** in `DateTimeFieldPlayground` demonstrates all variants, configuration presets, and bounds/format examples.
- Playground section **Month display** in `DateTimeFieldPlayground` demonstrates `monthDisplay()` (numeric, short, long) across month picker, date, and datetime fields, plus `showYearSegment(false)` month-only mode.
- Rebuild assets after CSS/JS changes — see [Assets & playground](#assets--playground).

---

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;
use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleDays;
use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleNormalizer;
use Bjanczak\FilamentFlexFields\Support\Schedule\ScheduleValidator;
use Bjanczak\FilamentFlexFields\Support\Timezones;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Field;
use Filament\Support\Facades\FilamentAsset;
use InvalidArgumentException;

class ScheduleField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;

    protected string $view = 'filament-flex-fields::forms.components.schedule-field';

    protected string|Closure $variant = 'primary';

    /** @var list<string>|Closure */
    protected array|Closure $days = ScheduleDays::ALL;

    protected string|Closure|null $timezone = 'UTC';

    protected int|Closure $timeStep = 5;

    protected int|Closure $minSlots = 1;

    protected int|Closure $maxSlots = 10;

    /** @var list<string>|Closure */
    protected array|Closure $lockedDays = [];

    protected bool|Closure $allowCopyToWeekdays = true;

    protected string|Closure $copySourceDay = 'mon';

    /** @var list<string>|Closure|null */
    protected array|Closure|null $workdays = null;

    protected bool|Closure $requireSlotsForEnabledDays = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->size('sm');

        $this->default(fn (ScheduleField $component): array => $component->defaultSchedule());

        $this->afterStateHydrated(function (ScheduleField $component, mixed $state): void {
            $normalized = $component->normalizeState($state);

            if ($component->stateMatches($normalized, $state)) {
                return;
            }

            $component->state($normalized);
        });

        $this->dehydrateStateUsing(fn (ScheduleField $component, mixed $state): array => $component->normalizeState($state));

        $this->rule(function (ScheduleField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if ($component->isRequired() && $component->isEmptyState($value)) {
                    $fail(__('validation.required', ['attribute' => $component->getLabel()]));

                    return;
                }

                if ($component->isEmptyState($value)) {
                    return;
                }

                $component->makeValidator()->validate(
                    $value,
                    $component->getDays(),
                    $fail,
                    $component->showsTimezoneSelector() ? $component->getDefaultTimezoneIdentifier() : null,
                    $component->showsTimezoneSelector() ? $component->getResolvedTimezoneIdentifiers() : null,
                );
            };
        });
    }

    public function getRequiredValidationRule(): string|Closure
    {
        return 'nullable';
    }

    /**
     * @param  list<string>|Closure  $days
     */
    public function days(array|Closure $days): static
    {
        $this->days = $days;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getDays(): array
    {
        return ScheduleDays::normalize($this->evaluate($this->days));
    }

    public function timezone(string|Closure|null $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function showsTimezoneSelector(): bool
    {
        return $this->evaluate($this->timezone) !== null;
    }

    public function getDefaultTimezoneIdentifier(): ?string
    {
        if (! $this->showsTimezoneSelector()) {
            return null;
        }

        $timezone = $this->evaluate($this->timezone);

        if (! is_string($timezone) || trim($timezone) === '') {
            return 'UTC';
        }

        $timezone = trim($timezone);
        $allowed = $this->getResolvedTimezoneIdentifiers();

        if (in_array($timezone, $allowed, true)) {
            return $timezone;
        }

        return $allowed[0] ?? 'UTC';
    }

    public function timeStep(int|Closure $minutes): static
    {
        $this->timeStep = $minutes;

        return $this;
    }

    public function getTimeStep(): int
    {
        return max(1, min(60, (int) $this->evaluate($this->timeStep)));
    }

    public function minSlots(int|Closure $count): static
    {
        $this->minSlots = $count;

        return $this;
    }

    public function getMinSlots(): int
    {
        return max(0, (int) $this->evaluate($this->minSlots));
    }

    public function maxSlots(int|Closure $count): static
    {
        $this->maxSlots = $count;

        return $this;
    }

    public function getMaxSlots(): int
    {
        return max(1, (int) $this->evaluate($this->maxSlots));
    }

    public function allowCopyToWeekdays(bool|Closure $condition = true): static
    {
        $this->allowCopyToWeekdays = $condition;

        return $this;
    }

    public function shouldAllowCopyToWeekdays(): bool
    {
        return (bool) $this->evaluate($this->allowCopyToWeekdays);
    }

    public function copySourceDay(string|Closure $day): static
    {
        $this->copySourceDay = $day;

        return $this;
    }

    public function getCopySourceDay(): string
    {
        $day = strtolower(trim((string) $this->evaluate($this->copySourceDay)));

        if (! in_array($day, ScheduleDays::ALL, true)) {
            throw new InvalidArgumentException("Invalid schedule copy source day [{$day}].");
        }

        return $day;
    }

    /**
     * @param  list<string>|Closure  $days
     */
    public function workdays(array|Closure $days): static
    {
        $this->workdays = $days;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getWorkdays(): array
    {
        if ($this->workdays === null) {
            return ScheduleDays::WEEKDAYS;
        }

        $configured = $this->evaluate($this->workdays);

        if (! is_array($configured)) {
            return ScheduleDays::WEEKDAYS;
        }

        /** @var list<string> $days */
        $days = array_values(array_map(static fn (mixed $day): string => (string) $day, $configured));

        return ScheduleDays::normalizeWorkdays($days);
    }

    public function requireSlotsForEnabledDays(bool|Closure $condition = true): static
    {
        $this->requireSlotsForEnabledDays = $condition;

        return $this;
    }

    public function shouldRequireSlotsForEnabledDays(): bool
    {
        return (bool) $this->evaluate($this->requireSlotsForEnabledDays);
    }

    /**
     * @param  list<string>|Closure  $days
     */
    public function lockedDays(array|Closure $days): static
    {
        $this->lockedDays = $days;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getLockedDays(): array
    {
        $configured = $this->evaluate($this->lockedDays);

        if (! is_array($configured)) {
            return [];
        }

        /** @var list<string> $days */
        $days = array_values(array_map(static fn (mixed $day): string => (string) $day, $configured));

        return array_values(array_intersect(
            ScheduleDays::onlyValidDays($days),
            $this->getDays(),
        ));
    }

    public function isDayLocked(string $day): bool
    {
        return in_array(strtolower(trim($day)), $this->getLockedDays(), true);
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'soft', 'flat'], true)) {
            throw new InvalidArgumentException("Invalid ScheduleField variant [{$variant}].");
        }

        return $variant;
    }

    /**
     * @param  list<string>|null  $days
     * @return array{timezone?: string, days: array<string, array{enabled: bool, slots: list<array{from: string, to: string}>}>}
     */
    public static function defaultSchedule(?string $timezone = null, ?array $days = null): array
    {
        $days = ScheduleDays::normalize($days ?? ScheduleDays::ALL);
        $schedule = [
            'days' => [],
        ];

        if ($timezone !== null) {
            $schedule['timezone'] = $timezone;
        }

        foreach ($days as $day) {
            $isWeekday = ScheduleDays::isWeekday($day);

            $schedule['days'][$day] = [
                'enabled' => $isWeekday,
                'slots' => $isWeekday
                    ? [['from' => '09:00', 'to' => '17:00']]
                    : [],
            ];
        }

        return $schedule;
    }

    /**
     * @return array{timezone?: string, days: array<string, array{enabled: bool, slots: list<array{from: string, to: string}>}>}
     */
    public function normalizeState(mixed $state): array
    {
        return $this->makeNormalizer()->normalize(
            $state,
            $this->getDays(),
            $this->showsTimezoneSelector() ? $this->getDefaultTimezoneIdentifier() : null,
        );
    }

    public function isEmptyState(mixed $state): bool
    {
        if (! is_array($state)) {
            return true;
        }

        $normalized = $this->normalizeState($state);

        foreach ($this->getDays() as $day) {
            $dayState = $normalized['days'][$day] ?? null;

            if (! is_array($dayState) || ! ($dayState['enabled'] ?? false)) {
                continue;
            }

            $slots = is_array($dayState['slots'] ?? null) ? $dayState['slots'] : [];

            if ($slots !== []) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    public function stateMatches(array $normalized, mixed $state): bool
    {
        if (! is_array($state)) {
            return false;
        }

        return json_encode($normalized) === json_encode($this->normalizeState($state));
    }

    /**
     * @return list<string>
     */
    public function getResolvedTimezoneIdentifiers(): array
    {
        return Timezones::resolve(null, []);
    }

    /**
     * @return list<array{id: string, label: string, offset: string}>
     */
    public function getTimezoneOptionsForJs(): array
    {
        /** @var list<array{id: string, label: string, offset: string}> $options */
        $options = collect(Timezones::metadata(null, []))
            ->map(fn (array $timezone): array => [
                'id' => $timezone['id'],
                'label' => $timezone['label'],
                'offset' => $timezone['offset'],
            ])
            ->values()
            ->all();

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAlpineConfiguration(): array
    {
        $dayLabels = [];

        foreach ($this->getDays() as $day) {
            $dayLabels[$day] = __("filament-flex-fields::default.schedule.days.{$day}");
        }

        return [
            'days' => $this->getDays(),
            'dayLabels' => $dayLabels,
            'weekdays' => $this->getWorkdays(),
            'timeStep' => $this->getTimeStep(),
            'minSlots' => $this->getMinSlots(),
            'maxSlots' => $this->getMaxSlots(),
            'lockedDays' => $this->getLockedDays(),
            'requireSlotsForEnabledDays' => $this->shouldRequireSlotsForEnabledDays(),
            'validationMessages' => [
                'from_before_to' => __('filament-flex-fields::default.schedule.validation.ui.from_before_to'),
                'min_slots' => __('filament-flex-fields::default.schedule.validation.ui.min_slots'),
                'max_slots' => __('filament-flex-fields::default.schedule.validation.ui.max_slots'),
                'overlap' => __('filament-flex-fields::default.schedule.validation.ui.overlap'),
            ],
            'allowCopyToWeekdays' => $this->shouldAllowCopyToWeekdays(),
            'copySourceDay' => $this->getCopySourceDay(),
            'showTimezone' => $this->showsTimezoneSelector(),
            'timezones' => $this->showsTimezoneSelector() ? $this->getTimezoneOptionsForJs() : [],
            'defaultTimezone' => $this->getDefaultTimezoneIdentifier(),
            'searchable' => true,
            'showOffset' => true,
            'searchPlaceholder' => __('filament-flex-fields::default.timezone.search_timezones'),
            'timezonePlaceholder' => __('filament-flex-fields::default.schedule.timezone_placeholder'),
            'labels' => [
                'timezone' => __('filament-flex-fields::default.schedule.timezone'),
                'timezonePlaceholder' => __('filament-flex-fields::default.schedule.timezone_placeholder'),
                'closed' => __('filament-flex-fields::default.schedule.closed'),
                'open' => __('filament-flex-fields::default.schedule.open'),
                'from' => __('filament-flex-fields::default.schedule.from'),
                'to' => __('filament-flex-fields::default.schedule.to'),
                'addSlot' => __('filament-flex-fields::default.schedule.add_slot'),
                'addBreak' => __('filament-flex-fields::default.schedule.add_break'),
                'removeSlot' => __('filament-flex-fields::default.schedule.remove_slot'),
                'copyToWeekdays' => __('filament-flex-fields::default.schedule.copy_to_weekdays'),
                'copyConfirm' => __('filament-flex-fields::default.schedule.copy_confirm'),
                'slot' => __('filament-flex-fields::default.schedule.slot'),
                'break' => __('filament-flex-fields::default.schedule.break'),
            ],
            'flexTimeSegmentsSrc' => FilamentAsset::getAlpineComponentSrc(
                'flex-time-segments',
                FilamentFlexFieldsPlugin::PACKAGE_NAME,
            ),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-schedule-field' => true,
            'fff-schedule-field--'.$this->getSize() => true,
            'fff-schedule-field--'.$this->getVariant() => true,
        ];
    }

    protected function makeNormalizer(): ScheduleNormalizer
    {
        return new ScheduleNormalizer;
    }

    protected function makeValidator(): ScheduleValidator
    {
        return new ScheduleValidator(
            minSlots: $this->getMinSlots(),
            maxSlots: $this->getMaxSlots(),
            requireSlotsForEnabledDays: $this->shouldRequireSlotsForEnabledDays(),
        );
    }
}

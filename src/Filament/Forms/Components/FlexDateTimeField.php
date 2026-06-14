<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Enums\DateTimeFieldMode;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\InteractsWithDateTimeConfiguration;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeAlpineConfiguration;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeConstraintResolver;
use Bjanczak\FilamentFlexFields\Support\DateTime\DateTimeSegmentHydrator;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;

abstract class FlexDateTimeField extends Field
{
    use CanBeReadOnly;
    use HasPlaceholder;
    use InteractsWithDateTimeConfiguration;

    protected string $view = 'filament-flex-fields::forms.components.flex-date-time-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (FlexDateTimeField $component, mixed $state): void {
            $component->state($component->normalizeState($state));
        });

        $this->dehydrateStateUsing(fn (FlexDateTimeField $component, mixed $state): string|array|null => $component->normalizeState($state));

        $this->rule(function (FlexDateTimeField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if ($component->isRequired() && $component->isEmptyState($value)) {
                    $fail(__('validation.required', ['attribute' => $component->getLabel()]));

                    return;
                }

                if ($component->isEmptyState($value)) {
                    return;
                }

                $normalized = $component->normalizeState($value);

                if ($normalized === null) {
                    $fail(__('filament-flex-fields::default.date_time.validation.invalid'));

                    return;
                }

                $component->validateConstraints($normalized, $fail);
            };
        });
    }

    public function getRequiredValidationRule(): string|Closure
    {
        return 'nullable';
    }

    public function normalizeState(mixed $state): string|array|null
    {
        return $this->makeValueNormalizer()->normalize($state);
    }

    public function isEmptyState(mixed $state): bool
    {
        if ($state === null || $state === '') {
            return true;
        }

        if (in_array($this->getMode(), [DateTimeFieldMode::DateRange, DateTimeFieldMode::TimeRange], true)) {
            if (! is_array($state)) {
                return blank($state);
            }

            return blank($state['start'] ?? null) && blank($state['end'] ?? null);
        }

        return blank($state);
    }

    /**
     * @param  string|array{start: string|null, end: string|null}  $value
     */
    protected function validateConstraints(string|array $value, Closure $fail): void
    {
        $normalizer = $this->makeValueNormalizer();
        $constraints = new DateTimeConstraintResolver(
            $normalizer,
            $this->getMinValue(),
            $this->getMaxValue(),
            $this->getIsDateUnavailableCallback(),
        );

        if (in_array($this->getMode(), [DateTimeFieldMode::DateRange, DateTimeFieldMode::TimeRange], true)) {
            $this->validateRangeConstraints($value, $constraints, $fail);

            return;
        }

        if (! is_string($value)) {
            $fail(__('filament-flex-fields::default.date_time.validation.invalid'));

            return;
        }

        $this->validateSingleConstraints($value, $constraints, $fail);
    }

    protected function validateSingleConstraints(string $value, DateTimeConstraintResolver $constraints, Closure $fail): void
    {
        if ($constraints->isBelowMin($value)) {
            $fail(__('filament-flex-fields::default.date_time.validation.before_min'));

            return;
        }

        if ($constraints->isAboveMax($value)) {
            $fail(__('filament-flex-fields::default.date_time.validation.after_max'));

            return;
        }

        if ($constraints->isUnavailable($value)) {
            $fail(__('filament-flex-fields::default.date_time.validation.unavailable'));
        }
    }

    /**
     * @param  array{start: string|null, end: string|null}  $value
     */
    protected function validateRangeConstraints(array $value, DateTimeConstraintResolver $constraints, Closure $fail): void
    {
        $start = $value['start'] ?? null;
        $end = $value['end'] ?? null;

        if (blank($start) || blank($end)) {
            $fail(__('filament-flex-fields::default.date_time.validation.incomplete_range'));

            return;
        }

        $this->validateSingleConstraints($start, $constraints, $fail);
        $this->validateSingleConstraints($end, $constraints, $fail);

        if ($constraints->compareValues($start, $end) > 0) {
            $fail(__('filament-flex-fields::default.date_time.validation.range_order'));

            return;
        }

        if (! $this->shouldAllowSameDay() && $start === $end && $this->getMode() === DateTimeFieldMode::DateRange) {
            $fail(__('filament-flex-fields::default.date_time.validation.same_day_not_allowed'));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getAlpineConfiguration(): array
    {
        return DateTimeAlpineConfiguration::forField($this);
    }

    /**
     * @return array{
     *     parts: list<string>,
     *     single: array<string, string>,
     *     range: array{start: array<string, string>, end: array<string, string>}|null,
     * }
     */
    public function getViewSegments(): array
    {
        return DateTimeSegmentHydrator::forField($this);
    }

    /**
     * @return string|array{start: string|null, end: string|null}|null
     */
    public function resolveInitialStateForAlpine(): string|array|null
    {
        if (! $this->isRootMounted()) {
            $default = $this->getDefaultState();

            return $this->normalizeState($default);
        }

        return $this->normalizeState($this->getState());
    }

    protected function isRootMounted(): bool
    {
        try {
            $this->getContainer();

            return true;
        } catch (\Error) {
            return false;
        }
    }
}

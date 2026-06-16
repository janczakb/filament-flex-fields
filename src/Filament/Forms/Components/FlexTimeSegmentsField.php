<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Concerns\InteractsWithFlexTimeValueConfiguration;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Field;
use InvalidArgumentException;

class FlexTimeSegmentsField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;
    use InteractsWithFlexTimeValueConfiguration;

    protected string $view = 'filament-flex-fields::forms.components.flex-time-segments-field';

    protected string|Closure $variant = 'primary';

    protected int|Closure $minuteStep = 15;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (FlexTimeSegmentsField $component, mixed $state): void {
            $component->state($component->normalizeState($state));
        });

        $this->dehydrateStateUsing(
            fn (FlexTimeSegmentsField $component, mixed $state): ?string => $component->normalizeState($state),
        );

        $this->rule(function (FlexTimeSegmentsField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if ($component->isRequired() && blank($value)) {
                    $fail(__('validation.required', ['attribute' => $component->getLabel()]));

                    return;
                }

                if (blank($value)) {
                    return;
                }

                $normalized = $component->normalizeState($value);

                if ($normalized === null) {
                    $fail(__('validation.date_format', [
                        'attribute' => $component->getLabel(),
                        'format' => $component->getStorageFormat(),
                    ]));

                    return;
                }

                $minValue = $component->getResolvedMinValue();
                $maxValue = $component->getResolvedMaxValue();

                if ($minValue !== null && $normalized < $minValue) {
                    $fail(__('validation.after_or_equal', [
                        'attribute' => $component->getLabel(),
                        'date' => $minValue,
                    ]));
                }

                if ($maxValue !== null && $normalized > $maxValue) {
                    $fail(__('validation.before_or_equal', [
                        'attribute' => $component->getLabel(),
                        'date' => $maxValue,
                    ]));
                }
            };
        });
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'flat', 'soft'], true)) {
            throw new InvalidArgumentException("Flex time segments field variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function minuteStep(int|Closure $minuteStep): static
    {
        $this->minuteStep = $minuteStep;

        return $this;
    }

    public function getMinuteStep(): int
    {
        $minuteStep = (int) $this->evaluate($this->minuteStep);

        return max(1, min(60, $minuteStep));
    }

    public function withRecommendedDefaults(): static
    {
        return $this
            ->size('md')
            ->variant('primary')
            ->minuteStep(15)
            ->hourCycle(24);
    }

    public function normalizeState(mixed $state): ?string
    {
        if ($state === null) {
            return null;
        }

        if (is_string($state) && trim($state) === '') {
            return null;
        }

        $value = trim((string) $state);

        if (! preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return $this->makeTimeValueNormalizer()->normalizeSingle($value);
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-flex-time-segments-field',
            'fff-flex-text-input-field',
            'fff-flex-time-segments-field--'.$this->getSize(),
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-flex-time-segments-field--'.$this->getVariant(),
            'fff-flex-text-input-field--'.$this->getVariant(),
        ];
    }
}

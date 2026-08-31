<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasImageChoiceCardOptions;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\OptionsArrayStateCast;
use Illuminate\Validation\Rule;

class ImageChoiceCards extends Field
{
    use HasImageChoiceCardOptions;

    protected string $view = 'filament-flex-fields::forms.components.image-choice-cards';

    protected bool|Closure $isMultiple = false;

    protected int|Closure|null $minSelections = null;

    protected int|Closure|null $maxSelections = null;

    protected int|Closure|null $exactSelections = null;

    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function minSelections(int|Closure|null $count): static
    {
        $this->minSelections = $count;

        return $this;
    }

    public function maxSelections(int|Closure|null $count): static
    {
        $this->maxSelections = $count;

        return $this;
    }

    public function exactSelections(int|Closure|null $count): static
    {
        $this->exactSelections = $count;

        return $this;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function getMinSelections(): ?int
    {
        $count = $this->evaluate($this->minSelections);

        return $count === null ? null : (int) $count;
    }

    public function getMaxSelections(): ?int
    {
        $count = $this->evaluate($this->maxSelections);

        return $count === null ? null : (int) $count;
    }

    public function getExactSelections(): ?int
    {
        $count = $this->evaluate($this->exactSelections);

        return $count === null ? null : (int) $count;
    }

    public function getIndicator(): string
    {
        $indicator = $this->evaluate($this->indicator);

        if (filled($indicator)) {
            return (string) $indicator;
        }

        return $this->isMultiple() ? 'checkbox' : 'check';
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        if (! $this->isMultiple()) {
            return parent::getDefaultStateCasts();
        }

        return [
            ...parent::getDefaultStateCasts(),
            app(OptionsArrayStateCast::class),
        ];
    }

    public function pruneDisabledSelection(): void
    {
        $this->state($this->pruneState($this->getState()));
    }

    public function pruneState(mixed $state): mixed
    {
        if ($this->isMultiple()) {
            if (! is_array($state)) {
                return [];
            }

            return collect($state)
                ->map(fn (mixed $item): string => (string) $item)
                ->unique()
                ->values()
                ->reject(fn (string $key): bool => $this->isOptionDisabled($key))
                ->values()
                ->all();
        }

        if ($state === null || $state === '') {
            return null;
        }

        return $this->isOptionDisabled((string) $state) ? null : $state;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (ImageChoiceCards $component): void {
            if ($component->isMultiple() && ! is_array($component->getState())) {
                $component->state([]);
            }

            $component->pruneDisabledSelection();
        });

        $this->dehydrateStateUsing(function (mixed $state, ImageChoiceCards $component): mixed {
            return $component->pruneState($state);
        });

        $this->default(fn (ImageChoiceCards $component): mixed => $component->isMultiple() ? [] : null);

        $this->rule(function (ImageChoiceCards $component): mixed {
            if ($component->isMultiple()) {
                return 'array';
            }

            return Rule::in($component->getOptionKeys());
        });

        $this->rule(function (ImageChoiceCards $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! $component->isMultiple()) {
                    return;
                }

                if (! is_array($value)) {
                    return;
                }

                $selected = collect($value)
                    ->map(fn (mixed $item): string => (string) $item)
                    ->unique()
                    ->values();

                $allowedKeys = collect($component->getOptionKeys())->map(fn ($key): string => (string) $key);

                if ($selected->diff($allowedKeys)->isNotEmpty()) {
                    $fail(__('filament-flex-fields::default.validation.image_choice_cards.invalid_option'));

                    return;
                }

                $count = $selected->count();
                $exact = $component->getExactSelections();

                if ($exact !== null) {
                    if ($count !== $exact) {
                        $fail(__('filament-flex-fields::default.validation.image_choice_cards.exact', ['count' => $exact]));
                    }

                    return;
                }

                $min = $component->getMinSelections();

                if ($component->isRequired() && $min === null) {
                    $min = 1;
                }

                if ($min !== null && $count < $min) {
                    $fail(__('filament-flex-fields::default.validation.image_choice_cards.min', ['count' => $min]));

                    return;
                }

                $max = $component->getMaxSelections();

                if ($max !== null && $count > $max) {
                    $fail(__('filament-flex-fields::default.validation.image_choice_cards.max', ['count' => $max]));
                }
            };
        });
    }
}

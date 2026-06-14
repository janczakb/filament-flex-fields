<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasChoiceCardOptions;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\OptionsArrayStateCast;

class ChoiceCheckboxCards extends Field
{
    use HasChoiceCardOptions;

    protected string $view = 'filament-flex-fields::forms.components.choice-checkbox-cards';

    protected int|Closure|null $minSelections = null;

    protected int|Closure|null $maxSelections = null;

    protected int|Closure|null $exactSelections = null;

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

        return match ($this->getLayout()) {
            'media' => 'none',
            'featured' => 'check',
            default => 'checkbox',
        };
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            app(OptionsArrayStateCast::class),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->rule('array');

        $this->rule(function (ChoiceCheckboxCards $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! is_array($value)) {
                    return;
                }

                $selected = collect($value)
                    ->map(fn (mixed $item): string => (string) $item)
                    ->unique()
                    ->values();

                $allowedKeys = collect($component->getOptionKeys())->map(fn ($key): string => (string) $key);

                if ($selected->diff($allowedKeys)->isNotEmpty()) {
                    $fail(__('filament-flex-fields::default.validation.choice_checkbox_cards.invalid_option'));

                    return;
                }

                $count = $selected->count();
                $exact = $component->getExactSelections();

                if ($exact !== null) {
                    if ($count !== $exact) {
                        $fail(__('filament-flex-fields::default.validation.choice_checkbox_cards.exact', ['count' => $exact]));
                    }

                    return;
                }

                $min = $component->getMinSelections();

                if ($component->isRequired() && $min === null) {
                    $min = 1;
                }

                if ($min !== null && $count < $min) {
                    $fail(__('filament-flex-fields::default.validation.choice_checkbox_cards.min', ['count' => $min]));

                    return;
                }

                $max = $component->getMaxSelections();

                if ($max !== null && $count > $max) {
                    $fail(__('filament-flex-fields::default.validation.choice_checkbox_cards.max', ['count' => $max]));
                }
            };
        });
    }
}

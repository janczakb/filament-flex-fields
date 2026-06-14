<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasChecklistOptions;
use Bjanczak\FilamentFlexFields\Concerns\ResolvesConfiguredIcons;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\OptionsArrayStateCast;
use Illuminate\Contracts\Support\Htmlable;

class FlexChecklist extends Field
{
    use HasChecklistOptions;
    use ResolvesConfiguredIcons;

    protected string $view = 'filament-flex-fields::forms.components.flex-checklist';

    protected int|Closure|null $minSelections = null;

    protected int|Closure|null $maxSelections = null;

    protected int|Closure|null $exactSelections = null;

    protected string|Closure|null $color = 'primary';

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->evaluate($this->color);
    }

    public function getLockIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveConfiguredIcon('flex_checklist_lock_icon', GravityIcon::Lock);
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-flex-checklist',
            'fff-flex-checklist--'.$this->getSize(),
        ];

        if ($color = $this->getColor()) {
            $classes[] = 'fi-color-'.$color;
        }

        return $classes;
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

        $this->rule(function (FlexChecklist $component): Closure {
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
                    $fail(__('filament-flex-fields::default.validation.flex_checklist.invalid_option'));

                    return;
                }

                $count = $selected->count();
                $exact = $component->getExactSelections();

                if ($exact !== null) {
                    if ($count !== $exact) {
                        $fail(__('filament-flex-fields::default.validation.flex_checklist.exact', ['count' => $exact]));
                    }

                    return;
                }

                $min = $component->getMinSelections();

                if ($component->isRequired() && $min === null) {
                    $min = 1;
                }

                if ($min !== null && $count < $min) {
                    $fail(__('filament-flex-fields::default.validation.flex_checklist.min', ['count' => $min]));

                    return;
                }

                $max = $component->getMaxSelections();

                if ($max !== null && $count > $max) {
                    $fail(__('filament-flex-fields::default.validation.flex_checklist.max', ['count' => $max]));
                }
            };
        });
    }
}

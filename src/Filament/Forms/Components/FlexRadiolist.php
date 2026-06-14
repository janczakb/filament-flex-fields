<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasChecklistOptions;
use Bjanczak\FilamentFlexFields\Concerns\ResolvesConfiguredIcons;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use InvalidArgumentException;

class FlexRadiolist extends Field
{
    use HasChecklistOptions {
        getNormalizedOptions as getChecklistNormalizedOptions;
    }
    use ResolvesConfiguredIcons;

    protected string $view = 'filament-flex-fields::forms.components.flex-radiolist';

    protected string|Closure|null $color = 'primary';

    protected string|Closure $variant = 'default';

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->evaluate($this->color);
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['default', 'label-only'], true)) {
            throw new InvalidArgumentException("Flex radiolist variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function isLabelOnlyVariant(): bool
    {
        return $this->getVariant() === 'label-only';
    }

    public function getLockIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveConfiguredIcon('flex_radiolist_lock_icon', GravityIcon::Lock);
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-flex-radiolist',
            'fff-flex-radiolist--'.$this->getSize(),
            'fff-flex-radiolist--'.$this->getVariant(),
        ];

        if ($color = $this->getColor()) {
            $classes[] = 'fi-color-'.$color;
        }

        return $classes;
    }

    /**
     * @return array<string|int, array{
     *     label: string,
     *     description: ?string,
     *     icon: ?string,
     *     disabled: bool,
     * }>
     */
    public function getNormalizedOptions(): array
    {
        $options = $this->getChecklistNormalizedOptions();

        if (! $this->isLabelOnlyVariant()) {
            return $options;
        }

        return array_map(
            fn (array $option): array => [
                ...$option,
                'icon' => null,
                'description' => null,
            ],
            $options,
        );
    }

    /**
     * @return array<string, string>
     */
    public function getRadiolistSizeStyles(): array
    {
        return collect($this->getChecklistSizeStyles())
            ->mapWithKeys(fn (string $value, string $key): array => [
                str_replace('flex-checklist', 'flex-radiolist', $key) => $value,
            ])
            ->all();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(function (FlexRadiolist $component): In {
            return Rule::in($component->getOptionKeys());
        });
    }
}

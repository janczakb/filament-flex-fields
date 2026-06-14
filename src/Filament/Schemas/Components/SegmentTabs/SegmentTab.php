<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasLabel;
use Filament\Schemas\Components\Contracts\CanConcealComponents;
use Filament\Support\Concerns\HasBadge;
use Filament\Support\Concerns\HasBadgeTooltip;
use Filament\Support\Concerns\HasIcon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

class SegmentTab extends Component implements CanConcealComponents
{
    use HasBadge;
    use HasBadgeTooltip;
    use HasIcon;
    use HasLabel;

    protected string $view = 'filament-flex-fields::schemas.components.segment-tabs.segment-tab';

    protected string|Closure|null $tooltip = null;

    final public function __construct(string|Htmlable|Closure|null $label = null)
    {
        $this->label($label);
    }

    public static function make(string|Htmlable|Closure|null $label = null): static
    {
        $static = app(static::class, ['label' => $label]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key(function (SegmentTab $component): string {
            $label = $component->getLabel();
            $statePath = $component->getStatePath();

            return Str::slug(Str::transliterate($label, strict: true)).'::'.(filled($statePath) ? "{$statePath}::segment-tab" : 'segment-tab');
        }, isInheritable: false);
    }

    public function tooltip(string|Closure|null $tooltip): static
    {
        $this->tooltip = $tooltip;

        return $this;
    }

    public function getTooltip(): ?string
    {
        $tooltip = $this->evaluate($this->tooltip);

        return filled($tooltip) ? (string) $tooltip : null;
    }

    public function canConcealComponents(): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\CanPersistTab;
use Filament\Schemas\Components\Concerns\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

class SegmentTabs extends Component
{
    use CanPersistTab;
    use HasControlSize;
    use HasLabel;

    protected string $view = 'filament-flex-fields::schemas.components.segment-tabs';

    protected int|Closure $activeTab = 1;

    protected string|Closure|null $tabQueryStringKey = null;

    protected string|Closure $variant = 'default';

    protected string|Closure|null $color = null;

    protected bool|Closure $hasSeparators = true;

    protected bool|Closure $isFullWidth = false;

    protected bool|Closure $isIconOnly = false;

    protected bool|Closure $expandSelectedLabel = false;

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

        $this->key(function (SegmentTabs $component): ?string {
            $label = $component->getLabel();

            if (blank($label)) {
                return null;
            }

            $statePath = $component->getStatePath();

            return Str::slug(Str::transliterate($label, strict: true)).'::'.(filled($statePath) ? "{$statePath}::segment-tabs" : 'segment-tabs');
        }, isInheritable: false);
    }

    /**
     * @param  array<SegmentTab> | Closure  $tabs
     */
    public function tabs(array|Closure $tabs): static
    {
        $this->components($tabs);

        return $this;
    }

    public function activeTab(int|Closure $activeTab): static
    {
        $this->activeTab = $activeTab;

        return $this;
    }

    public function persistTabInQueryString(string|Closure|null $key = 'segment-tab'): static
    {
        $this->tabQueryStringKey = $key;

        return $this;
    }

    public function getActiveTab(): int
    {
        if ($this->isTabPersistedInQueryString()) {
            $queryStringTab = request()->query($this->getTabQueryStringKey());

            foreach ($this->getChildSchema()->getComponents() as $index => $tab) {
                if ($tab->getId() !== $queryStringTab) {
                    continue;
                }

                return $index + 1;
            }
        }

        return $this->evaluate($this->activeTab);
    }

    public function getTabQueryStringKey(): ?string
    {
        return $this->evaluate($this->tabQueryStringKey);
    }

    public function isTabPersistedInQueryString(): bool
    {
        return filled($this->getTabQueryStringKey());
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function separators(bool|Closure $condition = true): static
    {
        $this->hasSeparators = $condition;

        return $this;
    }

    public function fullWidth(bool|Closure $condition = true): static
    {
        $this->isFullWidth = $condition;

        return $this;
    }

    public function iconOnly(bool|Closure $condition = true): static
    {
        $this->isIconOnly = $condition;

        return $this;
    }

    public function expandSelectedLabel(bool|Closure $condition = true): static
    {
        $this->expandSelectedLabel = $condition;

        return $this;
    }

    public function getVariant(): string
    {
        return $this->evaluate($this->variant);
    }

    public function getColor(): ?string
    {
        $color = $this->evaluate($this->color);

        if (filled($color)) {
            return (string) $color;
        }

        return $this->getVariant() === 'ghost' ? 'primary' : null;
    }

    public function hasSeparators(): bool
    {
        return (bool) $this->evaluate($this->hasSeparators);
    }

    public function isFullWidth(): bool
    {
        return (bool) $this->evaluate($this->isFullWidth);
    }

    public function isIconOnly(): bool
    {
        return (bool) $this->evaluate($this->isIconOnly);
    }

    public function shouldExpandSelectedLabel(): bool
    {
        return (bool) $this->evaluate($this->expandSelectedLabel);
    }

    /**
     * @return list<SegmentTab>
     */
    public function getVisibleTabs(): array
    {
        return array_values(array_filter(
            $this->getChildSchema()->getComponents(),
            fn (Component $tab): bool => $tab instanceof SegmentTab && $tab->isVisible(),
        ));
    }

    public function getActiveTabKey(): ?string
    {
        $activeTabIndex = $this->getActiveTab();
        $visibleCounter = 0;

        foreach ($this->getChildSchema()->getComponents() as $tab) {
            if (! $tab instanceof SegmentTab || ! $tab->isVisible()) {
                continue;
            }

            $visibleCounter++;

            if ($visibleCounter === $activeTabIndex) {
                return $tab->getKey(isAbsolute: false);
            }
        }

        return $this->getVisibleTabs()[0]?->getKey(isAbsolute: false);
    }

    public function isTabActive(SegmentTab $tab): bool
    {
        $activeTabKey = $this->getActiveTabKey();

        if (blank($activeTabKey)) {
            return false;
        }

        return $tab->getKey(isAbsolute: false) === $activeTabKey;
    }
}

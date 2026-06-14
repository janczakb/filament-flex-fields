<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\ResolvesConfiguredIcons;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\CanOpenUrl;
use Filament\Schemas\Components\Concerns\HasDescription;
use Filament\Schemas\Components\Concerns\HasHeading;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ItemCard extends Component
{
    use CanOpenUrl;
    use HasDescription;
    use HasHeading;
    use ResolvesConfiguredIcons;

    protected string $view = 'filament-flex-fields::schemas.components.item-card';

    protected string|Closure $variant = 'default';

    protected string|BackedEnum|Htmlable|Closure|null $icon = null;

    protected string|Closure|null $image = null;

    protected string|Closure $imageShape = 'rounded';

    protected string|Closure|null $imageAlt = null;

    protected bool|Closure $hasChevron = false;

    protected string|Closure $context = 'auto';

    protected bool|Closure|null $isPressable = null;

    final public function __construct(string|Closure|null $heading = null)
    {
        $this->heading($heading);
    }

    public static function make(string|Closure|null $heading = null): static
    {
        $static = app(static::class, ['heading' => $heading]);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->gap(false);
        $this->columns(1);
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function icon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function image(string|Closure|null $url): static
    {
        $this->image = $url;

        return $this;
    }

    public function imageShape(string|Closure $shape): static
    {
        $this->imageShape = $shape;

        return $this;
    }

    public function imageAlt(string|Closure|null $alt): static
    {
        $this->imageAlt = $alt;

        return $this;
    }

    public function chevron(bool|Closure $condition = true): static
    {
        $this->hasChevron = $condition;

        return $this;
    }

    public function context(string|Closure $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function standalone(): static
    {
        return $this->context('standalone');
    }

    public function inGroup(): static
    {
        return $this->context('group');
    }

    public function pressable(bool|Closure $condition = true): static
    {
        $this->isPressable = $condition;

        return $this;
    }

    public function pressableAction(Action|Closure|null $action): static
    {
        if ($action instanceof Closure) {
            $name = Str::camel(Str::slug((string) $this->getHeading(), '_')) ?: 'press';

            $action = Action::make($name)
                ->action($action);
        }

        $this->pressable();

        return $this->action($action);
    }

    public function action(?Action $action): static
    {
        if ($action !== null) {
            $this->assignActionKey($action);
        }

        return parent::action($action);
    }

    protected function assignActionKey(Action $action): void
    {
        if ($this->hasStatePath() || filled($this->evaluate($this->key))) {
            return;
        }

        $this->key(Str::kebab($action->getName()));
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['default', 'secondary', 'tertiary', 'outline', 'transparent'], true)) {
            throw new InvalidArgumentException("Item card variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        if ($this->hasLeadingImage()) {
            return null;
        }

        $icon = $this->evaluate($this->icon);

        if ($icon instanceof BackedEnum || $icon instanceof Htmlable) {
            return $icon;
        }

        return filled($icon) ? (string) $icon : null;
    }

    public function getImage(): ?string
    {
        $image = $this->evaluate($this->image);

        return filled($image) ? (string) $image : null;
    }

    public function getImageShape(): string
    {
        $shape = (string) $this->evaluate($this->imageShape);

        if (! in_array($shape, ['rounded', 'circle'], true)) {
            throw new InvalidArgumentException("Item card image shape [{$shape}] is not supported.");
        }

        return $shape;
    }

    public function getImageAlt(): string
    {
        $alt = $this->evaluate($this->imageAlt);

        if (filled($alt)) {
            return (string) $alt;
        }

        $heading = $this->getHeading();

        return filled($heading) ? (string) $heading : '';
    }

    public function hasLeadingImage(): bool
    {
        return $this->getImage() !== null;
    }

    public function hasLeadingIcon(): bool
    {
        return $this->getIcon() !== null;
    }

    public function hasChevron(): bool
    {
        return (bool) $this->evaluate($this->hasChevron);
    }

    public function getChevronIcon(): string|BackedEnum|Htmlable
    {
        return $this->resolveConfiguredIcon('item_card_chevron_icon', GravityIcon::ChevronRight);
    }

    public function getContext(): string
    {
        $context = (string) $this->evaluate($this->context);

        if ($context === 'auto') {
            $parent = $this->getContainer()->getParentComponent();

            return $parent instanceof ItemCardGroup ? 'group' : 'standalone';
        }

        if (! in_array($context, ['group', 'standalone'], true)) {
            throw new InvalidArgumentException("Item card context [{$context}] is not supported.");
        }

        return $context;
    }

    public function isPressable(): bool
    {
        if ($this->isPressable !== null) {
            return (bool) $this->evaluate($this->isPressable);
        }

        if ($this->hasInteractiveAction()) {
            return false;
        }

        $parent = $this->getParentItemCardGroup();

        if ($parent?->areRowsPressable()) {
            return true;
        }

        return $this->hasChevron() || $this->getPressableAction() !== null;
    }

    public function getPressableAction(): ?Action
    {
        if ($this->action === null) {
            return null;
        }

        return $this->getAction($this->action->getName());
    }

    public function hasInteractiveAction(): bool
    {
        $components = $this->getDefaultChildComponents();

        if ($components instanceof Schema) {
            return $components->getComponents() !== [];
        }

        return is_array($components) && $components !== [];
    }

    protected function getParentItemCardGroup(): ?ItemCardGroup
    {
        if (! isset($this->container)) {
            return null;
        }

        $parent = $this->getContainer()->getParentComponent();

        return $parent instanceof ItemCardGroup ? $parent : null;
    }
}

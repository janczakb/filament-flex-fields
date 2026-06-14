<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Concerns\HasDescription;
use Filament\Schemas\Components\Concerns\HasHeading;
use InvalidArgumentException;

class ItemCardGroup extends Component
{
    use HasDescription;
    use HasHeading;

    protected string $view = 'filament-flex-fields::schemas.components.item-card-group';

    protected string|Closure $layout = 'list';

    protected string|Closure $variant = 'default';

    protected bool|Closure $isDivided = false;

    protected string|Closure $headerStyle = 'embedded';

    protected bool|Closure $areRowsPressable = false;

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

    public function layout(string|Closure $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function divided(bool|Closure $condition = true): static
    {
        $this->isDivided = $condition;

        return $this;
    }

    public function separated(bool|Closure $condition = true): static
    {
        return $this->divided($condition);
    }

    public function withoutSeparators(): static
    {
        return $this->divided(false);
    }

    public function headerStyle(string|Closure $style): static
    {
        $this->headerStyle = $style;

        return $this;
    }

    public function pressable(bool|Closure $condition = true): static
    {
        $this->areRowsPressable = $condition;

        return $this;
    }

    public function areRowsPressable(): bool
    {
        return (bool) $this->evaluate($this->areRowsPressable);
    }

    public function getLayout(): string
    {
        $layout = (string) $this->evaluate($this->layout);

        if (! in_array($layout, ['list', 'grid'], true)) {
            throw new InvalidArgumentException("Item card group layout [{$layout}] is not supported.");
        }

        return $layout;
    }

    public function getVariant(): string
    {
        $variant = (string) $this->evaluate($this->variant);

        if (! in_array($variant, ['default', 'secondary', 'tertiary', 'outline', 'transparent'], true)) {
            throw new InvalidArgumentException("Item card group variant [{$variant}] is not supported.");
        }

        return $variant;
    }

    public function isDivided(): bool
    {
        return (bool) $this->evaluate($this->isDivided);
    }

    public function getHeaderStyle(): string
    {
        $style = (string) $this->evaluate($this->headerStyle);

        if (! in_array($style, ['embedded', 'outside'], true)) {
            throw new InvalidArgumentException("Item card group header style [{$style}] is not supported.");
        }

        return $style;
    }
}

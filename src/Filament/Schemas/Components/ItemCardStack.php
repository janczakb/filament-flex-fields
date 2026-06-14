<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schemas\Components;

use Closure;
use Filament\Schemas\Components\Component;
use InvalidArgumentException;

class ItemCardStack extends Component
{
    protected string $view = 'filament-flex-fields::schemas.components.item-card-stack';

    protected string|Closure $stackGap = 'md';

    public static function make(): static
    {
        $static = app(static::class);
        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->gap(false);
        $this->columns(1);
    }

    public function stackGap(string|Closure $stackGap): static
    {
        $this->stackGap = $stackGap;

        return $this;
    }

    public function getStackGap(): string
    {
        $stackGap = (string) $this->evaluate($this->stackGap);

        if (! in_array($stackGap, ['sm', 'md', 'lg'], true)) {
            throw new InvalidArgumentException("Item card stack gap [{$stackGap}] is not supported.");
        }

        return $stackGap;
    }
}

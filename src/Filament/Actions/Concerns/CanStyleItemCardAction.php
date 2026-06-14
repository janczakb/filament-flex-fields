<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Actions\Concerns;

use Closure;
use Filament\Support\Enums\Size;

trait CanStyleItemCardAction
{
    protected bool|Closure $isItemCardAction = false;

    public function itemCard(bool|Closure $condition = true): static
    {
        $this->isItemCardAction = $condition;

        $this->extraAttributes(function (): array {
            if (! $this->isItemCardAction()) {
                return [];
            }

            return [
                'class' => 'fff-item-card-action',
            ];
        }, merge: true);

        return $this
            ->outlined()
            ->size(Size::Small);
    }

    public function isItemCardAction(): bool
    {
        return (bool) $this->evaluate($this->isItemCardAction);
    }
}

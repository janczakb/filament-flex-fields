<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Actions\Concerns;

use Closure;
use InvalidArgumentException;

trait CanRoundAction
{
    protected string|Closure|null $actionRounded = null;

    public function rounded(string|Closure|null $rounded): static
    {
        $this->actionRounded = $rounded;

        $this->extraAttributes(function (): array {
            $class = $this->getRoundedClass();

            if ($class === null) {
                return [];
            }

            return [
                'class' => $class,
            ];
        }, merge: true);

        return $this;
    }

    public function getRounded(): ?string
    {
        $rounded = $this->evaluate($this->actionRounded);

        if ($rounded === null || $rounded === '') {
            return null;
        }

        $rounded = (string) $rounded;

        if (! in_array($rounded, ['none', 'sm', 'md', 'lg', 'xl', 'full'], true)) {
            throw new InvalidArgumentException("Action rounded [{$rounded}] is not supported.");
        }

        return $rounded;
    }

    public function getRoundedClass(): ?string
    {
        $rounded = $this->getRounded();

        if ($rounded === null) {
            return null;
        }

        return "fff-action--rounded-{$rounded}";
    }
}

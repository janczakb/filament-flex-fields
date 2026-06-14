<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns;

use Closure;

trait ConfiguresSlugActionButtons
{
    protected bool|Closure $showVisitLink = true;

    protected bool|Closure $showCopyButton = true;

    protected bool|Closure $showRegenerateButton = true;

    protected bool|Closure|null $showActionButtonLabels = null;

    public function showVisitLink(bool|Closure $condition = true): static
    {
        $this->showVisitLink = $condition;

        return $this;
    }

    public function showCopyButton(bool|Closure $condition = true): static
    {
        $this->showCopyButton = $condition;

        return $this;
    }

    public function showRegenerateButton(bool|Closure $condition = true): static
    {
        $this->showRegenerateButton = $condition;

        return $this;
    }

    public function actionButtonLabels(bool|Closure $condition = true): static
    {
        $this->showActionButtonLabels = $condition;

        return $this;
    }

    public function actionButtonsIconOnly(bool|Closure $condition = true): static
    {
        $this->showActionButtonLabels = fn (): bool => ! (bool) $this->evaluate($condition);

        return $this;
    }

    public function shouldShowVisitLink(): bool
    {
        return (bool) $this->evaluate($this->showVisitLink);
    }

    public function shouldShowCopyButton(): bool
    {
        return (bool) $this->evaluate($this->showCopyButton);
    }

    public function shouldShowRegenerateButton(): bool
    {
        return (bool) $this->evaluate($this->showRegenerateButton);
    }

    public function shouldShowActionButtonLabels(): bool
    {
        if ($this->showActionButtonLabels !== null) {
            return (bool) $this->evaluate($this->showActionButtonLabels);
        }

        return (bool) config('filament-flex-fields.slug.action_button_labels', true);
    }
}

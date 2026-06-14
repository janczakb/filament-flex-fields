<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Actions;

use Bjanczak\FilamentFlexFields\Filament\Actions\Concerns\CanRequireHoldConfirm;
use Bjanczak\FilamentFlexFields\Filament\Actions\Concerns\CanRoundAction;
use Bjanczak\FilamentFlexFields\Filament\Actions\Concerns\CanStyleItemCardAction;
use Filament\Actions\Action as BaseAction;

class Action extends BaseAction
{
    use CanRequireHoldConfirm;
    use CanRoundAction;
    use CanStyleItemCardAction;

    protected function toButtonHtml(): string
    {
        $html = parent::toButtonHtml();

        if ($this->hasHoldConfirm()) {
            return $this->wrapHoldConfirmButtonHtml($html);
        }

        return $html;
    }
}

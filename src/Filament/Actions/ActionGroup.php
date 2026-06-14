<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Actions;

use Bjanczak\FilamentFlexFields\Filament\Actions\Concerns\CanRoundAction;
use Bjanczak\FilamentFlexFields\Filament\Actions\Concerns\CanStyleItemCardAction;
use Filament\Actions\ActionGroup as BaseActionGroup;

class ActionGroup extends BaseActionGroup
{
    use CanRoundAction;
    use CanStyleItemCardAction;
}

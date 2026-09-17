<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Tables\Columns\Concerns;

use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Livewire\Component;
use Throwable;

/**
 * Emit CRG asset consumers inside the Livewire table DOM (form-field parity).
 * Markers follow the Livewire component lifecycle — SPA navigate / morph teardown
 * drops them and the injector uninstalls unretained CSS/JS.
 */
trait EmitsFlexFieldTableColumnAssets
{
    protected function withFlexFieldColumnAssets(string $component, string $html): string
    {
        return $this->flexFieldColumnAssetMarkup($component).$html;
    }

    protected function flexFieldColumnAssetMarkup(string $component): string
    {
        return FlexFieldStylesheetQueue::emitDomRootConsumerMarkup(
            $component,
            $this->flexFieldColumnAssetConsumerId($component),
        );
    }

    protected function flexFieldColumnAssetConsumerId(string $component): string
    {
        $livewireId = 'page';

        try {
            $livewire = $this->getLivewire();

            if ($livewire instanceof Component) {
                $livewireId = (string) $livewire->getId();
            }
        } catch (Throwable) {
            // Unmounted columns (unit tests / early format) fall back to page scope.
        }

        return $livewireId.'.'.$component;
    }
}

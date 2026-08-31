<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Concerns;

use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldStudio;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Column;

trait InteractsWithFlexFieldSchemas
{
    protected static function flexFieldFormSectionForResource(?string $label = null, bool $collapsible = false): Section
    {
        return app(FlexFieldStudio::class)
            ->form()
            ->forModel(static::getModel())
            ->sectionLabel($label)
            ->collapsible($collapsible)
            ->section();
    }

    /**
     * @return list<Column>
     */
    protected static function flexFieldTableColumnsForResource(?array $onlySlugs = null): array
    {
        $integration = app(FlexFieldStudio::class)
            ->table()
            ->forModel(static::getModel());

        if ($onlySlugs !== null) {
            $integration->only($onlySlugs);
        }

        return $integration->columns();
    }

    protected static function flexFieldTableFiltersForResource(?array $onlySlugs = null): array
    {
        $integration = app(FlexFieldStudio::class)
            ->filters()
            ->forModel(static::getModel());

        if ($onlySlugs !== null) {
            $integration->only($onlySlugs);
        }

        return $integration->filters();
    }

    protected function flexFieldFormSection(?string $label = null, bool $collapsible = false): Section
    {
        return app(FlexFieldStudio::class)
            ->form()
            ->forModel(static::getModel())
            ->record(method_exists($this, 'getRecord') ? $this->getRecord() : null)
            ->sectionLabel($label)
            ->collapsible($collapsible)
            ->section();
    }

    /**
     * @return list<Entry>
     */
    protected function flexFieldInfolistEntries(?array $onlySlugs = null): array
    {
        $integration = app(FlexFieldStudio::class)
            ->infolist()
            ->forModel(static::getModel());

        if ($onlySlugs !== null) {
            $integration->only($onlySlugs);
        }

        return $integration->entries();
    }

    protected function flexFieldInfolistSection(?string $label = null): Section
    {
        return app(FlexFieldStudio::class)
            ->infolist()
            ->forModel(static::getModel())
            ->section($label);
    }
}

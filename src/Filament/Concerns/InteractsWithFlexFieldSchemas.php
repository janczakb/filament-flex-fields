<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Concerns;

use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldStudio;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Column;

trait InteractsWithFlexFieldSchemas
{
    /**
     * @return list<Component|Section>
     */
    protected static function flexFieldFormLayoutForResource(?string $label = null, bool $collapsible = false): array
    {
        $integration = app(FlexFieldStudio::class)
            ->form()
            ->forModel(static::getModel())
            ->sectionLabel($label)
            ->collapsible($collapsible);

        return $integration->layout();
    }

    protected static function flexFieldFormSectionForResource(?string $label = null, bool $collapsible = false): Section
    {
        $layout = static::flexFieldFormLayoutForResource($label, $collapsible);

        if (count($layout) === 1 && $layout[0] instanceof Section) {
            return $layout[0];
        }

        return Section::make($label ?? __('filament-flex-fields::default.schema.custom_fields_section'))
            ->schema($layout);
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

    /**
     * @return list<Component|Section>
     */
    protected function flexFieldFormLayout(?string $label = null, bool $collapsible = false): array
    {
        $integration = app(FlexFieldStudio::class)
            ->form()
            ->forModel(static::getModel())
            ->record(method_exists($this, 'getRecord') ? $this->getRecord() : null)
            ->sectionLabel($label)
            ->collapsible($collapsible);

        return $integration->layout();
    }

    protected function flexFieldFormSection(?string $label = null, bool $collapsible = false): Section
    {
        $layout = $this->flexFieldFormLayout($label, $collapsible);

        if (count($layout) === 1 && $layout[0] instanceof Section) {
            return $layout[0];
        }

        return Section::make($label ?? __('filament-flex-fields::default.schema.custom_fields_section'))
            ->schema($layout);
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

    /**
     * @return list<Section>
     */
    protected static function flexFieldInfolistLayoutForResource(?string $label = null): array
    {
        $integration = app(FlexFieldStudio::class)
            ->infolist()
            ->forModel(static::getModel());

        $layout = $integration->layout();

        if ($label !== null && count($layout) === 1 && $layout[0] instanceof Section) {
            $layout[0]->heading($label);
        }

        return $layout;
    }

    /**
     * @return list<Section>
     */
    protected function flexFieldInfolistLayout(?string $label = null): array
    {
        $integration = app(FlexFieldStudio::class)
            ->infolist()
            ->forModel(static::getModel())
            ->record(method_exists($this, 'getRecord') ? $this->getRecord() : null);

        $layout = $integration->layout();

        if ($label !== null && count($layout) === 1 && $layout[0] instanceof Section) {
            $layout[0]->heading($label);
        }

        return $layout;
    }

    protected function flexFieldInfolistSection(?string $label = null): Section
    {
        return app(FlexFieldStudio::class)
            ->infolist()
            ->forModel(static::getModel())
            ->section($label);
    }
}

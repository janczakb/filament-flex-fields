<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Support\FilamentSchemaCompatibility;
use Bjanczak\FilamentFlexFields\Support\Playground\TextInput\TextInputDemo;
use Bjanczak\FilamentFlexFields\Support\TextInput\TextInputMessages;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/** Catalog-only controller; application fields never instantiate the scenario inventory. */
final class FlexTextInputPlayground
{
    /** @return array<string, mixed> */
    public function defaultState(): array
    {
        return TextInputDemo::defaults();
    }

    /** @return list<Component> */
    public function components(): array
    {
        $tabs = [];
        foreach (TextInputDemo::groups() as $group => $scenarios) {
            $fields = array_map(fn (array $scenario): FlexTextInput => TextInputDemo::field($scenario), $scenarios);
            $components = [Grid::make(['default' => 1, 'lg' => 2])->schema($fields)];
            $schema = FilamentSchemaCompatibility::supportsDeferredLoading()
                ? Schema::make()->key('input-demo-'.$group)->deferLoading($group !== 'overview')->components($components)
                : $components;
            $tabs[] = SegmentTab::make(TextInputMessages::get('demo.'.$group))->key($group)
                ->schema($schema);
        }

        return [Section::make('Flex Text Input')
            ->description(TextInputMessages::get('demo.intro'))
            ->schema([SegmentTabs::make('input-features')->persistTabInQueryString('input-tab')->tabs($tabs)])];
    }
}

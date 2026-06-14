<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SegmentTabsPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'segment_tabs__general_name' => 'Alex Rivera',
            'segment_tabs__general_email' => 'alex@example.com',
            'segment_tabs__advanced_api_key' => 'sk_live_xxx',
            'segment_tabs__advanced_webhook' => 'https://example.com/hooks',
            'segment_tabs__ghost_name' => 'Morgan Lee',
            'segment_tabs__ghost_bio' => 'Product designer',
        ];
    }

    public function section(): Section
    {
        return Section::make('Segment Tabs')
            ->description('Segment control header with tab panels and nested form schemas.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Grid::make(['default' => 1, 'lg' => 2])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        SegmentTabs::make('Account')
                            ->tabs([
                                SegmentTab::make('General')
                                    ->icon(GravityIcon::Person)
                                    ->schema([
                                        FlexTextInput::make('segment_tabs__general_name')
                                            ->label('Name'),
                                        FlexTextInput::make('segment_tabs__general_email')
                                            ->label('Email'),
                                    ]),
                                SegmentTab::make('Advanced')
                                    ->icon(GravityIcon::Gear)
                                    ->schema([
                                        FlexTextInput::make('segment_tabs__advanced_api_key')
                                            ->label('API key'),
                                        FlexTextInput::make('segment_tabs__advanced_webhook')
                                            ->label('Webhook URL'),
                                    ]),
                            ]),
                        SegmentTabs::make('Profile')
                            ->variant('ghost')
                            ->separators(false)
                            ->fullWidth()
                            ->tabs([
                                SegmentTab::make('Details')
                                    ->schema([
                                        FlexTextInput::make('segment_tabs__ghost_name')
                                            ->label('Display name'),
                                    ]),
                                SegmentTab::make('Bio')
                                    ->schema([
                                        FlexTextInput::make('segment_tabs__ghost_bio')
                                            ->label('Short bio'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [$this->section()];
    }
}

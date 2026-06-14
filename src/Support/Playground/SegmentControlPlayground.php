<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SegmentControlPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'segment_control__sm' => 'dashboard',
            'segment_control__md' => 'dashboard',
            'segment_control__lg' => 'dashboard',
            'segment_control__disabled' => 'dashboard',
            'segment_control__disabled_item' => 'dashboard',
            'segment_control__no_separators' => 'dashboard',
            'segment_control__full_width' => 'dashboard',
            'segment_control__icons_sm' => 'dashboard',
            'segment_control__icons_md' => 'dashboard',
            'segment_control__icons_lg' => 'dashboard',
            'segment_control__icons' => 'dashboard',
            'segment_control__icons_ghost' => 'dashboard',
            'segment_control__icon_only' => 'desktop',
            'segment_control__icon_expand' => 'chat',
            'segment_control__icon_expand_default' => 'chat',
            'segment_control__reference' => 'monthly',
            'segment_control__ghost_reference' => 'mtd',
        ];
    }

    public function section(): Section
    {
        $options = PlaygroundOptions::hero();
        $iconsOptions = PlaygroundOptions::heroWithIcons();
        $iconExpandOptions = PlaygroundOptions::iconExpand();

        return Section::make('Segment Control')
            ->description('SaaS-style sizes, disabled states, separators and icon expand.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Grid::make(1)
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        SegmentControl::make('segment_control__sm')
                            ->label('Sizes · SM')
                            ->size('sm')
                            ->options($options),
                        SegmentControl::make('segment_control__md')
                            ->label('Sizes · MD')
                            ->options($options),
                        SegmentControl::make('segment_control__lg')
                            ->label('Sizes · LG')
                            ->size('lg')
                            ->options($options),
                    ]),
                Grid::make(1)
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        SegmentControl::make('segment_control__icons_sm')
                            ->label('SM · icons')
                            ->size('sm')
                            ->options($iconsOptions),
                        SegmentControl::make('segment_control__icons_md')
                            ->label('MD · icons')
                            ->options($iconsOptions),
                        SegmentControl::make('segment_control__icons_lg')
                            ->label('LG · icons')
                            ->size('lg')
                            ->options($iconsOptions),
                    ]),
                Grid::make(1)
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        SegmentControl::make('segment_control__disabled')
                            ->label('Disabled')
                            ->disabled()
                            ->options($options),
                        SegmentControl::make('segment_control__disabled_item')
                            ->label('Disabled item')
                            ->options([
                                'dashboard' => 'Dashboard',
                                'analytics' => [
                                    'label' => 'Analytics',
                                    'disabled' => true,
                                ],
                                'reports' => 'Reports',
                            ]),
                        SegmentControl::make('segment_control__no_separators')
                            ->label('Without separators')
                            ->separators(false)
                            ->options($options),
                        SegmentControl::make('segment_control__full_width')
                            ->label('Full width')
                            ->fullWidth()
                            ->options($options),
                    ]),
                Grid::make(1)
                    ->extraAttributes(['class' => 'fff-playground-variants fff-playground-variants--stack'])
                    ->schema([
                        SegmentControl::make('segment_control__icons')
                            ->label('Icons + labels')
                            ->options($iconsOptions),
                        SegmentControl::make('segment_control__icons_ghost')
                            ->label('Icons + labels · ghost')
                            ->variant('ghost')
                            ->options($iconsOptions),
                        SegmentControl::make('segment_control__icon_only')
                            ->label('Icon only')
                            ->iconOnly()
                            ->options(PlaygroundOptions::devices()),
                        SegmentControl::make('segment_control__icon_expand')
                            ->label('Icon expand · ghost')
                            ->variant('ghost')
                            ->expandSelectedLabel()
                            ->options($iconExpandOptions),
                        SegmentControl::make('segment_control__icon_expand_default')
                            ->label('Icon expand · default')
                            ->expandSelectedLabel()
                            ->options($iconExpandOptions),
                        SegmentControl::make('segment_control__reference')
                            ->label('Reference · Monthly/Yearly')
                            ->separators(false)
                            ->options([
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                            ]),
                        SegmentControl::make('segment_control__ghost_reference')
                            ->label('Ghost · ranges')
                            ->variant('ghost')
                            ->separators(false)
                            ->options([
                                '1w' => '1W',
                                '4w' => '4W',
                                '1y' => '1Y',
                                'mtd' => 'MTD',
                                'qtd' => 'QTD',
                                'ytd' => 'YTD',
                                'all' => 'ALL',
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

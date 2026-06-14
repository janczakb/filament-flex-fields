<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressBar;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class ProgressBarPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'progress_bar__animated_value' => 35,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Progress bar')
                ->description('Linear progress with determinate, indeterminate, semantic colors, sizes, segmented delivery tracker, and optional track markers.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Section::make('Animated fill')
                        ->description('Drag the slider — bound progress bars animate their fill with CSS transitions.')
                        ->compact()
                        ->schema([
                            FlexSlider::make('progress_bar__animated_value')
                                ->label('Progress value')
                                ->range(0, 100)
                                ->step(1)
                                ->fillTrack()
                                ->showValue()
                                ->live(),
                            Grid::make(['default' => 1, 'lg' => 3])
                                ->extraAttributes(['class' => 'fff-playground-variants'])
                                ->schema([
                                    ProgressBar::make()
                                        ->label('Animated — fast (#6366f1)')
                                        ->value(fn (Get $get): float => (float) ($get('progress_bar__animated_value') ?? 35))
                                        ->showValue(true)
                                        ->color('#6366f1')
                                        ->animated()
                                        ->animationDuration(240)
                                        ->size('md'),
                                    ProgressBar::make()
                                        ->label('Animated — gradient (480ms)')
                                        ->value(fn (Get $get): float => (float) ($get('progress_bar__animated_value') ?? 35))
                                        ->showValue(true)
                                        ->gradientFrom('#ec4899')
                                        ->gradientTo('#f59e0b')
                                        ->color('#8b5cf6')
                                        ->animated()
                                        ->animationDuration(480)
                                        ->size('md'),
                                    ProgressBar::make()
                                        ->label('Animated — slow (#22c55e)')
                                        ->value(fn (Get $get): float => (float) ($get('progress_bar__animated_value') ?? 35))
                                        ->showValue(true)
                                        ->color('#22c55e')
                                        ->animated()
                                        ->animationDuration(900)
                                        ->size('lg'),
                                ]),
                        ]),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            ProgressBar::make()
                                ->label('Upload')
                                ->value(60)
                                ->max(100)
                                ->showValue(true)
                                ->size('md')
                                ->color('primary'),
                            ProgressBar::make()
                                ->label('Syncing data')
                                ->indeterminate()
                                ->size('md')
                                ->color('primary'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            ProgressBar::make()
                                ->label('Small')
                                ->value(42)
                                ->size('sm')
                                ->color('success'),
                            ProgressBar::make()
                                ->label('Medium')
                                ->value(68)
                                ->size('md')
                                ->color('warning'),
                            ProgressBar::make()
                                ->label('Large')
                                ->value(84)
                                ->size('lg')
                                ->color('danger'),
                        ]),
                    Grid::make(['default' => 1])
                        ->extraAttributes(['class' => 'fff-playground-variants fff-playground-variants--stack fff-playground-progress-sizes'])
                        ->schema([
                            ProgressBar::make()
                                ->segments([
                                    ['label' => 'Cooking Order', 'description' => 'Completed'],
                                    ['label' => 'Preparing Order', 'description' => '5 Minutes'],
                                    ['label' => 'Delivery', 'description' => 'Waiting for rider'],
                                ])
                                ->activeSegment(1)
                                ->activeSegmentProgress(0.55)
                                ->color('success')
                                ->size('sm'),
                            ProgressBar::make()
                                ->segments([
                                    ['label' => 'Cooking Order', 'description' => 'Completed'],
                                    ['label' => 'Preparing Order', 'description' => '5 Minutes'],
                                    ['label' => 'Delivery', 'description' => 'Waiting for rider'],
                                ])
                                ->activeSegment(1)
                                ->activeSegmentProgress(0.62)
                                ->color('success')
                                ->size('md'),
                            ProgressBar::make()
                                ->segments([
                                    ['label' => 'Cooking Order', 'description' => 'Completed'],
                                    ['label' => 'Preparing Order', 'description' => '5 Minutes'],
                                    ['label' => 'Delivery', 'description' => 'Waiting for rider'],
                                ])
                                ->activeSegment(1)
                                ->activeSegmentProgress(0.7)
                                ->color('success')
                                ->size('lg'),
                        ]),
                    ProgressBar::make()
                        ->label('Delivery route')
                        ->value(38)
                        ->showValue(true)
                        ->startMarker(GravityIcon::MapPin)
                        ->currentMarker(GravityIcon::Car)
                        ->endMarker(GravityIcon::House)
                        ->remainingTrackStyle('dashed')
                        ->color('success')
                        ->size('md'),
                    ProgressBar::make()
                        ->variant('pills')
                        ->value(23)
                        ->showValue(false)
                        ->gradientFrom('rgb(239 68 68)')
                        ->gradientTo('rgb(245 158 11)')
                        ->color('danger')
                        ->size('md'),
                    ProgressBar::make()
                        ->label('Static fill (no transition)')
                        ->value(72)
                        ->showValue(true)
                        ->color('#8b5cf6')
                        ->animated(false)
                        ->size('md'),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            ProgressBar::make()
                                ->label('Route progress')
                                ->value(45)
                                ->startMarker(GravityIcon::MapPin)
                                ->currentMarker(GravityIcon::Car)
                                ->endMarker(GravityIcon::House)
                                ->remainingTrackStyle('dashed')
                                ->color('primary')
                                ->size('lg'),
                            ProgressBar::make()
                                ->label('Five steps')
                                ->segments([
                                    ['label' => 'Placed', 'description' => 'Done'],
                                    ['label' => 'Confirmed', 'description' => 'Done'],
                                    ['label' => 'Packed', 'description' => 'In progress'],
                                    ['label' => 'Shipped', 'description' => 'Soon'],
                                    ['label' => 'Delivered', 'description' => 'Pending'],
                                ])
                                ->activeSegment(2)
                                ->activeSegmentProgress(0.45)
                                ->color('success')
                                ->size('md'),
                        ]),
                ]),
        ];
    }
}

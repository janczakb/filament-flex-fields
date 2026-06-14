<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\ProgressCircle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class ProgressCirclePlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'progress_circle__animated_value' => 35,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Progress circle')
                ->description('SVG circular progress. Gray track by default; gradientFrom/gradientTo colors only the progress fill. Optional trackGradientFrom/To for a gradient track.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Section::make('Animated fill')
                        ->description('Drag the slider — bound progress circles animate their fill with CSS transitions.')
                        ->compact()
                        ->schema([
                            FlexSlider::make('progress_circle__animated_value')
                                ->label('Progress value')
                                ->range(0, 100)
                                ->step(1)
                                ->fillTrack()
                                ->showValue()
                                ->live(),
                            Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                                ->extraAttributes(['class' => 'fff-playground-variants'])
                                ->schema([
                                    ProgressCircle::make()
                                        ->label('Animated — fast (#6366f1)')
                                        ->value(fn (Get $get): float => (float) ($get('progress_circle__animated_value') ?? 35))
                                        ->displayValue(fn (Get $get): string => ($get('progress_circle__animated_value') ?? 35).'%')
                                        ->max(100)
                                        ->gapAngle(36)
                                        ->color('#6366f1')
                                        ->animated()
                                        ->animationDuration(240)
                                        ->size('md'),
                                    ProgressCircle::make()
                                        ->label('Animated — gradient (520ms)')
                                        ->value(fn (Get $get): float => (float) ($get('progress_circle__animated_value') ?? 35))
                                        ->displayValue(fn (Get $get): string => ($get('progress_circle__animated_value') ?? 35).'%')
                                        ->max(100)
                                        ->gapAngle(36)
                                        ->gradientFrom('#ec4899')
                                        ->gradientTo('#f59e0b')
                                        ->color('#8b5cf6')
                                        ->animated()
                                        ->animationDuration(520)
                                        ->size('md'),
                                    ProgressCircle::make()
                                        ->label('Animated — semicircle (900ms)')
                                        ->value(fn (Get $get): float => (float) ($get('progress_circle__animated_value') ?? 35))
                                        ->displayValue(fn (Get $get): string => ($get('progress_circle__animated_value') ?? 35).'%')
                                        ->max(100)
                                        ->variant('semicircle')
                                        ->gapAngle(28)
                                        ->color('#22c55e')
                                        ->animated()
                                        ->animationDuration(900)
                                        ->size('md'),
                                ]),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'xl' => 4])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            ProgressCircle::make()
                                ->value(69)
                                ->max(100)
                                ->displayValue('69%')
                                ->gradientFrom('rgb(99 102 241)')
                                ->gradientTo('rgb(236 72 153)')
                                ->size('md')
                                ->color('primary'),
                            ProgressCircle::make()
                                ->value(124)
                                ->max(223)
                                ->fraction('124 / 223')
                                ->label('Grade rating')
                                ->size('md')
                                ->color('success'),
                            ProgressCircle::make()
                                ->value(72)
                                ->max(100)
                                ->displayValue('72%')
                                ->variant('semicircle')
                                ->gapAngle(24)
                                ->size('md')
                                ->color('warning'),
                            ProgressCircle::make()
                                ->value(55)
                                ->max(100)
                                ->displayValue('55%')
                                ->label('Static fill (no transition)')
                                ->gapAngle(36)
                                ->gradientFrom('#ec4899')
                                ->gradientTo('#f59e0b')
                                ->color('#6366f1')
                                ->animated(false)
                                ->size('md'),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            ProgressCircle::make()
                                ->value(55)
                                ->displayValue('55%')
                                ->gapAngle(36)
                                ->size('sm')
                                ->color('warning'),
                            ProgressCircle::make()
                                ->value(55)
                                ->displayValue('55%')
                                ->gapAngle(36)
                                ->size('md')
                                ->color('warning'),
                            ProgressCircle::make()
                                ->value(55)
                                ->displayValue('55%')
                                ->gapAngle(36)
                                ->size('lg')
                                ->color('warning'),
                        ]),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            ProgressCircle::make()
                                ->value(82)
                                ->max(100)
                                ->displayValue('82%')
                                ->label('Storage used')
                                ->gapAngle(40)
                                ->gradientFrom('#fde047')
                                ->gradientTo('#eab308')
                                ->size('lg')
                                ->color('warning'),
                            ProgressCircle::make()
                                ->value(63)
                                ->max(100)
                                ->displayValue('63%')
                                ->label('Uploading file')
                                ->variant('semicircle')
                                ->gapAngle(32)
                                ->gradientFrom('#fde047')
                                ->gradientTo('#eab308')
                                ->size('lg')
                                ->color('warning'),
                        ]),
                    Section::make('Card layouts')
                        ->description('Elevated shell cards with heading, description, and footer chrome.')
                        ->extraAttributes(['class' => 'fff-playground-section'])
                        ->schema([
                            Grid::make(['default' => 1, 'lg' => 2])
                                ->extraAttributes(['class' => 'fff-playground-variants'])
                                ->schema([
                                    ProgressCircle::make()
                                        ->value(34)
                                        ->max(100)
                                        ->displayValue('34%')
                                        ->heading('Bounce rate')
                                        ->description('Visitors who leave after viewing only one page.')
                                        ->footer('Last 30 days')
                                        ->shell()
                                        ->size('md')
                                        ->color('danger'),
                                    ProgressCircle::make()
                                        ->value(78)
                                        ->max(100)
                                        ->displayValue('78%')
                                        ->fraction('78 / 100')
                                        ->label('Completion rate')
                                        ->contentLayout('left')
                                        ->shell()
                                        ->size('md')
                                        ->color('success'),
                                ]),
                            Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                                ->extraAttributes(['class' => 'fff-playground-variants'])
                                ->schema([
                                    ProgressCircle::make()
                                        ->value(42)
                                        ->displayValue('42%')
                                        ->variant('semicircle')
                                        ->gapAngle(28)
                                        ->shell()
                                        ->size('sm')
                                        ->color('primary'),
                                    ProgressCircle::make()
                                        ->value(58)
                                        ->displayValue('58%')
                                        ->variant('semicircle')
                                        ->gapAngle(28)
                                        ->shell()
                                        ->size('md')
                                        ->color('warning'),
                                    ProgressCircle::make()
                                        ->value(71)
                                        ->displayValue('71%')
                                        ->variant('semicircle')
                                        ->gapAngle(28)
                                        ->shell()
                                        ->size('lg')
                                        ->color('success'),
                                    ProgressCircle::make()
                                        ->value(88)
                                        ->displayValue('88%')
                                        ->gapAngle(36)
                                        ->shell()
                                        ->size('lg')
                                        ->color('primary'),
                                ]),
                        ]),
                ]),
        ];
    }
}

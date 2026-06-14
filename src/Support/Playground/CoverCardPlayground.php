<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Actions\Action;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\CoverCard;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CoverCardPlayground
{
    private const ROBOT_IMAGE = 'https://images.unsplash.com/photo-1485827404703-89b9c7b0b5e5?auto=format&fit=crop&w=900&q=80';

    private const YACHT_BANNER_IMAGE = 'https://images.unsplash.com/photo-1567899378495-47b050a8d528?auto=format&fit=crop&w=1600&h=400&q=80';

    private const PEXELS_COVER_IMAGE = 'https://images.pexels.com/photos/33866367/pexels-photo-33866367.jpeg';

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Cover card')
                ->description('SaaS-style media card with configurable background, copy blocks, footer action and aspect ratio. Use fullWidth() with a wide ratio (e.g. 21:9) for low-height banners.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(1)
                        ->extraAttributes(['class' => 'fff-playground-variants fff-playground-variants--banners'])
                        ->schema([
                            CoverCard::make()
                                ->backgroundImage(self::PEXELS_COVER_IMAGE)
                                ->backgroundColor('#0f172a')
                                ->backgroundPosition('center')
                                ->ratio('21:9')
                                ->tone('light')
                                ->fullWidth()
                                ->contentOverlays()
                                ->topTitle('Charter season 2026')
                                ->topDescription('Mediterranean & Caribbean')
                                ->footerTitle('Early booking')
                                ->footerDescription('Save 15% before March')
                                ->footerAction(
                                    Action::make('charterBanner')
                                        ->label('Explore fleet')
                                        ->action(fn () => Notification::make()
                                            ->title('Banner CTA clicked')
                                            ->success()
                                            ->send()),
                                ),
                            CoverCard::make()
                                ->backgroundGradient('linear-gradient(90deg, rgb(15 23 42) 0%, rgb(30 58 138) 45%, rgb(14 116 144) 100%)')
                                ->ratio('3:1')
                                ->tone('light')
                                ->fullWidth()
                                ->footerTitle('Launch week')
                                ->footerDescription('Limited offer — ends Friday')
                                ->footerAction(
                                    Action::make('launchBanner')
                                        ->label('Learn more')
                                        ->action(fn () => Notification::make()
                                            ->title('Learn more opened')
                                            ->success()
                                            ->send()),
                                ),
                        ]),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            CoverCard::make()
                                ->backgroundImage(self::ROBOT_IMAGE)
                                ->backgroundColor('#e4e4e7')
                                ->ratio('3:4')
                                ->topTitle('NEO')
                                ->topDescription('Home Robot')
                                ->footerTitle('Available soon')
                                ->footerDescription('Get notified')
                                ->footerAction(
                                    Action::make('notifyPortrait')
                                        ->label('Notify me')
                                        ->action(fn () => Notification::make()
                                            ->title('Notification requested')
                                            ->success()
                                            ->send()),
                                ),
                            CoverCard::make()
                                ->backgroundImage(self::ROBOT_IMAGE)
                                ->backgroundColor('#d4d4d8')
                                ->ratio('4:3')
                                ->contentOverlays()
                                ->footerTitle('NEO')
                                ->footerDescription('$499/m')
                                ->footerAction(
                                    Action::make('getLandscape')
                                        ->label('Get now')
                                        ->action(fn () => Notification::make()
                                            ->title('Get now clicked')
                                            ->success()
                                            ->send()),
                                ),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            CoverCard::make()
                                ->backgroundGradient('linear-gradient(135deg, rgb(76 29 149) 0%, rgb(190 24 93) 52%, rgb(251 146 60) 100%)')
                                ->ratio('1:1')
                                ->tone('light')
                                ->topTitle('Sunset')
                                ->topDescription('Gradient background')
                                ->footerTitle('Premium')
                                ->footerDescription('From $19/mo')
                                ->footerAction(
                                    Action::make('upgradeSquare')
                                        ->label('Upgrade')
                                        ->action(fn () => Notification::make()
                                            ->title('Upgrade selected')
                                            ->success()
                                            ->send()),
                                ),
                            CoverCard::make()
                                ->backgroundColor('#f4f4f5')
                                ->ratio('9:16')
                                ->contentMaxWidth('16rem')
                                ->footerTitle('Tall card')
                                ->footerDescription('Vertical ratio')
                                ->footerAction(
                                    Action::make('tallAction')
                                        ->label('Open')
                                        ->action(fn () => Notification::make()
                                            ->title('Tall card opened')
                                            ->success()
                                            ->send()),
                                ),
                        ]),
                ]),
        ];
    }
}

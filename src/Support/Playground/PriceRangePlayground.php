<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PriceRangeField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class PriceRangePlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'price_range__basic' => ['min' => 100, 'max' => 1124],
            'price_range__secondary' => ['min' => 250, 'max' => 1750],
            'price_range__flat' => ['min' => 180, 'max' => 1400],
            'price_range__no_prefix' => ['min' => 150, 'max' => 900],
            'price_range__no_inputs' => ['min' => 120, 'max' => 980],
            'price_range__sm' => ['min' => 50, 'max' => 500],
            'price_range__lg' => ['min' => 1000, 'max' => 4500],
            'price_range__disabled' => ['min' => 200, 'max' => 800],
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $histogram = [
            30, 74, 85, 36, 98, 86, 30, 30, 55, 55, 40, 80, 95, 96, 63, 64, 68, 30, 47, 54,
            76, 30, 30, 30, 83, 30, 50, 45, 93, 56, 95, 30,
        ];

        return [
            Section::make('Price Range')
                ->description('HeroUI-style histogram range with animated bars, dual thumbs and min/max inputs.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    PriceRangeField::make('price_range__basic')
                        ->label('Price range')
                        ->helperText('Drag the handles or edit the inputs. Bars animate when the range changes.')
                        ->min(0)
                        ->max(2000)
                        ->step(1)
                        ->prefix('$')
                        ->histogram($histogram)
                        ->default(['min' => 100, 'max' => 1124])
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            PriceRangeField::make('price_range__secondary')
                                ->label('Secondary inputs')
                                ->variant('secondary')
                                ->min(0)
                                ->max(3000)
                                ->prefix('$')
                                ->histogram($histogram),
                            PriceRangeField::make('price_range__flat')
                                ->label('Flat variant')
                                ->variant('flat')
                                ->min(0)
                                ->max(3000)
                                ->prefix('$')
                                ->histogram($histogram),
                            PriceRangeField::make('price_range__no_prefix')
                                ->label('No prefix')
                                ->min(0)
                                ->max(2000)
                                ->withoutPrefix()
                                ->histogram($histogram),
                            PriceRangeField::make('price_range__no_inputs')
                                ->label('Slider only')
                                ->min(0)
                                ->max(1500)
                                ->prefix('€')
                                ->showInputs(false)
                                ->histogram($histogram),
                        ]),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            PriceRangeField::make('price_range__sm')
                                ->label('Small')
                                ->size('sm')
                                ->min(0)
                                ->max(1000)
                                ->prefix('$')
                                ->histogram($histogram),
                            PriceRangeField::make('price_range__lg')
                                ->label('Large')
                                ->size('lg')
                                ->min(0)
                                ->max(5000)
                                ->prefix('$')
                                ->histogram($histogram),
                            PriceRangeField::make('price_range__disabled')
                                ->label('Disabled')
                                ->disabled()
                                ->min(0)
                                ->max(1000)
                                ->prefix('$')
                                ->histogram($histogram),
                        ]),
                ]),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CalculatorField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class CalculatorFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'calculator__weight' => 1250.5,
            'calculator__quantity' => 48,
            'calculator__margin' => null,
            'calculator__sm' => 10,
            'calculator__soft' => 99.99,
            'calculator__disabled' => 500,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Calculator field')
                ->description('Numeric input with a shared floating calculator panel. One panel serves every CalculatorField on the page and remembers expressions per field.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    CalculatorField::make('calculator__weight')
                        ->label('Cargo weight (kg)')
                        ->decimalPlaces(2)
                        ->maxLength(8)
                        ->step(0.01)
                        ->minValue(0)
                        ->maxValue(99999)
                        ->helperText('Open the calculator icon — switch to another field below without closing the panel.')
                        ->columnSpanFull(),
                    CalculatorField::make('calculator__quantity')
                        ->label('Container count')
                        ->integer()
                        ->minValue(0)
                        ->maxValue(9999)
                        ->columnSpanFull(),
                    CalculatorField::make('calculator__margin')
                        ->label('Margin (%)')
                        ->decimalPlaces(2)
                        ->minValue(0)
                        ->maxValue(100)
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            CalculatorField::make('calculator__sm')
                                ->label('Small')
                                ->size('sm')
                                ->integer(),
                            CalculatorField::make('calculator__soft')
                                ->label('Soft variant')
                                ->variant('soft')
                                ->decimalPlaces(2),
                            CalculatorField::make('calculator__disabled')
                                ->label('Disabled')
                                ->disabled(),
                        ]),
                ]),
        ];
    }
}

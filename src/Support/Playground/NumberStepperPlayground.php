<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class NumberStepperPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'number_stepper__sm' => 1,
            'number_stepper__md' => 1,
            'number_stepper__lg' => 1,
            'number_stepper__disabled' => 3,
            'number_stepper__custom_icons' => 100,
            'number_stepper__currency' => 10,
            'number_stepper__percentage' => 50,
            'number_stepper__min_max_pos' => 3,
            'number_stepper__min_max_neg' => 0,
            'number_stepper__step_5' => 10,
            'number_stepper__step_10' => 20,
            'number_stepper__suffix_items' => 3,
            'number_stepper__reversed' => 1,
            'number_stepper__primary' => 1,
            'number_stepper__secondary' => 1,
            'number_stepper__tertiary' => 1,
            'number_stepper__outline' => 1,
            'number_stepper__guests' => 1,
            'number_stepper__adults' => 1,
            'number_stepper__children' => 3,
            'number_stepper__infants' => 0,
            'number_stepper__pets' => 0,
            'number_stepper__nullable' => null,
            'number_stepper__min_edge' => 0,
            'number_stepper__digit_overflow' => 99,
        ];
    }

    public function section(): Section
    {
        return Section::make('Number Stepper')
            ->description('SaaS-style sizes, formats, custom icons, variants and rolling digit animation.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3, 'xl' => 3])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        NumberStepper::make('number_stepper__sm')
                            ->label('Sizes · SM')
                            ->size('sm')
                            ->minValue(0)
                            ->maxValue(10),
                        NumberStepper::make('number_stepper__md')
                            ->label('Sizes · MD')
                            ->minValue(0)
                            ->maxValue(10),
                        NumberStepper::make('number_stepper__lg')
                            ->label('Sizes · LG')
                            ->size('lg')
                            ->minValue(0)
                            ->maxValue(10),
                    ]),
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3, 'xl' => 4])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        NumberStepper::make('number_stepper__custom_icons')
                            ->label('Custom icons')
                            ->icons([
                                'decrement' => GravityIcon::MagnifierMinus,
                                'increment' => GravityIcon::MagnifierPlus,
                            ])
                            ->minValue(0)
                            ->maxValue(200),
                        NumberStepper::make('number_stepper__currency')
                            ->label('Currency (USD)')
                            ->prefix('$')
                            ->decimalPlaces(2)
                            ->integer(false)
                            ->step(0.5)
                            ->minValue(0)
                            ->maxValue(999),
                        NumberStepper::make('number_stepper__percentage')
                            ->label('Percentage')
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100),
                        NumberStepper::make('number_stepper__disabled')
                            ->label('Disabled')
                            ->disabled()
                            ->minValue(0)
                            ->maxValue(10),
                    ]),
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3, 'xl' => 4])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        NumberStepper::make('number_stepper__min_max_pos')
                            ->label('Min: 0, Max: 5')
                            ->minValue(0)
                            ->maxValue(5),
                        NumberStepper::make('number_stepper__min_max_neg')
                            ->label('Min: -10, Max: 10')
                            ->minValue(-10)
                            ->maxValue(10),
                        NumberStepper::make('number_stepper__step_5')
                            ->label('Step: 5')
                            ->step(5)
                            ->minValue(0)
                            ->maxValue(50),
                        NumberStepper::make('number_stepper__step_10')
                            ->label('Step: 10')
                            ->step(10)
                            ->minValue(0)
                            ->maxValue(100),
                        NumberStepper::make('number_stepper__digit_overflow')
                            ->label('2→3 digits')
                            ->helperText('Starts at 99 — click + to see the box expand at 100')
                            ->minValue(0)
                            ->maxValue(150),
                    ]),
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3, 'xl' => 4])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        NumberStepper::make('number_stepper__suffix_items')
                            ->label('With suffix')
                            ->suffix('items')
                            ->minValue(0)
                            ->maxValue(99),
                        NumberStepper::make('number_stepper__reversed')
                            ->label('Reversed layout')
                            ->reversed()
                            ->minValue(0)
                            ->maxValue(10),
                        NumberStepper::make('number_stepper__nullable')
                            ->label('Nullable')
                            ->nullable()
                            ->nullLabel('No limit')
                            ->minValue(0)
                            ->maxValue(10),
                        NumberStepper::make('number_stepper__min_edge')
                            ->label('At minimum')
                            ->minValue(0)
                            ->maxValue(10)
                            ->helperText('Decrement disabled at 0'),
                    ]),
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4, 'xl' => 4])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        NumberStepper::make('number_stepper__primary')
                            ->label('primary')
                            ->variant('primary')
                            ->minValue(0)
                            ->maxValue(10),
                        NumberStepper::make('number_stepper__secondary')
                            ->label('secondary')
                            ->variant('secondary')
                            ->minValue(0)
                            ->maxValue(10),
                        NumberStepper::make('number_stepper__tertiary')
                            ->label('tertiary')
                            ->variant('tertiary')
                            ->minValue(0)
                            ->maxValue(10),
                        NumberStepper::make('number_stepper__outline')
                            ->label('outline')
                            ->variant('outline')
                            ->minValue(0)
                            ->maxValue(10),
                    ]),
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 2, 'xl' => 2])
                    ->extraAttributes(['class' => 'fff-playground-variants fff-playground-variants--stack'])
                    ->schema([
                        NumberStepper::make('number_stepper__guests')
                            ->label('Guests')
                            ->helperText('Maximum 10 guests per reservation')
                            ->minValue(1)
                            ->maxValue(10),
                        NumberStepper::make('number_stepper__adults')
                            ->label('Adults')
                            ->helperText('Ages 13 or above')
                            ->minValue(0)
                            ->maxValue(20),
                        NumberStepper::make('number_stepper__children')
                            ->label('Children')
                            ->helperText('Ages 2–12')
                            ->minValue(0)
                            ->maxValue(20),
                        NumberStepper::make('number_stepper__infants')
                            ->label('Infants')
                            ->helperText('Under 2')
                            ->minValue(0)
                            ->maxValue(10),
                        NumberStepper::make('number_stepper__pets')
                            ->label('Pets')
                            ->helperText('Bringing a service animal?')
                            ->minValue(0)
                            ->maxValue(5),
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

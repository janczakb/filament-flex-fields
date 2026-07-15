<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexMatrixTable;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;

class FlexMatrixTablePlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'flex_matrix_table__basic' => [
                'engine_power' => ['priority' => 'high', 'story_points' => 450, 'enabled' => true],
                'top_speed' => ['priority' => 'high', 'story_points' => 320, 'enabled' => true],
                'acceleration' => ['priority' => 'medium', 'story_points' => 3.8, 'enabled' => true],
                'fuel_consumption' => ['priority' => 'low', 'story_points' => 14.5, 'enabled' => false],
                'weight' => ['priority' => 'medium', 'story_points' => 1850, 'enabled' => true],
                'boot_capacity' => ['priority' => 'low', 'story_points' => 480, 'enabled' => true],
            ],
            'flex_matrix_table__specs' => [
                'length' => ['value' => '24.5'],
                'beam' => ['value' => '6.2'],
                'draft' => ['value' => '1.8'],
                'fuel_capacity' => ['value' => '8500'],
                'water_capacity' => ['value' => '2400'],
                'max_speed' => ['value' => '32'],
            ],
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            $this->section(),
        ];
    }

    public function section(): Section
    {
        return Section::make('Flex Matrix Table')
            ->description('A matrix table that allows arbitrary Filament schema fields as columns.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                FlexMatrixTable::make('flex_matrix_table__basic')
                    ->label('Car Configuration')
                    ->rows([
                        'engine_power' => ['label' => 'Engine Power', 'suffix' => 'HP'],
                        'top_speed' => ['label' => 'Top Speed', 'suffix' => 'km/h'],
                        'acceleration' => ['label' => '0-100 km/h', 'suffix' => 's'],
                        'fuel_consumption' => ['label' => 'Fuel Consumption', 'suffix' => 'l/100km'],
                        'weight' => ['label' => 'Weight', 'suffix' => 'kg'],
                        'boot_capacity' => ['label' => 'Boot Capacity', 'suffix' => 'L'],
                    ])
                    ->columnWidths([
                        'priority' => '1.5fr',
                        'story_points' => '1fr',
                        'enabled' => 'max-content',
                    ])
                    ->schema([
                        SelectField::make('priority')
                            ->label('Importance')
                            ->options([
                                'high' => 'High',
                                'medium' => 'Medium',
                                'low' => 'Low',
                            ])
                            ->size('sm')
                            ->inlineFieldLabel(false)
                            ->hiddenLabel(),

                        FlexTextInput::make('story_points')
                            ->label('Value')
                            ->numeric()
                            ->size('sm')
                            ->hiddenLabel()
                            ->suffix(function (Component $component) {
                                $statePathParts = explode('.', $component->getStatePath());
                                $rowKey = $statePathParts[count($statePathParts) - 2] ?? null;

                                $matrix = $component->getContainer()->getParentComponent();
                                $rows = $matrix->getNormalizedRows();

                                return $rows[$rowKey]['suffix'] ?? null;
                            }),

                        SwitchField::make('enabled')
                            ->label('Enabled')
                            ->size('sm')
                            ->inline()
                            ->hiddenLabel(),
                    ]),

                FlexMatrixTable::make('flex_matrix_table__specs')
                    ->label('Yacht Specifications')
                    ->rows([
                        'length' => ['label' => 'Overall Length', 'suffix' => 'm.'],
                        'beam' => ['label' => 'Beam', 'suffix' => 'm.'],
                        'draft' => ['label' => 'Draft', 'suffix' => 'm.'],
                        'fuel_capacity' => ['label' => 'Fuel Capacity', 'suffix' => 'l.'],
                        'water_capacity' => ['label' => 'Water Capacity', 'suffix' => 'l.'],
                        'max_speed' => ['label' => 'Max Speed', 'suffix' => 'kn.'],
                    ])
                    ->schema([
                        FlexTextInput::make('value')
                            ->label('Value')
                            ->numeric()
                            ->size('sm')
                            ->hiddenLabel()
                            ->suffix(function (Component $component) {
                                $statePathParts = explode('.', $component->getStatePath());
                                $rowKey = $statePathParts[count($statePathParts) - 2] ?? null;

                                $matrix = $component->getContainer()->getParentComponent();
                                $rows = $matrix->getNormalizedRows();

                                return $rows[$rowKey]['suffix'] ?? null;
                            }),
                    ]),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MatrixChoiceField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;

class MatrixChoiceFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'matrix_choice__mood' => [
                'saturday' => 'happy',
                'sunday' => 'neutral',
                'monday' => 'sad',
            ],
            'matrix_choice__features' => [
                'dark_mode' => ['high'],
                'csv_export' => ['medium'],
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
        return Section::make('Matrix Choice')
            ->description('Multiple choice grid with per-row validation, radio or checkbox mode, and inset frame layout.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                MatrixChoiceField::make('matrix_choice__mood')
                    ->label('Tell us about your mood')
                    ->helperText('One selection per day — radio mode')
                    ->mode('radio')
                    ->rows([
                        'saturday' => ['label' => 'Saturday', 'required' => true],
                        'sunday' => ['label' => 'Sunday', 'required' => true],
                        'monday' => 'Monday',
                    ])
                    ->matrixColumns([
                        'happy' => 'Happy',
                        'neutral' => 'Neutral',
                        'sad' => 'Sad',
                        'pleading' => 'Pleading',
                        'party' => 'Party',
                        'zany' => 'Zany',
                    ]),
                MatrixChoiceField::make('matrix_choice__features')
                    ->label('Feature priorities')
                    ->helperText('Checkbox mode — when Dark mode is High, CSV export High is disabled reactively')
                    ->mode('checkbox')
                    ->rows([
                        'dark_mode' => [
                            'label' => 'Dark mode',
                            'required' => true,
                            'max_selections' => 1,
                        ],
                        'csv_export' => [
                            'label' => 'CSV export',
                            'min_selections' => 1,
                            'max_selections' => 2,
                        ],
                        'api_access' => [
                            'label' => 'API access',
                            'disabled' => true,
                        ],
                    ])
                    ->matrixColumns([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ])
                    ->disableCellWhen('csv_export', 'high', 'dark_mode', 'high')
                    ->color('primary'),
            ]);
    }
}

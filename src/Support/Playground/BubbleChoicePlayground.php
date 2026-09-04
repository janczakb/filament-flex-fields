<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BubbleChoiceField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class BubbleChoicePlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'bubble_choice__habits' => ['journal', 'workout', 'on_track'],
            'bubble_choice__min_max' => ['read'],
            'bubble_choice__images' => ['forest'],
            'bubble_choice__icons' => ['calm'],
            'bubble_choice__many' => ['a1', 'a8'],
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $habitOptions = [
            'water' => ['label' => 'Water', 'description' => 'H2O'],
            'yoga' => ['label' => 'Yoga', 'description' => 'Flow'],
            'journal' => ['label' => 'Journal', 'description' => 'Notes'],
            'workout' => ['label' => 'Workout', 'description' => 'Gym'],
            'read' => ['label' => 'Read', 'description' => 'Books'],
            'meditate' => ['label' => 'Meditate', 'description' => 'Calm'],
            'cycle' => ['label' => 'Cycle', 'description' => 'Ride'],
            'sleep' => ['label' => 'Sleep', 'description' => 'Rest'],
            'study' => ['label' => 'Study', 'description' => 'Learn'],
            'save' => ['label' => 'Save', 'description' => 'Money'],
            'on_track' => ['label' => 'On track', 'description' => 'Goals'],
            'cook' => ['label' => 'Cook', 'description' => 'Food'],
            'walk' => ['label' => 'Walk', 'description' => 'Steps'],
            'hydrate' => ['label' => 'Hydrate', 'description' => 'Drink'],
            'focus' => ['label' => 'Focus', 'description' => 'Deep'],
            'breathe' => ['label' => 'Breathe', 'description' => 'Air'],
            'stretch' => ['label' => 'Stretch', 'description' => 'Body'],
            'plan' => ['label' => 'Plan', 'description' => 'Day'],
            'call' => ['label' => 'Call', 'description' => 'Talk'],
            'clean' => ['label' => 'Clean', 'description' => 'Home'],
        ];

        $manyOptions = [];

        for ($i = 1; $i <= 40; $i++) {
            $manyOptions['a'.$i] = [
                'label' => 'Option '.$i,
                'description' => 'Item '.$i,
            ];
        }

        $photoSeeds = [
            '1441974231531-c6227db76b6e',
            '1507525428034-b723cf961d3e',
            '1509316785289-025f5b846b35',
            '1449824913935-59a10b8d2000',
            '1469474968028-56623f02e42e',
            '1470071459604-3b5ec3a7fe05',
            '1501785888041-af3fc6d9d579',
            '1472214103451-9374bd1c798e',
            '1519681393784-d120267933ba',
            '1500530855697-b586d89ba3ee',
            '1464822759023-fed622ff2c3b',
            '1506905925346-21bda4d32df4',
            '1518837695005-2083093ee35b',
            '1475924156734-496f6cac6ec1',
            '1493246507139-91e8fad9978e',
            '1505142468610-359e7d316be0',
            '1439066615861-d1af74d74000',
            '1470770841072-f978cf4d019e',
            '1500534314209-a25ddb2bd429',
            '1469474968028-56623f02e42e',
        ];

        $imageBgLabels = [
            'Forest', 'Ocean', 'Desert', 'City', 'Meadow', 'Peak', 'Lake', 'Valley',
            'Summit', 'Canyon', 'Ridge', 'Alpine', 'Coast', 'Grove', 'Horizon',
            'River', 'Shore', 'Cliff', 'Dunes', 'Woods',
        ];

        $imageBgOptions = [];

        foreach ($imageBgLabels as $index => $label) {
            $key = strtolower(str_replace(' ', '_', $label));
            $imageBgOptions[$key] = [
                'label' => $label,
                'description' => strtoupper(substr($label, 0, 3)),
                'image' => 'https://images.unsplash.com/photo-'.$photoSeeds[$index].'?auto=format&fit=crop&w=240&q=80',
                'imageMode' => 'background',
            ];
        }

        $iconMeta = [
            ['calm', 'Calm', 'CLM', '#1e3a8a', '#93c5fd'],
            ['focus', 'Focus', 'FCS', '#4c1d95', '#c4b5fd'],
            ['energy', 'Energy', 'NRG', '#115e59', '#5eead4'],
            ['rest', 'Rest', 'RST', '#334155', '#cbd5e1'],
            ['spark', 'Spark', 'SPK', '#9a3412', '#fdba74'],
            ['flow', 'Flow', 'FLW', '#1d4ed8', '#93c5fd'],
            ['bloom', 'Bloom', 'BLM', '#9d174d', '#f9a8d4'],
            ['pulse', 'Pulse', 'PLS', '#b91c1c', '#fca5a5'],
            ['drift', 'Drift', 'DFT', '#0e7490', '#67e8f9'],
            ['glow', 'Glow', 'GLW', '#a16207', '#fde68a'],
            ['rise', 'Rise', 'RSE', '#166534', '#86efac'],
            ['still', 'Still', 'STL', '#1e293b', '#94a3b8'],
            ['wave', 'Wave', 'WAV', '#0369a1', '#7dd3fc'],
            ['root', 'Root', 'ROT', '#713f12', '#d6d3d1'],
            ['peak', 'Peak', 'PEK', '#312e81', '#a5b4fc'],
            ['haze', 'Haze', 'HAZ', '#57534e', '#d6d3d1'],
            ['tide', 'Tide', 'TID', '#155e75', '#a5f3fc'],
            ['ember', 'Ember', 'EMB', '#7c2d12', '#fdba74'],
            ['mist', 'Mist', 'MST', '#334155', '#e2e8f0'],
            ['orbit', 'Orbit', 'ORB', '#1e3a8a', '#bfdbfe'],
        ];

        $iconOptions = [];

        foreach ($iconMeta as $index => [$key, $label, $description, $color, $selectedColor]) {
            $iconOptions[$key] = [
                'label' => $label,
                'description' => $description,
                'color' => $color,
                'selectedColor' => $selectedColor,
                'image' => 'https://images.unsplash.com/photo-'.$photoSeeds[$index].'?auto=format&fit=crop&w=120&q=80',
                'imageMode' => 'icon',
            ];
        }

        $demoLayout = [
            'size' => 160,
            'minSize' => 25,
            'gutter' => 8,
            'provideProps' => true,
            'numCols' => 6,
            'fringeWidth' => 160,
            'yRadius' => 130,
            'xRadius' => 220,
            'cornerRadius' => 50,
            'compact' => true,
            'gravitation' => 5,
        ];

        return [
            Section::make('Bubble Choice')
                ->description('Pan the arena in every direction. Center bubbles stay large; fringe bubbles shrink and hide their content under 50px.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    BubbleChoiceField::make('bubble_choice__habits')
                        ->label('My habits today')
                        ->helperText('Scroll / trackpad pan in both axes. Selected shape morphs smoothly.')
                        ->options($habitOptions)
                        ->layoutOptions($demoLayout)
                        ->arenaHeight('400px')
                        ->arenaColor('#16260a')
                        ->bubbleColor('#8cb76c')
                        ->selectedBubbleColor('#c8f560')
                        ->default(['journal', 'workout', 'on_track'])
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            BubbleChoiceField::make('bubble_choice__min_max')
                                ->label('Pick 1–3 habits')
                                ->options($habitOptions)
                                ->layoutOptions($demoLayout)
                                ->arenaHeight('500px')
                                ->minItems(1)
                                ->maxItems(3)
                                ->default(['read']),
                            BubbleChoiceField::make('bubble_choice__images')
                                ->label('Image as background')
                                ->options($imageBgOptions)
                                ->layoutOptions($demoLayout)
                                ->arenaHeight('500px')
                                ->default(['forest']),
                            BubbleChoiceField::make('bubble_choice__icons')
                                ->label('Image as center icon')
                                ->options($iconOptions)
                                ->layoutOptions($demoLayout)
                                ->arenaHeight('500px')
                                ->default(['calm']),
                            BubbleChoiceField::make('bubble_choice__many')
                                ->label('Many options')
                                ->helperText('More rows = vertical pan.')
                                ->options($manyOptions)
                                ->layoutOptions($demoLayout)
                                ->arenaHeight('500px')
                                ->default(['a1', 'a8']),
                        ]),
                    PlaygroundCodeSnippet::make(<<<'PHP'
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BubbleChoiceField;

BubbleChoiceField::make('habits')
    ->label('My habits today')
    ->options([
        'water' => ['label' => 'Water', 'description' => 'H2O'],
        'yoga' => ['label' => 'Yoga', 'description' => 'Flow'],
        'journal' => ['label' => 'Journal', 'description' => 'Notes'],
        'workout' => ['label' => 'Workout', 'description' => 'Gym'],
    ])
    ->layoutOptions([
        'size' => 160,
        'minSize' => 25,
        'gutter' => 8,
        'numCols' => 6,
        'fringeWidth' => 160,
        'yRadius' => 130,
        'xRadius' => 220,
        'cornerRadius' => 50,
        'compact' => true,
        'gravitation' => 5,
    ])
    ->arenaHeight('400px')
    ->arenaColor('#16260a')
    ->bubbleColor('#8cb76c')
    ->selectedBubbleColor('#c8f560')
    ->selectedShape('scallop')
    ->minItems(1)
    ->maxItems(4);

// Rich option: image as background or center icon
BubbleChoiceField::make('mood')
    ->options([
        'calm' => [
            'label' => 'Calm',
            'description' => 'CLM',
            'color' => '#1e3a8a',
            'selectedColor' => '#93c5fd',
            'image' => asset('images/calm.jpg'),
            'imageMode' => 'icon', // or 'background'
        ],
        'focus' => [
            'label' => 'Focus',
            'image' => asset('images/focus.jpg'),
            'imageMode' => 'background',
        ],
    ])
    ->required();
PHP, filename: 'bubble-choice-usage.php'),
                ]),
        ];
    }
}

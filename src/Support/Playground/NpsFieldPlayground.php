<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NpsField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class NpsFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'nps_standard' => '9',
            'nps_color_coded' => '5',
            'nps_1_5' => '4',
            'nps_likert' => 'a',
            'nps_segments' => '5',
            'nps_segments_md' => '7',
            'nps_segments_lg' => '8',
            'nps_segments_rounded_md' => '6',
            'nps_emojis' => '3',
            'nps_emojis_sm' => '2',
            'nps_emojis_lg' => '4',
            'nps_emojis_gravity' => null,
            'nps_empty_pills' => null,
            'nps_empty_segments' => null,
            'nps_empty_emojis' => null,
            'nps_deselect_pills' => null,
            'nps_deselect_segments' => null,
            'nps_deselect_emojis' => null,
            'size_sm' => '2',
            'size_md' => '2',
            'size_lg' => '2',
        ];
    }

    public function section(): Section
    {
        $emojiOptions = [
            0 => 'Awful',
            1 => 'Poor',
            2 => 'Neutral',
            3 => 'Good',
            4 => 'Excellent',
        ];

        return Section::make('NpsField')
            ->description('Survey and Likert scale inputs with optional color coding.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Grid::make(1)
                    ->extraAttributes(['class' => 'fff-playground-variants fff-playground-variants--stack'])
                    ->schema([
                        NpsField::make('nps_standard')
                            ->label('Standard NPS (0-10)')
                            ->minLabel('Not likely at all')
                            ->maxLabel('Extremely likely'),

                        NpsField::make('nps_color_coded')
                            ->label('Color Coded NPS (0-10)')
                            ->minLabel('Detractor')
                            ->maxLabel('Promoter')
                            ->colorCoded(true),

                        NpsField::make('nps_1_5')
                            ->label('5-Point Scale (1-5)')
                            ->options(array_combine(range(1, 5), range(1, 5)))
                            ->minLabel('Poor')
                            ->maxLabel('Excellent'),

                        NpsField::make('nps_likert')
                            ->label('Textual Likert Scale')
                            ->options([
                                'sd' => 'Strongly Disagree',
                                'd' => 'Disagree',
                                'n' => 'Neutral',
                                'a' => 'Agree',
                                'sa' => 'Strongly Agree',
                            ]),
                    ]),

                Section::make('Empty initial state')
                    ->description('No pre-selected value — default is null until the user chooses.')
                    ->schema([
                        NpsField::make('nps_empty_pills')
                            ->label('Pills (empty)')
                            ->minLabel('Not likely')
                            ->maxLabel('Very likely'),

                        NpsField::make('nps_empty_segments')
                            ->label('Segments (empty)')
                            ->variant('segments')
                            ->rounding('full'),

                        NpsField::make('nps_empty_emojis')
                            ->label('Emojis (empty)')
                            ->variant('emojis')
                            ->options($emojiOptions),
                    ]),

                Section::make('Deselect when optional')
                    ->description('When the field is not required, clicking the active option again clears the selection.')
                    ->schema([
                        NpsField::make('nps_deselect_pills')
                            ->label('Optional pills — click again to clear')
                            ->options(array_combine(range(1, 5), range(1, 5))),

                        NpsField::make('nps_deselect_segments')
                            ->label('Optional segments — click again to clear')
                            ->variant('segments')
                            ->options(array_combine(range(1, 5), range(1, 5))),

                        NpsField::make('nps_deselect_emojis')
                            ->label('Optional emojis — click again to clear')
                            ->variant('emojis')
                            ->options($emojiOptions),
                    ]),

                Section::make('NpsField (Segments Variant)')
                    ->description('Configurable border radius via rounding() and size via size().')
                    ->schema([
                        NpsField::make('nps_segments')
                            ->label('How would you rate your experience? (sm, full radius)')
                            ->variant('segments')
                            ->size('sm')
                            ->rounding('full')
                            ->required(),

                        NpsField::make('nps_segments_md')
                            ->label('Medium segments (md, default radius)')
                            ->variant('segments')
                            ->size('md')
                            ->required(),

                        NpsField::make('nps_segments_lg')
                            ->label('Large segments (lg, full radius)')
                            ->variant('segments')
                            ->size('lg')
                            ->rounding('full')
                            ->required(),

                        NpsField::make('nps_segments_rounded_md')
                            ->label('Medium rounding (md radius)')
                            ->variant('segments')
                            ->rounding('md')
                            ->required(),
                    ]),

                Section::make('NpsField (Emojis Variant)')
                    ->schema([
                        NpsField::make('nps_emojis')
                            ->label('How are you feeling today? (webp images)')
                            ->options($emojiOptions)
                            ->variant('emojis')
                            ->required(),

                        NpsField::make('nps_emojis_gravity')
                            ->label('Custom Gravity icons')
                            ->options($emojiOptions)
                            ->variant('emojis')
                            ->icons([
                                0 => GravityIcon::CircleXmark,
                                1 => GravityIcon::Hand,
                                2 => GravityIcon::FaceSmile,
                                3 => GravityIcon::Heart,
                                4 => GravityIcon::Star,
                            ]),

                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                NpsField::make('nps_emojis_sm')
                                    ->label('Small emojis (sm)')
                                    ->options($emojiOptions)
                                    ->variant('emojis')
                                    ->size('sm'),

                                NpsField::make('nps_emojis_lg')
                                    ->label('Large emojis (lg)')
                                    ->options($emojiOptions)
                                    ->variant('emojis')
                                    ->size('lg'),
                            ]),
                    ]),

                Section::make('Pills — Different Sizes')
                    ->schema([
                        NpsField::make('size_sm')
                            ->label('Small Size (sm)')
                            ->size('sm')
                            ->options([
                                1 => 'Bad',
                                2 => 'Okay',
                                3 => 'Good',
                            ]),

                        NpsField::make('size_md')
                            ->label('Medium Size (md)')
                            ->size('md')
                            ->options([
                                1 => 'Bad',
                                2 => 'Okay',
                                3 => 'Good',
                            ]),

                        NpsField::make('size_lg')
                            ->label('Large Size (lg)')
                            ->size('lg')
                            ->options([
                                1 => 'Bad',
                                2 => 'Okay',
                                3 => 'Good',
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

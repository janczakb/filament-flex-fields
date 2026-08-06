<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class AudioFieldPlayground
{
    private const DEMO_AUDIO = 'https://download.samplelib.com/mp3/sample-15s.mp3';

    private const DEMO_AUDIO_ALT = 'https://download.samplelib.com/mp3/sample-6s.mp3';

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'audio__basic' => self::DEMO_AUDIO,
            'audio__fullwidth' => self::DEMO_AUDIO,
            'audio__sm' => self::DEMO_AUDIO,
            'audio__lg' => self::DEMO_AUDIO_ALT,
            'audio__custom_wave' => self::DEMO_AUDIO,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Audio player')
                ->description('Playback-only voice-note pill: play button, waveform, duration — for existing audio URLs / stored notes.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    AudioField::make('audio__basic')
                        ->label('Voice message')
                        ->helperText('Tap play or scrub the waveform. Duration shows on the right.')
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            AudioField::make('audio__fullwidth')
                                ->label('Full width')
                                ->fullWidth(),
                            AudioField::make('audio__sm')
                                ->label('Small')
                                ->size('sm'),
                            AudioField::make('audio__lg')
                                ->label('Large')
                                ->size('lg'),
                            AudioField::make('audio__custom_wave')
                                ->label('Custom waveform')
                                ->waveform([18, 32, 48, 64, 80, 72, 88, 76, 60, 44, 36, 52, 68, 84, 72, 56, 40, 28, 36, 50, 66, 54])
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }
}

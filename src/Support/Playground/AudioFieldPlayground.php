<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AudioField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;
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
            'voice_note__basic' => null,
            'voice_note__sm' => null,
            'voice_note__lg' => null,
            'voice_note__with_limit' => null,
            'voice_note__immediate' => null,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Audio field')
                ->description('Voice-note pill with play button, waveform bars, and duration — like iMessage voice messages.')
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

            Section::make('Voice Note Recorder')
                ->description('Voice recorder with real-time waveform visualizer, stop/cancel actions, and inline playback player.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    VoiceNoteRecorderField::make('voice_note__basic')
                        ->label('Record Voice Note')
                        ->helperText('Click microphone to start recording. Speak to see real-time frequency waves.')
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            VoiceNoteRecorderField::make('voice_note__sm')
                                ->label('Small recorder')
                                ->size('sm'),
                            VoiceNoteRecorderField::make('voice_note__lg')
                                ->label('Large recorder')
                                ->size('lg'),
                            VoiceNoteRecorderField::make('voice_note__with_limit')
                                ->label('30s duration limit')
                                ->maxDuration(30),
                            VoiceNoteRecorderField::make('voice_note__immediate')
                                ->label('Immediate upload')
                                ->helperText('Uploads right after recording. Delete removes the file from storage.')
                                ->uploadImmediately(),
                        ]),
                ]),
        ];
    }
}

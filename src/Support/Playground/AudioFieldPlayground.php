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

    private const STT_SAMPLE_ONE = 'https://audio-samples.github.io/samples/mp3/blizzard_unconditional/sample-1.mp3';

    private const STT_SAMPLE_FIVE = 'https://audio-samples.github.io/samples/mp3/blizzard_unconditional/sample-5.mp3';

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
            'audio__stt_tiny_en' => self::STT_SAMPLE_ONE,
            'audio__stt_small_multi' => self::STT_SAMPLE_FIVE,
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
            Section::make('Speech-to-text (Whisper Web)')
                ->description('Same AudioField with optional client-side Whisper transcription (@xenova/transformers). Models download on first Transcribe click — inspired by Xenova/whisper-web.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            AudioField::make('audio__stt_tiny_en')
                                ->label('Tiny · Multilingual default')
                                ->helperText('Blizzard sample #1 — multilingual, full-size tiny/base (whisper-web default).')
                                ->transcription()
                                ->fullWidth(),
                            AudioField::make('audio__stt_small_multi')
                                ->label('Small · Quantized English')
                                ->helperText('Blizzard sample #5 — disable Multilingual and enable Quantized for .en models.')
                                ->transcription()
                                ->whisperModel('Xenova/whisper-small')
                                ->whisperMultilingual(false)
                                ->whisperQuantized(true)
                                ->fullWidth(),
                        ]),
                ]),
        ];
    }
}

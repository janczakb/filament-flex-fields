<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class VoiceNoteRecorderFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'voice_note__basic' => null,
            'voice_note__meta' => [],
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
            Section::make('Voice Note Recorder')
                ->description('Record a voice note in-browser — same upload pipeline as file upload, with mic capture instead of a file picker.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    VoiceNoteRecorderField::make('voice_note__basic')
                        ->label('Record Voice Note')
                        ->helperText('Click microphone to start. Stop to review, then save — file lands on disk like FlexFileUpload. Transcript and waveform metadata appear in voice_note__meta after save.')
                        ->directory('voice-notes')
                        ->storeMetadataIn('voice_note__meta')
                        ->storeWaveformIn('voice_note__meta')
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            VoiceNoteRecorderField::make('voice_note__sm')
                                ->label('Small recorder')
                                ->directory('voice-notes')
                                ->size('sm'),
                            VoiceNoteRecorderField::make('voice_note__lg')
                                ->label('Large recorder')
                                ->directory('voice-notes')
                                ->size('lg'),
                            VoiceNoteRecorderField::make('voice_note__with_limit')
                                ->label('30s duration limit')
                                ->directory('voice-notes')
                                ->maxDuration(30),
                            VoiceNoteRecorderField::make('voice_note__immediate')
                                ->label('Immediate upload')
                                ->helperText('Uploads right after recording. Delete removes the file from storage.')
                                ->directory('voice-notes')
                                ->uploadImmediately(),
                        ]),
                ]),
        ];
    }
}

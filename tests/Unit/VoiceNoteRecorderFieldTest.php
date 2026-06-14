<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Filament\Support\Icons\Heroicon;
use Livewire\Livewire;

it('exposes voice note recorder field configuration api', function () {
    $field = VoiceNoteRecorderField::make('voice_note')
        ->maxDuration(60)
        ->size('sm');

    expect($field->getMaxDuration())->toBe(60)
        ->and($field->getSize())->toBe('sm')
        ->and($field->shouldUploadImmediately())->toBeFalse();
});

it('can upload immediately after recording', function () {
    $field = VoiceNoteRecorderField::make('voice_note')
        ->uploadImmediately();

    expect($field->shouldUploadImmediately())->toBeTrue();
});

it('can defer upload until form submit', function () {
    $field = VoiceNoteRecorderField::make('voice_note')
        ->uploadOnSubmit();

    expect($field->shouldUploadImmediately())->toBeFalse();
});

it('has sensible default accepted file types', function () {
    $field = VoiceNoteRecorderField::make('voice_note');

    expect($field->getAcceptedFileTypes())->toBe([
        'audio/*',
        'audio/mpeg',
        'audio/wav',
        'audio/webm',
        'audio/ogg',
        'audio/x-m4a',
        'audio/aac',
    ]);
});

it('resolves initial audio url from state', function () {
    $field = VoiceNoteRecorderField::make('voice_note')
        ->disk('public');

    TestableTranslatableForm::$formSchema = [$field];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    $livewireComponent = $livewire->instance();

    /** @var VoiceNoteRecorderField $resolvedField */
    $resolvedField = $livewireComponent->getSchema('form')->getComponent('voice_note');

    expect($resolvedField->getInitialAudioUrl())->toBeNull();

    $resolvedField->state('voice-notes/sample.wav');

    // getDisk()->url() resolves to public storage path normally
    expect($resolvedField->getInitialAudioUrl())->toBe(Storage::disk('public')->url('voice-notes/sample.wav'));

    $resolvedField->state(['voice-notes/array.wav']);
    expect($resolvedField->getInitialAudioUrl())->toBe(Storage::disk('public')->url('voice-notes/array.wav'));
});

it('uses gravity ui icons for voice note controls by default', function () {
    $field = VoiceNoteRecorderField::make('voice_note');

    expect($field->getPlayIcon())->toBe(GravityIcon::PlayFill)
        ->and($field->getPauseIcon())->toBe(GravityIcon::PauseFill)
        ->and($field->getMicrophoneIcon())->toBe(GravityIcon::Microphone)
        ->and($field->getStopIcon())->toBe(GravityIcon::Minus)
        ->and($field->getTrashIcon())->toBe(GravityIcon::TrashBin)
        ->and($field->getCheckmarkIcon())->toBe(GravityIcon::Check);
});

it('allows overriding voice note control icons', function () {
    $field = VoiceNoteRecorderField::make('voice_note')
        ->playIcon(Heroicon::OutlinedPlay)
        ->pauseIcon(Heroicon::OutlinedPause)
        ->microphoneIcon(Heroicon::OutlinedMicrophone)
        ->stopIcon(Heroicon::OutlinedStop)
        ->trashIcon(Heroicon::OutlinedTrash)
        ->checkmarkIcon(Heroicon::OutlinedCheck);

    expect($field->getPlayIcon())->toBe(Heroicon::OutlinedPlay)
        ->and($field->getPauseIcon())->toBe(Heroicon::OutlinedPause)
        ->and($field->getMicrophoneIcon())->toBe(Heroicon::OutlinedMicrophone)
        ->and($field->getStopIcon())->toBe(Heroicon::OutlinedStop)
        ->and($field->getTrashIcon())->toBe(Heroicon::OutlinedTrash)
        ->and($field->getCheckmarkIcon())->toBe(Heroicon::OutlinedCheck);
});

it('registers voice note field playground variants', function () {
    $builder = app(FlexFieldsPlaygroundBuilder::class);
    $state = $builder->defaultState();

    expect($state)->toHaveKeys([
        'voice_note__basic',
        'voice_note__sm',
        'voice_note__lg',
        'voice_note__with_limit',
        'voice_note__immediate',
    ]);
});

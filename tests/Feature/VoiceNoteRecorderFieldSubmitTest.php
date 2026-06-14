<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;

it('requires a voice note when the field is required', function () {
    TestableTranslatableForm::$formSchema = [
        VoiceNoteRecorderField::make('voice_note')
            ->required()
            ->label('Voice note'),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);

    expect(fn () => $livewire->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('accepts uploaded audio using filament file upload state shape', function () {
    Storage::fake('public');

    TestableTranslatableForm::$formSchema = [
        VoiceNoteRecorderField::make('voice_note')
            ->disk('public')
            ->directory('voice-notes'),
    ];

    $file = UploadedFile::fake()->create('voice-note.webm', 100, 'audio/webm');
    $fileKey = (string) Str::uuid();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->upload("data.voice_note.{$fileKey}", [$file]);

    $state = $livewire->get('data.voice_note');

    expect($state)->toBeArray()
        ->and($state)->toHaveKey($fileKey)
        ->and($state[$fileKey])->toBeInstanceOf(TemporaryUploadedFile::class);
});

it('persists uploaded voice note to disk on form dehydrate', function () {
    Storage::fake('public');

    TestableTranslatableForm::$formSchema = [
        VoiceNoteRecorderField::make('voice_note')
            ->disk('public')
            ->directory('voice-notes'),
    ];

    $file = UploadedFile::fake()->create('voice-note.webm', 100, 'audio/webm');
    $fileKey = (string) Str::uuid();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->upload("data.voice_note.{$fileKey}", [$file]);

    /** @var VoiceNoteRecorderField $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('voice_note');

    $field->saveUploadedFiles();

    $storedPath = $field->getState();

    expect($storedPath)->toBeString()
        ->and($storedPath)->toEndWith('.webm');

    Storage::disk('public')->assertExists($storedPath);
});

it('deletes persisted voice note from disk through delete uploaded file api', function () {
    Storage::fake('public');

    TestableTranslatableForm::$formSchema = [
        VoiceNoteRecorderField::make('voice_note')
            ->disk('public')
            ->directory('voice-notes'),
    ];

    $file = UploadedFile::fake()->create('voice-note.webm', 100, 'audio/webm');
    $fileKey = (string) Str::uuid();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->upload("data.voice_note.{$fileKey}", [$file]);

    /** @var VoiceNoteRecorderField $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('voice_note');

    $field->saveUploadedFiles();

    $storedPath = $field->getState();

    Storage::disk('public')->assertExists($storedPath);

    $field->deleteUploadedFile($fileKey);

    Storage::disk('public')->assertMissing($storedPath);
    expect($field->getState())->toBeNull();
});

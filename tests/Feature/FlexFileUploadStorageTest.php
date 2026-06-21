<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('moves file from temp to final destination on submit via dehydrate', function () {
    Storage::fake('public');
    
    TestableTranslatableForm::$formSchema = [
        FlexFileUpload::make('attachment')
            ->disk('public')
            ->directory('test-uploads'),
    ];

    $file = UploadedFile::fake()->image('avatar.jpg');
    $fileKey = (string) Str::uuid();

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->upload("data.attachment.{$fileKey}", [$file]);

    // Wymuszamy cykl dehydratacji formularza (odpali to nasze naprawione przedStateDehydrated)
    $form = $livewire->instance()->getSchema('form');
    $form->getState(); // To wyzwala walidację i dehydratację!

    /** @var FlexFileUpload $field */
    $field = $form->getComponent('attachment');
    $storedPath = $field->getState();

    // Wyciągnij pierwszy element jeśli to tablica (a będzie w tym przypadku, tak działa getState)
    if (is_array($storedPath)) {
        $storedPath = array_values($storedPath)[0] ?? null;
    }

    expect($storedPath)->toBeString()->not->toBeEmpty();
    expect($storedPath)->toStartWith('test-uploads/');
    
    // Upewnijmy się że plik znajduje się we właściwym katalogu
    Storage::disk('public')->assertExists($storedPath);
});

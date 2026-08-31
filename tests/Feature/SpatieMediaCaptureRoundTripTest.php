<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\Media\SpatieMediaCaptureAdapter;
use Bjanczak\FilamentFlexFields\Tests\Support\FakeCaptureMediaModel;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

beforeEach(function () {
    MediaCaptureOs::resetRuntimeState();
});

it('persists spatie media through the enterprise adapter and resolves payload', function () {
    $record = new FakeCaptureMediaModel;
    $field = FlexSpatieMediaLibraryFileUpload::make('attachment');
    $field->collection('documents');
    $field->model($record);
    $field->disk('local');

    $contents = '%PDF-1.4 test';

    /** @var TemporaryUploadedFile&\Mockery\MockInterface $temporaryFile */
    $temporaryFile = Mockery::mock(TemporaryUploadedFile::class);
    $temporaryFile->shouldReceive('exists')->andReturn(true);
    $temporaryFile->shouldReceive('get')->andReturn($contents);
    $temporaryFile->shouldReceive('getClientOriginalName')->andReturn('contract.pdf');
    $temporaryFile->shouldReceive('getClientOriginalExtension')->andReturn('pdf');

    $uuid = SpatieMediaCaptureAdapter::saveUploadedFile($field, $temporaryFile, $record);

    expect($uuid)->toBeString()->not->toBeEmpty()
        ->and($record->mediaItems)->toHaveCount(1)
        ->and($record->mediaItems[0]->customProperties['flex_capture'] ?? null)->toMatchArray([
            'field' => 'attachment',
            'collection' => 'documents',
        ]);

    $payload = SpatieMediaCaptureAdapter::resolveUploadedFilePayload($field, (string) $uuid);

    expect($payload)->toBeArray()
        ->and($payload['name'] ?? null)->toBe('contract')
        ->and($payload['url'] ?? null)->toContain($uuid);
});

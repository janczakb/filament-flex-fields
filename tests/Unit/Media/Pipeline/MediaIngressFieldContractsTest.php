<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SignatureField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\VoiceNoteSpatieRecorderField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\SignatureStorage;
use Bjanczak\FilamentFlexFields\Support\SignatureSvg;
use Bjanczak\FilamentFlexFields\Tests\Support\FakeCaptureMediaModel;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    MediaCaptureOs::resetRuntimeState();
    MediaCaptureOs::bootSafeDefaults();
    Storage::fake('local');
    config([
        'filament-flex-fields.media_capture.disk' => 'local',
        'filament-flex-fields.media_capture.require_virus_scan' => false,
    ]);
});

it('exposes FlexFileUpload::spatie factory sugar returning Spatie class', function () {
    $field = FlexFileUpload::spatie('docs');

    expect($field)->toBeInstanceOf(FlexSpatieMediaLibraryFileUpload::class)
        ->and($field->getName())->toBe('docs');
});

it('exposes VoiceNoteRecorderField::spatie factory sugar', function () {
    $field = VoiceNoteRecorderField::spatie('note');

    expect($field)->toBeInstanceOf(VoiceNoteSpatieRecorderField::class)
        ->and($field->getName())->toBe('note');
});

it('keeps signature state as ffstage token when Spatie sink copies media', function () {
    $svg = SignatureSvg::normalize('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M10,20 L30,40" fill="none" stroke="#18181b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>');
    expect($svg)->toBeString();

    $record = new FakeCaptureMediaModel;
    $token = SignatureStorage::store($svg, 'signatures', 'local');

    expect($token)->toStartWith(SignatureStorage::TOKEN_PREFIX);

    $uuid = SignatureStorage::sinkToSpatie($svg, $record, 'signatures', 'local', 'sig');

    expect($uuid)->toBeString()->not->toBeEmpty()
        ->and($record->mediaItems)->toHaveCount(1)
        ->and(SignatureStorage::resolve($token, 'local'))->toContain('<svg');
});

it('SignatureField::disk enables store-to-disk without changing Spatie state contract', function () {
    $field = SignatureField::make('sig')->disk('signatures', 'local');

    expect($field->shouldStoreToDisk())->toBeTrue()
        ->and($field->getStorageDirectory())->toBe('signatures')
        ->and($field->getStorageDisk())->toBe('local')
        ->and($field->shouldSpatieSink())->toBeFalse();
});

it('SignatureField::spatieSink enables optional Media sink metadata path', function () {
    $field = SignatureField::make('sig')
        ->disk()
        ->spatieSink('signatures', 'local', 'sig_media_uuid');

    expect($field->shouldSpatieSink())->toBeTrue()
        ->and($field->getSpatieSinkCollection())->toBe('signatures')
        ->and($field->getSpatieSinkUuidInPath())->toBe('sig_media_uuid');
});

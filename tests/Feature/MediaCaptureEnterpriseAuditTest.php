<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\Media\SpatieMediaCaptureAdapter;
use Bjanczak\FilamentFlexFields\Tests\Support\FakeCaptureMediaModel;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

beforeEach(function () {
    MediaCaptureOs::resetRuntimeState();
    ObservabilityHooks::clear();
});

it('rejects spatie uploads when virus scan fails and records upload.fail', function () {
    config(['filament-flex-fields.media_capture.scan_before_persist' => false]);
    MediaCaptureOs::registerVirusScanCallback(fn (string $path): bool => false);

    $record = new FakeCaptureMediaModel;
    $field = FlexSpatieMediaLibraryFileUpload::make('attachment');
    $field->collection('documents');
    $field->model($record);

    $filename = (string) Str::uuid().'.pdf';
    $contents = '%PDF-1.4 infected';

    FileUploadConfiguration::storage()->put(
        FileUploadConfiguration::path($filename),
        $contents,
    );

    FileUploadConfiguration::storage()->put(
        FileUploadConfiguration::path($filename).'.json',
        json_encode([
            'name' => 'infected.pdf',
            'type' => 'application/pdf',
            'size' => strlen($contents),
        ], JSON_THROW_ON_ERROR),
    );

    $temporaryFile = TemporaryUploadedFile::createFromLivewire($filename);

    $events = [];
    ObservabilityHooks::on(ObservabilityHooks::EVENT_UPLOAD_FAIL, function (array $payload) use (&$events): void {
        $events[] = $payload;
    });

    $uuid = SpatieMediaCaptureAdapter::saveUploadedFile($field, $temporaryFile, $record);

    expect($uuid)->toBeNull()
        ->and($events)->not->toBeEmpty()
        ->and($events[0]['reason'] ?? null)->toBe('virus_scan')
        ->and($events[0]['adapter'] ?? null)->toBe('spatie');
});

it('resolves signed upload url override on flex file upload', function () {
    MediaCaptureOs::registerSignedUploadUrlResolver(
        fn (string $disk, string $path, array $context): string => "https://signed.test/{$disk}/{$path}",
    );

    $field = Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload::make('attachment');
    $field->disk('local');

    expect($field->resolveMediaCaptureSignedUrl('uploads/file.pdf'))
        ->toBe('https://signed.test/local/uploads/file.pdf');
});

it('fail-closes virus scan when required without host callback', function () {
    config(['filament-flex-fields.media_capture.require_virus_scan' => true]);
    MediaCaptureOs::bootFromConfig();

    expect(MediaCaptureOs::passesVirusScan('/tmp/upload.pdf'))->toBeFalse()
        ->and(MediaCaptureOs::hasRegisteredVirusScanner())->toBeFalse();
});

it('resolves tenant disk when auto_disk is enabled', function () {
    config([
        'filament-flex-fields.media_capture.tenant.auto_disk' => true,
        'filament-flex-fields.media_capture.tenant.disk' => 'tenant-uploads',
    ]);

    expect(Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureTenantDiskResolver::resolveDisk('local'))
        ->toBe('tenant-uploads');
});

it('builds legal audit seal metadata with request context', function () {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M10,10 L90,90" fill="none" stroke="#000" stroke-width="2"/></svg>';

    $seal = Bjanczak\FilamentFlexFields\Support\Media\SignatureLegalPack::legalAuditSeal($svg);

    expect($seal)->toHaveKeys(['sealed_at', 'signer_id', 'ip_address', 'user_agent', 'document_hash', 'signature_path_count'])
        ->and($seal['signature_path_count'])->toBe(1);
});

<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks;
use Bjanczak\FilamentFlexFields\Support\Media\FlexMedia;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureQuarantine;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureTenantDiskResolver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\DiskPrecedenceResolver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaContext;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaIngress;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaKind;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaKindRetention;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaPayload;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaStorageDriver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\DiskMediaRef;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\SpatieMediaRef;
use Bjanczak\FilamentFlexFields\Tests\Support\FakeCaptureMediaModel;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    MediaCaptureOs::resetRuntimeState();
    MediaCaptureTenantDiskResolver::reset();
    ObservabilityHooks::clear();
    MediaCaptureOs::bootSafeDefaults();
    Storage::fake('local');
    config([
        'filament-flex-fields.media_capture.disk' => 'local',
        'filament-flex-fields.media_capture.require_virus_scan' => false,
        'filament-flex-fields.media_capture.scan_before_persist' => true,
        'filament-flex-fields.media_capture.quarantine_disk' => null,
        'filament-flex-fields.media_capture.tenant.auto_disk' => false,
        'filament-flex-fields.media_capture.tenant.disk' => null,
        'filament-flex-fields.enterprise.enabled' => true,
    ]);
});

it('persists disk payloads through MediaIngress with correlation id', function () {
    $success = [];
    ObservabilityHooks::on(ObservabilityHooks::EVENT_UPLOAD_SUCCESS, function (array $payload) use (&$success): void {
        $success[] = $payload;
    });

    $payload = MediaPayload::fromInlineString('%PDF-1.4', 'doc.pdf', 'application/pdf');
    $context = new MediaContext(
        kind: MediaKind::Upload,
        driver: MediaStorageDriver::Disk,
        field: 'docs',
        directory: 'uploads',
        filename: 'contract.pdf',
        disk: 'local',
    );

    $ref = FlexMedia::persist($payload, $context);

    expect($ref)->toBeInstanceOf(DiskMediaRef::class)
        ->and($ref->relativePath())->toBe('uploads/contract.pdf')
        ->and(Storage::disk('local')->exists('uploads/contract.pdf'))->toBeTrue()
        ->and($success)->not->toBeEmpty()
        ->and($success[0]['correlation_id'] ?? null)->not->toBeEmpty();
});

it('fail-closes pre-persist virus scan before driver write', function () {
    MediaCaptureOs::registerVirusScanCallback(fn (string $path): bool => false);

    $payload = MediaPayload::fromInlineString('malware', 'bad.bin');
    $context = new MediaContext(
        kind: MediaKind::Upload,
        driver: MediaStorageDriver::Disk,
        field: 'docs',
        directory: 'uploads',
        filename: 'bad.bin',
        disk: 'local',
    );

    expect(FlexMedia::persist($payload, $context))->toBeNull()
        ->and(Storage::disk('local')->exists('uploads/bad.bin'))->toBeFalse();
});

it('persists Spatie media via path/stream without TemporaryUploadedFile::get', function () {
    $absolute = sys_get_temp_dir().'/fff-ingress-'.Str::uuid()->toString().'.pdf';
    file_put_contents($absolute, '%PDF-1.4 stream-safe');

    $record = new FakeCaptureMediaModel;
    $payload = MediaPayload::fromPath($absolute, 'stream.pdf', 'application/pdf');
    $context = new MediaContext(
        kind: MediaKind::Upload,
        driver: MediaStorageDriver::Spatie,
        field: 'attachment',
        record: $record,
        collection: 'documents',
        disk: 'local',
        filename: 'stream.pdf',
        mediaName: 'stream',
    );

    $ref = app(MediaIngress::class)->persist($payload, $context);

    @unlink($absolute);

    expect($ref)->toBeInstanceOf(SpatieMediaRef::class)
        ->and($record->mediaItems)->toHaveCount(1)
        ->and($record->mediaItems[0]->content)->toBe('%PDF-1.4 stream-safe')
        ->and($record->mediaItems[0]->customProperties['flex_capture']['kind'] ?? null)->toBe('upload');
});

it('throws clearly when Spatie persist has no Eloquent record', function () {
    $payload = MediaPayload::fromInlineString('x', 'x.bin');
    $context = new MediaContext(
        kind: MediaKind::Upload,
        driver: MediaStorageDriver::Spatie,
        field: 'attachment',
        collection: 'documents',
    );

    expect(fn () => FlexMedia::persist($payload, $context))
        ->toThrow(\RuntimeException::class, 'HasMedia Eloquent record');
});

it('enforces disk precedence: explicit > collection > tenant > package default', function () {
    config([
        'filament-flex-fields.media_capture.disk' => 'local',
        'filament-flex-fields.media_capture.tenant.disk' => 'tenant-disk',
    ]);

    expect(DiskPrecedenceResolver::resolve('field-disk', 'collection-disk'))->toBe('field-disk')
        ->and(DiskPrecedenceResolver::resolve(null, 'collection-disk'))->toBe('collection-disk')
        ->and(DiskPrecedenceResolver::resolve(null, null))->toBe('tenant-disk');

    expect(DiskPrecedenceResolver::resolveForSpatie('field-disk', 'collection-disk'))->toBe('field-disk')
        ->and(DiskPrecedenceResolver::resolveForSpatie(null, 'collection-disk'))->toBe('collection-disk');

    config(['filament-flex-fields.media_capture.tenant.auto_disk' => false]);
    expect(DiskPrecedenceResolver::resolveForSpatie(null, null))->toBe('local');

    config(['filament-flex-fields.media_capture.tenant.auto_disk' => true]);
    expect(DiskPrecedenceResolver::resolveForSpatie(null, null))->toBe('tenant-disk');
});

it('maps MediaKind to retention policy keys', function () {
    expect(MediaKindRetention::policyKey(MediaKind::VoiceNote))->toBe('voice_notes')
        ->and(MediaKindRetention::policyKey(MediaKind::Signature))->toBe('signatures')
        ->and(MediaKindRetention::policyKey(MediaKind::RichAttachment))->toBe('uploads');
});

it('quarantines Spatie media before delete when quarantine disk is configured', function () {
    Storage::fake('quarantine');
    config(['filament-flex-fields.media_capture.quarantine_disk' => 'quarantine']);

    $absolute = sys_get_temp_dir().'/fff-q-'.Str::uuid()->toString().'.bin';
    file_put_contents($absolute, 'infected');

    $media = new class($absolute)
    {
        public function __construct(private string $path) {}

        public function getPath(): string
        {
            return $this->path;
        }

        public function getPathRelativeToRoot(): string
        {
            return 'media/infected.bin';
        }

        public function getAttributeValue(string $key): mixed
        {
            return $key === 'disk' ? 'local' : null;
        }

        public function delete(): void {}
    };

    $dest = MediaCaptureQuarantine::quarantineSpatieMedia($media);

    expect($dest)->toBeString()
        ->and(Storage::disk('quarantine')->exists($dest))->toBeTrue()
        ->and(is_file($absolute))->toBeFalse();
});

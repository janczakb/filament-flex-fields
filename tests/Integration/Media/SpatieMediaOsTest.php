<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Integration\Media;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VoiceNoteRecorderField;
use Bjanczak\FilamentFlexFields\Filament\Infolists\Components\Spatie\FlexSpatieMediaLibraryImageEntry;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\Spatie\FlexSpatieMediaLibraryImageColumn;
use Bjanczak\FilamentFlexFields\Support\Media\Data\MediaFailure;
use Bjanczak\FilamentFlexFields\Support\Media\Data\MediaPolicy;
use Bjanczak\FilamentFlexFields\Support\Media\Data\MediaWrite;
use Bjanczak\FilamentFlexFields\Support\Media\FlexMedia;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\Media\MediaOs;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaContext;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaKind;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaPayload;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaStorageDriver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\SpatieMediaRef;
use Bjanczak\FilamentFlexFields\Support\Media\Runtime\MediaRegistry;
use Bjanczak\FilamentFlexFields\Support\Media\Sources\MediaSource;
use Bjanczak\FilamentFlexFields\Support\Media\Spatie\OwnedMedia;
use Bjanczak\FilamentFlexFields\Support\Media\Spatie\SpatieOptions;
use Bjanczak\FilamentFlexFields\Support\Media\Spatie\SpatieUploadBridge;
use Bjanczak\FilamentFlexFields\Support\Media\SpatieMediaCaptureAdapter;
use Bjanczak\FilamentFlexFields\Support\Media\Staging\MediaStaging;
use Bjanczak\FilamentFlexFields\Support\Media\Staging\StageOptions;
use Bjanczak\FilamentFlexFields\Support\Media\Staging\StageRepository;
use Bjanczak\FilamentFlexFields\Support\SignatureStorage;
use Bjanczak\FilamentFlexFields\Support\SignatureSvg;
use Bjanczak\FilamentFlexFields\Tests\TestCase;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Schema as FormSchema;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\Models\Observers\MediaObserver;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

final class SpatieMediaOsTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), MediaLibraryServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('media_os_records', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
        $migration = dirname((new \ReflectionClass(MediaLibraryServiceProvider::class))->getFileName(), 2)
            .'/database/migrations/create_media_table.php.stub';
        (require $migration)->up();
        foreach (['media-os', 'collection-media', 'conversion-media', 'explicit-media', 'quarantine-media'] as $disk) {
            config()->set('filesystems.disks.'.$disk, ['driver' => 'local', 'root' => sys_get_temp_dir().'/fff-media-os/'.$disk]);
            Storage::fake($disk);
        }
        config()->set('media-library.disk_name', 'media-os');
        config()->set('media-library.queue_conversions_by_default', false);
        config()->set('filament-flex-fields.media_capture.disk', 'media-os');
        MediaCaptureOs::resetRuntimeState();
    }

    public function test_voice_recorder_uses_the_native_spatie_adapter_with_owned_audio(): void
    {
        $record = MediaOsRecord::create();
        $field = VoiceNoteRecorderField::spatie('voice')
            ->disk('media-os')->visibility('public')->collection('voice-notes');
        FormSchema::make()->model($record)->components([$field])->getComponents();
        $bytes = 'RIFF'.pack('V', 36 + 1600).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('V', 1600).str_repeat(chr(0), 1600);
        $file = \Mockery::mock(TemporaryUploadedFile::class);
        $file->shouldReceive('readStream')->andReturnUsing(static function () use ($bytes) {
            $stream = fopen('php://temp', 'w+b');
            fwrite($stream, $bytes);
            rewind($stream);

            return $stream;
        });
        $file->shouldReceive('getClientOriginalName')->andReturn('voice.wav');
        $file->shouldReceive('getClientOriginalExtension')->andReturn('wav');
        $file->shouldReceive('hashName')->andReturn('recorded-voice.wav');
        $uuid = app(SpatieUploadBridge::class)->save($field, $file, $record, stage: false);
        $this->assertIsString($uuid);
        $media = app(OwnedMedia::class)->find($record, $uuid, 'voice-notes');
        $this->assertNotNull($media);
        $this->assertSame('voice_note', $field->makeUploadMediaWrite()->kind);
        $this->assertSame('voice_note', $media->getCustomProperty('flex_capture.kind'));
        $this->assertSame($bytes, Storage::disk('media-os')->get($media->getPathRelativeToRoot()));
        $foreign = VoiceNoteRecorderField::spatie('voice')->collection('voice-notes');
        FormSchema::make()->model(MediaOsRecord::create())->components([$foreign])->getComponents();
        $this->assertNull(app(SpatieUploadBridge::class)->payload($foreign, $uuid));
    }

    public function test_file_click_metadata_is_scoped_to_the_owned_spatie_record_and_collection(): void
    {
        $record = MediaOsRecord::create();
        $field = FlexSpatieMediaLibraryFileUpload::make('documents')->collection('documents');
        FormSchema::make()->model($record)->components([$field])->getComponents();
        $receipt = app(MediaOs::class)->store(MediaSource::bytes('contract bytes', 'Contract.TXT'),
            new MediaWrite(adapter: 'spatie', record: $record, spatie: new SpatieOptions(collection: 'documents'), field: 'documents'));
        $bridge = app(SpatieUploadBridge::class);
        $metadata = $bridge->readInteraction($field, $receipt->identifier);
        $this->assertSame('Contract', $metadata['name']);
        $this->assertNotEmpty($metadata['type']);
        $this->assertSame($bridge->payload($field->getClone()->openable(), $receipt->identifier)['openableUrl'],
            $bridge->conversionUrl($field, $receipt->identifier, 'missing'));
        $foreign = FlexSpatieMediaLibraryFileUpload::make('documents')->collection('documents');
        FormSchema::make()->model(MediaOsRecord::create())->components([$foreign])->getComponents();
        $this->expectException(MediaFailure::class);
        $bridge->readInteraction($foreign, $receipt->identifier);
    }

    public function test_real_native_options_and_collection_disk_are_preserved(): void
    {
        $record = MediaOsRecord::create();
        $nativeCalls = 0;
        $options = new SpatieOptions(collection: 'documents', name: 'Contract', conversionsDisk: 'conversion-media',
            customProperties: ['custom' => ['owner' => 42]], headers: ['CacheControl' => 'private'], order: 7,
            queue: 'media-conversions', sanitizeFilename: static fn (string $filename): string => strtolower($filename),
            configure: function (FileAdder $adder) use (&$nativeCalls): void {
                $nativeCalls++;
                $adder->withAttributes(['name' => 'Native configured name']);
            });
        $receipt = app(MediaOs::class)->store(MediaSource::bytes('contract bytes', 'Contract.TXT'),
            new MediaWrite(adapter: 'spatie', record: $record, spatie: $options, field: 'contract'));
        $this->assertSame('collection-media', $receipt->disk);
        $this->assertSame('contract bytes', Storage::disk($receipt->disk)->get($receipt->path));
        $this->assertSame('conversion-media', $receipt->native->conversions_disk);
        $this->assertSame('Native configured name', $receipt->native->name);
        $this->assertSame('contract.txt', $receipt->native->file_name);
        $this->assertSame(7, $receipt->native->order_column);
        $this->assertSame(42, $receipt->native->getCustomProperty('custom.owner'));
        $this->assertSame('contract', $receipt->native->getCustomProperty('flex_capture.field'));
        $this->assertSame(1, $nativeCalls);
    }

    public function test_explicit_disk_beats_collection_and_uuid_lookup_is_owned(): void
    {
        $owner = MediaOsRecord::create();
        $stranger = MediaOsRecord::create();
        $receipt = app(MediaOs::class)->store(MediaSource::bytes('owned content', 'owned.txt'),
            new MediaWrite(adapter: 'spatie', disk: 'explicit-media', record: $owner,
                spatie: new SpatieOptions(collection: 'documents')));
        $this->assertSame('explicit-media', $receipt->disk);
        $this->assertNotNull(app(OwnedMedia::class)->find($owner, $receipt->identifier, 'documents'));
        $this->assertNull(app(OwnedMedia::class)->find($stranger, $receipt->identifier, 'documents'));
        $this->assertNull(app(OwnedMedia::class)->find($owner, $receipt->identifier, 'other'));
    }

    public function test_rejects_unsaved_create_record_and_rolls_back_rejected_edit_upload(): void
    {
        $source = MediaSource::bytes('test content', 'test.txt');
        try {
            app(MediaOs::class)->store($source, new MediaWrite(adapter: 'spatie', record: new MediaOsRecord));
            $this->fail('Missing create record must fail.');
        } catch (MediaFailure $failure) {
            $this->assertSame('spatie_record_missing', $failure->reason);
        }
        $record = MediaOsRecord::create();
        $scans = 0;
        MediaCaptureOs::registerVirusScanCallback(function (string $path) use (&$scans): bool {
            $this->assertFileExists($path);

            return ++$scans === 1;
        });
        try {
            app(MediaOs::class)->store($source, new MediaWrite(adapter: 'spatie', record: $record,
                spatie: new SpatieOptions(collection: 'documents'), policy: new MediaPolicy(quarantineDisk: 'quarantine-media')));
            $this->fail('Post-persist rejection must fail.');
        } catch (MediaFailure $failure) {
            $this->assertSame('scan_rejected', $failure->reason);
        }
        $this->assertSame(0, $record->media()->count());
        $this->assertCount(1, Storage::disk('quarantine-media')->allFiles());
        $this->assertSame([], Storage::disk('collection-media')->allFiles());
    }

    public function test_create_lease_binds_after_record_save_and_repeated_submit_restores_native_receipt(): void
    {
        $repository = new StageRepository(new StageOptions(disk: 'media-os'));
        $staging = new MediaStaging($repository, app(MediaOs::class), app(MediaRegistry::class));
        $options = new SpatieOptions(collection: 'documents');
        $lease = $staging->stage(MediaSource::bytes('create recording', 'voice.txt'),
            new MediaWrite(adapter: 'spatie', spatie: $options, field: 'voice'), 'tenant:1:user:7');
        $this->assertSame(0, Media::query()->count());
        $record = MediaOsRecord::create();
        $intent = new MediaWrite(adapter: 'spatie', record: $record, spatie: $options, field: 'voice');
        $first = $staging->commit($lease->id, 'tenant:1:user:7', $intent);
        $second = $staging->commit($lease->id, 'tenant:1:user:7', $intent);
        $this->assertSame($first->identifier, $second->identifier);
        $this->assertInstanceOf(Media::class, $second->native);
        $this->assertSame(1, $record->media()->count());
        $this->assertFalse(Storage::disk('media-os')->exists($repository->payloadPath($lease->id)));
        try {
            $staging->commit($lease->id, 'tenant:1:user:7', new MediaWrite(adapter: 'spatie', record: MediaOsRecord::create(), spatie: $options, field: 'voice'));
            $this->fail('A committed create lease must remain bound to its first record.');
        } catch (MediaFailure $failure) {
            $this->assertSame('stage_target_mismatch', $failure->reason);
        }
    }

    public function test_native_failure_after_row_creation_compensates_only_the_new_operation(): void
    {
        $record = MediaOsRecord::create();
        $intent = new MediaWrite(adapter: 'spatie', record: $record, spatie: new SpatieOptions(collection: 'documents'));
        $existing = app(MediaOs::class)->store(MediaSource::bytes('keep me', 'keep.txt'), $intent);
        Media::created(static function (Media $media) use ($existing): void {
            if ((string) $media->uuid !== $existing->identifier) {
                throw new \RuntimeException('Native listener unavailable after row creation.');
            }
        });
        try {
            app(MediaOs::class)->store(MediaSource::bytes('new upload', 'new.txt'), $intent);
            $this->fail('Native event failure must fail the write.');
        } catch (MediaFailure $failure) {
            $this->assertSame('write_failed', $failure->reason);
            $this->assertFalse($failure->cleanupFailed);
        }
        $this->assertSame(1, $record->media()->count());
        $this->assertSame('keep me', Storage::disk($existing->disk)->get($existing->path));
    }

    public function test_native_callback_preserves_custom_configuration_and_owned_stamp(): void
    {
        $record = MediaOsRecord::create();
        $options = new SpatieOptions(collection: 'documents', configure: static function (FileAdder $adder): void {
            $adder->withCustomProperties(['application' => 'native callback', 'flex_capture' => ['correlation_id' => 'invalid']]);
            $adder->withAttributes(['name' => 'Native name']);
        });
        $receipt = app(MediaOs::class)->store(MediaSource::bytes('native callback', 'test.txt'), new MediaWrite(adapter: 'spatie', record: $record, spatie: $options));
        $this->assertSame('native callback', $receipt->native->getCustomProperty('application'));
        $this->assertSame($receipt->operationId, $receipt->native->getCustomProperty('flex_capture.correlation_id'));
        $this->assertSame('Native name', $receipt->native->name);
    }

    public function test_legacy_pipeline_uses_real_spatie_and_preserves_borrowed_source(): void
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'real native stream');
        rewind($stream);
        try {
            $record = MediaOsRecord::create();
            $payload = MediaPayload::fromStream($stream, 'stream.txt');
            $reference = FlexMedia::persist($payload, new MediaContext(kind: MediaKind::Upload,
                driver: MediaStorageDriver::Spatie, record: $record, collection: 'documents'));
            $this->assertInstanceOf(SpatieMediaRef::class, $reference);
            $this->assertSame('real native stream', Storage::disk($reference->disk)->get($reference->path));
            $this->assertTrue(is_resource($stream));
            $this->assertSame(1, $record->media()->count());
        } finally {
            fclose($stream);
        }
    }

    public function test_signature_sink_keeps_disk_token_as_primary_state_with_real_has_media(): void
    {
        $svg = SignatureSvg::normalize('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 320"><path d="M10,20 L30,40" fill="none" stroke="#18181b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>');
        $this->assertIsString($svg);
        $record = MediaOsRecord::create();
        $token = SignatureStorage::store($svg, 'signatures', 'media-os');
        $uuid = SignatureStorage::sinkToSpatie($svg, $record, 'signatures', 'media-os', 'signature');
        $this->assertStringStartsWith(SignatureStorage::TOKEN_PREFIX, $token);
        $this->assertIsString($uuid);
        $this->assertSame(1, $record->media()->where('uuid', $uuid)->count());
        $this->assertStringContainsString('<svg', SignatureStorage::resolve($token, 'media-os'));
    }

    public function test_native_upload_bridge_streams_bytes_and_never_resolves_other_owner_uuid(): void
    {
        if (! class_exists(SpatieMediaLibraryFileUpload::class)) {
            $this->markTestSkipped('Optional Filament Spatie Media Library plugin is not installed.');
        }
        $record = MediaOsRecord::create();
        $field = FlexSpatieMediaLibraryFileUpload::make('attachment')->collection('documents')->disk('media-os')->visibility('public');
        FormSchema::make()->model($record)->components([$field])->getComponents();
        $file = \Mockery::mock(TemporaryUploadedFile::class);
        $file->shouldReceive('readStream')->andReturnUsing(static function () {
            $stream = fopen('php://temp', 'w+b');
            fwrite($stream, 'native upload bytes');
            rewind($stream);

            return $stream;
        });
        $file->shouldReceive('getClientOriginalName')->andReturn('upload.txt');
        $file->shouldReceive('getClientOriginalExtension')->andReturn('txt');
        $file->shouldReceive('hashName')->andReturn('uploaded-document.txt');
        $file->shouldNotReceive('get');
        $uuid = SpatieMediaCaptureAdapter::saveUploadedFile($field, $file, $record);
        $this->assertIsString($uuid);
        $payload = SpatieMediaCaptureAdapter::resolveUploadedFilePayload($field, $uuid);
        $this->assertNotNull($payload);
        $this->assertSame(19, $payload['size']);
        $this->assertNotNull($payload['url']);
        MediaCaptureOs::registerSignedUploadUrlResolver(static fn (string $disk, string $path): string => 'https://signed.test/'.$disk.'/'.$path);
        $native = app(OwnedMedia::class)->find($record, $uuid, 'documents');
        $signed = SpatieMediaCaptureAdapter::resolveUploadedFilePayload($field, $uuid);
        $this->assertSame('https://signed.test/media-os/'.$native->getPathRelativeToRoot(), $signed['url']);
        $foreign = FlexSpatieMediaLibraryFileUpload::make('attachment')->collection('documents');
        FormSchema::make()->model(MediaOsRecord::create())->components([$foreign])->getComponents();
        $this->assertNull(SpatieMediaCaptureAdapter::resolveUploadedFilePayload($foreign, $uuid));
        $wrongCollection = FlexSpatieMediaLibraryFileUpload::make('attachment')->collection('private-documents');
        FormSchema::make()->model($record)->components([$wrongCollection])->getComponents();
        $this->assertNull(SpatieMediaCaptureAdapter::resolveUploadedFilePayload($wrongCollection, $uuid));
    }

    public function test_native_custom_media_paths_and_real_conversion_disk_are_used(): void
    {
        config()->set('media-library.media_model', MediaOsCustomMedia::class);
        config()->set('media-library.path_generator', MediaOsPathGenerator::class);
        // This test switches the model after boot; emulate the provider's native observer registration.
        MediaOsCustomMedia::observe(app(MediaObserver::class));
        $image = imagecreatetruecolor(24, 24);
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        $this->assertIsString($png);
        $record = MediaOsRecord::create();
        $intent = new MediaWrite(adapter: 'spatie', record: $record,
            spatie: new SpatieOptions(collection: 'documents', conversionsDisk: 'conversion-media'));
        $receipt = app(MediaOs::class)->store(MediaSource::bytes($png, 'image.png'), $intent);
        $this->assertInstanceOf(MediaOsCustomMedia::class, $receipt->native);
        $this->assertStringStartsWith('owned/'.$receipt->identifier.'/', $receipt->path);
        $this->assertTrue($receipt->native->hasGeneratedConversion('small'));
        $this->assertTrue(Storage::disk('conversion-media')->exists($receipt->native->getPathRelativeToRoot('small')));
        $this->assertSame($png, Storage::disk($receipt->disk)->get($receipt->path));
        app(MediaOs::class)->delete($receipt, $intent);
        $this->assertSame([], Storage::disk('collection-media')->allFiles());
        $this->assertSame([], Storage::disk('conversion-media')->allFiles());
    }

    public function test_native_collection_acceptance_and_single_file_rules_remain_authoritative(): void
    {
        $record = MediaOsRecord::create();
        $intent = new MediaWrite(adapter: 'spatie', record: $record, spatie: new SpatieOptions(collection: 'portrait'));
        $first = app(MediaOs::class)->store(MediaSource::bytes('first', 'first.txt'), $intent);
        $second = app(MediaOs::class)->store(MediaSource::bytes('second', 'second.txt'), $intent);
        $this->assertSame(1, $record->media()->where('collection_name', 'portrait')->count());
        $this->assertNull(app(OwnedMedia::class)->find($record, $first->identifier, 'portrait'));
        $this->assertFalse(Storage::disk($first->disk)->exists($first->path));
        $this->assertSame('second', Storage::disk($second->disk)->get($second->path));
        try {
            app(MediaOs::class)->store(MediaSource::bytes('text masquerading as image', 'spoofed.png'),
                new MediaWrite(adapter: 'spatie', record: $record, spatie: new SpatieOptions(collection: 'images')));
            $this->fail('Native collection must reject the actual text MIME.');
        } catch (MediaFailure $failure) {
            $this->assertSame('write_failed', $failure->reason);
            $this->assertFalse($failure->cleanupFailed);
        }
        $this->assertSame(1, $record->media()->count());
    }

    public function test_private_native_preview_does_not_fall_back_to_public_url_when_signing_fails(): void
    {
        config()->set('media-library.media_model', MediaOsUnsignedMedia::class);
        MediaOsUnsignedMedia::observe(app(MediaObserver::class));
        MediaCaptureOs::registerSignedUploadUrlResolver(static fn (): ?string => null);
        MediaOsUnsignedMedia::$publicUrlCalls = 0;
        $record = MediaOsRecord::create();
        $receipt = app(MediaOs::class)->store(MediaSource::bytes('private', 'private.txt'),
            new MediaWrite(adapter: 'spatie', record: $record, spatie: new SpatieOptions(collection: 'documents')));
        $field = FlexSpatieMediaLibraryFileUpload::make('private')->collection('documents')->visibility('private');
        FormSchema::make()->model($record)->components([$field])->getComponents();
        $payload = SpatieMediaCaptureAdapter::resolveUploadedFilePayload($field, $receipt->identifier);
        $this->assertNotNull($payload);
        $this->assertNull($payload['url']);
        $this->assertSame(0, MediaOsUnsignedMedia::$publicUrlCalls);
    }

    public function test_native_field_filter_is_authoritative_for_preview_uuid_resolution(): void
    {
        $record = MediaOsRecord::create();
        $receipt = app(MediaOs::class)->store(MediaSource::bytes('filtered file', 'file.txt'),
            new MediaWrite(adapter: 'spatie', record: $record, spatie: new SpatieOptions(collection: 'documents', customProperties: ['gallery' => 1])));
        $field = FlexSpatieMediaLibraryFileUpload::make('gallery')->collection('documents')
            ->filterMediaUsing(fn (Collection $media): Collection => $media->filter(fn (Media $item): bool => $item->getCustomProperty('gallery') === 2));
        FormSchema::make()->model($record)->components([$field])->getComponents();
        $this->assertNull(SpatieMediaCaptureAdapter::resolveUploadedFilePayload($field, $receipt->identifier));
        $this->assertSame(1, $record->media()->count());
    }

    public function test_native_table_and_infolist_preserve_filters_collections_and_private_signing_failures(): void
    {
        config()->set('media-library.media_model', MediaOsUnsignedMedia::class);
        MediaOsUnsignedMedia::observe(app(MediaObserver::class));
        MediaOsUnsignedMedia::$publicUrlCalls = 0;
        MediaCaptureOs::registerSignedUploadUrlResolver(static fn (): ?string => null);
        $record = MediaOsRecord::create();
        $first = app(MediaOs::class)->store(MediaSource::bytes('private', 'first.txt'), new MediaWrite(adapter: 'spatie', record: $record,
            spatie: new SpatieOptions(collection: 'documents', customProperties: ['gallery' => 1])));
        $second = app(MediaOs::class)->store(MediaSource::bytes('private', 'second.txt'), new MediaWrite(adapter: 'spatie', record: $record,
            spatie: new SpatieOptions(collection: 'documents', customProperties: ['gallery' => 2])));
        $filter = fn (Collection $media): Collection => $media->filter(fn (Media $item): bool => $item->getCustomProperty('gallery') === 1);
        $tableOwner = \Mockery::mock(HasTable::class);
        $tableOwner->shouldReceive('getTableRecordKey')->with($record)->andReturn((string) $record->getKey());
        $table = Table::make($tableOwner)->query(MediaOsRecord::query());
        $column = FlexSpatieMediaLibraryImageColumn::make('images')->table($table)->record($record)->collection('documents')->visibility('private')->filterMediaUsing($filter);
        $this->assertSame([$first->identifier], $column->getState());
        $this->assertNull($column->getImageUrl($first->identifier));
        $this->assertNull($column->getImageUrl($second->identifier));
        $entry = FlexSpatieMediaLibraryImageEntry::make('images')->collection('documents')->visibility('private')->filterMediaUsing($filter);
        FormSchema::make()->model($record)->components([$entry])->getComponents();
        $this->assertSame([$first->identifier], $entry->getState());
        $this->assertNull($entry->getImageUrl($first->identifier));
        $this->assertNull($entry->getImageUrl($second->identifier));
        $this->assertSame(0, MediaOsUnsignedMedia::$publicUrlCalls);
        $all = FlexSpatieMediaLibraryImageColumn::make('all')->table($table)->record($record)->allCollections();
        $this->assertSame([$first->identifier, $second->identifier], $all->getState());
    }
}

final class MediaOsRecord extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'media_os_records';

    protected $guarded = [];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')->useDisk('collection-media');
        $this->addMediaCollection('portrait')->useDisk('collection-media')->singleFile();
        $this->addMediaCollection('images')->acceptsMimeTypes(['image/png']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('small')->width(12)->height(12)->nonQueued();
    }
}

final class MediaOsCustomMedia extends Media {}

final class MediaOsPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return 'owned/'.$media->uuid.'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive/';
    }
}

final class MediaOsUnsignedMedia extends Media
{
    public static int $publicUrlCalls = 0;

    public function getTemporaryUrl(?\DateTimeInterface $expiration = null, string $conversionName = '', array $options = []): string
    {
        throw new \RuntimeException('Signing is unavailable.');
    }

    public function getUrl(string $conversionName = ''): string
    {
        self::$publicUrlCalls++;

        return parent::getUrl($conversionName);
    }
}

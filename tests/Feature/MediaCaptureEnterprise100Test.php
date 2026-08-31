<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Bjanczak\FilamentFlexFields\Support\Enterprise\ObservabilityHooks;
use Bjanczak\FilamentFlexFields\Support\Media\BarcodeValue;
use Bjanczak\FilamentFlexFields\Support\Media\CircuitBreakerTranscriptionProvider;
use Bjanczak\FilamentFlexFields\Support\Media\LaravelStorageSignedUrlResolver;
use Bjanczak\FilamentFlexFields\Support\Media\MediaCaptureOs;
use Bjanczak\FilamentFlexFields\Support\Media\NullVoiceNoteTranscription;
use Bjanczak\FilamentFlexFields\Support\Media\SpatieMediaCaptureAdapter;
use Bjanczak\FilamentFlexFields\Support\Media\VoiceNoteTranscriptionInterface;
use Bjanczak\FilamentFlexFields\Tests\Support\FakeCaptureMediaModel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    MediaCaptureOs::resetRuntimeState();
    ObservabilityHooks::clear();
    Cache::flush();
});

it('boots default null transcription with circuit breaker wrapper', function () {
    MediaCaptureOs::bootFromConfig();

    $transcriber = MediaCaptureOs::transcriptionInterface();

    expect($transcriber)->not->toBeNull()
        ->and($transcriber)->toBeInstanceOf(CircuitBreakerTranscriptionProvider::class);
});

it('transcribes via null provider without throwing', function () {
    MediaCaptureOs::registerTranscriptionInterface(new NullVoiceNoteTranscription);

    expect(MediaCaptureOs::transcriptionInterface()?->transcribe('local', 'voice-notes/x.webm'))->toBeNull();
});

it('opens transcription circuit breaker after repeated failures', function () {
    $failing = new class implements VoiceNoteTranscriptionInterface
    {
        public function transcribe(string $disk, string $path, array $context = []): ?string
        {
            throw new RuntimeException('upstream down');
        }
    };

    $provider = new CircuitBreakerTranscriptionProvider($failing, 2, 30);

    expect($provider->transcribe('local', 'a.webm'))->toBeNull()
        ->and($provider->transcribe('local', 'a.webm'))->toBeNull()
        ->and($provider->transcribe('local', 'a.webm'))->toBeNull();
});

it('auto registers laravel storage signed url resolver from config', function () {
    config(['filament-flex-fields.media_capture.auto_signed_urls' => true]);
    MediaCaptureOs::bootFromConfig();

    expect(MediaCaptureOs::signedUploadUrlResolver())->not->toBeNull();
});

it('resolves signed urls through laravel storage resolver when driver supports temporary urls', function () {
    Storage::fake('local');
    Storage::disk('local')->put('uploads/doc.pdf', 'content');

    $resolver = new LaravelStorageSignedUrlResolver;
    $url = $resolver('local', 'uploads/doc.pdf');

    expect($url)->toBeString()->toContain('uploads/doc.pdf');
});

it('records barcode capture observability events from barcode value dto', function () {
    $events = [];
    ObservabilityHooks::on(ObservabilityHooks::EVENT_BARCODE_CAPTURE, function (array $payload) use (&$events): void {
        $events[] = $payload;
    });

    MediaCaptureOs::recordBarcodeCapture(new BarcodeValue('1234567890', 'ean_13'), 'sku_scan');

    expect($events)->toHaveCount(1)
        ->and($events[0]['field'])->toBe('sku_scan')
        ->and($events[0]['value'])->toBe('1234567890')
        ->and($events[0]['format'])->toBe('ean_13');
});

it('normalizes barcode value dto from array payload', function () {
    $barcode = BarcodeValue::fromArray(['value' => 'SKU-9', 'format' => 'code_128']);

    expect($barcode)->not->toBeNull()
        ->and($barcode?->toArray())->toBe(['value' => 'SKU-9', 'format' => 'code_128']);
});

it('resolves spatie signed url override via getPathRelativeToRoot', function () {
    MediaCaptureOs::registerSignedUploadUrlResolver(
        fn (string $disk, string $path, array $context): string => "https://signed.test/{$disk}/{$path}",
    );

    $record = new FakeCaptureMediaModel;
    $field = FlexSpatieMediaLibraryFileUpload::make('attachment');
    $field->collection('documents');
    $field->disk('local');
    $field->visibility('public');
    $field->model($record);

    $media = $record->addMediaFromString('pdf')->toMediaCollection('documents', 'local');

    $payload = SpatieMediaCaptureAdapter::resolveUploadedFilePayload($field, $media->uuid);

    expect($payload)->not->toBeNull()
        ->and($payload['url'])->toBe('https://signed.test/local/media/documents/upload.bin');
});

it('records retention prune audit events from prune command', function () {
    Storage::fake('local');
    config([
        'filament-flex-fields.media_capture.disk' => 'local',
        'filament-flex-fields.media_capture.directories' => [
            'temp_captures' => ['livewire-tmp'],
        ],
        'filament-flex-fields.media_capture.retention' => [
            'temp_captures' => ['enabled' => true, 'days' => 7],
        ],
    ]);

    Storage::disk('local')->put('livewire-tmp/old.tmp', 'x');
    touch(Storage::disk('local')->path('livewire-tmp/old.tmp'), now()->subDays(10)->getTimestamp());

    $events = [];
    ObservabilityHooks::on(ObservabilityHooks::EVENT_RETENTION_PRUNE, function (array $payload) use (&$events): void {
        $events[] = $payload;
    });

    Artisan::call('flex-fields:prune-capture-media', ['--category' => ['temp_captures']]);

    expect($events)->not->toBeEmpty()
        ->and($events[0]['category'])->toBe('temp_captures')
        ->and($events[0]['dry_run'])->toBeFalse();
});

it('never persists cvv and strips pan when never_store_pan is enabled', function () {
    config(['filament-flex-fields.media_capture.pci.never_store_pan' => true]);

    $field = CreditCardField::make('payment');

    $dehydrated = $field->dehydrateStateForStorage([
        'number' => '4242424242424242',
        'name' => 'Jane Doe',
        'expiry' => '12/30',
        'cvv' => '456',
    ]);

    expect($dehydrated)->toBe([
        'last4' => '4242',
        'name' => 'Jane Doe',
        'expiry' => '12/30',
    ])->and(array_keys($dehydrated))->not->toContain('cvv', 'number');
});

it('tokenizes pan when callback is registered', function () {
    config(['filament-flex-fields.media_capture.pci.never_store_pan' => true]);
    MediaCaptureOs::registerTokenizeCreditCardCallback(fn (string $pan): string => 'tok_'.$pan);

    $field = CreditCardField::make('payment');

    $dehydrated = $field->dehydrateStateForStorage([
        'number' => '4242424242424242',
        'name' => 'Jane Doe',
        'expiry' => '12/30',
        'cvv' => '456',
    ]);

    expect($dehydrated['token'])->toBe('tok_4242424242424242')
        ->and($dehydrated)->not->toHaveKey('number')
        ->and($dehydrated)->not->toHaveKey('cvv');
});

it('uses geocoding rate limit config key in proxy controller', function () {
    config([
        'filament-flex-fields.geocoding.rate_limit_per_minute' => 99,
        'filament-flex-fields.mapbox.rate_limit_per_minute' => 10,
    ]);

    expect((int) config('filament-flex-fields.geocoding.rate_limit_per_minute'))->toBe(99);
});

it('lists retention and barcode events in observability catalog', function () {
    expect(ObservabilityHooks::listEvents())->toContain(
        ObservabilityHooks::EVENT_RETENTION_PRUNE,
        ObservabilityHooks::EVENT_BARCODE_CAPTURE,
    );
});

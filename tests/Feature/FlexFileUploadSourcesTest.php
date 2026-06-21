<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\FileUploadSource;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexImageUpload;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadImportConstraints;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadImportException;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadRemoteImporter;
use Bjanczak\FilamentFlexFields\Support\Http\SafeRemoteUrlValidator;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;

/**
 * @return non-empty-string
 */
function createTestJpegForUrlImport(int $width = 320, int $height = 240): string
{
    $image = imagecreatetruecolor($width, $height);

    ob_start();
    imagejpeg($image, null, 90);
    $contents = ob_get_clean() ?: '';

    imagedestroy($image);

    return $contents !== '' ? $contents : throw new RuntimeException('Unable to create test JPEG.');
}

/**
 * @return array{fileKey: non-empty-string, temporaryFile: TemporaryUploadedFile}
 */
function createFlexFileUploadTemporaryJpeg(int $width = 320, int $height = 240): array
{
    $jpeg = createTestJpegForUrlImport($width, $height);
    $filename = (string) Str::uuid().'.jpg';

    FileUploadConfiguration::storage()->put(
        FileUploadConfiguration::path($filename),
        $jpeg,
    );

    FileUploadConfiguration::storage()->put(
        FileUploadConfiguration::path($filename).'.json',
        json_encode([
            'name' => 'webcam-test.jpg',
            'type' => 'image/jpeg',
            'size' => strlen($jpeg),
        ], JSON_THROW_ON_ERROR),
    );

    return [
        'fileKey' => (string) Str::uuid(),
        'temporaryFile' => TemporaryUploadedFile::createFromLivewire($filename),
    ];
}

it('exposes upload source tab configuration on flex file upload fields', function () {
    $field = FlexImageUpload::make('photo')
        ->allowUrlUpload()
        ->allowWebcamUpload()
        ->uploadSourceTabsVariant('ghost')
        ->uploadSourceTabsColor('primary');

    expect($field->hasUploadSourceTabs())->toBeTrue()
        ->and($field->shouldAllowUrlUpload())->toBeTrue()
        ->and($field->shouldAllowWebcamUpload())->toBeTrue()
        ->and($field->getUploadSourceTabsVariant())->toBe('ghost')
        ->and($field->getUploadSourceTabsColor())->toBe('primary')
        ->and($field->getUploadSourceTabKeys())->toBe([
            FileUploadSource::File->value,
            FileUploadSource::Url->value,
            FileUploadSource::Webcam->value,
        ])
        ->and($field->getWrapperClasses())->toContain('fff-flex-file-upload--source-tabs');
});

it('does not render upload source tabs when optional sources are disabled', function () {
    TestableTranslatableForm::$formSchema = [
        FlexFileUpload::make('attachment')->withRecommendedDefaults(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->not->toContain('fff-flex-file-upload__source-tabs')
        ->not->toContain('fff-flex-file-upload--source-tabs');

    expect(substr_count($html, '<div'))
        ->toBe(substr_count($html, '</div>'));
});

it('defaults upload source tabs to the standard segment-control variant', function () {
    $field = FlexImageUpload::make('photo')
        ->allowUrlUpload()
        ->allowWebcamUpload();

    expect($field->getUploadSourceTabsVariant())->toBe('default')
        ->and($field->getUploadSourceTabsColor())->toBeNull();
});

it('renders upload source tabs and panels when optional sources are enabled', function () {
    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo')
            ->allowUrlUpload()
            ->allowWebcamUpload(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fff-flex-file-upload__source-tabs')
        ->not->toContain('fff-segment-track--ghost')
        ->toContain('fff-flex-file-upload__source-panel--url')
        ->toContain('fff-flex-file-upload__url-dropzone')
        ->toContain('fff-flex-file-upload__source-dropzone')
        ->toContain('fff-flex-file-upload__source-panel--webcam')
        ->toContain('fff-flex-file-upload__webcam-placeholder')
        ->toContain('fff-flex-file-upload-webcam-modal')
        ->toContain('openWebcamModal()')
        ->toContain('confirmWebcamPhoto()')
        ->toContain('retakeWebcamPhoto()')
        ->toContain('webcamRemove')
        ->toContain('onWebcamModalOpened')
        ->toContain('fff-flex-file-upload__webcam-dock--solo')
        ->toContain('fff-segment-track')
        ->toContain('callSchemaComponentMethod');

    expect(substr_count($html, '<div'))
        ->toBe(substr_count($html, '</div>'));
});

it('blocks unsafe remote urls during import', function () {
    $validator = app(SafeRemoteUrlValidator::class);

    expect($validator->isAllowedHttpUrl('http://127.0.0.1/image.jpg'))->toBeFalse()
        ->and($validator->isAllowedHttpUrl('https://example.com/image.jpg'))->toBeTrue();
});

it('prepares a remote image for the filepond pipeline without persisting to the field disk', function () {
    Storage::fake('local');

    $this->mock(SafeRemoteUrlValidator::class)
        ->shouldReceive('isAllowedHttpUrl')
        ->andReturn(true);

    Http::fake([
        'https://cdn.example.com/*' => Http::response(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='),
            200,
            ['Content-Type' => 'image/png'],
        ),
    ]);

    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo')
            ->allowUrlUpload()
            ->disk('local')
            ->directory('tests/url-imports')
            ->maxSize(512),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    /** @var FlexImageUpload $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('photo');

    $payload = $field->importUploadedFileFromUrl('https://cdn.example.com/photo.png');

    expect($payload)
        ->toBeArray()
        ->and($payload['name'])->toBe('photo.png')
        ->and($payload['type'])->toBe('image/png')
        ->and($payload['previewUrl'])->toBeString()->not->toBe('')
        ->and($payload['stagingFilename'])->toBeString()->not->toBe('');

    expect($field->getRawState())->toBeEmpty();

    Storage::disk('local')->assertMissing('tests/url-imports/photo.png');

    $field->discardAlternateSourceStagingUpload($payload['stagingFilename']);
});

it('exposes a fetchable preview url for staged url imports', function () {
    Storage::fake('local');

    $this->mock(SafeRemoteUrlValidator::class)
        ->shouldReceive('isAllowedHttpUrl')
        ->andReturn(true);

    Http::fake([
        'https://cdn.example.com/*' => Http::response(
            createTestJpegForUrlImport(120, 90),
            200,
            ['Content-Type' => 'image/jpeg'],
        ),
    ]);

    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo')
            ->allowUrlUpload()
            ->disk('local')
            ->directory('tests/url-imports')
            ->maxSize(512),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    /** @var FlexImageUpload $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('photo');

    $payload = $field->importUploadedFileFromUrl('https://cdn.example.com/preview.jpg');

    $previewPath = parse_url($payload['previewUrl'], PHP_URL_PATH) ?: '';
    $previewQuery = parse_url($payload['previewUrl'], PHP_URL_QUERY);
    $previewRequestUrl = $previewQuery ? "{$previewPath}?{$previewQuery}" : $previewPath;

    $previewResponse = $this->get($previewRequestUrl);

    expect($previewResponse->isSuccessful())->toBeTrue()
        ->and($previewResponse->headers->get('Content-Type'))->toContain('image/jpeg')
        ->and((int) $previewResponse->headers->get('Content-Length'))->toBeGreaterThan(0);

    $field->discardAlternateSourceStagingUpload($payload['stagingFilename']);
});

it('processes alternate source uploads through the flex upload pipeline on form save', function () {
    Storage::fake('local');

    $this->mock(SafeRemoteUrlValidator::class)
        ->shouldReceive('isAllowedHttpUrl')
        ->andReturn(true);

    Http::fake([
        'https://cdn.example.com/*' => Http::response(
            createTestJpegForUrlImport(200, 150),
            200,
            ['Content-Type' => 'image/jpeg'],
        ),
    ]);

    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo')
            ->allowUrlUpload()
            ->disk('local')
            ->directory('tests/url-imports')
            ->maxSize(512)
            ->optimizeImages()
            ->maxImageWidth(100),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    /** @var FlexImageUpload $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('photo');

    $payload = $field->importUploadedFileFromUrl('https://cdn.example.com/large-photo.jpg');
    $temporaryFile = TemporaryUploadedFile::createFromLivewire($payload['stagingFilename']);

    $field->state([(string) Str::uuid() => $temporaryFile]);
    $field->saveUploadedFiles();

    $storedPath = array_values($field->getRawState() ?? [])[0] ?? null;

    expect($storedPath)->toBeString();

    Storage::disk('local')->assertExists($storedPath);

    $absolutePath = Storage::disk('local')->path($storedPath);
    [$width, $height] = getimagesize($absolutePath);

    expect($width)->toBeLessThanOrEqual(100)
        ->and($height)->toBeLessThanOrEqual(100);
});

it('exposes staged remote images through preview metadata for filepond', function () {
    Storage::fake('local');

    Http::fake([
        'https://atlantahumane.org/*' => Http::response(
            base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP/bAEMAAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/bAEMBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/AABEIAQAAAQMBIgACEQEDEQH/xAAVAAEBAAAAAAAAAAAAAAAAAAAACv/EABQQAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k='),
            200,
            ['Content-Type' => 'image/jpeg'],
        ),
    ]);

    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo')
            ->allowUrlUpload()
            ->disk('local')
            ->directory('tests/url-imports')
            ->maxSize(512),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    /** @var FlexImageUpload $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('photo');

    $payload = $field->importUploadedFileFromUrl('https://atlantahumane.org/wp-content/uploads/2025/11/dog-hero.jpg');

    expect($payload)
        ->toBeArray()
        ->and($payload['type'])->toBe('image/jpeg')
        ->and($payload['name'])->toEndWith('.jpg')
        ->and($payload['previewUrl'])->toBeString()->not->toBe('')
        ->and($payload['stagingFilename'])->toBeString()->not->toBe('');

    expect($field->getRawState())->toBeEmpty();

    $field->discardAlternateSourceStagingUpload($payload['stagingFilename']);
});

it('prepares remote images through livewire schema component methods like the browser', function () {
    Storage::fake('local');

    Http::fake([
        'https://atlantahumane.org/*' => Http::response(
            base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP/bAEMAAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/bAEMBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/AABEIAQAAAQMBIgACEQEDEQH/xAAVAAEBAAAAAAAAAAAAAAAAAAAACv/EABQQAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCwAA8A/9k='),
            200,
            ['Content-Type' => 'image/jpeg'],
        ),
    ]);

    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo')
            ->allowUrlUpload()
            ->disk('local')
            ->directory('tests/url-imports')
            ->maxSize(512),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    $componentKey = $livewire->instance()->getSchema('form')->getComponent('photo')->getKey();

    $payload = $livewire->instance()->callSchemaComponentMethod(
        $componentKey,
        'importUploadedFileFromUrl',
        ['url' => 'https://atlantahumane.org/wp-content/uploads/2025/11/dog-hero.jpg'],
    );

    /** @var FlexImageUpload $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('photo');

    expect($field->getRawState())->toBeEmpty();

    expect($payload)
        ->toBeArray()
        ->and($payload['type'])->toBe('image/jpeg')
        ->and($payload['name'])->toEndWith('.jpg')
        ->and($payload['previewUrl'])->toBeString()->not->toBe('')
        ->and($payload['stagingFilename'])->toBeString()->not->toBe('');

    $field->discardAlternateSourceStagingUpload($payload['stagingFilename']);
});

it('rejects url import through livewire when url upload is disabled', function () {
    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo'),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    $componentKey = $livewire->instance()->getSchema('form')->getComponent('photo')->getKey();

    expect(fn () => $livewire->instance()->callSchemaComponentMethod(
        $componentKey,
        'importUploadedFileFromUrl',
        ['url' => 'https://atlantahumane.org/wp-content/uploads/2025/11/dog-hero.jpg'],
    ))->toThrow(ValidationException::class);
});

it('rejects remote imports that exceed configured mime restrictions', function () {
    $this->mock(SafeRemoteUrlValidator::class)
        ->shouldReceive('isAllowedHttpUrl')
        ->andReturn(true);

    Http::fake([
        'https://cdn.example.com/*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf']),
    ]);

    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo')
            ->imagesOnly()
            ->allowUrlUpload(),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    /** @var FlexImageUpload $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('photo');

    expect(fn () => $field->importUploadedFileFromUrl('https://cdn.example.com/document.pdf'))
        ->toThrow(ValidationException::class);
});

it('throws when importing from url without enabling the feature', function () {
    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo'),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    /** @var FlexImageUpload $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('photo');

    expect(fn () => $field->importUploadedFileFromUrl('https://cdn.example.com/photo.png'))
        ->toThrow(ValidationException::class);
});

it('validates imported remote files against constraints in the importer', function () {
    Http::fake([
        'https://cdn.example.com/*' => Http::response('not-an-image', 200, ['Content-Type' => 'text/plain']),
    ]);

    $importer = app(FileUploadRemoteImporter::class);

    expect(fn () => $importer->importFromUrl(
        'https://cdn.example.com/file.txt',
        new FileUploadImportConstraints(
            acceptedMimeTypes: ['image/*'],
            allowedExtensions: [],
            maxSizeKb: 512,
            rejectExecutables: true,
            imagesOnly: true,
        ),
    ))->toThrow(FileUploadImportException::class);
});

it('does not enable webcam capture for document-only uploads', function () {
    $field = FlexFileUpload::make('attachment')
        ->documentsOnly()
        ->allowWebcamUpload();

    expect($field->supportsWebcamCapture())->toBeFalse()
        ->and($field->shouldAllowWebcamUpload())->toBeFalse()
        ->and($field->getUploadSourceTabKeys())->toBe([
            FileUploadSource::File->value,
        ])
        ->and($field->hasUploadSourceTabs())->toBeFalse();
});

it('commits staged url imports into livewire state for filepond sync', function () {
    Storage::fake('local');

    $this->mock(SafeRemoteUrlValidator::class)
        ->shouldReceive('isAllowedHttpUrl')
        ->andReturn(true);

    Http::fake([
        'https://cdn.example.com/*' => Http::response(
            createTestJpegForUrlImport(180, 120),
            200,
            ['Content-Type' => 'image/jpeg'],
        ),
    ]);

    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo')
            ->allowUrlUpload()
            ->disk('local')
            ->directory('tests/url-imports')
            ->maxSize(512),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    /** @var FlexImageUpload $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('photo');

    $payload = $field->importUploadedFileFromUrl('https://cdn.example.com/commit-me.jpg');

    expect($field->getRawState())->toBeEmpty();

    $field->commitAlternateSourceStagingUpload($payload['stagingFilename']);

    expect($field->getRawState())->toHaveCount(1);

    $uploadedFiles = $field->getUploadedFiles();

    expect($uploadedFiles)->toBeArray()
        ->and(collect($uploadedFiles)->filter()->count())->toBe(1)
        ->and(collect($uploadedFiles)->first()['url'] ?? null)->toBeString()->not->toBe('');
});

it('exposes commit alternate source staging through livewire schema methods', function () {
    Storage::fake('local');

    $this->mock(SafeRemoteUrlValidator::class)
        ->shouldReceive('isAllowedHttpUrl')
        ->andReturn(true);

    Http::fake([
        'https://cdn.example.com/*' => Http::response(
            createTestJpegForUrlImport(96, 96),
            200,
            ['Content-Type' => 'image/jpeg'],
        ),
    ]);

    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo')
            ->allowUrlUpload()
            ->disk('local')
            ->directory('tests/url-imports')
            ->maxSize(512),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    $componentKey = $livewire->instance()->getSchema('form')->getComponent('photo')->getKey();

    $payload = $livewire->instance()->callSchemaComponentMethod(
        $componentKey,
        'importUploadedFileFromUrl',
        ['url' => 'https://cdn.example.com/schema-commit.jpg'],
    );

    $livewire->instance()->callSchemaComponentMethod(
        $componentKey,
        'commitAlternateSourceStagingUpload',
        ['stagingFilename' => $payload['stagingFilename']],
    );

    /** @var FlexImageUpload $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('photo');

    expect($field->getRawState())->toHaveCount(1)
        ->and(collect($field->getUploadedFiles())->filter()->count())->toBe(1);
});

it('processes webcam uploads through the flex upload pipeline on form save', function () {
    Storage::fake('local');

    ['temporaryFile' => $temporaryFile] = createFlexFileUploadTemporaryJpeg(
        width: 320,
        height: 240,
    );

    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('photo')
            ->allowWebcamUpload()
            ->disk('local')
            ->directory('tests/webcam-uploads')
            ->maxImageWidth(100)
            ->maxSize(5120),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class);
    /** @var FlexImageUpload $field */
    $field = $livewire->instance()->getSchema('form')->getComponent('photo');

    $field->state([(string) Str::uuid() => $temporaryFile]);
    $field->saveUploadedFiles();

    $storedPath = array_values($field->getRawState() ?? [])[0] ?? null;

    expect($storedPath)->toBeString();

    [$width] = getimagesize(Storage::disk('local')->path($storedPath));

    expect($width)->toBeLessThanOrEqual(100);
});

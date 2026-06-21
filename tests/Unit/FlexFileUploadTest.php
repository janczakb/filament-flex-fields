<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexFileUpload;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexImageUpload;
use Bjanczak\FilamentFlexFields\Support\FileUpload\ExecutableExtensionGuard;
use Bjanczak\FilamentFlexFields\Support\FileUpload\FileUploadMimePresets;
use Bjanczak\FilamentFlexFields\Support\FileUpload\ScopedDirectoryResolver;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundBuilder;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

it('defaults remove uploaded file button to the right', function () {
    $field = FlexFileUpload::make('attachment');

    expect($field->getRemoveUploadedFileButtonPosition())->toBe('right');
});

it('applies mime presets on flex file upload fields', function () {
    $documents = FlexFileUpload::make('attachment')->documentsOnly();
    $images = FlexImageUpload::make('photo')->imagesOnly();
    $spreadsheets = FlexFileUpload::make('sheet')->spreadsheetsOnly();

    expect($documents->getAcceptedFileTypes())->toBe(FileUploadMimePresets::documents())
        ->and($images->getAcceptedFileTypes())->toBe(FileUploadMimePresets::images())
        ->and($spreadsheets->getAcceptedFileTypes())->toBe(FileUploadMimePresets::spreadsheets());
});

it('registers flex file upload validation helpers', function () {
    $field = FlexFileUpload::make('attachment')
        ->allowedExtensions(['pdf', 'docx'])
        ->rejectExecutableFiles()
        ->maxTotalSizeKb(2048)
        ->minImageDimensions(800, 600)
        ->maxImageDimensions(4000, 4000);

    expect($field->getAllowedExtensions())->toBe(['pdf', 'docx'])
        ->and($field->shouldRejectExecutableFiles())->toBeTrue()
        ->and($field->getMaxTotalSizeKb())->toBe(2048)
        ->and($field->getFlexMinImageWidth())->toBe(800)
        ->and($field->getFlexMinImageHeight())->toBe(600)
        ->and($field->getFlexMaxImageWidth())->toBe(4000)
        ->and($field->getFlexMaxImageHeight())->toBe(4000);
});

it('resolves scoped upload directories for records and drafts', function () {
    $record = new class extends Model
    {
        protected $table = 'posts';

        public function getKey(): int
        {
            return 42;
        }
    };

    expect(ScopedDirectoryResolver::resolve('uploads', $record, 7))
        ->toBe('uploads/'.class_basename($record).'/42')
        ->and(ScopedDirectoryResolver::resolve('uploads', null, 7))
        ->toBe('uploads/drafts/7');
});

it('exposes metadata storage path configuration', function () {
    $field = FlexFileUpload::make('attachment')
        ->storeMetadataIn('attachment_meta');

    expect($field->getStoreMetadataInPath())->toBe('attachment_meta');
});

it('applies recommended defaults for file and image uploads', function () {
    $file = FlexFileUpload::make('attachment')->withRecommendedDefaults();
    $image = FlexImageUpload::make('photo')->withRecommendedDefaults();

    expect($file->getMaxSize())->toBe(5120)
        ->and($file->isDownloadable())->toBeTrue()
        ->and($file->isOpenable())->toBeTrue()
        ->and($file->shouldDeleteFileOnRemove())->toBeTrue()
        ->and($file->shouldDeleteReplacedFiles())->toBeTrue()
        ->and($file->getAcceptedFileTypes())->toBe(FileUploadMimePresets::documents())
        ->and($image->getAcceptedFileTypes())->toBe(FileUploadMimePresets::images());
});

it('blocks executable extensions via guard', function () {
    expect(ExecutableExtensionGuard::isBlocked('shell.php'))->toBeTrue()
        ->and(ExecutableExtensionGuard::isBlocked('.htaccess'))->toBeTrue()
        ->and(ExecutableExtensionGuard::isBlocked('report.pdf'))->toBeFalse();
});

it('supports avatar mode on flex image upload', function () {
    $field = FlexImageUpload::make('avatar')
        ->avatar()
        ->imageEditor()
        ->circleCropper();

    expect($field->isAvatar())->toBeTrue()
        ->and($field->hasImageEditor())->toBeTrue()
        ->and($field->hasCircleCropper())->toBeTrue()
        ->and($field->getWrapperClasses())->toContain('fff-flex-file-upload--avatar');
});

it('renders upload skeleton before filepond is ready', function (): void {
    TestableTranslatableForm::$formSchema = [
        FlexFileUpload::make('attachment')
            ->withRecommendedDefaults()
            ->uploadSummary()
            ->maxFiles(1)
            ->remainingSlotsLabel(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fff-flex-file-upload__stage')
        ->toContain('fff-flex-file-upload__skeleton')
        ->toContain('fff-flex-file-upload__skeleton-dropzone')
        ->toContain('fff-flex-file-upload__live')
        ->toContain("'is-ready': displayReady")
        ->not->toContain('fff-flex-file-upload__ssr');
});

it('renders avatar upload skeleton', function (): void {
    TestableTranslatableForm::$formSchema = [
        FlexImageUpload::make('avatar')
            ->avatar()
            ->emptyStateHint('Upload profile photo'),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fff-flex-file-upload__skeleton--avatar')
        ->toContain('fff-flex-file-upload__skeleton-dropzone')
        ->not->toContain('fff-flex-file-upload__skeleton-icon')
        ->toContain('Upload profile photo')
        ->not->toContain('fff-flex-file-upload__ssr');
});

it('registers flex file upload playground section', function () {
    $state = app(FlexFieldsPlaygroundBuilder::class)->defaultState();

    expect($state)->toHaveKeys([
        'file_upload__avatar',
        'file_upload__documents',
        'file_upload__images',
        'file_upload__multi',
        'file_upload__metadata',
        'file_upload__metadata_meta',
    ]);
});

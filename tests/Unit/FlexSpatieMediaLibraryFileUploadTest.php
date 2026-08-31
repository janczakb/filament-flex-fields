<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

$spatieAvailable = class_exists(SpatieMediaLibraryFileUpload::class)
    && class_exists(Media::class);

it('defines flex spatie media library file upload when spatie is installed', function () {
    expect(class_exists(FlexSpatieMediaLibraryFileUpload::class))->toBeTrue()
        ->and(is_subclass_of(FlexSpatieMediaLibraryFileUpload::class, SpatieMediaLibraryFileUpload::class))->toBeTrue()
        ->and((new FlexSpatieMediaLibraryFileUpload('attachment'))->getView())->toBe('filament-flex-fields::forms.components.flex-file-upload')
        ->and(method_exists(FlexSpatieMediaLibraryFileUpload::class, 'registerFlexSpatieMediaLibraryEnterpriseHooks'))->toBeTrue();
})->skip(! $spatieAvailable, 'Spatie Media Library is not installed');

it('does not register disk-based flex file upload hooks on spatie adapter', function () {
    $field = new FlexSpatieMediaLibraryFileUpload('attachment');

    $reflection = new ReflectionClass($field);

    expect($reflection->hasMethod('registerFlexFileUploadHooks'))->toBeTrue()
        ->and($reflection->hasMethod('registerFlexSpatieMediaLibraryEnterpriseHooks'))->toBeTrue();
})->skip(! $spatieAvailable, 'Spatie Media Library is not installed');

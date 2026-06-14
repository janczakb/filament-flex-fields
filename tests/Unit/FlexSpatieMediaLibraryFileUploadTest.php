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
        ->and((new FlexSpatieMediaLibraryFileUpload('attachment'))->getView())->toBe('filament-flex-fields::forms.components.flex-file-upload');
})->skip(! $spatieAvailable, 'Spatie Media Library is not installed');

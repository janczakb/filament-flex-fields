<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\FlexFileUpload;

use Bjanczak\FilamentFlexFields\Support\Media\SpatieMediaCaptureAdapter;
use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Spatie-safe enterprise hooks — never replaces Spatie disk persistence with FlexFileUpload paths.
 *
 * @mixin BaseFileUpload
 */
trait FlexSpatieMediaLibraryEnterpriseHooks
{
    public function registerFlexSpatieMediaLibraryEnterpriseHooks(): void
    {
        $this->applyMediaCaptureStorageDefaults();

        $this->saveUploadedFileUsing(function (
            BaseFileUpload $component,
            TemporaryUploadedFile $file,
            ?Model $record = null,
        ): ?string {
            /** @var \Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload $component */
            return SpatieMediaCaptureAdapter::saveUploadedFile($component, $file, $record);
        });

        $this->getUploadedFileUsing(function (BaseFileUpload $component, string $file, string|array|null $storedFileNames = null): ?array {
            /** @var \Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieMediaLibraryFileUpload $component */
            return SpatieMediaCaptureAdapter::resolveUploadedFilePayload($component, $file);
        });
    }
}

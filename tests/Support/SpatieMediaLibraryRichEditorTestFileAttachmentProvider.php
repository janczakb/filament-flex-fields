<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Filament\Forms\Components\RichEditor\FileAttachmentProviders\Contracts\FileAttachmentProvider;
use Filament\Forms\Components\RichEditor\RichContentAttribute;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SpatieMediaLibraryRichEditorTestFileAttachmentProvider implements FileAttachmentProvider
{
    public function attribute(RichContentAttribute $attribute): static
    {
        return $this;
    }

    public function getFileAttachmentUrl(mixed $file): ?string
    {
        return 'https://example.test/media/'.$file.'.jpg';
    }

    public function saveUploadedFileAttachment(TemporaryUploadedFile $file): mixed
    {
        return null;
    }

    public function getDefaultFileAttachmentVisibility(): ?string
    {
        return 'public';
    }

    public function isExistingRecordRequiredToSaveNewFileAttachments(): bool
    {
        return false;
    }

    public function cleanUpFileAttachments(array $exceptIds): void {}
}

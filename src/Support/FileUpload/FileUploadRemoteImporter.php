<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FileUpload;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class FileUploadRemoteImporter
{
    public function __construct(
        private readonly RemoteFileFetcher $fetcher,
    ) {}

    public function fetchValidatedPayload(string $url, FileUploadImportConstraints $constraints): RemoteFilePayload
    {
        $payload = $this->fetcher->fetch($url, $constraints->maxSizeBytes());

        $this->assertPayloadMatchesConstraints($payload, $constraints);

        return $payload;
    }

    public function importFromUrl(string $url, FileUploadImportConstraints $constraints): TemporaryUploadedFile
    {
        $payload = $this->fetchValidatedPayload($url, $constraints);

        $storedFilename = $this->storeOnLivewireDisk($payload);

        return TemporaryUploadedFile::createFromLivewire($storedFilename);
    }

    public function storeOnFieldDisk(
        RemoteFilePayload $payload,
        Filesystem $disk,
        string $directory,
    ): string {
        $directory = trim($directory, '/');

        $filename = $this->sanitizeFilename($payload->filename);
        $path = "{$directory}/{$filename}";

        while ($disk->exists($path)) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $filename = Str::ulid().($extension !== '' ? ".{$extension}" : '');
            $path = "{$directory}/{$filename}";
        }

        $disk->put($path, $payload->contents);

        return $path;
    }

    private function sanitizeFilename(string $filename): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($filename));

        if (! is_string($sanitized) || $sanitized === '' || $sanitized === '.' || $sanitized === '..') {
            return 'remote-'.Str::lower(Str::random(8)).'.bin';
        }

        return $sanitized;
    }

    private function assertPayloadMatchesConstraints(RemoteFilePayload $payload, FileUploadImportConstraints $constraints): void
    {
        if ($constraints->rejectExecutables && ExecutableExtensionGuard::isBlocked($payload->filename)) {
            throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.validation.executable_blocked'));
        }

        $extension = strtolower(pathinfo($payload->filename, PATHINFO_EXTENSION));

        if ($constraints->allowedExtensions !== [] && ! in_array($extension, $constraints->allowedExtensions, true)) {
            throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.validation.extension_not_allowed', [
                'extensions' => implode(', ', $constraints->allowedExtensions),
            ]));
        }

        if ($constraints->acceptedMimeTypes !== null && $constraints->acceptedMimeTypes !== []) {
            if (! $this->mimeIsAccepted($payload->mimeType, $constraints->acceptedMimeTypes)) {
                throw new FileUploadImportException(
                    $constraints->imagesOnly
                        ? __('filament-flex-fields::default.file_upload.validation.images_only')
                        : __('filament-flex-fields::default.file_upload.sources.url_type_not_allowed'),
                );
            }
        }

        if ($constraints->maxSizeBytes() !== null && $payload->size > $constraints->maxSizeBytes()) {
            throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.sources.url_too_large'));
        }
    }

    private function storeOnLivewireDisk(RemoteFilePayload $payload): string
    {
        $extension = pathinfo($payload->filename, PATHINFO_EXTENSION);
        $extension = $extension !== '' ? '.'.$extension : '';
        $filename = (string) Str::uuid().$extension;

        FileUploadConfiguration::storage()->put(
            FileUploadConfiguration::path($filename),
            $payload->contents,
        );

        FileUploadConfiguration::storage()->put(
            FileUploadConfiguration::path($filename).'.json',
            json_encode([
                'name' => $payload->filename,
                'type' => $payload->mimeType,
                'size' => $payload->size,
            ], JSON_THROW_ON_ERROR),
        );

        return $filename;
    }

    /**
     * @param  list<string>  $acceptedMimeTypes
     */
    private function mimeIsAccepted(string $mimeType, array $acceptedMimeTypes): bool
    {
        $mimeType = strtolower($mimeType);

        foreach ($acceptedMimeTypes as $acceptedType) {
            $acceptedType = strtolower($acceptedType);

            if ($acceptedType === $mimeType) {
                return true;
            }

            if (str_ends_with($acceptedType, '/*')) {
                $prefix = substr($acceptedType, 0, -1);

                if (str_starts_with($mimeType, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}

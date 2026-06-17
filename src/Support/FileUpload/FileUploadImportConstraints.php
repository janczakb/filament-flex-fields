<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FileUpload;

/**
 * @phpstan-type AcceptedMimeTypes list<string>|null
 */
final readonly class FileUploadImportConstraints
{
    /**
     * @param  AcceptedMimeTypes  $acceptedMimeTypes
     * @param  list<string>  $allowedExtensions
     */
    public function __construct(
        public ?array $acceptedMimeTypes,
        public array $allowedExtensions,
        public ?int $maxSizeKb,
        public bool $rejectExecutables,
        public bool $imagesOnly,
    ) {}

    public function maxSizeBytes(): ?int
    {
        if ($this->maxSizeKb === null) {
            return null;
        }

        return $this->maxSizeKb * 1024;
    }
}

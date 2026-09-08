<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

/**
 * Binary ingress body as path and/or stream — never requires a full-file string buffer.
 */
final class MediaPayload
{
    /**
     * @param  resource|null  $stream
     */
    private function __construct(
        public readonly ?string $localPath,
        public readonly mixed $stream,
        public readonly ?string $inlineContents,
        public readonly ?string $originalName,
        public readonly ?string $mimeType,
        public readonly ?int $size,
    ) {}

    public static function fromTemporaryUploadedFile(TemporaryUploadedFile $file): self
    {
        $path = rescue(fn (): string => $file->getRealPath() ?: $file->path(), '', report: false);
        $path = is_string($path) && $path !== '' && is_file($path) ? $path : null;

        $originalName = rescue(fn (): string => (string) $file->getClientOriginalName(), '', report: false);
        $mime = rescue(fn (): string => (string) ($file->getMimeType() ?: ''), '', report: false);
        $size = rescue(fn (): int => (int) $file->getSize(), 0, report: false);

        return new self(
            localPath: $path,
            stream: null,
            inlineContents: null,
            originalName: $originalName !== '' ? $originalName : null,
            mimeType: $mime !== '' ? $mime : null,
            size: $size > 0 ? $size : null,
        );
    }

    public static function fromPath(
        string $absolutePath,
        ?string $originalName = null,
        ?string $mimeType = null,
        ?int $size = null,
    ): self {
        return new self(
            localPath: $absolutePath,
            stream: null,
            inlineContents: null,
            originalName: $originalName ?? basename($absolutePath),
            mimeType: $mimeType,
            size: $size ?? (is_file($absolutePath) ? (int) filesize($absolutePath) : null),
        );
    }

    /**
     * @param  resource  $stream
     */
    public static function fromStream(
        mixed $stream,
        ?string $originalName = null,
        ?string $mimeType = null,
        ?int $size = null,
    ): self {
        if (! is_resource($stream)) {
            throw new RuntimeException('MediaPayload::fromStream requires a stream resource.');
        }

        return new self(
            localPath: null,
            stream: $stream,
            inlineContents: null,
            originalName: $originalName,
            mimeType: $mimeType,
            size: $size,
        );
    }

    /**
     * Small inline bodies only (e.g. signature SVG). Prefer path/stream for uploads.
     */
    public static function fromInlineString(
        string $contents,
        ?string $originalName = null,
        ?string $mimeType = null,
    ): self {
        return new self(
            localPath: null,
            stream: null,
            inlineContents: $contents,
            originalName: $originalName,
            mimeType: $mimeType,
            size: strlen($contents),
        );
    }

    public function hasLocalPath(): bool
    {
        return is_string($this->localPath) && $this->localPath !== '' && is_file($this->localPath);
    }

    public function scanPath(): ?string
    {
        return $this->hasLocalPath() ? $this->localPath : null;
    }

    /**
     * Open a readable stream without loading the whole file into a string when a path exists.
     *
     * @return resource
     */
    public function openReadableStream(): mixed
    {
        if ($this->hasLocalPath()) {
            $localPath = $this->localPath;
            assert(is_string($localPath));

            $handle = fopen($localPath, 'rb');

            if (! is_resource($handle)) {
                throw new RuntimeException('Unable to open media payload path for reading.');
            }

            return $handle;
        }

        if (is_resource($this->stream)) {
            return $this->stream;
        }

        if (is_string($this->inlineContents)) {
            $handle = fopen('php://temp', 'r+b');

            if (! is_resource($handle)) {
                throw new RuntimeException('Unable to open temp stream for inline media payload.');
            }

            fwrite($handle, $this->inlineContents);
            rewind($handle);

            return $handle;
        }

        throw new RuntimeException('MediaPayload has no readable source (path, stream, or inline).');
    }
}

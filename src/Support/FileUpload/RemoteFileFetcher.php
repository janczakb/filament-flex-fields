<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FileUpload;

use Bjanczak\FilamentFlexFields\Support\Http\SafeRemoteUrlValidator;
use Bjanczak\FilamentFlexFields\Support\UrlMeta\RedirectUrlResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class RemoteFileFetcher
{
    private const int CHUNK_SIZE = 8192;

    private const int MAX_REDIRECTS = 5;

    private const string USER_AGENT = 'Mozilla/5.0 (compatible; FilamentFlexFields/1.0; +https://github.com/janczakb/filament-flex-fields)';

    public function __construct(
        private readonly SafeRemoteUrlValidator $urlValidator,
        private readonly RedirectUrlResolver $redirectUrlResolver,
    ) {}

    public function fetch(string $url, ?int $maxBytes = null): RemoteFilePayload
    {
        if (! $this->urlValidator->isAllowedHttpUrl($url)) {
            throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.sources.url_not_allowed'));
        }

        $currentUrl = $url;
        $timeout = (int) config('filament-flex-fields.file_upload.url_fetch_timeout_seconds', 10);

        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            if (! $this->urlValidator->isAllowedHttpUrl($currentUrl)) {
                throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.sources.url_not_allowed'));
            }

            try {
                $response = Http::timeout($timeout)
                    ->withUserAgent(self::USER_AGENT)
                    ->withOptions([
                        'stream' => true,
                        'allow_redirects' => false,
                    ])
                    ->get($currentUrl);
            } catch (ConnectionException) {
                throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.sources.url_unreachable'));
            } catch (Throwable) {
                throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.sources.url_fetch_failed'));
            }

            if ($response->redirect()) {
                $location = $response->header('Location');

                if (! is_string($location) || $location === '') {
                    throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.sources.url_fetch_failed'));
                }

                $currentUrl = $this->redirectUrlResolver->resolve($currentUrl, $location);

                continue;
            }

            if (! $response->successful()) {
                throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.sources.url_fetch_failed'));
            }

            return $this->readBinaryResponse($response, $currentUrl, $maxBytes);
        }

        throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.sources.url_fetch_failed'));
    }

    private function readBinaryResponse(Response $response, string $url, ?int $maxBytes): RemoteFilePayload
    {
        $stream = $response->toPsrResponse()->getBody();
        $buffer = '';
        $bytesRead = 0;

        try {
            while (! $stream->eof()) {
                $chunk = $stream->read(self::CHUNK_SIZE);

                if ($chunk === '') {
                    break;
                }

                $bytesRead += strlen($chunk);

                if ($maxBytes !== null && $bytesRead > $maxBytes) {
                    throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.sources.url_too_large'));
                }

                $buffer .= $chunk;
            }
        } finally {
            $stream->close();
        }

        if ($buffer === '') {
            throw new FileUploadImportException(__('filament-flex-fields::default.file_upload.sources.url_empty'));
        }

        $mimeType = $this->detectMimeType($buffer, $response->header('Content-Type'));
        $filename = $this->resolveFilename($url, $response->header('Content-Disposition'), $mimeType);

        return new RemoteFilePayload(
            contents: $buffer,
            mimeType: $mimeType,
            filename: $filename,
            size: strlen($buffer),
        );
    }

    private function detectMimeType(string $contents, ?string $headerMimeType): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->buffer($contents) ?: 'application/octet-stream';

        if (! is_string($headerMimeType) || $headerMimeType === '') {
            return $detected;
        }

        $headerMimeType = strtolower(trim(strtok($headerMimeType, ';') ?: $headerMimeType));

        if ($headerMimeType === 'application/octet-stream' || $headerMimeType === 'binary/octet-stream') {
            return $detected;
        }

        return $headerMimeType;
    }

    private function resolveFilename(string $url, ?string $contentDisposition, string $mimeType): string
    {
        if (is_string($contentDisposition) && preg_match('/filename\*=UTF-8\'\'([^;]+)|filename="([^"]+)"|filename=([^;]+)/i', $contentDisposition, $matches) === 1) {
            $filename = rawurldecode($matches[1] ?: $matches[2] ?: $matches[3] ?: '');

            if ($filename !== '') {
                return basename($filename);
            }
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (is_string($path) && ($basename = basename($path)) !== '' && str_contains($basename, '.')) {
            return $basename;
        }

        $extension = match (true) {
            str_starts_with($mimeType, 'image/jpeg') => 'jpg',
            str_starts_with($mimeType, 'image/png') => 'png',
            str_starts_with($mimeType, 'image/webp') => 'webp',
            str_starts_with($mimeType, 'image/gif') => 'gif',
            str_starts_with($mimeType, 'image/svg') => 'svg',
            str_starts_with($mimeType, 'application/pdf') => 'pdf',
            default => 'bin',
        };

        return 'remote-'.Str::lower(Str::random(8)).'.'.$extension;
    }
}

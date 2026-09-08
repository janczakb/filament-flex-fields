<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Drivers;

use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\DiskPrecedenceResolver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaContext;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaPayload;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\DiskMediaRef;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\MediaRef;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class DiskMediaDriver implements MediaDriver
{
    public function persist(MediaPayload $payload, MediaContext $context): ?MediaRef
    {
        $diskName = DiskPrecedenceResolver::resolve(
            $context->disk,
            null,
            [
                'field' => $context->field,
                'record' => $context->record,
                'kind' => $context->kind->value,
                'correlation_id' => $context->correlationId,
            ],
        );

        $directory = trim((string) ($context->directory ?? 'uploads'), '/');
        $filename = $context->filename;

        if (! is_string($filename) || $filename === '') {
            $extension = pathinfo((string) $payload->originalName, PATHINFO_EXTENSION);
            $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        }

        $relativePath = ($directory !== '' ? $directory.'/' : '').ltrim($filename, '/');
        $disk = Storage::disk($diskName);

        try {
            if ($directory !== '') {
                $disk->makeDirectory($directory);
            }

            $stream = $payload->openReadableStream();

            try {
                $written = $disk->writeStream($relativePath, $stream, [
                    'visibility' => 'private',
                ]);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if ($written === false && ! $disk->exists($relativePath)) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        return new DiskMediaRef($diskName, $relativePath);
    }
}

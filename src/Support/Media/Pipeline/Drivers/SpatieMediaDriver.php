<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Drivers;

use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\DiskPrecedenceResolver;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaContext;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\MediaPayload;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\MediaRef;
use Bjanczak\FilamentFlexFields\Support\Media\Pipeline\Refs\SpatieMediaRef;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

/**
 * Stream/path Spatie ingest — never buffers entire uploads via addMediaFromString($file->get()).
 */
final class SpatieMediaDriver implements MediaDriver
{
    public function persist(MediaPayload $payload, MediaContext $context): ?MediaRef
    {
        $record = $context->record;

        if (! $record instanceof Model) {
            throw new RuntimeException(
                'Spatie media ingress requires a HasMedia Eloquent record. Persist the model before Spatie uploads (create-form timing).',
            );
        }

        if (! method_exists($record, 'addMedia') && ! method_exists($record, 'addMediaFromStream') && ! method_exists($record, 'addMediaFromString')) {
            throw new RuntimeException('Spatie media ingress requires a model that implements Spatie Media Library (addMedia*).');
        }

        $diskName = DiskPrecedenceResolver::resolveForSpatie(
            $context->disk,
            null,
            [
                'field' => $context->field,
                'record' => $record,
                'kind' => $context->kind->value,
                'collection' => $context->collection,
                'correlation_id' => $context->correlationId,
            ],
        );

        $collection = $context->collection ?? 'default';
        $filename = $context->filename ?? $payload->originalName ?? 'upload.bin';
        $mediaName = $context->mediaName ?? pathinfo((string) $filename, PATHINFO_FILENAME);

        try {
            $mediaAdder = $this->createMediaAdder($record, $payload);

            if (! method_exists($mediaAdder, 'addCustomHeaders')) {
                return null;
            }

            $media = $mediaAdder
                ->addCustomHeaders($context->customHeaders)
                ->usingFileName($filename)
                ->usingName($mediaName)
                ->storingConversionsOnDisk($context->conversionsDisk ?? '')
                ->withCustomProperties(array_merge(
                    $context->customProperties,
                    $this->flexCaptureStamp($context),
                ))
                ->withManipulations($context->manipulations)
                ->withResponsiveImagesIf($context->responsiveImages)
                ->withProperties($context->properties)
                ->toMediaCollection($collection, $diskName);
        } catch (Throwable $throwable) {
            if ($throwable instanceof RuntimeException) {
                throw $throwable;
            }

            return null;
        }

        if (! is_object($media)) {
            return null;
        }

        $uuid = method_exists($media, 'getAttributeValue')
            ? (string) $media->getAttributeValue('uuid')
            : (string) data_get($media, 'uuid');
        $path = method_exists($media, 'getPathRelativeToRoot')
            ? (string) $media->getPathRelativeToRoot()
            : null;

        return new SpatieMediaRef(
            uuid: $uuid,
            media: $media,
            disk: $diskName,
            path: $path,
        );
    }

    private function createMediaAdder(Model $record, MediaPayload $payload): object
    {
        if ($payload->hasLocalPath() && method_exists($record, 'addMedia')) {
            return $record->addMedia($payload->localPath);
        }

        if (method_exists($record, 'addMediaFromStream')) {
            return $record->addMediaFromStream($payload->openReadableStream());
        }

        // Tiny inline bodies only (signature SVG sink). Never for TemporaryUploadedFile uploads.
        if (is_string($payload->inlineContents) && method_exists($record, 'addMediaFromString')) {
            return $record->addMediaFromString($payload->inlineContents);
        }

        if ($payload->hasLocalPath() && method_exists($record, 'addMediaFromString')) {
            // Last-resort path read via stream into temp — still avoid TemporaryUploadedFile::get().
            $stream = $payload->openReadableStream();
            $contents = stream_get_contents($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            return $record->addMediaFromString(is_string($contents) ? $contents : '');
        }

        throw new RuntimeException('Unable to create Spatie media adder from payload (no path/stream/inline source).');
    }

    /**
     * @return array{flex_capture: array{field: string|null, collection: string|null, kind: string, correlation_id: string|null}}
     */
    private function flexCaptureStamp(MediaContext $context): array
    {
        return [
            'flex_capture' => [
                'field' => $context->field,
                'collection' => $context->collection ?? 'default',
                'kind' => $context->kind->value,
                'correlation_id' => $context->correlationId,
            ],
        ];
    }
}

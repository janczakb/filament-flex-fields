<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Pipeline;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable ingress context for a single persist attempt.
 *
 * @phpstan-type ContextArray array{
 *     field?: string|null,
 *     collection?: string|null,
 *     disk?: string|null,
 *     directory?: string|null,
 *     filename?: string|null,
 *     media_name?: string|null,
 *     conversions_disk?: string|null,
 *     custom_properties?: array<string, mixed>,
 *     manipulations?: array<string, mixed>,
 *     properties?: array<string, mixed>,
 *     custom_headers?: array<string, string>,
 *     responsive_images?: bool,
 *     correlation_id?: string|null,
 * }
 */
final class MediaContext
{
    /**
     * @param  array<string, mixed>  $customProperties
     * @param  array<string, mixed>  $manipulations
     * @param  array<string, mixed>  $properties
     * @param  array<string, string>  $customHeaders
     */
    public function __construct(
        public readonly MediaKind $kind,
        public readonly MediaStorageDriver $driver,
        public readonly ?string $field = null,
        public readonly ?Model $record = null,
        public readonly ?string $collection = null,
        public readonly ?string $disk = null,
        public readonly ?string $directory = null,
        public readonly ?string $filename = null,
        public readonly ?string $mediaName = null,
        public readonly ?string $conversionsDisk = null,
        public readonly array $customProperties = [],
        public readonly array $manipulations = [],
        public readonly array $properties = [],
        public readonly array $customHeaders = [],
        public readonly bool $responsiveImages = false,
        public readonly ?string $correlationId = null,
    ) {}

    public function withCorrelationId(string $correlationId): self
    {
        return new self(
            kind: $this->kind,
            driver: $this->driver,
            field: $this->field,
            record: $this->record,
            collection: $this->collection,
            disk: $this->disk,
            directory: $this->directory,
            filename: $this->filename,
            mediaName: $this->mediaName,
            conversionsDisk: $this->conversionsDisk,
            customProperties: $this->customProperties,
            manipulations: $this->manipulations,
            properties: $this->properties,
            customHeaders: $this->customHeaders,
            responsiveImages: $this->responsiveImages,
            correlationId: $correlationId,
        );
    }
}

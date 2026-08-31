<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FakeCaptureMediaModel extends Model
{
    /** @var list<FakeCaptureMedia> */
    public array $mediaItems = [];

    public function addMediaFromString(string $content): FakeCaptureMediaAdder
    {
        return new FakeCaptureMediaAdder($this, $content);
    }

    public function getRelationValue($key)
    {
        if ($key === 'media') {
            return collect($this->mediaItems);
        }

        return parent::getRelationValue($key);
    }
}

final class FakeCaptureMediaAdder
{
    /** @var array<string, mixed> */
    private array $customHeaders = [];

    private string $fileName = 'upload.bin';

    private string $name = 'upload';

    private string $conversionsDisk = '';

    /** @var array<string, mixed> */
    private array $customProperties = [];

    /** @var array<string, array<string, string>> */
    private array $manipulations = [];

    private bool $responsiveImages = false;

    /** @var array<string, mixed> */
    private array $properties = [];

    public function __construct(
        private readonly FakeCaptureMediaModel $model,
        private readonly string $content,
    ) {}

    /**
     * @param  array<string, mixed>  $headers
     */
    public function addCustomHeaders(array $headers): self
    {
        $this->customHeaders = $headers;

        return $this;
    }

    public function usingFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function usingName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function storingConversionsOnDisk(string $disk): self
    {
        $this->conversionsDisk = $disk;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function withCustomProperties(array $properties): self
    {
        $this->customProperties = $properties;

        return $this;
    }

    /**
     * @param  array<string, array<string, string>>  $manipulations
     */
    public function withManipulations(array $manipulations): self
    {
        $this->manipulations = $manipulations;

        return $this;
    }

    public function withResponsiveImagesIf(bool $condition): self
    {
        $this->responsiveImages = $condition;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function withProperties(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function toMediaCollection(string $collection, string $disk): FakeCaptureMedia
    {
        $media = new FakeCaptureMedia(
            uuid: (string) Str::uuid(),
            fileName: $this->fileName,
            name: $this->name,
            collection: $collection,
            disk: $disk,
            content: $this->content,
            customProperties: $this->customProperties,
            customHeaders: $this->customHeaders,
            manipulations: $this->manipulations,
            properties: $this->properties,
            conversionsDisk: $this->conversionsDisk,
            responsiveImages: $this->responsiveImages,
        );

        $this->model->mediaItems[] = $media;

        return $media;
    }
}

final class FakeCaptureMedia
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $fileName,
        public readonly string $name,
        public readonly string $collection,
        public readonly string $disk,
        public readonly string $content,
        /** @var array<string, mixed> */
        public readonly array $customProperties,
        /** @var array<string, mixed> */
        public readonly array $customHeaders,
        /** @var array<string, array<string, string>> */
        public readonly array $manipulations,
        /** @var array<string, mixed> */
        public readonly array $properties,
        public readonly string $conversionsDisk,
        public readonly bool $responsiveImages,
    ) {}

    public function getPath(): string
    {
        return sys_get_temp_dir().'/fake-capture-'.$this->uuid;
    }

    public function getPathRelativeToRoot(): string
    {
        return 'media/'.$this->collection.'/'.$this->fileName;
    }

    public function getAttributeValue(string $key): mixed
    {
        return match ($key) {
            'uuid' => $this->uuid,
            'file_name' => $this->fileName,
            'name' => $this->name,
            default => null,
        };
    }

    public function delete(): void
    {
        // no-op for tests
    }

    public function hasGeneratedConversion(string $conversion): bool
    {
        return false;
    }

    public function getTemporaryUrl(\DateTimeInterface $expiration, string $conversion = ''): string
    {
        return 'https://example.test/media/'.$this->uuid;
    }

    public function getUrl(string $conversion = ''): string
    {
        return 'https://example.test/media/'.$this->uuid;
    }
}

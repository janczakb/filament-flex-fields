<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media;

final readonly class BarcodeValue
{
    public function __construct(
        public string $value,
        public ?string $format = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $value = trim((string) ($data['value'] ?? ''));

        if ($value === '') {
            return null;
        }

        $format = $data['format'] ?? null;

        if (is_string($format)) {
            $format = trim($format);
        }

        return new self(
            value: $value,
            format: is_string($format) && $format !== '' ? $format : null,
        );
    }

    /**
     * @return array{value: string, format: string|null}
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'format' => $this->format,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\RichEditor;

final class RichEditorAttachmentManifest
{
    /**
     * @param  array<string, string>  $variants
     * @param  array<string, int>  $widths
     */
    public function __construct(
        public readonly string $master,
        public readonly array $variants = [],
        public readonly array $widths = [],
    ) {}

    public function manifestPath(): string
    {
        return RichEditorAttachmentPaths::manifestPath($this->master);
    }

    /**
     * @return list<string>
     */
    public function allPaths(): array
    {
        return array_values(array_unique([
            $this->master,
            ...array_values($this->variants),
        ]));
    }

    /**
     * @return array{master: string, variants: array<string, string>, widths: array<string, int>}
     */
    public function toArray(): array
    {
        return [
            'master' => $this->master,
            'variants' => $this->variants,
            'widths' => $this->widths,
        ];
    }

    /**
     * @param  array{master?: string, variants?: array<string, string>, widths?: array<string, int>}  $payload
     */
    public static function fromArray(array $payload): ?self
    {
        $master = $payload['master'] ?? null;

        if (! is_string($master) || $master === '') {
            return null;
        }

        $variants = $payload['variants'] ?? [];

        if (! is_array($variants)) {
            $variants = [];
        }

        /** @var array<string, string> $variants */
        $variants = array_filter(
            $variants,
            fn (mixed $path, mixed $name): bool => is_string($name) && is_string($path) && $path !== '',
            ARRAY_FILTER_USE_BOTH,
        );

        $widths = $payload['widths'] ?? [];

        if (! is_array($widths)) {
            $widths = [];
        }

        /** @var array<string, int> $widths */
        $widths = array_filter(
            $widths,
            fn (mixed $width, mixed $name): bool => is_string($name) && is_int($width) && $width > 0,
            ARRAY_FILTER_USE_BOTH,
        );

        return new self(master: $master, variants: $variants, widths: $widths);
    }
}

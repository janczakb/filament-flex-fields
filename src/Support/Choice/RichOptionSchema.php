<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Choice;

use Illuminate\Support\Collection;

/**
 * Canonical rich option shape for Choice OS v2 (Cards, Checklist, DualList, Tags, Matrix).
 */
final readonly class RichOptionSchema
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public string|int $value,
        public string $label,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $badge = null,
        public ?string $image = null,
        public bool $disabled = false,
        public ?array $meta = null,
    ) {}

    /**
     * @param  array<string, mixed>|string|int  $data
     */
    public static function fromArray(array|string|int $data, string|int|null $fallbackValue = null): self
    {
        if (is_string($data) || is_int($data)) {
            $value = $fallbackValue ?? $data;

            return new self(
                value: is_int($value) ? $value : (string) $value,
                label: (string) $data,
            );
        }

        $value = $data['value'] ?? $fallbackValue ?? $data['label'] ?? '';
        $label = (string) ($data['label'] ?? $value);

        $description = filled($data['description'] ?? $data['desc'] ?? null)
            ? (string) ($data['description'] ?? $data['desc'])
            : null;

        $icon = filled($data['icon'] ?? null) ? (string) $data['icon'] : null;
        $badge = filled($data['badge'] ?? null) ? (string) $data['badge'] : null;
        $image = filled($data['image'] ?? null) ? (string) $data['image'] : null;

        $meta = self::normalizeMeta($data['meta'] ?? null);

        return new self(
            value: is_int($value) ? $value : (string) $value,
            label: $label,
            description: $description,
            icon: $icon,
            badge: $badge,
            image: $image,
            disabled: (bool) ($data['disabled'] ?? false),
            meta: $meta,
        );
    }

    /**
     * @return array{
     *     value: string|int,
     *     label: string,
     *     description?: string,
     *     icon?: string,
     *     badge?: string,
     *     image?: string,
     *     disabled?: bool,
     *     meta?: array<string, mixed>,
     * }
     */
    public function toArray(): array
    {
        $payload = [
            'value' => $this->value,
            'label' => $this->label,
        ];

        if ($this->description !== null) {
            $payload['description'] = $this->description;
        }

        if ($this->icon !== null) {
            $payload['icon'] = $this->icon;
        }

        if ($this->badge !== null) {
            $payload['badge'] = $this->badge;
        }

        if ($this->image !== null) {
            $payload['image'] = $this->image;
        }

        if ($this->disabled) {
            $payload['disabled'] = true;
        }

        if ($this->meta !== null) {
            $payload['meta'] = $this->meta;
        }

        return $payload;
    }

    /**
     * @param  array<int|string, array<string, mixed>|string|int>|list<array<string, mixed>|string|int>  $options
     * @return Collection<string|int, self>
     */
    public static function normalizeMany(array $options): Collection
    {
        if ($options === []) {
            return collect();
        }

        $normalized = collect();

        if (array_is_list($options)) {
            foreach ($options as $index => $row) {
                if (is_string($row) || is_int($row)) {
                    $schema = self::fromArray($row);

                    $normalized->put($schema->value, $schema);

                    continue;
                }

                if (! is_array($row)) {
                    continue;
                }

                $schema = self::fromArray($row, fallbackValue: "option_{$index}");

                $normalized->put($schema->value, $schema);
            }

            return $normalized;
        }

        foreach ($options as $value => $option) {
            if (is_string($option) || is_int($option)) {
                $schema = self::fromArray($option, fallbackValue: $value);

                $normalized->put($schema->value, $schema);

                continue;
            }

            if (! is_array($option)) {
                continue;
            }

            $schema = self::fromArray([
                ...$option,
                'value' => $option['value'] ?? $value,
            ], fallbackValue: $value);

            $normalized->put($schema->value, $schema);
        }

        return $normalized;
    }

    public function hasRichPresentation(): bool
    {
        return $this->description !== null
            || $this->icon !== null
            || $this->badge !== null
            || $this->image !== null
            || $this->meta !== null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function normalizeMeta(mixed $meta): ?array
    {
        if ($meta === null || $meta === '') {
            return null;
        }

        if (is_string($meta)) {
            return ['text' => $meta];
        }

        if (! is_array($meta)) {
            return null;
        }

        return $meta;
    }
}

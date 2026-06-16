<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\SocialLinks;

use Bjanczak\FilamentFlexFields\Data\SocialPlatform;
use InvalidArgumentException;

final readonly class SocialPlatformDefinition
{
    /**
     * @param  list<string>  $hosts
     */
    public function __construct(
        public string $value,
        public string $label,
        public string $placeholder,
        public array $hosts = [],
        public ?string $iconSvg = null,
    ) {}

    public static function fromEnum(SocialPlatform $platform): self
    {
        return new self(
            value: $platform->value,
            label: $platform->label(),
            placeholder: $platform->placeholder(),
            hosts: $platform->hostPatterns(),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $value = trim((string) ($attributes['value'] ?? ''));

        if ($value === '') {
            throw new InvalidArgumentException('Custom social platform requires a non-empty [value].');
        }

        $label = trim((string) ($attributes['label'] ?? $value));
        $placeholder = trim((string) ($attributes['placeholder'] ?? 'https://'));

        $hosts = $attributes['hosts'] ?? [];

        if (! is_array($hosts)) {
            $hosts = [];
        }

        $hosts = array_values(array_filter(array_map(
            fn (mixed $host): string => trim((string) $host),
            $hosts,
        ), fn (string $host): bool => $host !== ''));

        $iconSvg = isset($attributes['iconSvg']) && is_string($attributes['iconSvg'])
            ? $attributes['iconSvg']
            : null;

        return new self(
            value: $value,
            label: $label !== '' ? $label : $value,
            placeholder: $placeholder !== '' ? $placeholder : 'https://',
            hosts: $hosts,
            iconSvg: $iconSvg,
        );
    }

    /**
     * @return array{value: string, label: string, placeholder: string, brand: string, hosts: list<string>}
     */
    public function toAlpineArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label,
            'placeholder' => $this->placeholder,
            'brand' => $this->value,
            'hosts' => $this->hosts,
        ];
    }
}

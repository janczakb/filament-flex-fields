<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Enums\FieldCategory;
use Bjanczak\FilamentFlexFields\Enums\FieldType;

class FieldTypeRegistry
{
    /**
     * @return list<array{
     *     type: FieldType,
     *     label: string,
     *     icon: string,
     *     category: FieldCategory,
     *     custom: bool,
     *     default_config: array<string, mixed>,
     * }>
     */
    public static function all(): array
    {
        return array_map(
            fn (FieldType $type): array => self::describe($type),
            FieldType::cases(),
        );
    }

    /**
     * @return array{
     *     type: FieldType,
     *     label: string,
     *     icon: string,
     *     category: FieldCategory,
     *     custom: bool,
     *     default_config: array<string, mixed>,
     * }
     */
    public static function describe(FieldType $type): array
    {
        return [
            'type' => $type,
            'label' => $type->label(),
            'icon' => $type->icon(),
            'category' => $type->category(),
            'custom' => $type->isCustomComponent(),
            'default_config' => $type->defaultConfig(),
        ];
    }

    /**
     * @return array<string, list<array{
     *     type: FieldType,
     *     label: string,
     *     icon: string,
     *     category: FieldCategory,
     *     custom: bool,
     *     default_config: array<string, mixed>,
     * }>>
     */
    public static function groupedByCategory(): array
    {
        $grouped = [];

        foreach (FieldCategory::cases() as $category) {
            $grouped[$category->value] = array_map(
                fn (FieldType $type): array => self::describe($type),
                FieldType::forCategory($category),
            );
        }

        return $grouped;
    }

    public static function count(): int
    {
        return count(FieldType::cases());
    }

    public static function find(string $value): ?FieldType
    {
        return FieldType::tryFrom($value);
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Wow;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;

/**
 * Searchable FieldType catalog for playground command palette (⌘K).
 */
final class CommandPaletteCatalog
{
    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     playground_slug: string|null,
     * }>
     */
    public static function all(): array
    {
        $entries = [];

        foreach (FieldType::cases() as $type) {
            $entries[] = [
                'id' => $type->value,
                'label' => $type->label(),
                'playground_slug' => self::playgroundSlugFor($type),
            ];
        }

        return $entries;
    }

    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     playground_slug: string|null,
     * }>
     */
    public static function search(string $query): array
    {
        $needle = mb_strtolower(trim($query));

        if ($needle === '') {
            return self::all();
        }

        return array_values(array_filter(
            self::all(),
            static function (array $entry) use ($needle): bool {
                return str_contains(mb_strtolower($entry['id']), $needle)
                    || str_contains(mb_strtolower($entry['label']), $needle)
                    || ($entry['playground_slug'] !== null && str_contains(mb_strtolower($entry['playground_slug']), $needle));
            },
        ));
    }

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     playground_slug: string|null,
     * }|null
     */
    public static function find(string $id): ?array
    {
        foreach (self::all() as $entry) {
            if ($entry['id'] === $id) {
                return $entry;
            }
        }

        return null;
    }

    public static function playgroundSlugFor(FieldType $type): ?string
    {
        $slugByComponent = self::playgroundSlugByComponentId();

        foreach ($type->assetComponents() as $component) {
            if (isset($slugByComponent[$component])) {
                return $slugByComponent[$component];
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private static function playgroundSlugByComponentId(): array
    {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        $map = [];

        foreach (array_keys(FlexFieldsPlaygroundRegistry::definitions()) as $slug) {
            $map[$slug] = $slug;
        }

        foreach (array_keys(FlexFieldsPlaygroundRegistry::definitions()) as $slug) {
            $component = FlexFieldAssets::PLAYGROUND_STYLESHEET_ALIASES[$slug] ?? $slug;

            if (! isset($map[$component])) {
                $map[$component] = $slug;
            }
        }

        foreach (FlexFieldAssets::PLAYGROUND_STYLESHEET_ALIASES as $slug => $component) {
            if (! isset($map[$component])) {
                $map[$component] = $slug;
            }
        }

        return $map;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Wow;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsPlaygroundRegistry;

/**
 * Searchable catalog for playground command palette (⌘K).
 *
 * Indexes FieldType entries and every registered playground hub slug.
 */
final class CommandPaletteCatalog
{
    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     playground_slug: string|null,
     *     kind: 'field'|'hub',
     * }>
     */
    public static function all(): array
    {
        $entries = [];
        $indexedSlugs = [];

        foreach (FieldType::cases() as $type) {
            $playgroundSlug = self::playgroundSlugFor($type);

            $entries[] = [
                'id' => $type->value,
                'label' => $type->label(),
                'playground_slug' => $playgroundSlug,
                'kind' => 'field',
            ];

            if ($playgroundSlug !== null) {
                $indexedSlugs[$playgroundSlug] = true;
            }
        }

        foreach (FlexFieldsPlaygroundRegistry::definitions() as $slug => $definition) {
            if (isset($indexedSlugs[$slug])) {
                continue;
            }

            $entries[] = [
                'id' => $slug,
                'label' => $definition['label'],
                'playground_slug' => $slug,
                'kind' => 'hub',
            ];

            $indexedSlugs[$slug] = true;
        }

        return $entries;
    }

    /**
     * @return list<array{
     *     id: string,
     *     label: string,
     *     playground_slug: string|null,
     *     kind: 'field'|'hub',
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
     *     kind: 'field'|'hub',
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

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Composition;

/**
 * Certified layout nesting recipes for Composition OS.
 *
 * Each recipe documents a supported component stack that Flex Fields tests,
 * documents, and playground demos treat as a first-class pattern.
 */
final class CertifiedRecipes
{
    public const TRANSLATABLE_SEGMENT_TABS_ITEM_CARD = 'translatable.segment-tabs.item-card';

    public const TRANSLATABLE_SEGMENT_TABS = 'translatable.segment-tabs';

    public const SEGMENT_TABS_ITEM_CARD = 'segment-tabs.item-card';

    /**
     * @return list<array{
     *     id: string,
     *     description: string,
     *     nesting: list<string>,
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'id' => self::TRANSLATABLE_SEGMENT_TABS_ITEM_CARD,
                'description' => 'Multi-locale settings hub: TranslatableFields locale filmstrip with inner SegmentTabs and ItemCard panels per tab.',
                'nesting' => ['TranslatableFields', 'SegmentTabs', 'ItemCard'],
            ],
            [
                'id' => self::TRANSLATABLE_SEGMENT_TABS,
                'description' => 'Locale tabs with grouped fields: TranslatableFields wrapping SegmentTabs for sectioned content per locale.',
                'nesting' => ['TranslatableFields', 'SegmentTabs'],
            ],
            [
                'id' => self::SEGMENT_TABS_ITEM_CARD,
                'description' => 'Single-locale settings hub: SegmentTabs driving ItemCard or ItemCardGroup rows per tab.',
                'nesting' => ['SegmentTabs', 'ItemCard'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function ids(): array
    {
        return array_column(self::all(), 'id');
    }

    /**
     * @return array{
     *     id: string,
     *     description: string,
     *     nesting: list<string>,
     * }|null
     */
    public static function find(string $id): ?array
    {
        foreach (self::all() as $recipe) {
            if ($recipe['id'] === $id) {
                return $recipe;
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Choice;

/**
 * Choice OS v2 entry point — validates and normalizes option lists for rich choice fields.
 */
final class RichOptionSchemaV2
{
    public const PROFILE_CARDS = 'cards';

    public const PROFILE_CHECKLIST = 'checklist';

    public const PROFILE_DUAL_LIST = 'dual_list';

    public const PROFILE_TAGS = 'tags';

    public const PROFILE_MATRIX = 'matrix';

    /**
     * @return list<string>
     */
    public static function profiles(): array
    {
        return [
            self::PROFILE_CARDS,
            self::PROFILE_CHECKLIST,
            self::PROFILE_DUAL_LIST,
            self::PROFILE_TAGS,
            self::PROFILE_MATRIX,
        ];
    }

    public static function validate(mixed $raw): bool
    {
        if (! is_array($raw)) {
            return false;
        }

        if ($raw === []) {
            return true;
        }

        if (array_is_list($raw)) {
            foreach ($raw as $row) {
                if (is_string($row) || is_int($row)) {
                    continue;
                }

                if (! is_array($row)) {
                    return false;
                }

                if (! self::isValidOptionRow($row)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($raw as $value => $option) {
            if (is_string($option) || is_int($option)) {
                if (! filled($value) && ! filled($option)) {
                    return false;
                }

                continue;
            }

            if (! is_array($option)) {
                return false;
            }

            if (! self::isValidOptionRow([...$option, 'value' => $option['value'] ?? $value])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string|int, string|array<string, mixed>>
     */
    public static function normalize(mixed $raw, string $profile = self::PROFILE_CARDS): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        return match ($profile) {
            self::PROFILE_CARDS, self::PROFILE_CHECKLIST => self::normalizeRichChoiceMap($raw),
            self::PROFILE_DUAL_LIST => self::normalizeDualListMap($raw),
            self::PROFILE_TAGS => self::normalizeTagsList($raw),
            self::PROFILE_MATRIX => self::normalizeMatrixLabels($raw),
            default => self::normalizeRichChoiceMap($raw),
        };
    }

    /**
     * @return array<string|int, string|array<string, mixed>>
     */
    public static function normalizeCards(mixed $raw): array
    {
        return self::normalize($raw, self::PROFILE_CARDS);
    }

    /**
     * @return array<string|int, string|array<string, mixed>>
     */
    public static function normalizeChecklist(mixed $raw): array
    {
        return self::normalize($raw, self::PROFILE_CHECKLIST);
    }

    /**
     * @return array<string, array{label: string, description: ?string, disabled: bool}>
     */
    public static function normalizeDualList(mixed $raw): array
    {
        /** @var array<string, array{label: string, description: ?string, disabled: bool}> */
        return self::normalize($raw, self::PROFILE_DUAL_LIST);
    }

    /**
     * @return list<string>
     */
    public static function normalizeTags(mixed $raw): array
    {
        /** @var list<string> */
        return self::normalize($raw, self::PROFILE_TAGS);
    }

    /**
     * @return array<string, string>
     */
    public static function normalizeMatrix(mixed $raw): array
    {
        /** @var array<string, string> */
        return self::normalize($raw, self::PROFILE_MATRIX);
    }

    /**
     * @param  array<int|string, array<string, mixed>|string|int>|list<array<string, mixed>|string|int>  $raw
     * @return array<string|int, string|array<string, mixed>>
     */
    private static function normalizeRichChoiceMap(array $raw): array
    {
        $options = [];

        foreach (RichOptionSchema::normalizeMany($raw) as $schema) {
            if (! $schema->hasRichPresentation() && ! $schema->disabled) {
                $options[$schema->value] = $schema->label;

                continue;
            }

            $payload = $schema->toArray();
            unset($payload['value']);

            $options[$schema->value] = $payload;
        }

        return $options;
    }

    /**
     * @param  array<int|string, array<string, mixed>|string|int>|list<array<string, mixed>|string|int>  $raw
     * @return array<string, array{label: string, description: ?string, disabled: bool}>
     */
    private static function normalizeDualListMap(array $raw): array
    {
        $options = [];

        foreach (RichOptionSchema::normalizeMany($raw) as $schema) {
            $options[(string) $schema->value] = [
                'label' => $schema->label,
                'description' => $schema->description,
                'disabled' => $schema->disabled,
            ];
        }

        return $options;
    }

    /**
     * @param  array<int|string, array<string, mixed>|string|int>|list<array<string, mixed>|string|int>  $raw
     * @return list<string>
     */
    private static function normalizeTagsList(array $raw): array
    {
        if (array_is_list($raw) && ($raw === [] || is_string($raw[0] ?? null) || is_int($raw[0] ?? null))) {
            return array_values(array_map(
                static fn (string|int $item): string => (string) $item,
                array_filter($raw, static fn (mixed $item): bool => is_string($item) || is_int($item)),
            ));
        }

        return RichOptionSchema::normalizeMany($raw)
            ->values()
            ->map(static fn (RichOptionSchema $schema): string => $schema->label)
            ->values()
            ->all();
    }

    /**
     * @param  array<int|string, array<string, mixed>|string|int>|list<array<string, mixed>|string|int>  $raw
     * @return array<string, string>
     */
    private static function normalizeMatrixLabels(array $raw): array
    {
        $labels = [];

        foreach (RichOptionSchema::normalizeMany($raw) as $schema) {
            $labels[(string) $schema->value] = $schema->label;
        }

        return $labels;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function isValidOptionRow(array $row): bool
    {
        $value = $row['value'] ?? $row['label'] ?? null;

        return filled($value) || filled($row['label'] ?? null);
    }
}

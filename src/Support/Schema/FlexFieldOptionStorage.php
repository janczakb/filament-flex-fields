<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Illuminate\Support\Str;

final class FlexFieldOptionStorage
{
    /**
     * @param  mixed  $stored
     * @return list<array<string, mixed>>
     */
    public static function configToRepeater(mixed $stored, FieldType $type): array
    {
        if (! is_array($stored) || $stored === []) {
            return [];
        }

        if (array_is_list($stored)) {
            $items = [];

            foreach ($stored as $index => $row) {
                if (is_string($row) || is_int($row)) {
                    $items[] = [
                        'value' => (string) $row,
                        'label' => (string) $row,
                    ];

                    continue;
                }

                if (is_array($row)) {
                    $items[] = self::normalizeRepeaterRow($row, $type, $index);
                }
            }

            return $items;
        }

        $items = [];

        foreach ($stored as $value => $label) {
            if (is_array($label)) {
                $items[] = self::normalizeRepeaterRow(['value' => $value, ...$label], $type, count($items));

                continue;
            }

            $items[] = [
                'value' => (string) $value,
                'label' => (string) $label,
            ];
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>|null  $state
     * @return list<array<string, mixed>>
     */
    public static function repeaterToConfigList(?array $state, FieldType $type): array
    {
        if ($state === null || $state === []) {
            return [];
        }

        $options = [];

        foreach ($state as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($label === '' && $value === '') {
                continue;
            }

            if ($value === '' && $label !== '') {
                $value = Str::slug($label, '_');
            }

            if ($label === '' && $value !== '') {
                $label = $value;
            }

            $option = [
                'value' => $value,
                'label' => $label,
            ];

            foreach (self::optionalKeysForType($type) as $key) {
                if (! array_key_exists($key, $row)) {
                    continue;
                }

                $candidate = $row[$key];

                if ($key === 'disabled') {
                    $option[$key] = (bool) $candidate;

                    continue;
                }

                if (filled($candidate)) {
                    $option[$key] = $candidate;
                }
            }

            $options[] = $option;
        }

        return $options;
    }

    /**
     * @param  list<array<string, mixed>>|null  $state
     * @return list<string>
     */
    public static function repeaterToSuggestions(?array $state): array
    {
        if ($state === null || $state === []) {
            return [];
        }

        $suggestions = [];

        foreach ($state as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? $row['value'] ?? ''));

            if ($label === '') {
                continue;
            }

            $suggestions[] = $label;
        }

        return array_values(array_unique($suggestions));
    }

    /**
     * @return list<string>
     */
    public static function optionalKeysForType(FieldType $type): array
    {
        $keys = ['color', 'disabled'];

        if ($type->supportsRichFieldOptions()) {
            $keys = [...$keys, 'description', 'icon', 'tooltip', 'badge'];
        }

        if ($type === FieldType::ImageChoiceCards) {
            $keys = [...$keys, 'image', 'alt'];
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function normalizeRepeaterRow(array $row, FieldType $type, int $index): array
    {
        $label = (string) ($row['label'] ?? $row['name'] ?? $row['value'] ?? "Option {$index}");
        $value = (string) ($row['value'] ?? Str::slug($label, '_'));

        $normalized = [
            'value' => $value,
            'label' => $label,
        ];

        foreach (self::optionalKeysForType($type) as $key) {
            if (array_key_exists($key, $row)) {
                $normalized[$key] = $row[$key];
            }
        }

        return $normalized;
    }
}

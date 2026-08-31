<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Enums\FieldType;

final class FlexFieldMatrixStorage
{
    /**
     * @param  mixed  $stored
     * @return list<array{value: string, label: string, icon?: string}>
     */
    public static function configToRepeater(mixed $stored, string $valueKey = 'value'): array
    {
        if (! is_array($stored) || $stored === []) {
            return [];
        }

        $items = [];

        if (array_is_list($stored)) {
            foreach ($stored as $index => $row) {
                if (is_string($row) || is_int($row)) {
                    $items[] = ['value' => (string) $row, 'label' => (string) $row];

                    continue;
                }

                if (is_array($row)) {
                    $label = (string) ($row['label'] ?? $row['name'] ?? $row[$valueKey] ?? "Item {$index}");
                    $items[] = [
                        'value' => (string) ($row[$valueKey] ?? $row['value'] ?? $label),
                        'label' => $label,
                        'icon' => $row['icon'] ?? null,
                    ];
                }
            }

            return $items;
        }

        foreach ($stored as $value => $label) {
            if (is_array($label)) {
                $items[] = [
                    'value' => (string) ($label['value'] ?? $value),
                    'label' => (string) ($label['label'] ?? $value),
                    'icon' => $label['icon'] ?? null,
                ];

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
     * @return list<array{value: string, label: string, icon?: string}>
     */
    public static function repeaterToConfigList(?array $state, bool $withIcons = false): array
    {
        if ($state === null || $state === []) {
            return [];
        }

        $items = [];

        foreach ($state as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($label === '' && $value === '') {
                continue;
            }

            if ($value === '') {
                $value = str($label)->slug('_')->toString();
            }

            if ($label === '') {
                $label = $value;
            }

            $item = [
                'value' => $value,
                'label' => $label,
            ];

            if ($withIcons && filled($row['icon'] ?? null)) {
                $item['icon'] = (string) $row['icon'];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    public static function mergeIntoField(array $field): array
    {
        $type = FieldType::tryFrom((string) ($field['type'] ?? ''));

        if ($type !== FieldType::MatrixChoice) {
            unset($field['field_matrix_rows'], $field['field_matrix_columns']);

            return $field;
        }

        $config = is_array($field['config'] ?? null) ? $field['config'] : [];

        if (array_key_exists('field_matrix_rows', $field)) {
            $config['rows'] = self::repeaterToConfigList(
                is_array($field['field_matrix_rows']) ? $field['field_matrix_rows'] : [],
            );
        }

        if (array_key_exists('field_matrix_columns', $field)) {
            $config['columns'] = self::repeaterToConfigList(
                is_array($field['field_matrix_columns']) ? $field['field_matrix_columns'] : [],
                withIcons: true,
            );
        }

        $field['config'] = $config;
        unset($field['field_matrix_rows'], $field['field_matrix_columns']);

        return $field;
    }
}

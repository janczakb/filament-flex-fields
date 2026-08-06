<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\Concerns;

/**
 * Studio (Flex Forms) stores choice options as list rows `{ value, label, … }`.
 * Flex Fields components expect associative maps keyed by option value:
 * - labeled lists / Select: `value => label` (string)
 * - rich choice fields: `value => label|array{label, description, icon, …}`
 *
 * Normalize only at the configurator boundary — never rewrite Studio schema storage.
 */
trait NormalizesStudioChoiceOptions
{
    /**
     * @param  list<string>  $richKeys
     * @return array<string|int, string|array<string, mixed>>
     */
    protected function normalizeStudioChoiceOptions(mixed $raw, array $richKeys = ['description', 'desc', 'icon', 'disabled', 'tooltip']): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        if (! array_is_list($raw)) {
            return $raw;
        }

        $options = [];

        foreach ($raw as $index => $row) {
            if (is_string($row) || is_int($row)) {
                $options[(string) $row] = (string) $row;

                continue;
            }

            if (! is_array($row)) {
                continue;
            }

            $label = (string) ($row['label'] ?? $row['value'] ?? "Option {$index}");
            $value = $row['value'] ?? $label;
            $key = is_int($value) ? $value : (string) $value;

            $payload = $row;
            unset($payload['value']);
            $payload['label'] = $label;

            $hasRichMeta = false;

            foreach ($richKeys as $richKey) {
                if (array_key_exists($richKey, $payload) && filled($payload[$richKey])) {
                    $hasRichMeta = true;

                    break;
                }
            }

            $options[$key] = $hasRichMeta ? $payload : $label;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    protected function normalizeStudioLabeledList(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        if (! array_is_list($raw)) {
            $normalized = [];

            foreach ($raw as $key => $value) {
                if (is_string($value) || is_int($value)) {
                    $normalized[(string) $key] = (string) $value;

                    continue;
                }

                if (is_array($value)) {
                    $label = (string) ($value['label'] ?? $value['value'] ?? $key);
                    $normalized[(string) ($value['value'] ?? $key)] = $label;
                }
            }

            return $normalized;
        }

        $normalized = [];

        foreach ($raw as $index => $row) {
            if (is_string($row) || is_int($row)) {
                $normalized[(string) $row] = (string) $row;

                continue;
            }

            if (! is_array($row)) {
                continue;
            }

            $label = (string) ($row['label'] ?? $row['value'] ?? "Item {$index}");
            $value = (string) ($row['value'] ?? $label);
            $normalized[$value] = $label;
        }

        return $normalized;
    }
}

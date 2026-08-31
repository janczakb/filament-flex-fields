<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;

final class FlexFieldValueCsvExchange
{
    /**
     * @param  iterable<FlexFieldDefinition>  $definitions
     * @param  iterable<object>  $records
     */
    public function export(iterable $definitions, iterable $records, string $valuesColumn = ''): string
    {
        $column = $valuesColumn !== '' ? $valuesColumn : FlexFieldsConfig::getValuesColumn();
        $headers = ['id', ...collect($definitions)->map(fn (FlexFieldDefinition $definition): string => $definition->slug)->all()];
        $lines = [implode(',', $headers)];

        foreach ($records as $record) {
            $row = [(string) (is_object($record) && method_exists($record, 'getKey')
                ? $record->getKey()
                : (is_object($record) ? ($record->id ?? '') : ''))];

            foreach ($definitions as $definition) {
                $value = data_get($record, "{$column}.{$definition->slug}");
                $row[] = $this->escapeCsv($this->stringifyValue($value, $definition));
            }

            $lines[] = implode(',', $row);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  iterable<FlexFieldDefinition>  $definitions
     * @return array<string, array<string, mixed>>
     */
    public function import(string $csv, iterable $definitions): array
    {
        $lines = preg_split('/\R/', trim($csv)) ?: [];
        $header = str_getcsv(array_shift($lines) ?? '') ?: [];
        $slugIndex = array_flip(array_slice($header, 1));

        $definitionsBySlug = collect($definitions)->keyBy(fn (FlexFieldDefinition $definition): string => $definition->slug);

        $payload = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = str_getcsv($line);
            $recordId = (string) ($cells[0] ?? '');

            if ($recordId === '') {
                continue;
            }

            $values = [];

            foreach ($slugIndex as $slug => $headerIndex) {
                $definition = $definitionsBySlug->get($slug);

                if ($definition === null) {
                    continue;
                }

                $values[$slug] = $this->castImportedValue($cells[(int) $headerIndex + 1] ?? null, $definition);
            }

            $payload[$recordId] = $values;
        }

        return $payload;
    }

    protected function stringifyValue(mixed $value, FlexFieldDefinition $definition): string
    {
        if ($definition->isEncrypted && is_string($value)) {
            $value = FlexFieldEncryption::decrypt($value);
        }

        if (is_array($value)) {
            return json_encode($value) ?: '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) ($value ?? '');
    }

    protected function castImportedValue(?string $value, FlexFieldDefinition $definition): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($definition->type->value, ['toggle', 'switch_field'], true)) {
            return in_array(strtolower($value), ['1', 'true', 'yes'], true);
        }

        if ($definition->isEncrypted) {
            return FlexFieldEncryption::encrypt($value);
        }

        return $value;
    }

    protected function escapeCsv(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}

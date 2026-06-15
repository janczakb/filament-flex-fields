<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Data\FlexFieldSchema;

class FlexFieldSchemaMigrator
{
    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function migrate(array $schema): array
    {
        $version = (int) ($schema['version'] ?? 0);

        while ($version < FlexFieldSchema::CURRENT_VERSION) {
            $schema = match ($version) {
                0 => $this->migrateFromVersion0($schema),
                default => $schema,
            };

            $version++;
            $schema['version'] = $version;
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    protected function migrateFromVersion0(array $schema): array
    {
        if (isset($schema['field']) && ! isset($schema['fields'])) {
            $schema['fields'] = $schema['field'];
            unset($schema['field']);
        }

        if (! isset($schema['fields']) || ! is_array($schema['fields'])) {
            $schema['fields'] = [];
        }

        $schema['fields'] = array_map(
            fn (mixed $field): array => $this->migrateFieldFromVersion0(is_array($field) ? $field : []),
            $schema['fields'],
        );

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    protected function migrateFieldFromVersion0(array $field): array
    {
        if (isset($field['name']) && ! isset($field['slug'])) {
            $field['slug'] = $field['name'];
            unset($field['name']);
        }

        if (isset($field['default']) && ! isset($field['default_value']) && ! isset($field['defaultValue'])) {
            $field['default_value'] = $field['default'];
            unset($field['default']);
        }

        if (isset($field['required']) && ! isset($field['is_required']) && ! isset($field['isRequired'])) {
            $field['is_required'] = $field['required'];
            unset($field['required']);
        }

        return $field;
    }
}

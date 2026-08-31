<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use JsonException;

final class SchemaImportExport
{
    public const EXPORT_VERSION = 1;

    /**
     * @param  array<string, mixed>  $schema
     */
    public function export(array $schema): string
    {
        $payload = [
            'version' => self::EXPORT_VERSION,
            'schema' => $schema,
        ];

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new JsonException('Invalid flex field schema JSON: '.$exception->getMessage(), $exception->getCode(), $exception);
        }

        if (! is_array($decoded)) {
            throw new JsonException('Flex field schema export must decode to an object.');
        }

        $version = (int) ($decoded['version'] ?? 0);

        if ($version !== self::EXPORT_VERSION) {
            throw new JsonException(sprintf(
                'Unsupported flex field schema export version %d (expected %d).',
                $version,
                self::EXPORT_VERSION,
            ));
        }

        $schema = $decoded['schema'] ?? null;

        if (! is_array($schema)) {
            throw new JsonException('Flex field schema export is missing a schema object.');
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array{ok: bool, errors: list<string>}
     */
    public function dryRunValidate(array $schema): array
    {
        $errors = [];

        if (! isset($schema['target']) || ! is_string($schema['target']) || trim($schema['target']) === '') {
            $errors[] = 'Schema target must be a non-empty string (Eloquent model class).';
        }

        if (! isset($schema['fields']) || ! is_array($schema['fields'])) {
            $errors[] = 'Schema fields must be an array.';
        } else {
            foreach ($schema['fields'] as $index => $field) {
                if (! is_array($field)) {
                    $errors[] = "Field at index {$index} must be an object.";

                    continue;
                }

                $slug = $field['slug'] ?? null;

                if (! is_string($slug) || trim($slug) === '') {
                    $errors[] = "Field at index {$index} is missing a slug.";
                }

                $label = $field['label'] ?? null;

                if (! is_string($label) || trim($label) === '') {
                    $errors[] = "Field at index {$index} is missing a label.";
                }

                $type = $field['type'] ?? null;

                if (! is_string($type) || trim($type) === '') {
                    $errors[] = "Field at index {$index} is missing a type.";

                    continue;
                }

                if (FieldType::tryFrom($type) === null) {
                    $errors[] = "Field at index {$index} uses unknown type \"{$type}\".";
                }
            }
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function checksum(array $schema): string
    {
        $normalized = json_encode($schema, JSON_THROW_ON_ERROR);

        return hash('sha256', $normalized);
    }
}

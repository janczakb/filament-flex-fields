<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Bjanczak\FilamentFlexFields\Support\Intelligence\FormulaEngine;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

final class FlexFieldGroupValidator
{
    public function __construct(
        private readonly SchemaImportExport $importExport = new SchemaImportExport,
    ) {}

    /**
     * @return array{ok: bool, errors: list<string>}
     */
    public function validateGroup(FlexFieldGroup $group): array
    {
        $errors = [];

        $schema = $group->toRegistrySchema();
        $dryRun = $this->importExport->dryRunValidate($schema);
        $errors = [...$errors, ...$dryRun['errors']];

        $fields = $group->fields ?? [];

        if (! is_array($fields)) {
            $errors[] = 'Fields must be an array.';

            return [
                'ok' => false,
                'errors' => $errors,
            ];
        }

        $slugs = [];
        $sectionIds = $this->collectSectionIds(is_array($group->sections) ? $group->sections : []);
        $formulas = [];

        foreach ($fields as $index => $field) {
            if (! is_array($field)) {
                $errors[] = "Field at index {$index} must be an object.";

                continue;
            }

            $slug = $field['slug'] ?? null;

            if (is_string($slug) && filled($slug)) {
                if (isset($slugs[$slug])) {
                    $errors[] = "Duplicate field slug \"{$slug}\" within this group.";
                }

                $slugs[$slug] = true;
            }

            foreach (['config', 'visible_when', 'required_when', 'disabled_when'] as $jsonKey) {
                if (! array_key_exists($jsonKey, $field)) {
                    continue;
                }

                $value = $field[$jsonKey];

                if ($value === null || $value === '' || is_array($value)) {
                    continue;
                }

                if (! is_string($value)) {
                    $errors[] = "Field \"{$slug}\" has invalid {$jsonKey} (must be JSON object or array).";

                    continue;
                }

                try {
                    json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    $errors[] = "Field \"{$slug}\" has invalid JSON in {$jsonKey}.";
                }
            }

            try {
                FlexFieldDefinition::fromArray($this->normalizeFieldAttributes($field));
            } catch (Throwable $exception) {
                $label = is_string($slug) && filled($slug) ? $slug : (string) $index;
                $errors[] = "Field \"{$label}\" could not be parsed: {$exception->getMessage()}";
            }

            $sectionId = $field['section_id'] ?? null;

            if (is_string($sectionId) && filled($sectionId) && ! isset($sectionIds[$sectionId])) {
                $label = is_string($slug) && filled($slug) ? $slug : (string) $index;
                $errors[] = "Field \"{$label}\" references unknown section \"{$sectionId}\".";
            }

            if (is_string($slug) && filled($slug)) {
                $formula = $field['formula'] ?? $field['calculated'] ?? null;

                if (is_string($formula) && trim($formula) !== '') {
                    $formulas[$slug] = trim($formula);
                }
            }

            $type = FieldType::tryFrom((string) ($field['type'] ?? ''));

            if ($type?->requiresConfiguredOptions()) {
                $normalized = $this->normalizeFieldAttributes($field);
                $config = is_array($normalized['config'] ?? null) ? $normalized['config'] : [];
                $options = $config['options'] ?? [];

                if (! is_array($options) || $options === []) {
                    $label = is_string($slug) && filled($slug) ? $slug : (string) $index;
                    $errors[] = "Field \"{$label}\" requires at least one option.";
                }
            }
        }

        if ($formulas !== [] && FormulaEngine::detectCycle($formulas) !== []) {
            $errors[] = 'Field formulas contain a circular dependency.';
        }

        foreach (is_array($group->sections) ? $group->sections : [] as $index => $section) {
            if (! is_array($section)) {
                $errors[] = "Section at index {$index} must be an object.";

                continue;
            }

            $sectionId = $section['id'] ?? null;

            if (! is_string($sectionId) || ! filled($sectionId)) {
                $errors[] = "Section at index {$index} is missing an id.";
            }
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function assertValidGroup(FlexFieldGroup $group): void
    {
        $result = $this->validateGroup($group);

        if ($result['ok']) {
            return;
        }

        throw ValidationException::withMessages([
            'fields' => implode("\n", $result['errors']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    public function normalizeFieldAttributes(array $field): array
    {
        $field = $this->decodeJsonAttributes($field);
        $field = FlexFieldTypeSettingsStorage::mergeIntoField($field);
        $field = FlexFieldMatrixStorage::mergeIntoField($field);
        $field = $this->mergeFieldOptionsIntoConfig($field);

        unset($field['field_options'], $field['field_matrix_rows'], $field['field_matrix_columns'], $field['type_settings']);

        return $field;
    }

    /**
     * @param  list<array<string, mixed>>|null  $fields
     * @return list<array<string, mixed>>
     */
    public function prepareFieldsForForm(?array $fields): array
    {
        if ($fields === null) {
            return [];
        }

        return array_values(array_map(
            fn (array $field): array => $this->prepareFieldForForm($field),
            $fields,
        ));
    }

    /**
     * @param  list<array<string, mixed>>|null  $fields
     * @return list<array<string, mixed>>
     */
    public function normalizeFields(?array $fields): array
    {
        if ($fields === null) {
            return [];
        }

        return array_values(array_map(
            fn (array $field): array => $this->normalizeFieldAttributes($field),
            $fields,
        ));
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function prepareFieldForForm(array $field): array
    {
        $field = $this->decodeJsonAttributes($field);
        $type = FieldType::tryFrom((string) ($field['type'] ?? ''));
        $config = is_array($field['config'] ?? null) ? $field['config'] : [];

        if ($type?->supportsUserDefinedOptions()) {
            $storageKey = $type->usesSuggestionsInsteadOfOptions() ? 'suggestions' : 'options';
            $field['field_options'] = FlexFieldOptionStorage::configToRepeater($config[$storageKey] ?? [], $type);
        }

        if ($type === FieldType::MatrixChoice) {
            $field['field_matrix_rows'] = FlexFieldMatrixStorage::configToRepeater($config['rows'] ?? []);
            $field['field_matrix_columns'] = FlexFieldMatrixStorage::configToRepeater($config['columns'] ?? []);
        }

        if ($type !== null) {
            $field['type_settings'] = FlexFieldTypeSettingsStorage::extractFromConfig($type, $config);
        }

        unset($field['config']);

        return $field;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function decodeJsonAttributes(array $field): array
    {
        foreach (['config', 'visible_when', 'required_when', 'disabled_when'] as $jsonKey) {
            if (! array_key_exists($jsonKey, $field)) {
                continue;
            }

            $value = $field[$jsonKey];

            if ($value === null || $value === '') {
                unset($field[$jsonKey]);

                continue;
            }

            if (is_string($value)) {
                try {
                    $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    $field[$jsonKey] = is_array($decoded) ? $decoded : $value;
                } catch (JsonException) {
                    // Validation will catch invalid JSON separately.
                }
            }
        }

        return $field;
    }

    /**
     * @param  list<array<string, mixed>>  $sections
     * @return array<string, true>
     */
    private function collectSectionIds(array $sections): array
    {
        $ids = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            $id = $section['id'] ?? null;

            if (is_string($id) && filled($id)) {
                $ids[$id] = true;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private function mergeFieldOptionsIntoConfig(array $field): array
    {
        if (! array_key_exists('field_options', $field)) {
            return $field;
        }

        $type = FieldType::tryFrom((string) ($field['type'] ?? ''));

        if (! $type?->supportsUserDefinedOptions()) {
            return $field;
        }

        $config = is_array($field['config'] ?? null) ? $field['config'] : [];
        $optionsState = is_array($field['field_options']) ? $field['field_options'] : [];

        if ($type->usesSuggestionsInsteadOfOptions()) {
            $config['suggestions'] = FlexFieldOptionStorage::repeaterToSuggestions($optionsState);
        } else {
            $config['options'] = FlexFieldOptionStorage::repeaterToConfigList($optionsState, $type);
        }

        $field['config'] = $config;

        return $field;
    }
}

<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Enums\FieldType;

final class FlexFieldTypeSettingsStorage
{
    /**
     * @return list<string>
     */
    public static function reservedConfigKeys(FieldType $type): array
    {
        $keys = [];

        if ($type->supportsUserDefinedOptions()) {
            $keys[] = $type->usesSuggestionsInsteadOfOptions() ? 'suggestions' : 'options';
        }

        if ($type === FieldType::MatrixChoice) {
            $keys[] = 'rows';
            $keys[] = 'columns';
            $keys[] = 'column_icons';
        }

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function extractFromConfig(FieldType $type, array $config): array
    {
        $reserved = self::reservedConfigKeys($type);
        $settings = [];

        foreach ($type->defaultConfig() as $key => $default) {
            if (in_array($key, $reserved, true)) {
                continue;
            }

            if (array_key_exists($key, $config)) {
                $settings[$key] = $config[$key];
            }
        }

        foreach ($config as $key => $value) {
            if (in_array($key, $reserved, true) || array_key_exists($key, $settings)) {
                continue;
            }

            $settings[$key] = $value;
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    public static function mergeIntoField(array $field): array
    {
        if (! array_key_exists('type_settings', $field)) {
            return $field;
        }

        $type = FieldType::tryFrom((string) ($field['type'] ?? ''));

        if ($type === null) {
            unset($field['type_settings']);

            return $field;
        }

        $config = is_array($field['config'] ?? null) ? $field['config'] : [];
        $settings = is_array($field['type_settings']) ? $field['type_settings'] : [];
        $reserved = self::reservedConfigKeys($type);

        foreach ($settings as $key => $value) {
            if (in_array($key, $reserved, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                unset($config[$key]);

                continue;
            }

            $config[$key] = $value;
        }

        if ($config === []) {
            unset($field['config']);
        } else {
            $field['config'] = $config;
        }

        unset($field['type_settings']);

        return $field;
    }
}

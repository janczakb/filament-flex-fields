<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Closure;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;

final class JsonFieldConditions
{
    /** @var list<string> */
    public const OPERATORS = [
        'equals',
        'not_equals',
        'filled',
        'empty',
        'in',
        'contains',
        'not_contains',
        'greater_than',
        'less_than',
    ];

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $rule
     */
    public static function compileVisibleWhen(array $rule, string $flexStatePathPrefix = 'flex_field_values', ?Model $record = null): Closure
    {
        return self::compile($rule, $flexStatePathPrefix, $record);
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $rule
     */
    public static function compileRequiredWhen(array $rule, string $flexStatePathPrefix = 'flex_field_values', ?Model $record = null): Closure
    {
        return self::compile($rule, $flexStatePathPrefix, $record);
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $rule
     */
    public static function compileDisabledWhen(array $rule, string $flexStatePathPrefix = 'flex_field_values', ?Model $record = null): Closure
    {
        return self::compile($rule, $flexStatePathPrefix, $record);
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $rule
     */
    public static function evaluate(array $rule, callable $get, ?Model $record = null): bool
    {
        foreach (self::normalizeRules($rule) as $singleRule) {
            if (! self::evaluateSingle($singleRule, $get, $record)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $rule
     */
    private static function compile(array $rule, string $flexStatePathPrefix, ?Model $record): Closure
    {
        return fn (Get $get): bool => self::evaluate(
            $rule,
            fn (string $field): mixed => self::resolveFieldValue($field, $get, $flexStatePathPrefix, $record),
            $record,
        );
    }

    private static function resolveFieldValue(string $field, Get $get, string $flexStatePathPrefix, ?Model $record): mixed
    {
        if (str_starts_with($field, 'model.')) {
            $attribute = substr($field, strlen('model.'));

            return $get($attribute);
        }

        if (str_starts_with($field, 'relation.')) {
            return self::resolveRelationValue(substr($field, strlen('relation.')), $record);
        }

        if (str_starts_with($field, 'flex_field.')) {
            $slug = substr($field, strlen('flex_field.'));

            return $get(filled($flexStatePathPrefix) ? "{$flexStatePathPrefix}.{$slug}" : $slug);
        }

        return $get(filled($flexStatePathPrefix) ? "{$flexStatePathPrefix}.{$field}" : $field);
    }

    private static function resolveRelationValue(string $path, ?Model $record): mixed
    {
        if ($record === null || $path === '') {
            return null;
        }

        return data_get($record, $path);
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $rule
     * @return list<array<string, mixed>>
     */
    private static function normalizeRules(array $rule): array
    {
        if (isset($rule['and']) && is_array($rule['and'])) {
            /** @var list<array<string, mixed>> $andRules */
            $andRules = array_values(array_filter(
                $rule['and'],
                fn (mixed $item): bool => is_array($item),
            ));

            return $andRules;
        }

        if (self::isRuleList($rule)) {
            /** @var list<array<string, mixed>> $rule */
            return $rule;
        }

        return [$rule];
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $rule
     */
    private static function isRuleList(array $rule): bool
    {
        if ($rule === []) {
            return false;
        }

        return array_is_list($rule) && is_array($rule[0] ?? null);
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private static function evaluateSingle(array $rule, callable $get, ?Model $record): bool
    {
        $source = (string) ($rule['source'] ?? 'flex_field');
        $field = (string) ($rule['field'] ?? '');
        $operator = (string) ($rule['operator'] ?? '');
        $expected = $rule['value'] ?? null;

        if ($field === '') {
            return false;
        }

        if (! str_contains($field, '.')) {
            $field = FlexFieldConditionPaths::qualify($source, $field);
        }

        $actual = match ($source) {
            'model' => $get(ltrim(str_replace('model.', '', $field), '.')),
            'relation' => self::resolveRelationValue(
                str_starts_with($field, 'relation.') ? substr($field, 9) : $field,
                $record,
            ),
            default => $get(str_starts_with($field, 'flex_field.')
                ? substr($field, 11)
                : $field),
        };

        return match ($operator) {
            'equals' => self::valuesEqual($actual, $expected),
            'not_equals' => ! self::valuesEqual($actual, $expected),
            'filled' => filled($actual),
            'empty' => blank($actual),
            'in' => self::valueIn($actual, $expected),
            'contains' => is_string($actual) && is_string($expected) && str_contains($actual, $expected),
            'not_contains' => is_string($actual) && is_string($expected) && ! str_contains($actual, $expected),
            'greater_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'less_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            default => false,
        };
    }

    private static function valuesEqual(mixed $actual, mixed $expected): bool
    {
        if (is_numeric($actual) && is_numeric($expected)) {
            return (float) $actual === (float) $expected;
        }

        return $actual == $expected;
    }

    private static function valueIn(mixed $actual, mixed $expected): bool
    {
        if (! is_array($expected)) {
            return false;
        }

        foreach ($expected as $candidate) {
            if (self::valuesEqual($actual, $candidate)) {
                return true;
            }
        }

        return false;
    }
}

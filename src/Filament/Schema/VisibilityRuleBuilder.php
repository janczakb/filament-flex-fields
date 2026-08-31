<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Schema;

use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldConditionPaths;
use Bjanczak\FilamentFlexFields\Support\Schema\JsonFieldConditions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

final class VisibilityRuleBuilder
{
    /**
     * @param  callable(): array<string, string>|null  $flexFieldOptions
     * @param  callable(): array<string, string>|null  $modelAttributeOptions
     */
    public static function make(
        string $name,
        ?callable $flexFieldOptions = null,
        ?callable $modelAttributeOptions = null,
    ): Repeater {
        return Repeater::make($name)
            ->label(__('filament-flex-fields::default.schema.visibility_rules'))
            ->schema([
                Select::make('source')
                    ->label(__('filament-flex-fields::default.schema.visibility_source'))
                    ->options([
                        'flex_field' => __('filament-flex-fields::default.schema.visibility_source_flex_field'),
                        'model' => __('filament-flex-fields::default.schema.visibility_source_model'),
                        'relation' => __('filament-flex-fields::default.schema.visibility_source_relation'),
                    ])
                    ->default('flex_field')
                    ->required()
                    ->live()
                    ->native(false),
                Select::make('field')
                    ->label(__('filament-flex-fields::default.schema.visibility_field'))
                    ->options(function (Get $get) use ($flexFieldOptions, $modelAttributeOptions): array {
                        return match ($get('source')) {
                            'model' => is_callable($modelAttributeOptions) ? ($modelAttributeOptions() ?? []) : [],
                            'relation' => [],
                            default => is_callable($flexFieldOptions) ? ($flexFieldOptions() ?? []) : [],
                        };
                    })
                    ->searchable()
                    ->required()
                    ->visible(fn (Get $get): bool => $get('source') !== 'relation'),
                TextInput::make('field')
                    ->label(__('filament-flex-fields::default.schema.visibility_relation_path'))
                    ->placeholder('owner.email')
                    ->required()
                    ->visible(fn (Get $get): bool => $get('source') === 'relation'),
                Select::make('operator')
                    ->label(__('filament-flex-fields::default.schema.visibility_operator'))
                    ->options(collect(JsonFieldConditions::OPERATORS)
                        ->mapWithKeys(fn (string $operator): array => [
                            $operator => __('filament-flex-fields::default.schema.visibility_operator_'.$operator),
                        ])
                        ->all())
                    ->required()
                    ->native(false),
                TextInput::make('value')
                    ->label(__('filament-flex-fields::default.schema.visibility_value'))
                    ->visible(fn (Get $get): bool => ! in_array($get('operator'), ['filled', 'empty'], true)),
            ])
            ->columns(2)
            ->collapsible()
            ->default([])
            ->formatStateUsing(fn (?array $state): array => self::rulesToRepeaterState($state))
            ->dehydrateStateUsing(fn (?array $state): ?array => self::repeaterStateToRules($state));
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>|null  $stored
     * @return list<array<string, mixed>>
     */
    public static function rulesToRepeaterState(?array $stored): array
    {
        if ($stored === null || $stored === []) {
            return [];
        }

        $rules = $stored;

        if (isset($stored['and']) && is_array($stored['and'])) {
            $rules = $stored['and'];
        }

        if (! array_is_list($rules)) {
            $rules = [$rules];
        }

        $items = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $field = (string) ($rule['field'] ?? '');
            $source = (string) ($rule['source'] ?? 'flex_field');

            if ($source === 'flex_field' && str_starts_with($field, 'flex_field.')) {
                $field = substr($field, strlen('flex_field.'));
            }

            if ($source === 'model' && str_starts_with($field, 'model.')) {
                $field = substr($field, strlen('model.'));
            }

            if ($source === 'relation' && str_starts_with($field, 'relation.')) {
                $field = substr($field, strlen('relation.'));
            }

            $items[] = [
                'source' => $source,
                'field' => $field,
                'operator' => (string) ($rule['operator'] ?? 'equals'),
                'value' => $rule['value'] ?? null,
            ];
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>|null  $state
     * @return array<string, mixed>|null
     */
    public static function repeaterStateToRules(?array $state): ?array
    {
        if ($state === null || $state === []) {
            return null;
        }

        $rules = [];

        foreach ($state as $item) {
            if (! is_array($item)) {
                continue;
            }

            $source = (string) ($item['source'] ?? 'flex_field');
            $field = (string) ($item['field'] ?? '');

            $rules[] = [
                'source' => $source,
                'field' => FlexFieldConditionPaths::qualify($source, $field),
                'operator' => (string) ($item['operator'] ?? 'equals'),
                'value' => $item['value'] ?? null,
            ];
        }

        if ($rules === []) {
            return null;
        }

        return ['and' => $rules];
    }
}

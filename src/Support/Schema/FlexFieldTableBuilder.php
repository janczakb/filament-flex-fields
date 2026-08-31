<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\RatingColumn;
use Bjanczak\FilamentFlexFields\Filament\Tables\Columns\SignaturePreviewColumn;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

final class FlexFieldTableBuilder
{
    /**
     * @param  iterable<FlexFieldDefinition>  $definitions
     * @return list<Column>
     */
    public function build(iterable $definitions, string $valuesColumn = ''): array
    {
        $column = $valuesColumn !== '' ? $valuesColumn : FlexFieldsConfig::getValuesColumn();
        $columns = [];

        foreach ($definitions as $definition) {
            if (! $definition->isActive) {
                continue;
            }

            $columns[] = $this->makeColumn($definition, $column);
        }

        return $columns;
    }

    public function makeColumn(FlexFieldDefinition $definition, string $valuesColumn): Column
    {
        $statePath = "{$valuesColumn}.{$definition->slug}";

        return match ($definition->type->value) {
            'toggle', 'switch_field' => IconColumn::make("flex_{$definition->slug}")
                ->label($definition->label)
                ->boolean()
                ->getStateUsing(fn ($record): mixed => data_get($record, $statePath))
                ->toggleable(isToggledHiddenByDefault: (bool) ($definition->config['list_toggleable_hidden'] ?? false)),
            'rating', 'nps_field' => RatingColumn::make("flex_{$definition->slug}")
                ->label($definition->label)
                ->getStateUsing(fn ($record): mixed => data_get($record, $statePath))
                ->toggleable(),
            'signature' => SignaturePreviewColumn::make("flex_{$definition->slug}")
                ->label($definition->label)
                ->getStateUsing(fn ($record): mixed => data_get($record, $statePath))
                ->toggleable(),
            default => TextColumn::make("flex_{$definition->slug}")
                ->label($definition->label)
                ->getStateUsing(fn ($record): mixed => $this->formatValue(
                    data_get($record, $statePath),
                    $definition,
                ))
                ->searchable(query: function ($query, string $search) use ($statePath): void {
                    $query->where($statePath, 'like', "%{$search}%");
                })
                ->sortable(query: function ($query, string $direction) use ($statePath): void {
                    $query->orderBy($statePath, $direction);
                })
                ->toggleable(isToggledHiddenByDefault: (bool) ($definition->config['list_toggleable_hidden'] ?? false)),
        };
    }

    protected function formatValue(mixed $value, FlexFieldDefinition $definition): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($definition->type->value, ['select', 'single_select', 'select_field'], true)) {
            $options = $definition->config['options'] ?? [];

            if (is_array($options) && is_string($value) && isset($options[$value])) {
                return is_string($options[$value]) ? $options[$value] : $value;
            }
        }

        if ($definition->type->value === 'currency') {
            $currency = (string) ($definition->config['currency'] ?? 'USD');

            return is_numeric($value) ? number_format((float) $value, 2).' '.$currency : $value;
        }

        if (is_array($value)) {
            return implode(', ', array_map(
                fn (mixed $item): string => is_scalar($item) ? (string) $item : json_encode($item),
                $value,
            ));
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        return $value;
    }
}

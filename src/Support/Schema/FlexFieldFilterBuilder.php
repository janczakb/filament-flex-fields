<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

final class FlexFieldFilterBuilder
{
    /**
     * @param  iterable<FlexFieldDefinition>  $definitions
     * @return list<Filter>
     */
    public function build(iterable $definitions, string $valuesColumn = ''): array
    {
        $column = $valuesColumn !== '' ? $valuesColumn : FlexFieldsConfig::getValuesColumn();
        $filters = [];

        foreach ($definitions as $definition) {
            if (! $definition->isActive) {
                continue;
            }

            $filter = $this->makeFilter($definition, $column);

            if ($filter !== null) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    public function makeFilter(FlexFieldDefinition $definition, string $valuesColumn): ?Filter
    {
        $statePath = "{$valuesColumn}.{$definition->slug}";

        if (in_array($definition->type->value, ['toggle', 'switch_field'], true)) {
            return TernaryFilter::make("flex_filter_{$definition->slug}")
                ->label($definition->label)
                ->queries(
                    true: fn (Builder $query): Builder => $query->where($statePath, true),
                    false: fn (Builder $query): Builder => $query->where($statePath, false),
                    blank: fn (Builder $query): Builder => $query,
                );
        }

        if (in_array($definition->type->value, ['select', 'single_select', 'select_field'], true)) {
            $options = $definition->config['options'] ?? [];

            if (! is_array($options) || $options === []) {
                return null;
            }

            return SelectFilter::make("flex_filter_{$definition->slug}")
                ->label($definition->label)
                ->options($options)
                ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                    ? $query->where($statePath, $data['value'])
                    : $query);
        }

        return Filter::make("flex_filter_{$definition->slug}")
            ->label($definition->label)
            ->form([
                \Filament\Forms\Components\TextInput::make('value')
                    ->label($definition->label),
            ])
            ->query(function (Builder $query, array $data) use ($statePath): Builder {
                $value = $data['value'] ?? null;

                if (! filled($value)) {
                    return $query;
                }

                return $query->where($statePath, 'like', '%'.$value.'%');
            });
    }
}

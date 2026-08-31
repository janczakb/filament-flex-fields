<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Support\FlexFieldsConfig;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;

final class FlexFieldInfolistBuilder
{
    /**
     * @param  iterable<FlexFieldDefinition>  $definitions
     * @return list<Entry>
     */
    public function build(iterable $definitions, string $valuesColumn = ''): array
    {
        $column = $valuesColumn !== '' ? $valuesColumn : FlexFieldsConfig::getValuesColumn();
        $entries = [];

        foreach ($definitions as $definition) {
            if (! $definition->isActive) {
                continue;
            }

            if (($definition->config['visible_in_view'] ?? true) === false) {
                continue;
            }

            $entries[] = $this->makeEntry($definition, $column);
        }

        return $entries;
    }

    public function makeEntry(FlexFieldDefinition $definition, string $valuesColumn): Entry
    {
        $statePath = "{$valuesColumn}.{$definition->slug}";

        if (in_array($definition->type->value, ['toggle', 'switch_field'], true)) {
            return IconEntry::make("flex_{$definition->slug}")
                ->label($definition->label)
                ->boolean()
                ->getStateUsing(fn ($record): mixed => data_get($record, $statePath));
        }

        if ($definition->type->value === 'currency') {
            return TextEntry::make("flex_{$definition->slug}")
                ->label($definition->label)
                ->getStateUsing(function ($record) use ($statePath, $definition): mixed {
                    $value = data_get($record, $statePath);

                    if (! is_numeric($value)) {
                        return $value;
                    }

                    $currency = (string) ($definition->config['currency'] ?? 'USD');

                    return number_format((float) $value, 2).' '.$currency;
                })
                ->placeholder('—');
        }

        if (in_array($definition->type->value, ['rich_editor', 'markdown_editor'], true)) {
            return TextEntry::make("flex_{$definition->slug}")
                ->label($definition->label)
                ->getStateUsing(fn ($record): mixed => data_get($record, $statePath))
                ->html()
                ->placeholder('—');
        }

        return TextEntry::make("flex_{$definition->slug}")
            ->label($definition->label)
            ->getStateUsing(fn ($record): mixed => $this->formatValue(data_get($record, $statePath), $definition))
            ->placeholder('—');
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

        if (is_array($value)) {
            return implode(', ', array_map(
                fn (mixed $item): string => is_scalar($item) ? (string) $item : json_encode($item),
                $value,
            ));
        }

        return $value;
    }
}

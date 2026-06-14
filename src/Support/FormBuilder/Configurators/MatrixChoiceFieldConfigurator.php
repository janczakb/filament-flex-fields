<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MatrixChoiceField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class MatrixChoiceFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof MatrixChoiceField);

        return $this->configureMatrixChoiceField($field, $config);
    }

    public function configureMatrixChoiceField(MatrixChoiceField $field, array $config): MatrixChoiceField
    {
        $field = $field
            ->rows($config['rows'] ?? [])
            ->matrixColumns($config['columns'] ?? [])
            ->size($config['size'] ?? config('filament-flex-fields.ui.matrix_choice_size', 'md'));

        if (isset($config['mode'])) {
            $field->mode($config['mode']);
        }

        if (isset($config['column_icons']) && is_array($config['column_icons'])) {
            $field->columnIcons($config['column_icons']);
        }

        if (isset($config['disabled_rows']) && is_array($config['disabled_rows'])) {
            $field->disabledRows($config['disabled_rows']);
        }

        if (isset($config['required_rows']) && is_array($config['required_rows'])) {
            $field->requiredRows($config['required_rows']);
        }

        if (isset($config['disabled_cells']) && is_array($config['disabled_cells'])) {
            $field->disabledCells($config['disabled_cells']);
        }

        if (isset($config['disable_cell_when']) && is_array($config['disable_cell_when'])) {
            foreach ($config['disable_cell_when'] as $rule) {
                if (! is_array($rule)) {
                    continue;
                }

                $field->disableCellWhen(
                    (string) ($rule['row'] ?? ''),
                    (string) ($rule['column'] ?? ''),
                    (string) ($rule['when_row'] ?? ''),
                    $rule['when_columns'] ?? $rule['when_column'] ?? [],
                );
            }
        }

        if (isset($config['disable_row_when']) && is_array($config['disable_row_when'])) {
            foreach ($config['disable_row_when'] as $rule) {
                if (! is_array($rule)) {
                    continue;
                }

                $field->disableRowWhen(
                    (string) ($rule['row'] ?? ''),
                    (string) ($rule['when_row'] ?? ''),
                    $rule['when_columns'] ?? $rule['when_column'] ?? [],
                );
            }
        }

        if (isset($config['color'])) {
            $field->color($config['color']);
        }

        return $field;
    }
}

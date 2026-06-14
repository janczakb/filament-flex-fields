<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MatrixChoiceField extends Field
{
    use HasControlSize;

    protected string $view = 'filament-flex-fields::forms.components.matrix-choice-field';

    protected string|Closure $mode = 'radio';

    /**
     * @var array<string | int, string | array<string, mixed>> | Closure
     */
    protected array|Closure $rows = [];

    /**
     * @var array<string | int, string | array<string, mixed>> | Closure
     */
    protected array|Closure $matrixColumns = [];

    /**
     * @var array<string | int, string> | Closure
     */
    protected array|Closure $columnIcons = [];

    /**
     * @var array<string | int> | Closure
     */
    protected array|Closure $disabledRows = [];

    /**
     * @var array<string | int> | Closure
     */
    protected array|Closure $requiredRows = [];

    /**
     * @var array<string, list<string | int>> | Closure
     */
    protected array|Closure $disabledCells = [];

    /**
     * @var list<array{
     *     type: 'cell'|'row',
     *     row: string,
     *     column: ?string,
     *     when_row: string,
     *     when_columns: list<string>,
     * }>
     */
    protected array $conditionalDisableRules = [];

    protected string|Closure|null $color = 'primary';

    /**
     * @var array<string, array{
     *     label: string,
     *     description: ?string,
     *     disabled: bool,
     *     required: bool,
     *     min_selections: ?int,
     *     max_selections: ?int,
     * }>|null
     */
    protected ?array $normalizedRowsCache = null;

    /**
     * @var array<string, array{
     *     label: string,
     *     icon: ?string,
     *     disabled: bool,
     * }>|null
     */
    protected ?array $normalizedColumnsCache = null;

    /**
     * @var array<string, array<int, string>>|null
     */
    protected ?array $disabledCellsMapCache = null;

    public function mode(string|Closure $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode(): string
    {
        $mode = (string) $this->evaluate($this->mode);

        if (! in_array($mode, ['radio', 'checkbox'], true)) {
            throw new InvalidArgumentException("Matrix choice mode [{$mode}] is not supported.");
        }

        return $mode;
    }

    public function isCheckboxMode(): bool
    {
        return $this->getMode() === 'checkbox';
    }

    /**
     * @param  array<string | int, string | array<string, mixed>> | Closure  $rows
     */
    public function rows(array|Closure $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * @param  array<string | int, string | array<string, mixed>> | Closure  $columns
     */
    public function matrixColumns(array|Closure $columns): static
    {
        $this->matrixColumns = $columns;

        return $this;
    }

    /**
     * @param  array<string | int, string> | Closure  $icons
     */
    public function columnIcons(array|Closure $icons): static
    {
        $this->columnIcons = $icons;

        return $this;
    }

    /**
     * @param  array<string | int> | Closure  $keys
     */
    public function disabledRows(array|Closure $keys): static
    {
        $this->disabledRows = $keys;

        return $this;
    }

    /**
     * @param  array<string | int> | Closure  $keys
     */
    public function requiredRows(array|Closure $keys): static
    {
        $this->requiredRows = $keys;

        return $this;
    }

    /**
     * @param  array<string, list<string | int>> | Closure  $cells
     */
    public function disabledCells(array|Closure $cells): static
    {
        $this->disabledCells = $cells;

        return $this;
    }

    /**
     * Disable a cell when another row matches one of the given column keys.
     *
     * @param  string|list<string>  $whenColumns
     */
    public function disableCellWhen(
        string $row,
        string $column,
        string $whenRow,
        string|array $whenColumns,
    ): static {
        $this->conditionalDisableRules[] = [
            'type' => 'cell',
            'row' => $row,
            'column' => $column,
            'when_row' => $whenRow,
            'when_columns' => collect(Arr::wrap($whenColumns))
                ->map(fn (mixed $columnKey): string => (string) $columnKey)
                ->values()
                ->all(),
        ];

        return $this;
    }

    /**
     * Disable an entire row when another row matches one of the given column keys.
     *
     * @param  string|list<string>  $whenColumns
     */
    public function disableRowWhen(
        string $row,
        string $whenRow,
        string|array $whenColumns,
    ): static {
        $this->conditionalDisableRules[] = [
            'type' => 'row',
            'row' => $row,
            'column' => null,
            'when_row' => $whenRow,
            'when_columns' => collect(Arr::wrap($whenColumns))
                ->map(fn (mixed $columnKey): string => (string) $columnKey)
                ->values()
                ->all(),
        ];

        return $this;
    }

    /**
     * @return list<array{
     *     type: 'cell'|'row',
     *     row: string,
     *     column: ?string,
     *     when_row: string,
     *     when_columns: list<string>,
     * }>
     */
    public function getConditionalDisableRules(): array
    {
        return $this->conditionalDisableRules;
    }

    /**
     * @param  array{
     *     type: 'cell'|'row',
     *     row: string,
     *     column: ?string,
     *     when_row: string,
     *     when_columns: list<string>,
     * }  $rule
     * @param  array<string, mixed>  $state
     */
    public function matchesConditionalDisableRule(array $rule, array $state): bool
    {
        $whenRow = $rule['when_row'];
        $whenColumns = $rule['when_columns'];
        $value = $state[$whenRow] ?? ($this->isCheckboxMode() ? [] : null);

        if ($this->isCheckboxMode()) {
            $selected = collect(Arr::wrap($value))
                ->map(fn (mixed $columnKey): string => (string) $columnKey);

            return $selected->intersect($whenColumns)->isNotEmpty();
        }

        return filled($value) && in_array((string) $value, $whenColumns, true);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function isRowConditionallyDisabled(string $rowKey, array $state): bool
    {
        foreach ($this->getConditionalDisableRules() as $rule) {
            if ($rule['type'] !== 'row' || $rule['row'] !== $rowKey) {
                continue;
            }

            if ($this->matchesConditionalDisableRule($rule, $state)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function isCellConditionallyDisabled(string $rowKey, string $columnKey, array $state): bool
    {
        foreach ($this->getConditionalDisableRules() as $rule) {
            if ($rule['type'] !== 'cell' || $rule['row'] !== $rowKey || $rule['column'] !== $columnKey) {
                continue;
            }

            if ($this->matchesConditionalDisableRule($rule, $state)) {
                return true;
            }
        }

        return false;
    }

    public function color(string|Closure|null $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->evaluate($this->color);
    }

    /**
     * @return list<string>
     */
    public function getRowKeys(): array
    {
        return array_keys($this->getNormalizedRows());
    }

    /**
     * @return list<string>
     */
    public function getColumnKeys(): array
    {
        return array_keys($this->getNormalizedColumns());
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     description: ?string,
     *     disabled: bool,
     *     required: bool,
     *     min_selections: ?int,
     *     max_selections: ?int,
     * }>
     */
    public function getNormalizedRows(): array
    {
        if ($this->normalizedRowsCache !== null) {
            return $this->normalizedRowsCache;
        }

        $disabledRows = collect($this->getDisabledRowKeys())->map(fn ($key): string => (string) $key);
        $requiredRows = collect($this->getRequiredRowKeys())->map(fn ($key): string => (string) $key);
        $normalized = [];

        foreach ($this->evaluate($this->rows) as $value => $row) {
            $key = (string) $value;

            if (is_string($row)) {
                $normalized[$key] = $this->normalizeRowArray($key, [
                    'label' => $row,
                ], $disabledRows, $requiredRows);

                continue;
            }

            if (is_array($row)) {
                $normalized[$key] = $this->normalizeRowArray($key, $row, $disabledRows, $requiredRows);
            }
        }

        return $this->normalizedRowsCache = $normalized;
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     icon: ?string,
     *     disabled: bool,
     * }>
     */
    public function getNormalizedColumns(): array
    {
        if ($this->normalizedColumnsCache !== null) {
            return $this->normalizedColumnsCache;
        }

        $icons = collect($this->evaluate($this->columnIcons))->mapWithKeys(
            fn (mixed $icon, string|int $key): array => [(string) $key => (string) $icon],
        );
        $normalized = [];

        foreach ($this->evaluate($this->matrixColumns) as $value => $column) {
            $key = (string) $value;

            if (is_string($column)) {
                $normalized[$key] = [
                    'label' => $column,
                    'icon' => $icons->get($key),
                    'disabled' => false,
                ];

                continue;
            }

            if (is_array($column)) {
                $icon = $column['icon'] ?? $icons->get($key);

                $normalized[$key] = [
                    'label' => (string) ($column['label'] ?? $key),
                    'icon' => filled($icon) ? (string) $icon : null,
                    'disabled' => (bool) ($column['disabled'] ?? false),
                ];
            }
        }

        return $this->normalizedColumnsCache = $normalized;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getDisabledCellsMap(): array
    {
        if ($this->disabledCellsMapCache !== null) {
            return $this->disabledCellsMapCache;
        }

        return $this->disabledCellsMapCache = collect($this->evaluate($this->disabledCells))
            ->mapWithKeys(fn (mixed $columns, string|int $rowKey): array => [
                (string) $rowKey => collect(Arr::wrap($columns))
                    ->map(fn (mixed $columnKey): string => (string) $columnKey)
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    public function isRowDisabled(string $rowKey, ?array $state = null): bool
    {
        if ($this->getNormalizedRows()[$rowKey]['disabled'] ?? true) {
            return true;
        }

        if ($state !== null && $this->isRowConditionallyDisabled($rowKey, $state)) {
            return true;
        }

        return false;
    }

    public function isCellDisabled(string $rowKey, string $columnKey, ?array $state = null): bool
    {
        if ($this->isRowDisabled($rowKey, $state)) {
            return true;
        }

        $column = $this->getNormalizedColumns()[$columnKey] ?? null;

        if ($column === null || $column['disabled']) {
            return true;
        }

        if (in_array($columnKey, $this->getDisabledCellsMap()[$rowKey] ?? [], true)) {
            return true;
        }

        if ($state !== null && $this->isCellConditionallyDisabled($rowKey, $columnKey, $state)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-matrix-choice',
            'fff-matrix-choice--'.$this->getSize(),
            'fff-matrix-choice--'.$this->getMode(),
        ];

        if ($color = $this->getColor()) {
            $classes[] = 'fi-color-'.$color;
        }

        return $classes;
    }

    /**
     * @return array<string, string>
     */
    public function getMatrixSizeStyles(): array
    {
        return match ($this->getSize()) {
            'sm' => [
                '--fff-matrix-choice-radius' => '0.875rem',
                '--fff-matrix-choice-frame-padding' => '0.3125rem',
                '--fff-matrix-choice-header-py' => '0.625rem',
                '--fff-matrix-choice-header-px' => '0.5rem',
                '--fff-matrix-choice-row-py' => '0.625rem',
                '--fff-matrix-choice-row-px' => '0.75rem',
                '--fff-matrix-choice-row-label-size' => '0.8125rem',
                '--fff-matrix-choice-column-label-size' => '0.75rem',
                '--fff-matrix-choice-indicator-size' => '0.875rem',
                '--fff-matrix-choice-indicator-icon-size' => '0.5rem',
            ],
            'lg' => [
                '--fff-matrix-choice-radius' => '1.125rem',
                '--fff-matrix-choice-frame-padding' => '0.5rem',
                '--fff-matrix-choice-header-py' => '0.875rem',
                '--fff-matrix-choice-header-px' => '0.75rem',
                '--fff-matrix-choice-row-py' => '0.875rem',
                '--fff-matrix-choice-row-px' => '1rem',
                '--fff-matrix-choice-row-label-size' => '1rem',
                '--fff-matrix-choice-column-label-size' => '0.875rem',
                '--fff-matrix-choice-indicator-size' => '1.25rem',
                '--fff-matrix-choice-indicator-icon-size' => '1rem',
            ],
            default => [
                '--fff-matrix-choice-radius' => '1rem',
                '--fff-matrix-choice-frame-padding' => '0.375rem',
                '--fff-matrix-choice-header-py' => '0.75rem',
                '--fff-matrix-choice-header-px' => '0.625rem',
                '--fff-matrix-choice-row-py' => '0.75rem',
                '--fff-matrix-choice-row-px' => '0.875rem',
                '--fff-matrix-choice-row-label-size' => '0.875rem',
                '--fff-matrix-choice-column-label-size' => '0.8125rem',
                '--fff-matrix-choice-indicator-size' => '1rem',
                '--fff-matrix-choice-indicator-icon-size' => '0.75rem',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function getInitialState(): array
    {
        $state = $this->getState();

        if (! is_array($state)) {
            $state = [];
        }

        $normalized = [];

        foreach ($this->getRowKeys() as $rowKey) {
            $value = $state[$rowKey] ?? null;

            if ($this->isCheckboxMode()) {
                $normalized[$rowKey] = collect(Arr::wrap($value))
                    ->map(fn (mixed $columnKey): string => (string) $columnKey)
                    ->values()
                    ->all();

                continue;
            }

            $normalized[$rowKey] = filled($value) ? (string) $value : null;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<int|string, string>  $disabledRows
     * @param  Collection<int|string, string>  $requiredRows
     * @return array{
     *     label: string,
     *     description: ?string,
     *     disabled: bool,
     *     required: bool,
     *     min_selections: ?int,
     *     max_selections: ?int,
     * }
     */
    protected function normalizeRowArray(
        string $key,
        array $row,
        Collection $disabledRows,
        Collection $requiredRows,
    ): array {
        $required = array_key_exists('required', $row)
            ? (bool) $row['required']
            : $requiredRows->contains($key);

        if ($this->isRequired() && ! array_key_exists('required', $row) && ! $requiredRows->isNotEmpty()) {
            $required = true;
        }

        $minSelections = $row['min_selections'] ?? $row['min'] ?? null;
        $maxSelections = $row['max_selections'] ?? $row['max'] ?? null;

        return [
            'label' => (string) ($row['label'] ?? $key),
            'description' => filled($row['description'] ?? $row['desc'] ?? null)
                ? (string) ($row['description'] ?? $row['desc'])
                : null,
            'disabled' => (bool) ($row['disabled'] ?? false) || $disabledRows->contains($key),
            'required' => $required,
            'min_selections' => is_numeric($minSelections) ? (int) $minSelections : null,
            'max_selections' => is_numeric($maxSelections) ? (int) $maxSelections : null,
        ];
    }

    /**
     * @return array<string | int>
     */
    public function getDisabledRowKeys(): array
    {
        return Arr::wrap($this->evaluate($this->disabledRows));
    }

    /**
     * @return array<string | int>
     */
    public function getRequiredRowKeys(): array
    {
        return Arr::wrap($this->evaluate($this->requiredRows));
    }

    /**
     * @return array<string, string|list<string>>
     */
    public function dehydrateValue(mixed $state): array
    {
        if (! is_array($state)) {
            return [];
        }

        $normalized = [];

        foreach ($this->getRowKeys() as $rowKey) {
            $value = $state[$rowKey] ?? null;

            if ($this->isCheckboxMode()) {
                $selected = collect(Arr::wrap($value))
                    ->map(fn (mixed $columnKey): string => (string) $columnKey)
                    ->filter(fn (string $columnKey): bool => array_key_exists($columnKey, $this->getNormalizedColumns()))
                    ->values()
                    ->all();

                if ($selected !== []) {
                    $normalized[$rowKey] = $selected;
                }

                continue;
            }

            if (filled($value) && array_key_exists((string) $value, $this->getNormalizedColumns())) {
                $normalized[$rowKey] = (string) $value;
            }
        }

        return $normalized;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(function (MatrixChoiceField $component, mixed $state): void {
            if (! is_array($state)) {
                $component->state([]);

                return;
            }

            $component->state($component->getInitialState());
        });

        $this->dehydrateStateUsing(function (MatrixChoiceField $component, mixed $state): array {
            return $component->dehydrateValue($state);
        });

        $this->rule(function (MatrixChoiceField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if (! is_array($value)) {
                    $fail(__('filament-flex-fields::default.validation.matrix_choice.invalid'));

                    return;
                }

                $rows = $component->getNormalizedRows();
                $columnKeys = $component->getColumnKeys();
                $disabledCells = $component->getDisabledCellsMap();

                foreach ($rows as $rowKey => $row) {
                    if ($row['disabled']) {
                        continue;
                    }

                    $rowValue = $value[$rowKey] ?? ($component->isCheckboxMode() ? [] : null);

                    if ($component->isCheckboxMode()) {
                        $selected = collect(Arr::wrap($rowValue))
                            ->map(fn (mixed $columnKey): string => (string) $columnKey)
                            ->unique()
                            ->values();

                        if ($row['required'] && $selected->isEmpty()) {
                            $fail(__('filament-flex-fields::default.validation.matrix_choice.row_required', [
                                'row' => $row['label'],
                            ]));

                            return;
                        }

                        foreach ($selected as $columnKey) {
                            if (! in_array($columnKey, $columnKeys, true)) {
                                $fail(__('filament-flex-fields::default.validation.matrix_choice.invalid_option'));

                                return;
                            }

                            if ($component->isCellDisabled($rowKey, $columnKey, $value)) {
                                $fail(__('filament-flex-fields::default.validation.matrix_choice.invalid_option'));

                                return;
                            }
                        }

                        $min = $row['min_selections'];

                        if ($row['required'] && $min === null) {
                            $min = 1;
                        }

                        if ($min !== null && $selected->count() < $min) {
                            $fail(__('filament-flex-fields::default.validation.matrix_choice.row_min', [
                                'row' => $row['label'],
                                'count' => $min,
                            ]));

                            return;
                        }

                        $max = $row['max_selections'];

                        if ($max !== null && $selected->count() > $max) {
                            $fail(__('filament-flex-fields::default.validation.matrix_choice.row_max', [
                                'row' => $row['label'],
                                'count' => $max,
                            ]));

                            return;
                        }

                        continue;
                    }

                    if ($row['required'] && blank($rowValue)) {
                        $fail(__('filament-flex-fields::default.validation.matrix_choice.row_required', [
                            'row' => $row['label'],
                        ]));

                        return;
                    }

                    if (blank($rowValue)) {
                        continue;
                    }

                    $columnKey = (string) $rowValue;

                    if (! in_array($columnKey, $columnKeys, true)) {
                        $fail(__('filament-flex-fields::default.validation.matrix_choice.invalid_option'));

                        return;
                    }

                    if ($component->isCellDisabled($rowKey, $columnKey, $value)) {
                        $fail(__('filament-flex-fields::default.validation.matrix_choice.invalid_option'));

                        return;
                    }
                }

                foreach ($value as $rowKey => $rowValue) {
                    if (! array_key_exists((string) $rowKey, $rows)) {
                        $fail(__('filament-flex-fields::default.validation.matrix_choice.invalid'));

                        return;
                    }
                }
            };
        });
    }
}

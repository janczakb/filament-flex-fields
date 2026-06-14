@php
    use Illuminate\Support\Js;

    $statePath = $getStatePath();
    $rows = $getNormalizedRows();
    $columns = $getNormalizedColumns();
    $isDisabled = $isDisabled();
    $wrapperClasses = $getWrapperClasses();
    $initialState = $getInitialState();
    $disabledCells = $getDisabledCellsMap();
    $conditionalDisableRules = $getConditionalDisableRules();
    $columnCount = count($columns);
    $isCheckboxMode = $isCheckboxMode();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'matrix-choice-field'])
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            mode: @js($getMode()),
            rowKeys: {{ Js::from(array_keys($rows)) }},
            columnKeys: {{ Js::from(array_keys($columns)) }},
            disabledRows: {{ Js::from(collect($rows)->mapWithKeys(fn (array $row, string $key): array => [$key => $row['disabled']])->all()) }},
            disabledCells: {{ Js::from($disabledCells) }},
            conditionalDisableRules: {{ Js::from($conditionalDisableRules) }},
            disabledColumns: {{ Js::from(collect($columns)->mapWithKeys(fn (array $column, string $key): array => [$key => $column['disabled']])->all()) }},
            disabled: @js($isDisabled),
            normalize(value) {
                return String(value);
            },
            ensureState() {
                const next = { ...(this.state ?? {}) };

                this.rowKeys.forEach((rowKey) => {
                    if (!(rowKey in next)) {
                        next[rowKey] = this.mode === 'checkbox' ? [] : null;
                    }
                });

                this.state = next;
            },
            rowSelection(rowKey) {
                const value = (this.state ?? {})[this.normalize(rowKey)];

                if (this.mode === 'checkbox') {
                    return Array.isArray(value)
                        ? value.map((item) => this.normalize(item))
                        : [];
                }

                if (value === null || value === undefined || value === '') {
                    return null;
                }

                return this.normalize(value);
            },
            matchesConditionalRule(rule) {
                const whenSelection = this.rowSelection(rule.when_row);
                const whenColumns = (rule.when_columns ?? []).map((column) => this.normalize(column));

                if (whenColumns.length === 0) {
                    return false;
                }

                if (this.mode === 'checkbox') {
                    return Array.isArray(whenSelection)
                        && whenColumns.some((column) => whenSelection.includes(column));
                }

                return whenSelection !== null && whenColumns.includes(whenSelection);
            },
            isRowConditionallyDisabled(rowKey) {
                const row = this.normalize(rowKey);

                return this.conditionalDisableRules.some((rule) => {
                    return rule.type === 'row'
                        && this.normalize(rule.row) === row
                        && this.matchesConditionalRule(rule);
                });
            },
            isCellConditionallyDisabled(rowKey, columnKey) {
                const row = this.normalize(rowKey);
                const column = this.normalize(columnKey);

                return this.conditionalDisableRules.some((rule) => {
                    return rule.type === 'cell'
                        && this.normalize(rule.row) === row
                        && this.normalize(rule.column) === column
                        && this.matchesConditionalRule(rule);
                });
            },
            isRowDisabled(rowKey) {
                return this.disabled
                    || (this.disabledRows[this.normalize(rowKey)] ?? false)
                    || this.isRowConditionallyDisabled(rowKey);
            },
            isColumnDisabled(columnKey) {
                return this.disabledColumns[this.normalize(columnKey)] ?? false;
            },
            isCellDisabled(rowKey, columnKey) {
                if (this.isRowDisabled(rowKey) || this.isColumnDisabled(columnKey)) {
                    return true;
                }

                const cells = this.disabledCells[this.normalize(rowKey)] ?? [];

                if (cells.includes(this.normalize(columnKey))) {
                    return true;
                }

                return this.isCellConditionallyDisabled(rowKey, columnKey);
            },
            pruneDisabledSelections() {
                const next = { ...(this.state ?? {}) };
                let changed = false;

                this.rowKeys.forEach((rowKey) => {
                    const row = this.normalize(rowKey);

                    if (this.isRowDisabled(row)) {
                        const current = next[row];

                        if (this.mode === 'checkbox') {
                            if (Array.isArray(current) && current.length > 0) {
                                next[row] = [];
                                changed = true;
                            }

                            return;
                        }

                        if (current !== null && current !== undefined && current !== '') {
                            next[row] = null;
                            changed = true;
                        }

                        return;
                    }

                    if (this.mode === 'checkbox') {
                        const current = Array.isArray(next[row]) ? [...next[row]] : [];
                        const filtered = current.filter((columnKey) => ! this.isCellDisabled(row, columnKey));

                        if (filtered.length !== current.length) {
                            next[row] = filtered;
                            changed = true;
                        }

                        return;
                    }

                    if (this.isCellDisabled(row, next[row])) {
                        next[row] = null;
                        changed = true;
                    }
                });

                if (changed) {
                    this.state = next;
                }
            },
            isSelected(rowKey, columnKey) {
                const row = this.normalize(rowKey);
                const column = this.normalize(columnKey);
                const selection = this.rowSelection(row);

                if (this.mode === 'checkbox') {
                    return Array.isArray(selection) && selection.includes(column);
                }

                return selection === column;
            },
            selectRadio(rowKey, columnKey) {
                if (this.isCellDisabled(rowKey, columnKey)) {
                    return;
                }

                this.ensureState();
                this.state = {
                    ...this.state,
                    [this.normalize(rowKey)]: this.normalize(columnKey),
                };
            },
            toggleCheckbox(rowKey, columnKey) {
                if (this.isCellDisabled(rowKey, columnKey)) {
                    return;
                }

                this.ensureState();

                const row = this.normalize(rowKey);
                const column = this.normalize(columnKey);
                const current = Array.isArray(this.state[row]) ? [...this.state[row]] : [];
                const index = current.indexOf(column);

                if (index >= 0) {
                    current.splice(index, 1);
                } else {
                    current.push(column);
                }

                this.state = {
                    ...this.state,
                    [row]: current,
                };
            },
            interact(rowKey, columnKey) {
                if (this.mode === 'checkbox') {
                    this.toggleCheckbox(rowKey, columnKey);
                } else {
                    this.selectRadio(rowKey, columnKey);
                }

                this.pruneDisabledSelections();
            },
        }"
        x-init="ensureState(); pruneDisabledSelections(); $watch('state', () => pruneDisabledSelections())"
        @class([
            ...$wrapperClasses,
            'is-disabled' => $isDisabled,
        ])
        @style([
            ...$getMatrixSizeStyles(),
            '--fff-matrix-choice-columns: '.$columnCount,
        ])
        role="grid"
        aria-label="{{ $getLabel() }}"
        aria-multiselectable="{{ $isCheckboxMode ? 'true' : 'false' }}"
    >
        <div class="fff-matrix-choice__frame">
            <div
                class="fff-matrix-choice__header"
                role="row"
            >
                <div class="fff-matrix-choice__corner" role="columnheader" aria-hidden="true"></div>

                @foreach ($columns as $columnKey => $column)
                    <div
                        class="fff-matrix-choice__column-header"
                        role="columnheader"
                        wire:key="{{ $statePath }}-matrix-choice-column-{{ $columnKey }}"
                    >
                        @if (filled($column['icon']))
                            <span class="fff-matrix-choice__column-icon" aria-hidden="true">
                                <x-filament::icon
                                    :icon="$column['icon']"
                                    class="fff-matrix-choice__column-icon-svg"
                                />
                            </span>
                        @endif

                        <span class="fff-matrix-choice__column-label">{{ $column['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="fff-matrix-choice__body">
                @foreach ($rows as $rowKey => $row)
                    @php
                        $initialSelection = $initialState[$rowKey] ?? ($isCheckboxMode ? [] : null);
                        $isRowInitiallyDisabled = $isDisabled || $row['disabled'];
                    @endphp

                    <div
                        class="fff-matrix-choice__row"
                        role="row"
                        aria-label="{{ $row['label'] }}"
                        wire:key="{{ $statePath }}-matrix-choice-row-{{ $rowKey }}"
                        x-bind:class="{
                            'is-disabled': isRowDisabled(@js($rowKey)),
                        }"
                        @class([
                            'is-disabled' => $isRowInitiallyDisabled,
                        ])
                    >
                        <div class="fff-matrix-choice__row-label" role="rowheader">
                            <span class="fff-matrix-choice__row-title">{{ $row['label'] }}</span>

                            @if (filled($row['description']))
                                <span class="fff-matrix-choice__row-description">{{ $row['description'] }}</span>
                            @endif
                        </div>

                        @foreach ($columns as $columnKey => $column)
                            @php
                                $isInitiallySelected = $isCheckboxMode
                                    ? in_array((string) $columnKey, $initialSelection, true)
                                    : $initialSelection === (string) $columnKey;
                                $isInitiallyDisabled = $isRowInitiallyDisabled
                                    || $column['disabled']
                                    || in_array((string) $columnKey, $disabledCells[$rowKey] ?? [], true);
                            @endphp

                            <div
                                class="fff-matrix-choice__cell"
                                role="gridcell"
                                wire:key="{{ $statePath }}-matrix-choice-cell-{{ $rowKey }}-{{ $columnKey }}"
                                x-bind:class="{
                                    'is-selected': isSelected(@js($rowKey), @js($columnKey)),
                                    'is-disabled': isCellDisabled(@js($rowKey), @js($columnKey)),
                                }"
                                @class([
                                    'is-selected' => $isInitiallySelected,
                                    'is-disabled' => $isInitiallyDisabled,
                                ])
                                x-on:click="interact(@js($rowKey), @js($columnKey))"
                                x-on:keydown.enter.prevent="interact(@js($rowKey), @js($columnKey))"
                                x-on:keydown.space.prevent="interact(@js($rowKey), @js($columnKey))"
                                tabindex="0"
                                x-bind:aria-selected="isSelected(@js($rowKey), @js($columnKey)) ? 'true' : 'false'"
                                x-bind:aria-disabled="isCellDisabled(@js($rowKey), @js($columnKey)) ? 'true' : null"
                            >
                                @if ($isCheckboxMode)
                                    <label class="fff-matrix-choice__checkbox">
                                        <input
                                            type="checkbox"
                                            class="fff-matrix-choice__input"
                                            value="{{ $columnKey }}"
                                            aria-label="{{ $row['label'] }} — {{ $column['label'] }}"
                                            @checked($isInitiallySelected)
                                            x-bind:checked="isSelected(@js($rowKey), @js($columnKey))"
                                            x-bind:disabled="isCellDisabled(@js($rowKey), @js($columnKey))"
                                            tabindex="-1"
                                            @disabled($isInitiallyDisabled)
                                        />

                                        <span class="fff-matrix-choice__control" aria-hidden="true">
                                            <span class="fff-matrix-choice__indicator fff-matrix-choice__indicator--checkbox">
                                                <svg
                                                    class="fff-matrix-choice__indicator-icon"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                    aria-hidden="true"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </span>
                                        </span>
                                    </label>
                                @else
                                    <label class="fff-matrix-choice__radio">
                                        <input
                                            type="radio"
                                            name="{{ $statePath }}[{{ $rowKey }}]"
                                            value="{{ $columnKey }}"
                                            class="fff-matrix-choice__input"
                                            aria-label="{{ $row['label'] }} — {{ $column['label'] }}"
                                            @checked($isInitiallySelected)
                                            x-bind:checked="isSelected(@js($rowKey), @js($columnKey))"
                                            x-bind:disabled="isCellDisabled(@js($rowKey), @js($columnKey))"
                                            tabindex="-1"
                                            @disabled($isInitiallyDisabled)
                                        />

                                        <span class="fff-matrix-choice__control" aria-hidden="true">
                                            <span class="fff-matrix-choice__indicator fff-matrix-choice__indicator--radio">
                                                <span class="fff-matrix-choice__indicator-dot"></span>
                                            </span>
                                        </span>
                                    </label>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-dynamic-component>

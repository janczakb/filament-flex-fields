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
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('matrix-choice-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="matrixChoiceFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            mode: @js($getMode()),
            rowKeys: {{ Js::from(array_keys($rows)) }},
            columnKeys: {{ Js::from(array_keys($columns)) }},
            disabledRows: {{ Js::from(collect($rows)->mapWithKeys(fn (array $row, string $key): array => [$key => $row['disabled']])->all()) }},
            disabledCells: {{ Js::from($disabledCells) }},
            conditionalDisableRules: {{ Js::from($conditionalDisableRules) }},
            disabledColumns: {{ Js::from(collect($columns)->mapWithKeys(fn (array $column, string $key): array => [$key => $column['disabled']])->all()) }},
            disabled: @js($isDisabled),
        })"
        x-init="init()"
        x-on:keydown="onMatrixKeydown($event)"
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
                        $rowIndex = $loop->index;
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
                                $columnIndex = $loop->index;
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
                                data-matrix-row="{{ $rowIndex }}"
                                data-matrix-col="{{ $columnIndex }}"
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
                                x-on:keydown.space="onCellSpaceKeydown($event, @js($rowKey), @js($columnKey))"
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

@php
    $statePath = $getStatePath();
    $rows = $getNormalizedRows();
    $isDisabled = $isDisabled();
    $wrapperClasses = $getWrapperClasses();
    
    $containers = $getChildComponentContainers();
    $firstContainer = head($containers);
    $schemaComponents = $firstContainer ? $firstContainer->getComponents() : [];
    $columnCount = count($schemaComponents);
    
    $columnWidths = $getColumnWidths();
    $gridColumnsTemplate = null;
    
    if (count($schemaComponents) > 0) {
        $widths = [];
        foreach ($schemaComponents as $component) {
            $name = $component->getName();
            $widths[] = $columnWidths[$name] ?? 'minmax(0, 1fr)';
        }
        $gridColumnsTemplate = 'var(--fff-matrix-choice-row-label-width) ' . implode(' ', $widths);
    }
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'matrix-choice-field'])
    
    <div
        @class([
            ...$wrapperClasses,
            'is-disabled' => $isDisabled,
        ])
        @style([
            ...$getMatrixSizeStyles(),
            '--fff-matrix-choice-columns: '.$columnCount,
            '--fff-matrix-choice-columns-template: '.$gridColumnsTemplate => $gridColumnsTemplate !== null,
        ])
    >
        <div class="fff-matrix-choice__frame">
            <div class="fff-matrix-choice__header">
                <div class="fff-matrix-choice__corner"></div>

                @foreach ($schemaComponents as $component)
                    <div class="fff-matrix-choice__column-header">
                        <span class="fff-matrix-choice__column-label">{{ $component->getLabel() }}</span>
                    </div>
                @endforeach
            </div>

            <div class="fff-matrix-choice__body">
                @foreach ($rows as $rowKey => $row)
                    @php
                        $isRowInitiallyDisabled = $isDisabled || $row['disabled'];
                        $itemContainer = $containers[$rowKey] ?? null;
                    @endphp

                    <div
                        class="fff-matrix-choice__row"
                        wire:key="{{ $statePath }}-matrix-choice-row-{{ $rowKey }}"
                        @class([
                            'is-disabled' => $isRowInitiallyDisabled,
                        ])
                    >
                        <div class="fff-matrix-choice__row-label">
                            <span class="fff-matrix-choice__row-title">{{ $row['label'] }}</span>

                            @if (filled($row['description']))
                                <span class="fff-matrix-choice__row-description">{{ $row['description'] }}</span>
                            @endif
                        </div>

                        @if ($itemContainer)
                            @foreach ($itemContainer->getComponents() as $component)
                            <div
                                class="fff-matrix-choice__cell"
                                wire:key="{{ $itemContainer->getStatePath() }}-cell-{{ $component->getName() }}"
                                @class([
                                    'is-disabled' => $isRowInitiallyDisabled,
                                ])
                                style="padding: 0.5rem; display: flex; align-items: center; justify-content: center;"
                            >
                                <div class="w-full">
                                    {{ $component }}
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-dynamic-component>

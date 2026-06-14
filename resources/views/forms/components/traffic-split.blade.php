@php
    $statePath = $getStatePath();
    $segmentCount = $getSegmentCount();
    $minWeight = $getMinWeight();
    $valueThreshold = $getValueThreshold();
    $isDisabled = $isDisabled();
    $size = $getSize();
    $variant = $getVariant();
    $labels = $getLabels();
    $lockedSegments = $getLockedSegments();
    $rawState = $getState();
    $weights = $field->normalizeWeights(is_array($rawState) ? $rawState : null);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'traffic-split'])
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('traffic-split', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="trafficSplitFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')", isOptimisticallyLive: false) }},
            segmentCount: @js($segmentCount),
            minWeight: @js($minWeight),
            valueThreshold: @js($valueThreshold),
            disabled: @js($isDisabled),
            labels: @js($labels),
            lockedSegments: @js($lockedSegments),
            initialWeights: @js($weights),
            isLinked: @js($field->isLinkedToRepeater()),
        })"
        x-init="init()"
        @class([
            'fff-traffic-split',
            'fff-traffic-split--'.$size,
            'fff-traffic-split--'.$variant,
            'is-disabled' => $isDisabled,
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div
            x-ref="container"
            class="fff-traffic-split__track"
            wire:key="{{ $statePath }}-segments-{{ $segmentCount }}"
        >
            <div class="fff-traffic-split__segments">
                @foreach (range(0, $segmentCount - 1) as $index)
                    <div
                        class="fff-traffic-split__segment-wrap"
                        style="width: {{ $weights[$index] }}%"
                        x-bind:style="'width: ' + weights[{{ $index }}] + '%;'"
                    >
                        @if ($index > 0)
                            <div class="fff-traffic-split__gap" aria-hidden="true"></div>
                        @endif

                        <div
                            class="fff-traffic-split__segment"
                            @class(['is-locked' => $field->isSegmentLocked($index)])
                            x-bind:class="{ 'is-locked': isSegmentLocked({{ $index }}) }"
                        >
                            <span
                                class="fff-traffic-split__segment-label"
                                x-text="segmentLabel({{ $index }})"
                            >{{ $getSegmentLabel($index) }}</span>
                            <span
                                class="fff-traffic-split__segment-value"
                                x-show="shouldShowValue({{ $index }})"
                                x-text="weights[{{ $index }}] + '%'"
                            >{{ $weights[$index] >= $valueThreshold ? $weights[$index].'%' : '' }}</span>
                        </div>

                        @if ($index < $segmentCount - 1)
                            <div class="fff-traffic-split__gap" aria-hidden="true"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            @foreach (range(0, $segmentCount - 2) as $index)
                @php
                    $handlePosition = array_sum(array_slice($weights, 0, $index + 1));
                @endphp
                <div
                    class="fff-traffic-split__handle"
                    style="left: {{ $handlePosition }}%"
                    x-bind:class="{ 'is-locked': isHandleLocked({{ $index }}) }"
                    x-bind:style="'left: ' + handlePosition({{ $index }}) + '%;'"
                    x-on:mousedown.stop="startDrag({{ $index }}, $event)"
                    x-on:touchstart.stop="startDrag({{ $index }}, $event)"
                    x-bind:aria-disabled="isHandleLocked({{ $index }}) ? 'true' : 'false'"
                    role="separator"
                    aria-orientation="vertical"
                    aria-label="{{ __('filament-flex-fields::default.traffic_split_resize', ['from' => $index + 1, 'to' => $index + 2]) }}"
                >
                    <span class="fff-traffic-split__handle-bar"></span>
                </div>
            @endforeach
        </div>
    </div>
</x-dynamic-component>

@php
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $wrapperClasses = $getWrapperClasses();
    $prefix = $getPrefix();
    $initialState = $field->normalizeState(is_array($getState()) ? $getState() : []);
    $rangeSpan = max($getMax() - $getMin(), 1);
    $initialMinPercent = (($initialState['min'] - $getMin()) / $rangeSpan) * 100;
    $initialMaxPercent = (($initialState['max'] - $getMin()) / $rangeSpan) * 100;
    $initialFillWidth = max(0, $initialMaxPercent - $initialMinPercent);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'price-range'])
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('price-range', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="priceRangeFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            initialState: @js($initialState),
            minBound: @js($getMin()),
            maxBound: @js($getMax()),
            step: @js($getStep()),
            integer: @js($isInteger()),
            decimalPlaces: @js($getDecimalPlaces()),
            prefix: @js($prefix),
            histogram: @js($getHistogram()),
            disabled: @js($isDisabled),
        })"
        x-init="init()"
        @class([
            'fff-price-range',
            'fff-price-range--'.$getSize(),
            'fff-price-range--'.$getVariant(),
            'is-disabled' => $isDisabled,
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div class="fff-price-range__chart" x-ref="chart" aria-hidden="true">
            <template x-for="(height, index) in displayHistogram" :key="index">
                <span
                    class="fff-price-range__bar"
                    x-bind:class="{ 'is-in-range': isBarInRange(index) }"
                    x-bind:style="'height: ' + height + '%'"
                ></span>
            </template>
        </div>

        <div class="fff-price-range__slider">
            <div
                x-ref="track"
                class="fff-price-range__track"
                x-on:pointerdown="onTrackPointerDown($event)"
                x-on:pointermove="onTrackPointerMove($event)"
                x-on:pointerup="onTrackPointerUp($event)"
                x-on:pointercancel="onTrackPointerUp($event)"
            >
                <span class="fff-price-range__track-rail" aria-hidden="true"></span>
                <span
                    class="fff-price-range__track-fill"
                    style="left: {{ $initialMinPercent }}%; width: {{ $initialFillWidth }}%"
                    x-bind:style="fillStyle"
                    aria-hidden="true"
                ></span>

                <button
                    type="button"
                    class="fff-price-range__thumb"
                    x-bind:class="{ 'is-dragging': activeThumb === 'min' }"
                    style="left: {{ $initialMinPercent }}%"
                    x-bind:style="thumbStyle(minPercent)"
                    x-on:pointerdown="onThumbPointerDown('min', $event)"
                    :disabled="disabled"
                    :aria-valuemin="minBound"
                    :aria-valuemax="maxBound"
                    :aria-valuenow="minValue"
                    :aria-valuetext="formatValue(minValue)"
                    aria-label="{{ $getMinInputLabel() }}"
                ></button>

                <button
                    type="button"
                    class="fff-price-range__thumb"
                    x-bind:class="{ 'is-dragging': activeThumb === 'max' }"
                    style="left: {{ $initialMaxPercent }}%"
                    x-bind:style="thumbStyle(maxPercent)"
                    x-on:pointerdown="onThumbPointerDown('max', $event)"
                    :disabled="disabled"
                    :aria-valuemin="minBound"
                    :aria-valuemax="maxBound"
                    :aria-valuenow="maxValue"
                    :aria-valuetext="formatValue(maxValue)"
                    aria-label="{{ $getMaxInputLabel() }}"
                ></button>
            </div>
        </div>

        @if ($shouldShowInputs())
            <div class="fff-price-range__inputs">
                <label @class([
                    'fff-price-range__input-wrap',
                    'fff-price-range__input-wrap--has-prefix' => filled($prefix),
                ])>
                    <span class="sr-only">{{ $getMinInputLabel() }}</span>
                    @if (filled($prefix))
                        <span class="fff-price-range__input-prefix">{{ $prefix }}</span>
                    @endif
                    <input
                        type="number"
                        class="fff-price-range__input"
                        value="{{ $initialState['min'] }}"
                        x-bind:value="minValue"
                        :min="minBound"
                        :max="maxBound"
                        :step="step"
                        :disabled="disabled"
                        x-on:change="onMinInput($event)"
                        x-on:input="onMinInput($event)"
                    />
                </label>

                <span class="fff-price-range__inputs-divider" aria-hidden="true"></span>

                <label @class([
                    'fff-price-range__input-wrap',
                    'fff-price-range__input-wrap--has-prefix' => filled($prefix),
                ])>
                    <span class="sr-only">{{ $getMaxInputLabel() }}</span>
                    @if (filled($prefix))
                        <span class="fff-price-range__input-prefix">{{ $prefix }}</span>
                    @endif
                    <input
                        type="number"
                        class="fff-price-range__input"
                        value="{{ $initialState['max'] }}"
                        x-bind:value="maxValue"
                        :min="minBound"
                        :max="maxBound"
                        :step="step"
                        :disabled="disabled"
                        x-on:change="onMaxInput($event)"
                        x-on:input="onMaxInput($event)"
                    />
                </label>
            </div>
        @endif
    </div>
</x-dynamic-component>

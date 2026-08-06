@php
    $statePath = $getStatePath();
    $minValue = $getMinValue();
    $maxValue = $getMaxValue();
    $step = $getStep();
    $isInteger = $isInteger();
    $isNullable = $isNullable();
    $isDisabled = $isDisabled();
    $size = $getSize();
    $variant = $getVariant();
    $displayPrefix = $getDisplayPrefix();
    $displaySuffix = $getDisplaySuffix();
    $nullLabel = $getNullLabel() ?? '—';
    $decrementIcon = $getDecrementIcon();
    $incrementIcon = $getIncrementIcon();
    $isReversed = $isReversed();
    $decimalPlaces = $getDecimalPlaces();
    $isWheelAnimated = $isWheelAnimated();
    $widthAnchorText = $getWidthAnchorText();
    $widthAnchorMainText = $getWidthAnchorMainText();
    $initialState = $getState();
    $initialDisplayMain = $field->formatDisplayMain($initialState);
    $hasInitialDisplayValue = $field->hasDisplayValue($initialState);
    $initialSizerText = $field->getInitialSizerText($initialState);

    // Filament silently drops `debounce` for `$entangle()`-bound fields (it only
    // ever emits `, true)`/`, false)` for the `live` flag), so `->live(debounce:
    // ...)` would otherwise fire a Livewire request on every +/- click. Instead,
    // entangle non-live here and let the Alpine component debounce its own
    // explicit `$wire.set(..., true)` commit when a debounce is configured.
    $liveDebounceMs = $field->isLiveDebounced() ? $field->getNormalizedLiveDebounce() : null;
    $stateBindingExpression = $liveDebounceMs !== null
        ? "\$entangle('{$statePath}', false)"
        : $applyStateBindingModifiers("\$entangle('{$statePath}')");
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'number-stepper'])
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('number-stepper', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="numberStepperFormComponent({
            state: $wire.{{ $stateBindingExpression }},
            statePath: @js($statePath),
            liveDebounce: @js($liveDebounceMs),
            min: @js($minValue),
            max: @js($maxValue),
            step: @js($step),
            integer: @js($isInteger),
            nullable: @js($isNullable),
            disabled: @js($isDisabled),
            nullLabel: @js($nullLabel),
            prefix: @js($displayPrefix),
            suffix: @js($displaySuffix),
            decimalPlaces: @js($decimalPlaces),
            wheelAnimated: @js($isWheelAnimated),
            widthAnchor: @js($widthAnchorText),
        })"
        x-init="init()"
        @class([
            'fff-number-stepper',
            'fff-number-stepper--'.$size,
            'fff-number-stepper--'.$variant,
            'is-disabled' => $isDisabled,
            'is-reversed' => $isReversed,
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        @if ($isReversed)
            @include('filament-flex-fields::forms.components.partials.number-stepper-button', [
                'action' => 'increment',
                'canProperty' => 'canIncrement',
                'icon' => $incrementIcon,
                'label' => __('filament-flex-fields::default.number_stepper_increase'),
            ])
        @else
            @include('filament-flex-fields::forms.components.partials.number-stepper-button', [
                'action' => 'decrement',
                'canProperty' => 'canDecrement',
                'icon' => $decrementIcon,
                'label' => __('filament-flex-fields::default.number_stepper_decrease'),
            ])
        @endif

        <div class="fff-number-stepper__value">
            <span
                class="fff-number-stepper__value-sizer"
                aria-hidden="true"
                x-text="valueSizerText"
            >{{ $initialSizerText }}</span>

            <div class="fff-number-stepper__value-display">
                <span
                    class="fff-number-stepper__ssr-value"
                    x-bind:class="{ 'is-replaced': flowReady }"
                >
                    @if ($hasInitialDisplayValue)
                        <span class="fff-number-stepper__ssr-main">{{ $initialDisplayMain }}</span>

                        @if (filled($displaySuffix))
                            <span class="fff-number-stepper__ssr-suffix">{!! '&nbsp;' !!}{{ $displaySuffix }}</span>
                        @endif
                    @else
                        {{ $nullLabel }}
                    @endif
                </span>

                <span
                    x-text="nullLabel"
                    class="fff-number-stepper__null-label"
                    x-bind:class="{ 'is-ready': flowReady && ! hasValue }"
                ></span>

                <number-flow
                    x-ref="numberFlow"
                    class="fff-number-stepper__flow"
                    x-bind:class="{ 'is-ready': flowReady && hasValue }"
                ></number-flow>
            </div>
        </div>

        @if ($isReversed)
            @include('filament-flex-fields::forms.components.partials.number-stepper-button', [
                'action' => 'decrement',
                'canProperty' => 'canDecrement',
                'icon' => $decrementIcon,
                'label' => __('filament-flex-fields::default.number_stepper_decrease'),
            ])
        @else
            @include('filament-flex-fields::forms.components.partials.number-stepper-button', [
                'action' => 'increment',
                'canProperty' => 'canIncrement',
                'icon' => $incrementIcon,
                'label' => __('filament-flex-fields::default.number_stepper_increase'),
            ])
        @endif
    </div>
</x-dynamic-component>

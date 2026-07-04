@php
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $hasError = filled($statePath) && $errors->has($statePath);
    $livewireKey = $getLivewireKey();
    $fieldId = $field->getCalculatorFieldId();
    $initialState = $getState();
    $initialValue = filled($initialState) ? (string) $initialState : '';
    $placeholder = $getPlaceholder() ?? __('filament-flex-fields::default.calculator.placeholder');
    $calculatorIcon = $field->getCalculatorIcon();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'calculator-field'])

    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $getVariant()])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('calculator-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="calculatorFieldFormComponent({
            fieldId: @js($fieldId),
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            label: @js($getLabel()),
            disabled: @js($isDisabled),
            readOnly: @js($isReadOnly),
            min: @js($field->getMinValue()),
            max: @js($field->getMaxValue()),
            step: @js($field->getStep()),
            integer: @js($field->isInteger()),
            decimalPlaces: @js($field->getDecimalPlaces()),
            maxLength: @js($field->getMaxLength()),
            roundingMode: @js($field->getRoundingMode()),
            calculatorLabel: @js(__('filament-flex-fields::default.calculator.open')),
            panelLabels: @js([
                'applyLabel' => __('filament-flex-fields::default.calculator.apply'),
                'closeLabel' => __('filament-flex-fields::default.calculator.close'),
                'panelTitle' => __('filament-flex-fields::default.calculator.title'),
            ]),
        })"
        x-init="init()"
        @class([
            'fff-calculator-field',
            'fff-flex-text-input',
            'fff-calculator-field--'.$getSize(),
            'fff-flex-text-input--'.$getSize(),
            'fff-calculator-field--'.$getVariant(),
            'fff-flex-text-input--'.$getVariant(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
            'is-panel-target' => false,
        ])
        x-bind:class="{ 'is-panel-target': isPanelTarget, 'is-focused': document.activeElement === $refs.numberInput }"
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div @class([
            'fff-calculator-field__shell fff-flex-text-input__shell',
            'is-invalid' => $hasError,
        ])>
            <div class="fff-calculator-field__row fff-flex-text-input__row">
                <div class="fff-calculator-field__control fff-flex-text-input__control">
                    <div class="fff-calculator-field__input-wrap">
                        <input
                            type="text"
                            inputmode="decimal"
                            class="fff-calculator-field__input fff-flex-text-input__input"
                            x-ref="numberInput"
                            value="{{ e($initialValue) }}"
                            x-model="inputValue"
                            x-on:input="onInput($event)"
                            x-on:focus="onInputFocus()"
                            placeholder="{{ e($placeholder) }}"
                            @disabled($isDisabled)
                            @readonly($isReadOnly)
                        />
                    </div>
                </div>

                <button
                    type="button"
                    class="fff-calculator-field__trigger"
                    x-ref="calculatorTrigger"
                    x-on:click.stop="openCalculator()"
                    x-bind:disabled="isLocked"
                    x-bind:aria-label="calculatorLabel"
                    x-bind:aria-expanded="isPanelTarget"
                >
                    {{ \Filament\Support\generate_icon_html($calculatorIcon, attributes: new \Illuminate\View\ComponentAttributeBag(['class' => 'fff-calculator-field__trigger-icon'])) }}
                </button>
            </div>
        </div>

        @once
            {!! \Bjanczak\FilamentFlexFields\Support\CalculatorPanelMount::renderOnce() !!}
        @endonce
    </div>
</x-dynamic-component>

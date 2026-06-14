@php
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $livewireKey = $getLivewireKey();
    $hasError = filled($statePath) && $errors->has($statePath);
    $initialState = $getState();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($field->getWrapperClasses())
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'flex-color-picker'])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $field->getLayout(), $field->getVariant(), $field->getFormat(), $field->isAlphaEnabled(), $field->isEyedropperEnabled(), $field->getGridColumns(), $field->getGridRows()])), 0, 64) }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flex-color-picker', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="flexColorPickerFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            layout: @js($field->getLayout()),
            format: @js($field->getFormat()),
            alphaEnabled: @js($field->isAlphaEnabled()),
            eyedropperEnabled: @js($field->isEyedropperEnabled()),
            gridColumns: @js($field->getGridColumns()),
            gridRows: @js($field->getGridRows()),
            gridColors: @js($field->getGridColors()),
            readOnly: @js($isDisabled || $isReadOnly),
            labels: {
                eyedropper: @js(__('filament-flex-fields::default.flex_color_picker.eyedropper')),
                format: @js(__('filament-flex-fields::default.flex_color_picker.format')),
                value: @js(__('filament-flex-fields::default.flex_color_picker.value')),
                opacity: @js(__('filament-flex-fields::default.flex_color_picker.opacity')),
            },
        })"
        x-init="init()"
        x-on:click.outside="closePanel()"
        @class([
            'fff-flex-color-picker',
            'fff-flex-text-input',
            'fff-flex-color-picker--'.$field->getSize(),
            'fff-flex-text-input--'.$field->getSize(),
            'fff-flex-color-picker--'.$field->getVariant(),
            'fff-flex-text-input--'.$field->getVariant(),
            'fff-flex-color-picker--'.$field->getLayout(),
            'is-disabled' => $isDisabled,
            'is-read-only' => $isReadOnly,
            'has-focus-outline' => $shouldShowFocusOutline(),
        ])
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div @class([
            'fff-flex-color-picker__shell fff-flex-text-input__shell',
            'is-invalid' => $hasError,
        ])>
            <div class="fff-flex-color-picker__row fff-flex-text-input__row">
                <button
                    type="button"
                    class="fff-flex-color-picker__trigger"
                    x-on:click.stop="togglePanel()"
                    x-bind:disabled="readOnly"
                    x-bind:aria-expanded="panelOpen"
                    aria-haspopup="dialog"
                >
                    <span
                        class="fff-flex-color-picker__preview"
                        x-bind:class="{ 'has-transparency': showsTransparencyPattern }"
                        @if (filled($initialState))
                            style="background-color: {{ e($initialState) }}"
                        @endif
                        x-bind:style="{ backgroundColor: swatchColor }"
                    ></span>
                    <span
                        class="fff-flex-color-picker__trigger-value"
                        x-text="state || '—'"
                    >{{ filled($initialState) ? e($initialState) : '—' }}</span>
                    <span
                        class="fff-flex-color-picker__trigger-chevron"
                        aria-hidden="true"
                    ></span>
                </button>
            </div>

            <div
                class="fff-flex-color-picker__panel"
                x-cloak
                x-show="panelOpen"
                x-transition.opacity.duration.150ms
                role="dialog"
                x-on:click.stop
            >
                <template x-if="layout === 'advanced'">
                    <div class="fff-flex-color-picker__advanced">
                        <div
                            class="fff-flex-color-picker__saturation"
                            x-ref="saturationArea"
                            x-bind:style="{ background: saturationBackground }"
                            x-on:pointerdown.prevent="startSaturationDrag($event)"
                        >
                            <span
                                class="fff-flex-color-picker__saturation-handle"
                                x-bind:style="saturationHandleStyle"
                            ></span>
                        </div>

                        <div class="fff-flex-color-picker__controls">
                            @if ($field->isEyedropperEnabled())
                                <button
                                    type="button"
                                    class="fff-flex-color-picker__eyedropper"
                                    x-show="eyedropperSupported"
                                    x-cloak
                                    x-on:click="pickFromScreen()"
                                    x-bind:disabled="readOnly"
                                    x-bind:aria-label="labels.eyedropper"
                                >
                                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-icon="leading" class="pointer-events-none size-5 shrink-0 transition-inherit-all"><path d="m10.5 6.5 7 7M2 22s4.5-.5 7-3L21 7a2.828 2.828 0 1 0-4-4L5 15c-2.5 2.5-3 7-3 7Z"></path></svg>
                                </button>
                            @endif

                            <div class="fff-flex-color-picker__sliders">
                                <div class="fff-flex-color-picker__slider-wrap">
                                    <input
                                        type="range"
                                        min="0"
                                        max="360"
                                        class="fff-flex-color-picker__hue"
                                        x-bind:value="hsva.h"
                                        x-on:input="onHueInput($event)"
                                        x-bind:disabled="readOnly"
                                        x-bind:style="{
                                            background: hueGradient,
                                            '--fff-flex-color-picker-thumb-color': hueThumbColor,
                                        }"
                                    />
                                </div>

                                <div
                                    class="fff-flex-color-picker__slider-wrap"
                                    x-show="alphaEnabled"
                                    x-cloak
                                >
                                    <div
                                        class="fff-flex-color-picker__alpha"
                                        x-bind:style="{ background: alphaGradient }"
                                    >
                                        <input
                                            type="range"
                                            min="0"
                                            max="100"
                                            class="fff-flex-color-picker__alpha-input"
                                            x-bind:value="hsva.a * 100"
                                            x-on:input="onAlphaInput($event)"
                                            x-bind:disabled="readOnly"
                                            x-bind:style="{ '--fff-flex-color-picker-thumb-color': previewColor }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="layout === 'grid'">
                    <div class="fff-flex-color-picker__grid-layout">
                        <div
                            class="fff-flex-color-picker__grid"
                            x-bind:style="{ '--fff-flex-color-picker-grid-columns': gridColumns }"
                        >
                            <template x-for="color in gridPalette" x-bind:key="color">
                                <button
                                    type="button"
                                    class="fff-flex-color-picker__grid-swatch"
                                    x-bind:style="{ backgroundColor: color }"
                                    x-bind:class="{ 'is-selected': isGridColorSelected(color) }"
                                    x-on:click="selectGridColor(color)"
                                    x-bind:disabled="readOnly"
                                    x-bind:aria-label="color"
                                ></button>
                            </template>
                        </div>

                        <div
                            class="fff-flex-color-picker__opacity-section"
                            x-show="alphaEnabled"
                            x-cloak
                        >
                            <span
                                class="fff-flex-color-picker__opacity-label"
                                x-text="labels.opacity"
                            ></span>

                            <div class="fff-flex-color-picker__slider-wrap">
                                <div
                                    class="fff-flex-color-picker__alpha"
                                    x-bind:style="{ background: alphaGradient }"
                                >
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        class="fff-flex-color-picker__alpha-input"
                                        x-bind:value="hsva.a * 100"
                                        x-on:input="onAlphaInput($event)"
                                        x-bind:disabled="readOnly"
                                        x-bind:style="{ '--fff-flex-color-picker-thumb-color': previewColor }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="fff-flex-color-picker__bottom-bar">
                    <label class="fff-flex-color-picker__format-wrap">
                        <span class="sr-only" x-text="labels.format"></span>
                        <select
                            class="fff-flex-color-picker__format"
                            x-model="inputFormat"
                            x-on:change="setInputFormat(inputFormat)"
                            x-bind:disabled="readOnly"
                        >
                            <template x-for="availableFormat in availableFormats" x-bind:key="availableFormat">
                                <option
                                    x-bind:value="availableFormat"
                                    x-text="availableFormat.toUpperCase()"
                                ></option>
                            </template>
                        </select>
                    </label>

                    <div class="fff-flex-color-picker__value-wrap">
                        <span
                            class="fff-flex-color-picker__value-preview"
                            x-bind:class="{ 'has-transparency': showsTransparencyPattern }"
                            x-bind:style="{ backgroundColor: swatchColor }"
                        ></span>

                        <input
                            type="text"
                            class="fff-flex-color-picker__value-input"
                            x-model="valueInput"
                            x-on:change="onValueInput()"
                            x-on:keydown.enter.prevent="onValueInput()"
                            x-bind:disabled="readOnly"
                            x-bind:aria-label="labels.value"
                        />

                        <span
                            class="fff-flex-color-picker__value-divider"
                            x-show="alphaEnabled"
                            x-cloak
                            aria-hidden="true"
                        ></span>

                        <input
                            type="text"
                            class="fff-flex-color-picker__opacity-input"
                            x-show="alphaEnabled"
                            x-cloak
                            x-model="opacityInput"
                            x-on:change="onOpacityInput()"
                            x-on:keydown.enter.prevent="onOpacityInput()"
                            x-bind:disabled="readOnly"
                            x-bind:aria-label="labels.opacity"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

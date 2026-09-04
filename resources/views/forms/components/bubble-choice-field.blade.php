@php
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $wrapperClasses = $getWrapperClasses();
    $arenaColor = $getArenaColor();
    $layout = $getLayoutOptionsForJs();
    $cornerOffset = ((float) $layout['cornerRadius']) * (1.414 - 1) / 1.414;
    $verticalPad = 'calc(50% - '.(((float) $layout['yRadius']) + ((float) $layout['size']) / 2 - $cornerOffset).'px)';
    $horizontalPad = 'calc(50% - '.(((float) $layout['xRadius']) + ((float) $layout['size']) / 2 - $cornerOffset).'px)';
    $rootStyle = '--fff-bubble-choice-arena-height: '.$getArenaHeight()
        .'; --fff-bubble-choice-v-pad: '.$verticalPad
        .'; --fff-bubble-choice-h-pad: '.$horizontalPad
        // Inline url(#…) so fragment refs resolve against the document (not the lazy CSS file).
        .'; --fff-bubble-choice-clip-circle: url(#fff-bubble-choice-clip-circle)'
        .'; --fff-bubble-choice-clip-scallop: url(#fff-bubble-choice-clip-scallop)';

    if (filled($arenaColor)) {
        $rootStyle .= '; --fff-bubble-choice-arena-color: '.$arenaColor;
    }
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'bubble-choice',
        'livewireKey' => $getLivewireKey(),
    ])
    @include('filament-flex-fields::forms.components.partials.bubble-choice-clip-defs')
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('bubble-choice', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="bubbleChoiceFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            options: @js($getOptionsForJs()),
            disabled: @js($isDisabled),
            maxItems: @js($getMaxItems()),
            selectedShape: @js($getSelectedShape()),
            bubbleColor: @js($getBubbleColor()),
            selectedBubbleColor: @js($getSelectedBubbleColor()),
            layout: @js($layout),
        })"
        @class([
            'fff-bubble-choice',
            'is-disabled' => $isDisabled,
        ])
        :class="{ 'is-laid-out': isLaidOut, 'is-scroll-settled': isScrollSettled }"
        style="{{ $rootStyle }}"
        role="group"
        aria-label="{{ $getLabel() }}"
    >
        <div class="fff-bubble-choice__container">
            <div
                class="fff-bubble-choice__scrollable"
                x-ref="scrollable"
                @scroll="onScroll($event)"
            >
                <div class="fff-bubble-choice__spacer" :style="spacerStyle()"></div>
                <div class="fff-bubble-choice__rows" :style="rowContainerStyle()">
                    <template x-for="(row, rowIndex) in rows" :key="'row-'+rowIndex">
                        <div class="fff-bubble-choice__row" :style="rowStyle(rowIndex)">
                            <template x-for="(cell, colIndex) in row" :key="'cell-'+rowIndex+'-'+colIndex+(cell.option?.value ?? 'pad')">
                                <div
                                    class="fff-bubble-choice__cell"
                                    :class="{ 'is-pad': cell.isPad }"
                                    :style="cellStyle(rowIndex, colIndex)"
                                >
                                    <button
                                        type="button"
                                        class="fff-bubble-choice__bubble"
                                        :class="{
                                            'is-selected': cell.option && selectionMorphProgress(cell.option.value) > 0.001,
                                            'is-disabled': cell.option && (cell.option.disabled || disabled),
                                            'has-image-bg': cell.option?.image && imageMode(cell.option) === 'background',
                                        }"
                                        :style="bubbleButtonStyle(cell.option)"
                                        :aria-pressed="cell.option ? isSelected(cell.option.value) : false"
                                        :disabled="cell.isPad || ! cell.option || disabled || cell.option.disabled || (! isSelected(cell.option.value) && ! canSelectMore())"
                                        :tabindex="cell.isPad ? -1 : 0"
                                        @click="cell.option && toggle(cell.option.value)"
                                    >
                                        <span
                                            class="fff-bubble-choice__mask"
                                            x-show="cell.option?.image && imageMode(cell.option) === 'background'"
                                            aria-hidden="true"
                                        ></span>
                                        <div
                                            class="fff-bubble-choice__inner"
                                            :class="{ 'is-faded': ! contentVisible(rowIndex, colIndex) }"
                                            x-show="! cell.isPad && cell.option"
                                        >
                                            <span
                                                class="fff-bubble-choice__icon"
                                                x-show="cell.option?.image && imageMode(cell.option) === 'icon'"
                                                aria-hidden="true"
                                            ></span>
                                            <span class="fff-bubble-choice__label" x-text="cell.option?.label"></span>
                                            <span
                                                class="fff-bubble-choice__desc"
                                                x-show="Boolean(cell.option?.description)"
                                                x-text="cell.option?.description"
                                            ></span>
                                        </div>
                                    </button>
                                    {{-- Outside the button clip so the stroke can sit on the scallop/circle edge. --}}
                                    <svg
                                        class="fff-bubble-choice__selection-stroke"
                                        :style="selectionStrokeStyle(cell.option)"
                                        viewBox="0 0 1 1"
                                        preserveAspectRatio="none"
                                        aria-hidden="true"
                                        focusable="false"
                                        x-show="cell.option?.image && imageMode(cell.option) === 'background'"
                                        x-cloak
                                    >
                                        <path
                                            class="fff-bubble-choice__selection-stroke-path"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-linejoin="round"
                                            stroke-linecap="round"
                                            :d="selectionStrokePath(cell.option.value)"
                                            :stroke-width="selectionStrokeWidth(cell.option.value)"
                                        />
                                    </svg>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="fff-bubble-choice__spacer" :style="spacerStyle()"></div>
            </div>
        </div>
    </div>
</x-dynamic-component>

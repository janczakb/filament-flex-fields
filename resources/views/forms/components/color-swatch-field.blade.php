@php
    $statePath = $getStatePath();
    $colors = $getColors();
    $sectionLabel = $getSectionLabel();
    $sectionIcon = $getSectionIcon();
    $isDisabled = $isDisabled();
    $size = $getSize();
    $hasTooltips = $hasTooltips();
    $initialState = $getState();
    $hasHeader = filled($sectionLabel) || filled($sectionIcon);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'color-swatch'])
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            disabled: @js($isDisabled),
            select(key) {
                if (this.disabled) {
                    return;
                }

                this.state = key;
            },
            isSelected(key) {
                return this.state === key;
            },
        }"
        @class([
            'fff-color-swatch',
            'fff-color-swatch--'.$size,
            'is-disabled' => $isDisabled,
        ])
        role="radiogroup"
        aria-label="{{ $getLabel() }}"
    >
        @if ($hasHeader)
            <div class="fff-color-swatch__header">
                @if (filled($sectionIcon))
                    <span class="fff-color-swatch__header-icon" aria-hidden="true">
                        {{ \Filament\Support\generate_icon_html($sectionIcon) }}
                    </span>
                @endif

                @if (filled($sectionLabel))
                    <span class="fff-color-swatch__header-label">{{ $sectionLabel }}</span>
                @endif
            </div>
        @endif

        <div class="fff-color-swatch__pills">
            @foreach ($colors as $key => $hex)
                @php
                    $colorLabel = $field->getColorLabel($key);
                @endphp

                <button
                    type="button"
                    @class([
                        'fff-color-swatch__pill',
                        'fff-color-swatch__pill--light' => $field->isLightSwatch($hex),
                        'is-selected' => $initialState === $key,
                    ])
                    style="--fff-color-swatch-pill: {{ $hex }}"
                    role="radio"
                    aria-label="{{ __('filament-flex-fields::default.color_swatch.select', ['color' => $colorLabel]) }}"
                    @if ($hasTooltips)
                        x-tooltip="{ content: @js($colorLabel), theme: $store.theme }"
                    @endif
                    x-bind:class="{ 'is-selected': isSelected(@js($key)) }"
                    x-bind:aria-checked="isSelected(@js($key)) ? 'true' : 'false'"
                    @if (! $isDisabled)
                        x-on:click="select(@js($key))"
                    @endif
                ></button>
            @endforeach
        </div>
    </div>
</x-dynamic-component>

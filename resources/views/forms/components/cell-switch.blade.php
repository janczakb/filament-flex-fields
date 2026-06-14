@php
    $statePath = $getStatePath();
    $variant = $getVariant();
    $size = $getSize();
    $description = $getDescription();
    $isDisabled = $isDisabled();
    $label = $getLabel();
    $isChecked = (bool) $getState();
    $checkedAttribute = $isChecked ? 'true' : 'false';
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :has-inline-label="true"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            disabled: @js($isDisabled),
            toggle() {
                if (this.disabled) {
                    return;
                }

                this.state = ! Boolean(this.state);
            },
        }"
        @class([
            'fff-cell-switch',
            'fff-cell-switch--'.$size,
            'fff-cell-switch--'.$variant,
            'is-disabled' => $isDisabled,
        ])
        role="group"
        aria-label="{{ $label }}"
    >
        <button
            type="button"
            class="fff-cell-switch__track"
            role="switch"
            aria-checked="{{ $checkedAttribute }}"
            x-bind:aria-checked="Boolean(state) ? 'true' : 'false'"
            x-bind:disabled="disabled"
            x-on:click="toggle()"
        >
            <span class="fff-cell-switch__content">
                <span class="fff-cell-switch__label">{{ $label }}</span>

                @if (filled($description))
                    <span class="fff-cell-switch__description">{{ $description }}</span>
                @endif
            </span>

            <span
                class="fff-cell-switch__control"
                data-checked="{{ $checkedAttribute }}"
                x-bind:data-checked="Boolean(state) ? 'true' : 'false'"
                aria-hidden="true"
            >
                <span class="fff-cell-switch__thumb"></span>
            </span>
        </button>
    </div>
</x-dynamic-component>

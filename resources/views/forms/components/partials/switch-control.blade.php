@props([
    'statePath',
    'label',
    'fieldId' => null,
    'isDisabled',
    'isRippleEnabled',
    'hasIcons',
    'onIcon',
    'offIcon',
    'onColorClasses',
    'offColorClasses',
    'toggleColorClassBinding',
    'extraAlpineAttributes' => '',
    'labelled' => false,
    'isChecked' => false,
])

@php
    $checkedAttribute = $isChecked ? 'true' : 'false';
@endphp

<button
    type="button"
    @class([
        'fff-switch__control',
        $isChecked ? $onColorClasses : $offColorClasses,
    ])
    role="switch"
    @if ($labelled && filled($fieldId))
        aria-labelledby="{{ $fieldId }}-label"
    @else
        aria-label="{{ $label }}"
    @endif
    aria-checked="{{ $checkedAttribute }}"
    data-checked="{{ $checkedAttribute }}"
    x-bind:aria-checked="Boolean(state) ? 'true' : 'false'"
    x-bind:disabled="disabled"
    x-bind:class="{ {{ $toggleColorClassBinding }} }"
    x-bind:data-checked="Boolean(state) ? 'true' : 'false'"
    x-on:click="ripple($event); toggle()"
    wire:loading.attr="disabled"
    wire:target="{{ $statePath }}"
    {{ $extraAlpineAttributes }}
>
    <span @class([
        'fff-switch__thumb',
        'fff-switch__thumb--with-icons' => $hasIcons,
    ])>
        @if ($hasIcons)
            <span class="fff-switch__thumb-icons">
                <span class="fff-switch__thumb-icon fff-switch__thumb-icon--off" aria-hidden="true">
                    {{ \Filament\Support\generate_icon_html($offIcon, size: \Filament\Support\Enums\IconSize::ExtraSmall) }}
                </span>

                <span class="fff-switch__thumb-icon fff-switch__thumb-icon--on" aria-hidden="true">
                    {{ \Filament\Support\generate_icon_html($onIcon, size: \Filament\Support\Enums\IconSize::ExtraSmall) }}
                </span>
            </span>
        @endif
    </span>
</button>

@php
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isReadOnly = $isReadOnly();
    $wrapperClasses = $getWrapperClasses();
    $hasError = filled($statePath) && $errors->has($statePath);
    $livewireKey = $getLivewireKey();
    $normalizedValue = $field->normalizeState($getState()) ?? '';
    $entangle = $applyStateBindingModifiers("\$entangle('{$statePath}')");
    $alpineConfiguration = $field->getAlpineConfiguration();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
            ->class($wrapperClasses)
    "
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'flex-time-segments'])
    <div
        wire:ignore
        wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$isDisabled, $isReadOnly, $getSize(), $field->getVariant(), $field->getMinuteStep()])), 0, 64) }}"
        @class([
            'fff-flex-time-segments-field__shell',
            'has-focus-outline' => $shouldShowFocusOutline(),
            'has-error' => $hasError,
        ])
    >
        @include('filament-flex-fields::forms.components.partials.flex-time-segments', [
            'size' => $getSize(),
            'variant' => $field->getVariant(),
            'value' => $normalizedValue,
            'minuteStep' => $field->getMinuteStep(),
            'hourCycle' => $alpineConfiguration['hourCycle'],
            'minValue' => $alpineConfiguration['minValue'],
            'maxValue' => $alpineConfiguration['maxValue'],
            'hourPlaceholder' => $alpineConfiguration['hourPlaceholder'],
            'minutePlaceholder' => $alpineConfiguration['minutePlaceholder'],
            'disabled' => $isDisabled,
            'readOnly' => $isReadOnly,
            'live' => true,
            'getValueExpression' => '() => $wire.'.$entangle,
            'setValueExpression' => '(value) => { $wire.'.$entangle.' = value }',
            'ariaLabel' => $getLabel(),
        ])
    </div>
</x-dynamic-component>

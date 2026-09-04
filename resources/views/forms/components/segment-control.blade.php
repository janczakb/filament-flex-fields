@php
    use Illuminate\Support\Js;

    $statePath = $getStatePath();
    $options = $getNormalizedOptions();
    $optionKeys = array_keys($options);
    $size = $getSize();
    $variant = $getVariant();
    $color = $getColor();
    $hasSeparators = $hasSeparators();
    $isFullWidth = $isFullWidth();
    $isIconOnly = $isIconOnly();
    $expandSelectedLabel = $shouldExpandSelectedLabel();
    $isDisabled = $isDisabled();
    $currentState = $getState();
    $normalizedCurrentState = filled($currentState) || $currentState === 0 || $currentState === '0'
        ? (string) $currentState
        : null;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'segment-control',
        'livewireKey' => $getLivewireKey(),
    ])
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('segment-control', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="segmentControlFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            optionKeys: {{ Js::from(array_values($optionKeys)) }},
            disabledOptions: {{ Js::from(collect($options)->mapWithKeys(fn (array $option, string | int $key): array => [(string) $key => $option['disabled']])->all()) }},
            separators: @js($hasSeparators),
            disabled: @js($isDisabled),
            overflowShell: @js(! $isFullWidth),
        })"
        x-init="init()"
        @class([
            'fff-segment-control',
            'w-full' => $isFullWidth,
            'is-disabled' => $isDisabled,
            'fi-color-'.$color => filled($color),
        ])
        role="radiogroup"
        aria-label="{{ $getLabel() }}"
    >
        @if ($isFullWidth)
            <div
                x-ref="track"
                @class([
                    'fff-segment-track',
                    'fff-segment-track--'.$size,
                    'fff-segment-track--ghost' => $variant === 'ghost',
                ])
                x-bind:class="{ 'is-animated': indicatorAnimated, 'is-hydrated': indicatorHydrated }"
            >
                @include('filament-flex-fields::forms.components.partials.segment-control-track-items', [
                    'options' => $options,
                    'size' => $size,
                    'hasSeparators' => $hasSeparators,
                    'isIconOnly' => $isIconOnly,
                    'expandSelectedLabel' => $expandSelectedLabel,
                    'normalizedCurrentState' => $normalizedCurrentState,
                    'variant' => $variant,
                ])
            </div>
        @else
            <x-filament-flex-fields::segment-overflow-shell
                :variant="$variant"
                :tablist-role="null"
            >
                <div
                    x-ref="track"
                    @class([
                        'fff-segment-track',
                        'fff-segment-track--'.$size,
                        'fff-segment-track--ghost' => $variant === 'ghost',
                        'fff-segment-track--in-shell' => true,
                    ])
                    x-bind:class="{ 'is-animated': indicatorAnimated, 'is-hydrated': indicatorHydrated }"
                >
                    @include('filament-flex-fields::forms.components.partials.segment-control-track-items', [
                        'options' => $options,
                        'size' => $size,
                        'hasSeparators' => $hasSeparators,
                        'isIconOnly' => $isIconOnly,
                        'expandSelectedLabel' => $expandSelectedLabel,
                        'normalizedCurrentState' => $normalizedCurrentState,
                        'variant' => $variant,
                    ])
                </div>
            </x-filament-flex-fields::segment-overflow-shell>
        @endif
    </div>
</x-dynamic-component>

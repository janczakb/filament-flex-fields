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
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('segment-control', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="segmentControlFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            optionKeys: {{ Js::from(array_values($optionKeys)) }},
            disabledOptions: {{ Js::from(collect($options)->mapWithKeys(fn (array $option, string | int $key): array => [(string) $key => $option['disabled']])->all()) }},
            separators: @js($hasSeparators),
            disabled: @js($isDisabled),
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
        <div
            x-ref="track"
            @class([
                'fff-segment-track',
                'fff-segment-track--'.$size,
                'fff-segment-track--ghost' => $variant === 'ghost',
            ])
            x-bind:class="{ 'is-animated': indicatorAnimated }"
        >
            <div
                x-ref="indicator"
                aria-hidden="true"
                @class([
                    'fff-segment-indicator',
                    'fff-segment-indicator--ghost' => $variant === 'ghost',
                ])
                x-bind:class="{ 'is-animated': indicatorAnimated }"
                :style="indicatorStyle"
            ></div>

            @foreach ($options as $value => $option)
                @if (! $loop->first && $hasSeparators)
                    <span
                        class="fff-segment-separator"
                        x-bind:class="separatorClass({{ $loop->index - 1 }})"
                        aria-hidden="true"
                    ></span>
                @endif

                <button
                    type="button"
                    role="radio"
                    @class([
                        'fff-segment-item',
                        'fff-segment-item--'.$size,
                    ])
                    data-segment-value="{{ $value }}"
                    x-bind:data-segment-selected="isSelected(@js($value)) ? 'true' : 'false'"
                    x-bind:aria-checked="isSelected(@js($value)) ? 'true' : 'false'"
                    x-bind:disabled="disabled || isOptionDisabled(@js($value))"
                    x-on:click="select(@js($value))"
                    @if (filled($option['tooltip'] ?? null))
                        x-tooltip="{ content: @js($option['tooltip']), theme: $store.theme }"
                    @endif
                >
                    @if ($option['icon'])
                        <x-filament::icon :icon="$option['icon']" />
                    @endif

                    @if ($isIconOnly)
                        <span class="sr-only">{{ $option['label'] }}</span>
                    @elseif ($expandSelectedLabel)
                        <span
                            x-show="isSelected(@js($value))"
                            x-cloak
                        >{{ $option['label'] }}</span>
                    @else
                        <span>{{ $option['label'] }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</x-dynamic-component>

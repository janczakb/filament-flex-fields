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
    @php
        $isSelected = $normalizedCurrentState !== null && (string) $value === $normalizedCurrentState;
    @endphp

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
        data-segment-selected="{{ $isSelected ? 'true' : 'false' }}"
        aria-checked="{{ $isSelected ? 'true' : 'false' }}"
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
                @unless ($isSelected)
                    x-show="isSelected(@js($value))"
                    x-cloak
                @endunless
            >{{ $option['label'] }}</span>
        @else
            <span>{{ $option['label'] }}</span>
        @endif
    </button>
@endforeach

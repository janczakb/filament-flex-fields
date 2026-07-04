@php
    $options = $field->getOptions();
    $minLabel = $field->getMinLabel();
    $maxLabel = $field->getMaxLabel();
    $isColorCoded = $field->isColorCoded();
    $statePath = $getStatePath();
    $size = $field->getSize();
    $variant = $field->getVariant();
    $roundingClass = 'fff-rounding-'.$field->getRounding();
    $usesSegmentControl = $variant === 'pills';
    $isDisabled = $isDisabled();
    $isRequired = $isRequired();
    $disabledOptions = collect($options)
        ->mapWithKeys(fn (mixed $label, string|int $key): array => [(string) $key => $field->isOptionDisabled($key)])
        ->all();
    $currentState = $getState();
    $normalizedCurrentState = filled($currentState) || $currentState === 0 || $currentState === '0'
        ? (string) $currentState
        : null;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'nps-field'])

    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('nps-field', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="npsFieldFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            optionKeys: @js(array_values(array_map(strval(...), array_keys($options)))),
            disabledOptions: @js($disabledOptions),
            disabled: @js($isDisabled),
            required: @js($isRequired),
            usesIndicator: @js($usesSegmentControl),
        })"
        x-init="init()"
        id="{{ $getId() }}"
        @class([
            'fff-nps-field',
            'fff-nps-field--'.$size,
            'fff-nps-field--variant-'.$variant,
            'fff-nps-field--color-coded' => $isColorCoded,
            'fff-nps-field--full-width' => in_array($variant, ['segments', 'emojis', 'pills'], true),
            'fff-segment-control w-full' => $usesSegmentControl,
            'is-disabled' => $isDisabled,
            'fi-color-primary' => ! $isColorCoded && in_array($variant, ['segments', 'emojis', 'pills'], true),
        ])
        role="radiogroup"
        aria-label="{{ $getLabel() }}"
        @if ($isRequired)
            aria-required="true"
        @endif
    >
        <div
            @class([
                'fff-nps-field__track',
                $roundingClass,
                'fff-segment-track fff-segment-track--'.$size => $usesSegmentControl,
                'fff-nps-field__track--segments' => $variant === 'segments',
                'fff-nps-field__track--emojis' => $variant === 'emojis',
            ])
            @if ($usesSegmentControl)
                x-ref="track"
                x-bind:class="{ 'is-animated': indicatorAnimated, 'is-hydrated': indicatorHydrated }"
            @endif
        >
            @if ($usesSegmentControl)
                <div
                    x-ref="indicator"
                    aria-hidden="true"
                    class="fff-segment-indicator"
                    x-bind:class="{ 'is-animated': indicatorAnimated }"
                    :style="indicatorStyle"
                ></div>
            @endif

            @foreach ($options as $value => $label)
                @php
                    $id = $getId().'-'.$value;
                    $optionTone = $field->getOptionTone($value);
                    $styleVariables = $field->getOptionStyleVariables($value);
                    $optionIcon = $field->getOptionIcon($value);
                    $emojiImage = $field->getEmojiImage($value);
                    $isSelected = $normalizedCurrentState !== null && (string) $value === $normalizedCurrentState;
                    $isOptionDisabled = $isDisabled || $field->isOptionDisabled($value);
                    $optionStyle = $styleVariables !== []
                        ? implode('; ', array_map(
                            fn (string $property, string $cssValue): string => "{$property}: {$cssValue}",
                            array_keys($styleVariables),
                            array_values($styleVariables),
                        ))
                        : null;
                @endphp

                <label
                    id="{{ $id }}-label"
                    role="radio"
                    @class([
                        'fff-nps-field__option',
                        'fff-segment-item fff-segment-item--'.$size => $usesSegmentControl,
                        'fff-nps-field__segment' => $variant === 'segments',
                        'fff-nps-field__emoji-option' => $variant === 'emojis',
                        'is-disabled' => $isOptionDisabled,
                    ])
                    data-value="{{ $value }}"
                    data-segment-selected="{{ $isSelected ? 'true' : 'false' }}"
                    @if ($optionTone)
                        data-nps-tone="{{ $optionTone }}"
                    @endif
                    @if ($styleVariables !== [])
                        style="{{ $optionStyle }}"
                    @endif
                    x-bind:data-segment-selected="isSelected(@js($value)) ? 'true' : 'false'"
                    x-bind:aria-checked="isSelected(@js($value)) ? 'true' : 'false'"
                    x-bind:aria-disabled="isOptionDisabled(@js($value)) ? 'true' : 'false'"
                    x-on:click.prevent="select(@js($value))"
                    x-on:keydown.enter.prevent="select(@js($value))"
                    x-on:keydown.space.prevent="select(@js($value))"
                    x-bind:tabindex="isOptionDisabled(@js($value)) ? '-1' : (isSelected(@js($value)) ? '0' : '-1')"
                >
                    <input
                        type="radio"
                        name="{{ $statePath }}"
                        id="{{ $id }}"
                        value="{{ $value }}"
                        class="sr-only"
                        tabindex="-1"
                        x-bind:checked="isSelected(@js($value))"
                        @disabled($isOptionDisabled)
                    />

                    @if ($variant === 'emojis')
                        <span class="fff-nps-field__emoji-ring">
                            @if (filled($optionIcon))
                                <x-filament::icon
                                    :icon="$optionIcon"
                                    class="fff-nps-field__emoji-icon"
                                />
                            @elseif (filled($emojiImage))
                                <img
                                    src="{{ $emojiImage }}"
                                    alt=""
                                    class="fff-nps-field__emoji-image"
                                    loading="lazy"
                                    decoding="async"
                                />
                            @endif
                        </span>
                    @endif

                    <span @class([
                        'fff-nps-field__option-label',
                        'fff-segment-item__label' => $usesSegmentControl,
                    ])>{{ $label }}</span>
                </label>
            @endforeach
        </div>

        @if ($minLabel || $maxLabel)
            <div class="fff-nps-field__extremes">
                @if ($minLabel)
                    <span class="fff-nps-field__extreme fff-nps-field__extreme--min">{{ $minLabel }}</span>
                @endif
                @if ($maxLabel)
                    <span class="fff-nps-field__extreme fff-nps-field__extreme--max">{{ $maxLabel }}</span>
                @endif
            </div>
        @endif
    </div>
</x-dynamic-component>

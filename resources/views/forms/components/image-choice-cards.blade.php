@php
    use Illuminate\Support\Js;

    $statePath = $getStatePath();
    $options = $getNormalizedOptions();
    $optionKeys = array_keys($options);
    $size = $getSize();
    $columns = $getGridColumnConfig();
    $indicator = $getIndicator();
    $variant = $getVariant();
    $color = $getColor();
    $isRippleEnabled = $isRippleEnabled();
    $isDisabled = $isDisabled();
    $isMultiple = $isMultiple();
    $maxSelections = $getMaxSelections();
    $roundingClass = 'fff-rounding-'.$getRounding();
    $disabledMap = collect($options)
        ->mapWithKeys(fn (array $option, string | int $key): array => [(string) $key => $option['disabled']])
        ->all();
    $disabledMapHash = md5(json_encode($disabledMap) ?: '');
    $currentState = $getState();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'image-choice-cards',
        'livewireKey' => $getLivewireKey(),
    ])
    <div
        wire:key="{{ $statePath }}-icc-{{ $disabledMapHash }}"
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('image-choice-cards', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="imageChoiceCardsFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            multiple: @js($isMultiple),
            disabledOptions: {{ Js::from($disabledMap) }},
            disabled: @js($isDisabled),
            rippleEnabled: @js($isRippleEnabled),
            maxSelections: @js($maxSelections),
        })"
        x-init="init()"
        @class([
            'fff-image-choice-cards',
            'fff-image-choice-cards--'.$size,
            'fff-image-choice-cards--'.$variant,
            'fff-image-choice-cards--indicator-'.$indicator,
            'fff-image-choice-cards--color-'.$color,
            $roundingClass,
            'is-disabled' => $isDisabled,
        ])
        @style([
            ...$getImageChoiceCardSizeStyles(),
            '--fff-image-choice-cards-cols-default: '.$columns['default'],
            '--fff-image-choice-cards-cols-sm: '.$columns['sm'],
            '--fff-image-choice-cards-cols-md: '.$columns['md'],
            '--fff-image-choice-cards-cols-lg: '.$columns['lg'],
            '--fff-image-choice-cards-cols-xl: '.$columns['xl'],
            '--fff-image-choice-cards-aspect: '.$getImageAspectRatio(),
            '--fff-image-choice-cards-fit: '.$getImageFit(),
        ])
        role="{{ $isMultiple ? 'group' : 'radiogroup' }}"
        aria-label="{{ $getLabel() }}"
        @if ($isMultiple)
            aria-multiselectable="true"
        @endif
    >
        <div class="fff-image-choice-cards__grid">
        @foreach ($options as $value => $option)
            @php
                $key = (string) $value;
                $optionDisabled = $isDisabled || $option['disabled'];
                $isInitiallySelected = $isMultiple
                    ? is_array($currentState) && in_array($key, collect($currentState)->map(fn (mixed $item): string => (string) $item)->all(), true)
                    : filled($currentState) && (string) $currentState === $key;
            @endphp

            <label
                wire:key="{{ $statePath }}-icc-option-{{ $key }}"
                x-on:click="ripple($event)"
                x-bind:class="{
                    'is-selected': isSelected(@js($key)),
                    'is-disabled': disabled || isOptionDisabled(@js($key)) || (@js($isMultiple) && ! isSelected(@js($key)) && isMaxReached()),
                }"
                @class([
                    'fff-image-choice-cards__item',
                    'is-selected' => $isInitiallySelected,
                    'is-disabled' => $optionDisabled,
                ])
            >
                <input
                    type="{{ $isMultiple ? 'checkbox' : 'radio' }}"
                    name="{{ $statePath }}{{ $isMultiple ? '[]' : '' }}"
                    value="{{ $key }}"
                    class="fff-image-choice-cards__input"
                    x-bind:checked="isSelected(@js($key))"
                    @checked($isInitiallySelected)
                    x-bind:disabled="disabled || isOptionDisabled(@js($key)) || (@js($isMultiple) && ! isSelected(@js($key)) && isMaxReached())"
                    x-on:change="{{ $isMultiple ? 'toggle' : 'select' }}(@js($key))"
                    @disabled($optionDisabled)
                />

                <span class="fff-image-choice-cards__media">
                    @if (filled($option['image']))
                        <img
                            class="fff-image-choice-cards__image"
                            src="{{ $option['image'] }}"
                            alt="{{ $option['alt'] }}"
                            loading="lazy"
                            decoding="async"
                        />
                    @else
                        <span class="fff-image-choice-cards__placeholder">
                            <svg
                                class="fff-image-choice-cards__placeholder-icon"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"
                                />
                            </svg>
                        </span>
                    @endif
                </span>

                <span class="fff-image-choice-cards__footer">
                    <span class="fff-image-choice-cards__label">{{ $option['label'] }}</span>

                    @if ($indicator !== 'none')
                        <span class="fff-image-choice-cards__indicator" aria-hidden="true">
                            @if ($indicator === 'check')
                                <span class="fff-image-choice-cards__indicator-ring"></span>
                                <svg
                                    class="fff-image-choice-cards__indicator-icon"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            @elseif ($indicator === 'checkbox')
                                <svg
                                    class="fff-image-choice-cards__indicator-icon"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            @else
                                <span class="fff-image-choice-cards__indicator-dot"></span>
                            @endif
                        </span>
                    @endif
                </span>
            </label>
        @endforeach
        </div>
    </div>
</x-dynamic-component>

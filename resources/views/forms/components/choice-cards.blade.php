@php
    use Illuminate\Support\Js;

    $statePath = $getStatePath();
    $options = $getNormalizedOptions();
    $optionKeys = array_keys($options);
    $size = $getSize();
    $layout = $getLayout();
    $columns = $getGridColumnConfig();
    $indicator = $getIndicator();
    $variant = $getVariant();
    $color = $getColor();
    $isRippleEnabled = $isRippleEnabled();
    $isDisabled = $isDisabled();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            optionKeys: {{ Js::from(array_values($optionKeys)) }},
            disabledOptions: {{ Js::from(collect($options)->mapWithKeys(fn (array $option, string | int $key): array => [(string) $key => $option['disabled']])->all()) }},
            disabled: @js($isDisabled),
            rippleEnabled: @js($isRippleEnabled),
            normalize(value) {
                return value === null || value === undefined ? null : String(value);
            },
            isSelected(value) {
                return this.normalize(this.state) === this.normalize(value);
            },
            isOptionDisabled(value) {
                return this.disabledOptions[this.normalize(value)] ?? false;
            },
            canSelect(value) {
                return ! this.disabled && ! this.isOptionDisabled(value);
            },
            select(value) {
                if (! this.canSelect(value)) {
                    return;
                }

                this.state = value;
            },
            ripple(event) {
                if (! this.rippleEnabled) {
                    return;
                }

                const card = event.currentTarget;
                const circle = document.createElement('span');
                const diameter = Math.max(card.clientWidth, card.clientHeight);

                circle.className = 'fff-choice-cards__ripple';
                circle.style.width = `${diameter}px`;
                circle.style.height = `${diameter}px`;
                circle.style.left = `${event.offsetX - (diameter / 2)}px`;
                circle.style.top = `${event.offsetY - (diameter / 2)}px`;

                card.appendChild(circle);

                window.setTimeout(() => circle.remove(), 650);
            },
        }"
        @class([
            'fff-choice-cards',
            'fff-choice-cards--'.$size,
            'fff-choice-cards--'.$layout,
            'fff-choice-cards--'.$variant,
            'fff-choice-cards--indicator-'.$indicator,
            'fff-choice-cards--color-'.$color,
            'fff-choice-cards--grid' => $layout === 'grid' || ($layout === 'media' && max($columns) > 1),
            'is-disabled' => $isDisabled,
        ])
        @style([
            ...$getChoiceCardSizeStyles(),
            '--fff-choice-cards-cols-default: '.$columns['default'],
            '--fff-choice-cards-cols-sm: '.$columns['sm'],
            '--fff-choice-cards-cols-md: '.$columns['md'],
            '--fff-choice-cards-cols-lg: '.$columns['lg'],
        ])
        role="radiogroup"
        aria-label="{{ $getLabel() }}"
    >
        @foreach ($options as $value => $option)
            @php
                $key = (string) $value;
            @endphp

            <label
                wire:key="{{ $statePath }}-choice-{{ $key }}"
                x-on:click="ripple($event)"
                x-bind:class="{
                    'is-selected': isSelected(@js($key)),
                    'is-disabled': disabled || isOptionDisabled(@js($key)),
                }"
                @class([
                    'fff-choice-cards__item',
                    'is-disabled' => $isDisabled || $option['disabled'],
                ])
            >
                <input
                    type="radio"
                    name="{{ $statePath }}"
                    value="{{ $key }}"
                    class="fff-choice-cards__input"
                    x-bind:checked="isSelected(@js($key))"
                    x-bind:disabled="disabled || isOptionDisabled(@js($key))"
                    x-on:change="select(@js($key))"
                    @disabled($isDisabled || $option['disabled'])
                />

                @if ($indicator !== 'none')
                    <span class="fff-choice-cards__indicator" aria-hidden="true">
                        @if ($indicator === 'check')
                            <svg
                                class="fff-choice-cards__indicator-icon"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        @else
                            <span class="fff-choice-cards__indicator-dot"></span>
                        @endif
                    </span>
                @endif

                <span @class([
                    'fff-choice-cards__content',
                    'fff-choice-cards__content--media' => $layout === 'media',
                    'fff-choice-cards__content--featured' => $layout === 'featured',
                ])>
                    @if ($layout === 'featured')
                        <span class="fff-choice-cards__leading">
                            @if (filled($option['icon']))
                                <span class="fff-choice-cards__icon-box">
                                    <x-filament::icon
                                        :icon="$option['icon']"
                                        class="fff-choice-cards__icon"
                                    />
                                </span>
                            @endif

                            <span class="fff-choice-cards__heading">
                                <span class="fff-choice-cards__label">{{ $option['label'] }}</span>

                                @if (filled($option['badge']))
                                    <span @class([
                                        'fff-choice-cards__badge',
                                        'fff-choice-cards__badge--'.$option['badge_color'],
                                    ])>{{ $option['badge'] }}</span>
                                @endif
                            </span>
                        </span>
                    @endif

                    @if ($layout !== 'featured')
                        <span class="fff-choice-cards__copy">
                            @if ($layout === 'media')
                                @if (filled($option['icon']))
                                    <span class="fff-choice-cards__media-icon" aria-hidden="true">
                                        <x-filament::icon
                                            :icon="$option['icon']"
                                            class="fff-choice-cards__icon"
                                        />
                                    </span>
                                @endif

                                <span class="fff-choice-cards__text">
                                    <span class="fff-choice-cards__label">{{ $option['label'] }}</span>

                                    @if (filled($option['description']))
                                        <span class="fff-choice-cards__description">{{ $option['description'] }}</span>
                                    @endif
                                </span>
                            @else
                                <span class="fff-choice-cards__label">{{ $option['label'] }}</span>

                                @if (filled($option['description']))
                                    <span class="fff-choice-cards__description">{{ $option['description'] }}</span>
                                @endif
                            @endif

                            @if (filled($option['meta']))
                                <span class="fff-choice-cards__meta">{{ $option['meta'] }}</span>
                            @endif
                        </span>
                    @else
                        @if (filled($option['price']))
                            <span class="fff-choice-cards__price fff-choice-cards__price--featured">
                                <span class="fff-choice-cards__price-value">{{ $option['price'] }}</span>

                                @if (filled($option['price_suffix']))
                                    <span class="fff-choice-cards__price-suffix">{{ $option['price_suffix'] }}</span>
                                @endif
                            </span>
                        @endif

                        @if (filled($option['description']))
                            <span class="fff-choice-cards__description">{{ $option['description'] }}</span>
                        @endif
                    @endif

                    @if ($layout !== 'featured' && filled($option['price']))
                        <span class="fff-choice-cards__price">
                            <span class="fff-choice-cards__price-value">{{ $option['price'] }}</span>

                            @if (filled($option['price_suffix']))
                                <span class="fff-choice-cards__price-suffix">{{ $option['price_suffix'] }}</span>
                            @endif
                        </span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
</x-dynamic-component>

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
    $maxSelections = $getMaxSelections();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'choice-cards'])
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            optionKeys: {{ Js::from(array_values($optionKeys)) }},
            disabledOptions: {{ Js::from(collect($options)->mapWithKeys(fn (array $option, string | int $key): array => [(string) $key => $option['disabled']])->all()) }},
            disabled: @js($isDisabled),
            rippleEnabled: @js($isRippleEnabled),
            maxSelections: @js($maxSelections),
            normalize(value) {
                return String(value);
            },
            selectedValues() {
                if (! Array.isArray(this.state)) {
                    return [];
                }

                return this.state.map(value => this.normalize(value));
            },
            isSelected(value) {
                return this.selectedValues().includes(this.normalize(value));
            },
            isOptionDisabled(value) {
                return this.disabledOptions[this.normalize(value)] ?? false;
            },
            isMaxReached() {
                return this.maxSelections !== null && this.selectedValues().length >= this.maxSelections;
            },
            canToggle(value) {
                if (this.disabled || this.isOptionDisabled(value)) {
                    return false;
                }

                if (this.isSelected(value)) {
                    return true;
                }

                return ! this.isMaxReached();
            },
            toggle(value) {
                if (! this.canToggle(value)) {
                    return;
                }

                const key = this.normalize(value);
                const current = this.selectedValues();

                if (current.includes(key)) {
                    this.state = current.filter(item => item !== key);

                    return;
                }

                this.state = [...current, key];
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
        role="group"
        aria-multiselectable="true"
        aria-label="{{ $getLabel() }}"
    >
        @foreach ($options as $value => $option)
            @php
                $key = (string) $value;
            @endphp

            <label
                wire:key="{{ $statePath }}-choice-checkbox-{{ $key }}"
                x-on:click="ripple($event)"
                x-bind:class="{
                    'is-selected': isSelected(@js($key)),
                    'is-disabled': disabled || isOptionDisabled(@js($key)) || (! isSelected(@js($key)) && isMaxReached()),
                }"
                @class([
                    'fff-choice-cards__item',
                    'is-disabled' => $isDisabled || $option['disabled'],
                ])
            >
                <input
                    type="checkbox"
                    value="{{ $key }}"
                    class="fff-choice-cards__input"
                    x-bind:checked="isSelected(@js($key))"
                    x-bind:disabled="disabled || isOptionDisabled(@js($key)) || (! isSelected(@js($key)) && isMaxReached())"
                    x-on:change="toggle(@js($key))"
                    @disabled($isDisabled || $option['disabled'])
                />

                @if ($indicator !== 'none')
                    <span class="fff-choice-cards__indicator" aria-hidden="true">
                        @if ($indicator === 'check')
                            <span class="fff-choice-cards__indicator-ring"></span>
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
                        @elseif ($indicator === 'checkbox')
                            <svg
                                class="fff-choice-cards__indicator-icon"
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

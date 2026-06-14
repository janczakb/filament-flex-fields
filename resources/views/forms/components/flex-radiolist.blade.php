@php
    $statePath = $getStatePath();
    $options = $getNormalizedOptions();
    $isDisabled = $isDisabled();
    $wrapperClasses = $getWrapperClasses();
    $initialSelected = filled($getState()) ? (string) $getState() : null;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'flex-radiolist'])
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            disabledOptions: {{ \Illuminate\Support\Js::from(collect($options)->mapWithKeys(fn (array $option, string | int $key): array => [(string) $key => $option['disabled']])->all()) }},
            disabled: @js($isDisabled),
            normalize(value) {
                return String(value);
            },
            selectedValue() {
                if (this.state === null || this.state === undefined || this.state === '') {
                    return null;
                }

                return this.normalize(this.state);
            },
            isSelected(value) {
                const selected = this.selectedValue();

                return selected !== null && selected === this.normalize(value);
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

                this.state = this.normalize(value);
            },
        }"
        @class([
            ...$wrapperClasses,
            'is-disabled' => $isDisabled,
        ])
        @style($getRadiolistSizeStyles())
        role="radiogroup"
        aria-label="{{ $getLabel() }}"
        data-layout="stack"
        data-orientation="vertical"
    >
        @foreach ($options as $value => $option)
            @php
                $key = (string) $value;
                $isInitiallySelected = $initialSelected === $key;
                $isInitiallyDisabled = $isDisabled || $option['disabled'];
            @endphp

            <div
                wire:key="{{ $statePath }}-flex-radiolist-{{ $key }}"
                role="radio"
                aria-label="{{ $option['label'] }}"
                aria-checked="{{ $isInitiallySelected ? 'true' : 'false' }}"
                x-bind:aria-checked="isSelected(@js($key)) ? 'true' : 'false'"
                x-bind:aria-disabled="disabled || isOptionDisabled(@js($key)) ? 'true' : null"
                x-bind:class="{
                    'is-selected': isSelected(@js($key)),
                    'is-disabled': disabled || isOptionDisabled(@js($key)),
                }"
                @class([
                    'fff-flex-radiolist__item',
                    'is-selected' => $isInitiallySelected,
                    'is-disabled' => $isInitiallyDisabled,
                ])
                x-on:click="select(@js($key))"
                x-on:keydown.enter.prevent="select(@js($key))"
                x-on:keydown.space.prevent="select(@js($key))"
                tabindex="-1"
            >
                <div
                    class="fff-flex-radiolist__cell"
                    style="display: contents"
                >
                    <div class="fff-flex-radiolist__selection-cell">
                        <label class="fff-flex-radiolist__radio" x-on:click.stop="select(@js($key))">
                            <input
                                type="radio"
                                name="{{ $statePath }}"
                                value="{{ $key }}"
                                class="fff-flex-radiolist__input"
                                aria-label="{{ __('Select row') }}"
                                @checked($isInitiallySelected)
                                x-bind:checked="isSelected(@js($key))"
                                x-bind:disabled="disabled || isOptionDisabled(@js($key))"
                                tabindex="-1"
                                @disabled($isDisabled || $option['disabled'])
                            />

                            <span class="fff-flex-radiolist__control" aria-hidden="true">
                                <span class="fff-flex-radiolist__indicator">
                                    <span class="fff-flex-radiolist__indicator-dot"></span>
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="fff-flex-radiolist__content">
                        @if (filled($option['icon']))
                            <span class="fff-flex-radiolist__icon-box" aria-hidden="true">
                                <x-filament::icon
                                    :icon="$option['icon']"
                                    class="fff-flex-radiolist__icon"
                                />
                            </span>
                        @endif

                        <div class="fff-flex-radiolist__copy">
                            <span class="fff-flex-radiolist__label">{{ $option['label'] }}</span>

                            @if (filled($option['description']))
                                <span class="fff-flex-radiolist__description">{{ $option['description'] }}</span>
                            @endif
                        </div>
                    </div>

                    @if ($option['disabled'])
                        <div class="fff-flex-radiolist__action" aria-hidden="true">
                            <x-filament::icon
                                :icon="$field->getLockIcon()"
                                class="fff-flex-radiolist__action-icon"
                            />
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-dynamic-component>

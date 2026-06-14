@php
    use Illuminate\Support\Js;

    $statePath = $getStatePath();
    $options = $getNormalizedOptions();
    $isDisabled = $isDisabled();
    $maxSelections = $getMaxSelections();
    $wrapperClasses = $getWrapperClasses();
    $initialSelected = collect($getState() ?? [])
        ->map(fn (mixed $value): string => (string) $value)
        ->values()
        ->all();
    $initialMaxReached = $maxSelections !== null && count($initialSelected) >= $maxSelections;
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-flex-fields::partials.load-stylesheet', ['component' => 'flex-checklist'])
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('flex-checklist', \Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin::PACKAGE_NAME) }}"
        x-data="flexChecklistFormComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            initialSelected: {{ Js::from($initialSelected) }},
            disabledOptions: {{ Js::from(collect($options)->mapWithKeys(fn (array $option, string | int $key): array => [(string) $key => $option['disabled']])->all()) }},
            disabled: @js($isDisabled),
            maxSelections: @js($maxSelections),
        })"
        @class([
            ...$wrapperClasses,
            'is-disabled' => $isDisabled,
        ])
        @style($getChecklistSizeStyles())
        role="grid"
        aria-multiselectable="true"
        aria-label="{{ $getLabel() }}"
        data-layout="stack"
        data-orientation="vertical"
    >
        @foreach ($options as $value => $option)
            @php
                $key = (string) $value;
                $isInitiallySelected = in_array($key, $initialSelected, true);
                $isInitiallyDisabled = $isDisabled
                    || $option['disabled']
                    || (! $isInitiallySelected && $initialMaxReached);
            @endphp

            <div
                wire:key="{{ $statePath }}-flex-checklist-{{ $key }}"
                role="row"
                aria-label="{{ $option['label'] }}"
                aria-selected="{{ $isInitiallySelected ? 'true' : 'false' }}"
                x-bind:aria-selected="isSelected(@js($key)) ? 'true' : 'false'"
                x-bind:aria-disabled="disabled || isOptionDisabled(@js($key)) ? 'true' : null"
                x-bind:class="{
                    'is-selected': isSelected(@js($key)),
                    'is-disabled': disabled || isOptionDisabled(@js($key)) || (! isSelected(@js($key)) && isMaxReached()),
                }"
                @class([
                    'fff-flex-checklist__item',
                    'is-selected' => $isInitiallySelected,
                    'is-disabled' => $isInitiallyDisabled,
                ])
                x-on:click="toggle(@js($key))"
                x-on:keydown.enter.prevent="toggle(@js($key))"
                x-on:keydown.space.prevent="toggle(@js($key))"
                tabindex="-1"
            >
                <div
                    class="fff-flex-checklist__cell"
                    role="gridcell"
                    style="display: contents"
                >
                    <div class="fff-flex-checklist__selection-cell">
                        <label class="fff-flex-checklist__checkbox" x-on:click.stop="toggle(@js($key))">
                            <input
                                type="checkbox"
                                value="{{ $key }}"
                                class="fff-flex-checklist__input"
                                aria-label="{{ __('Select row') }}"
                                @checked($isInitiallySelected)
                                x-bind:checked="isSelected(@js($key))"
                                x-bind:disabled="disabled || isOptionDisabled(@js($key)) || (! isSelected(@js($key)) && isMaxReached())"
                                tabindex="-1"
                                @disabled($isDisabled || $option['disabled'])
                            />

                            <span class="fff-flex-checklist__control" aria-hidden="true">
                                <span class="fff-flex-checklist__indicator">
                                    <svg
                                        class="fff-flex-checklist__indicator-icon"
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
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="fff-flex-checklist__content">
                        @if (filled($option['icon']))
                            <span class="fff-flex-checklist__icon-box" aria-hidden="true">
                                <x-filament::icon
                                    :icon="$option['icon']"
                                    class="fff-flex-checklist__icon"
                                />
                            </span>
                        @endif

                        <div class="fff-flex-checklist__copy">
                            <span class="fff-flex-checklist__label">{{ $option['label'] }}</span>

                            @if (filled($option['description']))
                                <span class="fff-flex-checklist__description">{{ $option['description'] }}</span>
                            @endif
                        </div>
                    </div>

                    @if ($option['disabled'])
                        <div class="fff-flex-checklist__action" aria-hidden="true">
                            <x-filament::icon
                                :icon="$field->getLockIcon()"
                                class="fff-flex-checklist__action-icon"
                            />
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-dynamic-component>

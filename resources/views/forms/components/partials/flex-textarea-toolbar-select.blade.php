<div
    x-data="{
        open: false,
        initialValue: @js($select['initialValue']),
        value: $wire.{{ $applyStateBindingModifiers("\$entangle('{$select['statePath']}')") }},
        options: @js($select['options']),
        placeholder: @js($select['placeholder']),
        selectedLabel() {
            const selectedValue = this.value ?? this.initialValue

            return this.options[selectedValue] ?? this.placeholder
        },
    }"
    x-on:keydown.escape.window="open = false"
    x-on:click.outside="open = false"
    class="fff-flex-textarea__select"
>
    <button
        type="button"
        class="fff-flex-textarea__select-trigger"
        x-bind:aria-expanded="open ? 'true' : 'false'"
        x-on:click="open = ! open"
    >
        @if (filled($select['icon']))
            <span class="fff-flex-textarea__select-icon">
                {{ \Filament\Support\generate_icon_html($select['icon'], size: \Filament\Support\Enums\IconSize::Small) }}
            </span>
        @endif

        <span class="fff-flex-textarea__select-value" x-text="selectedLabel()">{{ $select['initialLabel'] }}</span>

        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="fff-flex-textarea__select-chevron" x-bind:class="{ 'is-open': open }">
            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>

    <div class="fff-flex-textarea__select-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms>
        <template x-for="[optionValue, optionLabel] in Object.entries(options)" :key="optionValue">
            <button
                type="button"
                class="fff-flex-textarea__select-option"
                x-bind:class="{ 'is-active': (value ?? initialValue) === optionValue }"
                x-on:click="value = optionValue; open = false"
            >
                <span x-text="optionLabel"></span>

                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="fff-flex-textarea__select-check" x-show="(value ?? initialValue) === optionValue">
                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                </svg>
            </button>
        </template>
    </div>
</div>

<button
    type="button"
    class="fi-select-input-option"
    x-bind:class="{
        'fi-selected': isOptionSelectedInDropdown(headlessOptionValue(row.option)),
        'fi-disabled': isHeadlessOptionDisabled(row.option),
    }"
    x-bind:data-value="headlessOptionValue(row.option)"
    role="option"
    x-bind:aria-selected="isOptionSelected(headlessOptionValue(row.option)) ? 'true' : 'false'"
    x-bind:aria-disabled="isHeadlessOptionDisabled(row.option) ? 'true' : 'false'"
    x-bind:disabled="isHeadlessOptionDisabled(row.option)"
    x-on:click="toggleOption(headlessOptionValue(row.option))"
    x-on:mouseenter="comboboxHighlightedIndex = headlessOptionFlatIndex(headlessOptionValue(row.option))"
>
    @if ($isUserSelectField)
        <template x-if="! isOptionSelectedInDropdown(headlessOptionValue(row.option))">
            <span x-html="optionDropdownLabel(row.option)"></span>
        </template>

        <template x-if="isOptionSelectedInDropdown(headlessOptionValue(row.option))">
            <span class="fff-select-option-selected-row">
                <span
                    class="fff-select-option-selected-row__label"
                    x-html="optionDropdownLabel(row.option)"
                ></span>
                <span
                    class="fff-select-option-selected-check"
                    aria-hidden="true"
                    data-visible="true"
                >
                    <svg class="fff-select-option-selected-check__svg" aria-hidden="true" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 17 18" focusable="false">
                        <polyline points="1 9 7 14 15 4"></polyline>
                    </svg>
                </span>
            </span>
        </template>
    @else
        <template x-if="! isOptionSelectedInDropdown(headlessOptionValue(row.option))">
            <span>
                <template x-if="isHtmlAllowed">
                    <span class="fff-select-headless-option-label" x-html="optionDropdownLabel(row.option)"></span>
                </template>
                <template x-if="! isHtmlAllowed">
                    <span class="fff-select-headless-option-label" x-text="optionDropdownLabel(row.option)"></span>
                </template>
            </span>
        </template>

        <template x-if="isOptionSelectedInDropdown(headlessOptionValue(row.option))">
            <span class="fff-select-option-selected-row">
                <span class="fff-select-option-selected-row__label">
                    <template x-if="isHtmlAllowed">
                        <span class="fff-select-headless-option-label" x-html="optionDropdownLabel(row.option)"></span>
                    </template>
                    <template x-if="! isHtmlAllowed">
                        <span class="fff-select-headless-option-label" x-text="optionDropdownLabel(row.option)"></span>
                    </template>
                </span>

                @if ($isGridLayout)
                    <span
                        class="fff-select-option-selected-check"
                        aria-hidden="true"
                        x-html="selectedOptionCheckIconHtml"
                    ></span>
                @else
                    <span
                        class="fff-select-option-selected-check"
                        aria-hidden="true"
                        data-visible="false"
                    >
                        <svg class="fff-select-option-selected-check__svg" aria-hidden="true" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 17 18" focusable="false">
                            <polyline points="1 9 7 14 15 4"></polyline>
                        </svg>
                    </span>
                @endif
            </span>
        </template>
    @endif
</button>

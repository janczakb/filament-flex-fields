        <template x-teleport="body">
            <div
                x-ref="headlessMenu"
                id="{{ $headlessMenuDomId }}"
                data-fff-select-menu-owner="{{ $headlessComponentKey }}"
                x-show="comboboxOpen"
                x-cloak
                x-on:click.stop
                x-on:keydown="onHeadlessMenuKeydown($event)"
                x-bind:class="{ 'is-positioned': menuReady }"
                @class([
                    'fi-dropdown-panel',
                    'fff-select-dropdown-panel',
                    'fff-select-headless-menu',
                    'fff-select-dropdown-panel--below',
                    'fff-teleported-menu',
                    'fff-select-dropdown-panel--dropdown-fixed',
                    'fff-select-dropdown-panel--overlay-scroll',
                    // Filament theme defaults to max-w-[14rem]! — kill it so matchTriggerWidth can work.
                    'fi-width-none',
                    'fff-select-dropdown-panel--layout-' . ($isUserSelectField ? 'list' : ($useRichListDropdownLayout ? 'list' : ($isGridLayout ? 'grid' : 'plain'))),
                    'fff-select-dropdown-panel--user-select' => $isUserSelectField,
                    'fi-select-input-ctn-option-labels-not-wrapped' => ! $canOptionLabelsWrap,
                ])
                x-bind:aria-label="{{ json_encode($fieldLabel ?? $statePath) }}"
            >
                @if ($isSearchable)
                    <div
                        class="fi-select-input-search-ctn fff-select-input-search-ctn"
                        @if ($isInlineSearch)
                            x-show="shouldShowMenuSearch()"
                            x-cloak
                        @endif
                    >
                        <input
                            type="search"
                            class="fi-input fi-select-input-search-input"
                            id="{{ $headlessSearchId }}"
                            autocomplete="off"
                            aria-controls="{{ $headlessListboxId }}"
                            aria-autocomplete="list"
                            {{-- auto: Hebrew/Arabic → RTL caret; Latin queries keep LTR caret (forced dir=rtl put the caret on the wrong side for "teraz"). --}}
                            dir="auto"
                            x-model="comboboxQuery"
                            x-ref="headlessSearchInput"
                            x-on:keydown.down.prevent="comboboxMoveHighlight(1)"
                            x-on:keydown.up.prevent="comboboxMoveHighlight(-1)"
                            x-on:keydown.enter.prevent="comboboxSelectHighlighted()"
                            x-on:keydown.escape.stop="comboboxCloseMenu()"
                            x-bind:placeholder="@js($getSearchPrompt())"
                        />
                        <button
                            type="button"
                            class="fff-select-search-clear-btn"
                            x-cloak
                            x-show="String(comboboxQuery ?? '').length > 0"
                            x-on:click.stop="comboboxClearSearch()"
                            x-bind:aria-label="@js(__('filament-flex-fields::default.select_field.clear_search'))"
                        >
                            {!! $headlessSearchClearIconHtml !!}
                        </button>
                    </div>
                @endif

                <div class="fff-select-dropdown-scroller">
                <div
                    @class([
                        'fi-select-input-options-ctn',
                        'fi-dropdown-list' => $isGridLayout,
                    ])
                    id="{{ $headlessListboxId }}"
                    role="listbox"
                    x-ref="headlessOptionsList"
                    x-on:scroll.passive="onHeadlessOptionsScroll($event)"
                    x-bind:aria-label="{{ json_encode($fieldLabel ?? $statePath) }}"
                >
                    @include('filament-flex-fields::forms.components.partials.select-field.option-list')

                    @include('filament-flex-fields::forms.components.partials.select-field.empty-create')
                    <div
                        class="fff-select-dropdown-scrollbar"
                        data-visible="false"
                        data-active="false"
                        aria-hidden="true"
                    >
                        <div class="fff-select-dropdown-scrollbar__thumb"></div>
                    </div>
                </div>
            </div>
        </template>

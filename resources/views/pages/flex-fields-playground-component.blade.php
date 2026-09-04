<x-filament-panels::page>
    <div
        x-data="{
            dark: window.FffPlaygroundTheme.isDark(),
            toggle() {
                window.FffPlaygroundTheme.toggle();
                this.dark = window.FffPlaygroundTheme.isDark();
            },
            paletteOpen: false,
            paletteQuery: '',
            paletteIndex: 0,
            paletteEntries: @js($this->commandPaletteEntries()),
            get paletteFiltered() {
                const needle = (this.paletteQuery || '').trim().toLowerCase();
                const list = ! needle
                    ? this.paletteEntries
                    : this.paletteEntries.filter((entry) => {
                        return String(entry.id).toLowerCase().includes(needle)
                            || String(entry.label).toLowerCase().includes(needle)
                            || (entry.playground_slug && String(entry.playground_slug).toLowerCase().includes(needle));
                    });

                return list.filter((entry) => entry.url);
            },
            openPalette() {
                this.paletteOpen = true;
                this.paletteQuery = '';
                this.paletteIndex = 0;
                this.$nextTick(() => this.$refs.paletteInput?.focus());
            },
            closePalette() {
                this.paletteOpen = false;
                this.paletteQuery = '';
                this.paletteIndex = 0;
            },
            movePalette(delta) {
                const max = this.paletteFiltered.length - 1;
                if (max < 0) {
                    this.paletteIndex = 0;
                    return;
                }
                this.paletteIndex = Math.max(0, Math.min(max, this.paletteIndex + delta));
            },
            jumpTo(entry) {
                if (! entry?.url) {
                    return;
                }

                this.closePalette();

                if (window.Livewire?.navigate) {
                    window.Livewire.navigate(entry.url);
                    return;
                }

                window.location.assign(entry.url);
            },
            confirmPalette() {
                const entry = this.paletteFiltered[this.paletteIndex];
                if (entry) {
                    this.jumpTo(entry);
                }
            },
        }"
        @keydown.window.meta.k.prevent="openPalette()"
        @keydown.window.ctrl.k.prevent="openPalette()"
        @keydown.window.escape="if (paletteOpen) closePalette()"
    >
        <div class="fff-playground-toolbar">
            <div class="fff-playground-toolbar__info">
                <p class="fff-playground-toolbar__title">{{ $this->getTitle() }}</p>
                <p class="fff-playground-toolbar__text">
                    Component playground preview. Use the grouped sub-navigation to switch between demos.
                    Press <kbd class="fff-playground-kbd">⌘K</kbd> / <kbd class="fff-playground-kbd">Ctrl+K</kbd> for the command palette.
                </p>
            </div>

            <div class="fff-playground-toolbar__actions">
                <button
                    type="button"
                    class="fff-playground-theme-toggle"
                    x-on:click="openPalette()"
                    aria-label="Open command palette"
                >
                    <span class="fff-playground-theme-toggle__label">Jump</span>
                    <span class="fff-playground-kbd" aria-hidden="true">⌘K</span>
                </button>

                <button
                    type="button"
                    class="fff-playground-theme-toggle"
                    x-on:click="toggle()"
                    x-bind:aria-pressed="dark ? 'true' : 'false'"
                    aria-label="Toggle dark mode preview"
                >
                    <span class="fff-playground-theme-toggle__label">
                        <span x-show="! dark" x-cloak>Light mode</span>
                        <span x-show="dark" x-cloak>Dark mode</span>
                    </span>

                    <span
                        class="fff-playground-theme-toggle__control"
                        x-bind:data-checked="dark ? 'true' : 'false'"
                        aria-hidden="true"
                    >
                        <span class="fff-playground-theme-toggle__thumb"></span>
                    </span>
                </button>
            </div>
        </div>

        <form class="fi-form grid gap-y-6">
            {{ $this->form }}
        </form>

        <div
            class="fff-command-palette"
            x-show="paletteOpen"
            x-cloak
            x-transition.opacity.duration.150ms
            role="dialog"
            aria-modal="true"
            aria-label="Playground command palette"
            @click.self="closePalette()"
        >
            <div class="fff-command-palette__panel" @click.stop>
                <div class="fff-command-palette__header">
                    <input
                        type="search"
                        class="fff-command-palette__input"
                        placeholder="Search fields…"
                        x-ref="paletteInput"
                        x-model="paletteQuery"
                        @keydown.arrow-down.prevent="movePalette(1)"
                        @keydown.arrow-up.prevent="movePalette(-1)"
                        @keydown.enter.prevent="confirmPalette()"
                        @input="paletteIndex = 0"
                    />
                </div>
                <ul class="fff-command-palette__list" role="listbox">
                    <template x-for="(entry, index) in paletteFiltered" :key="entry.id">
                        <li>
                            <button
                                type="button"
                                class="fff-command-palette__item"
                                role="option"
                                x-bind:aria-selected="index === paletteIndex ? 'true' : 'false'"
                                x-bind:data-active="index === paletteIndex ? 'true' : 'false'"
                                @click="jumpTo(entry)"
                                @mouseenter="paletteIndex = index"
                            >
                                <span class="fff-command-palette__item-label" x-text="entry.label"></span>
                                <span class="fff-command-palette__item-meta">
                                    <span x-text="entry.id"></span>
                                    <span x-text="entry.playground_slug"></span>
                                </span>
                            </button>
                        </li>
                    </template>
                    <li
                        class="fff-command-palette__empty"
                        x-show="paletteFiltered.length === 0"
                    >
                        No playground hubs match.
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:navigating', (event) => {
            if (! window.location.pathname.includes('flex-fields-playground')) {
                return;
            }

            const destination = event.detail?.destination?.url ?? event.detail?.url ?? '';

            if (destination.includes('flex-fields-playground')) {
                return;
            }

            window.FffPlaygroundTheme?.reset();
        });
    </script>
</x-filament-panels::page>

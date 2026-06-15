<x-filament-panels::page>
    <div
        x-data="{
            dark: window.FffPlaygroundTheme.isDark(),
            toggle() {
                window.FffPlaygroundTheme.toggle();
                this.dark = window.FffPlaygroundTheme.isDark();
            },
        }"
    >
        <div class="fff-playground-toolbar">
            <div class="fff-playground-toolbar__info">
                <p class="fff-playground-toolbar__title">{{ $this->getTitle() }}</p>
                <p class="fff-playground-toolbar__text">
                    Component playground preview. Use the left sub-navigation to switch between demos.
                </p>
            </div>

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

        <form class="fi-form grid gap-y-6">
            {{ $this->form }}
        </form>
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

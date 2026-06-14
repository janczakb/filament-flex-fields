<x-filament-panels::page>
    <link
        rel="stylesheet"
        href="{{ \Bjanczak\FilamentFlexFields\Support\FlexFieldAssets::playgroundStylesheetHref() }}"
        data-navigate-track
    />

    @if ($slug = $this->getPlaygroundSlug())
        @php($stylesheetComponent = \Bjanczak\FilamentFlexFields\Support\FlexFieldAssets::resolveStylesheetComponent($slug))
        @if (\Bjanczak\FilamentFlexFields\Support\FlexFieldAssets::shouldLoadStylesheetsFor($stylesheetComponent))
            @foreach (\Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue::enqueueFor($stylesheetComponent) as $stylesheet)
                <link
                    rel="stylesheet"
                    href="{{ \Bjanczak\FilamentFlexFields\Support\FlexFieldAssets::stylesheetHref($stylesheet) }}"
                    data-navigate-track
                />
            @endforeach
        @endif
    @endif

    <div
        x-data="{
            dark: localStorage.getItem('fff-playground-dark-mode') === 'true',
            init() {
                this.apply();
            },
            toggle() {
                this.dark = ! this.dark;
                localStorage.setItem('fff-playground-dark-mode', this.dark ? 'true' : 'false');
                this.apply();
            },
            apply() {
                document.documentElement.classList.toggle('dark', this.dark);
            },
        }"
        x-init="init()"
        x-on:destroy.window="document.documentElement.classList.remove('dark')"
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
        document.addEventListener('livewire:navigating', () => {
            if (! window.location.pathname.includes('flex-fields-playground')) {
                document.documentElement.classList.remove('dark');
            }
        });
    </script>
</x-filament-panels::page>

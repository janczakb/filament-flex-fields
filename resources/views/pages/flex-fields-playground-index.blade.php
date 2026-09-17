<x-filament-panels::page>
    <div class="fff-playground-catalog">
        <header class="fff-playground-catalog__intro">
            <h1 class="fff-playground-catalog__heading">All components</h1>
            <p class="fff-playground-catalog__lede">
                Browse every Flex Fields playground hub. Open a card to jump into the live demo.
            </p>
        </header>

        @foreach ($this->catalogSections() as $section)
            <section class="fff-playground-catalog__section" aria-labelledby="fff-catalog-{{ $section['category']->value }}">
                <h2
                    id="fff-catalog-{{ $section['category']->value }}"
                    class="fff-playground-catalog__section-title"
                >{{ $section['label'] }}</h2>

                <div class="fff-playground-catalog__grid">
                    @foreach ($section['hubs'] as $hub)
                        <a
                            href="{{ $hub['url'] }}"
                            wire:navigate
                            class="fff-playground-catalog__card"
                        >
                            <div class="fff-playground-catalog__preview" aria-hidden="true">
                                @if (filled($hub['image']))
                                    <img
                                        class="fff-playground-catalog__image"
                                        src="{{ $hub['image'] }}"
                                        alt=""
                                        loading="lazy"
                                        decoding="async"
                                        onerror="this.classList.add('is-failed'); this.nextElementSibling?.classList.add('is-visible');"
                                    />
                                    <span class="fff-playground-catalog__icon-fallback">
                                        <x-filament::icon
                                            :icon="$hub['icon']"
                                            class="fff-playground-catalog__icon"
                                        />
                                    </span>
                                @else
                                    <span class="fff-playground-catalog__icon-fallback is-visible">
                                        <x-filament::icon
                                            :icon="$hub['icon']"
                                            class="fff-playground-catalog__icon"
                                        />
                                    </span>
                                @endif
                            </div>
                            <span class="fff-playground-catalog__label">{{ $hub['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>

@if (count($hubs) > 0)
    <div class="fff-playground-related-hubs" data-slot="playground-related-hubs">
        <p class="fff-playground-related-hubs__title">Related layout hubs</p>
        <p class="fff-playground-related-hubs__text">
            These playground pages compose together — locale tabs, segment panels, full-page layouts, and item-card settings lists.
        </p>
        <ul class="fff-playground-related-hubs__list" role="list">
            @foreach ($hubs as $hub)
                <li>
                    <a
                        href="{{ $hub['url'] }}"
                        class="fff-playground-related-hubs__link"
                        wire:navigate
                    >
                        <span class="fff-playground-related-hubs__link-label">{{ $hub['label'] }}</span>
                        <span class="fff-playground-related-hubs__link-slug">{{ $hub['slug'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif

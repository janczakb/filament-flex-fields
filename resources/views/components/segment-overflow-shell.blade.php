@php
    use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
    use Bjanczak\FilamentFlexFields\Support\GravityIcon;
    use Filament\Support\Enums\IconSize;
@endphp

@props([
    'variant' => 'default',
    'tablistLabel' => null,
    'tablistRole' => null,
])

<div
    @class([
        'fff-segment-overflow-shell',
        'fff-segment-overflow-shell--ghost' => $variant === 'ghost',
    ])
    x-bind:class="{ 'is-overflow-interactive': segmentOverflowInteractive }"
>
    <div
        @class([
            'fff-segment-scroll-shadow',
            'fff-segment-scroll-shadow--horizontal',
            'fff-segment-scroll-shadow--fade',
            'fff-segment-scroll-shadow--preparing',
        ])
        data-fff-segment-overflow
        x-ref="scrollShadow"
        x-on:scroll.passive="updateSegmentScrollShadow()"
        @if (filled($tablistRole))
            role="{{ $tablistRole }}"
        @endif
        @if (filled($tablistLabel))
            aria-label="{{ $tablistLabel }}"
        @endif
    >
        {{ $slot }}

        @if ($segmentOverflowSsr = FlexFieldAssets::segmentOverflowSsrInlineContents())
            <script>{!! $segmentOverflowSsr !!}</script>
        @endif
    </div>

    <button
        type="button"
        class="fff-segment-overflow-shell__scroll-prev"
        tabindex="-1"
        x-bind:class="{ 'hidden': segmentOverflowInteractive && ! segmentCanScrollBefore }"
        x-on:click="scrollSegmentOverflowBy(-1)"
        aria-label="{{ __('filament-flex-fields::default.segment_control_scroll_previous') }}"
    >
        {{ \Filament\Support\generate_icon_html(GravityIcon::ChevronLeft, size: IconSize::Small) }}
    </button>

    <button
        type="button"
        class="fff-segment-overflow-shell__scroll-next"
        tabindex="-1"
        x-bind:class="{ 'hidden': segmentOverflowInteractive && ! segmentCanScrollAfter }"
        x-on:click="scrollSegmentOverflowBy(1)"
        aria-label="{{ __('filament-flex-fields::default.segment_control_scroll_next') }}"
    >
        {{ \Filament\Support\generate_icon_html(GravityIcon::ChevronRight, size: IconSize::Small) }}
    </button>
</div>

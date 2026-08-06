@php
    $aspectRatioStyle = $getAspectRatioStyle();
    $backgroundStyles = $getBackgroundStyles();
    $maxWidth = $getContentMaxWidth();
    $footerAction = $getFooterAction();

    $cardStyles = [];

    if (filled($aspectRatioStyle)) {
        $cardStyles[] = '--fff-cover-card-aspect-ratio: '.$aspectRatioStyle;
    }

    if (filled($maxWidth) && ! $isFullWidth()) {
        $cardStyles[] = 'max-width: '.$maxWidth;
    }

    /*
     * Append component styles via ->style() so they concatenate with any
     * `style` from extraAttributes. Emitting a second style="" attribute
     * (the old approach) is invalid HTML and browsers keep only one of the
     * two — so either aspect-ratio or the custom min-width was dropped.
     */
    $mergedAttributes = $attributes
        ->merge([
            'id' => $getId(),
        ], escape: false)
        ->merge($getExtraAttributes(), escape: false);

    if ($cardStyles !== []) {
        $mergedAttributes = $mergedAttributes->style($cardStyles);
    }
@endphp

@include('filament-flex-fields::partials.load-stylesheet', ['component' => 'cover-card'])

<div
    {{
        $mergedAttributes
            ->class([
                'fff-cover-card',
                'fff-cover-card--tone-' . $getTone(),
                'fff-cover-card--radius-' . $getRadius(),
                'is-full-width' => $isFullWidth(),
            ])
    }}
    data-slot="cover-card"
>
    <div
        class="fff-cover-card__background"
        @if ($backgroundStyles !== [])
            style="{{ implode('; ', $backgroundStyles) }}"
        @endif
        aria-hidden="true"
    ></div>

    @if ($hasContentOverlays())
        @if ($shouldShowTopOverlay())
            <div
                class="fff-cover-card__overlay fff-cover-card__overlay--top"
                @if ($hasCustomTopOverlayGradient())
                    style="--fff-cover-card-top-overlay: {{ $getTopOverlayGradient() }}"
                @endif
                aria-hidden="true"
            ></div>
        @endif

        @if ($shouldShowFooterOverlay())
            <div
                class="fff-cover-card__overlay fff-cover-card__overlay--bottom"
                @if ($hasCustomFooterOverlayGradient())
                    style="--fff-cover-card-footer-overlay: {{ $getFooterOverlayGradient() }}"
                @endif
                aria-hidden="true"
            ></div>
        @endif
    @else
        <div class="fff-cover-card__scrim" aria-hidden="true"></div>
    @endif

    <div class="fff-cover-card__content">
        @if ($hasTopContent())
            <div class="fff-cover-card__top">
                @if (filled($getTopTitle()))
                    <span class="fff-cover-card__top-title">{{ $getTopTitle() }}</span>
                @endif

                @if (filled($getTopDescription()))
                    <span class="fff-cover-card__top-description">{{ $getTopDescription() }}</span>
                @endif
            </div>
        @endif

        @if ($hasFooterContent())
            <div @class([
                'fff-cover-card__footer',
                'fff-cover-card__footer--action-only' => ! $hasFooterCopy() && $hasFooterAction(),
            ])>
                @if ($hasFooterCopy())
                    <div class="fff-cover-card__footer-copy">
                        @if (filled($getFooterTitle()))
                            <span class="fff-cover-card__footer-title">{{ $getFooterTitle() }}</span>
                        @endif

                        @if (filled($getFooterDescription()))
                            <span class="fff-cover-card__footer-description">{{ $getFooterDescription() }}</span>
                        @endif
                    </div>
                @endif

                @if ($footerAction)
                    <div class="fff-cover-card__footer-action fi-fixed-positioning-context">
                        {{ $footerAction }}
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

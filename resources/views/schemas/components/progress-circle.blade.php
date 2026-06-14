@php
    use Filament\Support\Enums\IconSize;

    $size = $getSize();
    $colorToken = $getColorToken();
    $variant = $getVariant();
    $metrics = $getSvgMetrics();
    $contentLayout = $getContentLayout();
    $markerIconSize = match ($size) {
        'sm' => IconSize::Small,
        'lg' => IconSize::Large,
        default => IconSize::Medium,
    };
    $pausedIconSize = match ($size) {
        'sm' => IconSize::Medium,
        'lg' => IconSize::ExtraLarge,
        default => IconSize::Large,
    };
    $gradientFrom = $getGradientFrom();
    $gradientTo = $getGradientTo();
    $hasShell = $hasShell();
    $hasCardChrome = $hasCardChrome();
    $inlineStyles = [];

    if ($accentColor = $getAccentCssColor()) {
        $inlineStyles[] = '--fff-progress-circle-accent: ' . $accentColor;
    }

    if ($shouldAnimateFill()) {
        $inlineStyles[] = '--fff-progress-fill-duration: ' . $getAnimationDuration() . 'ms';
    }
@endphp

@include('filament-flex-fields::partials.load-stylesheet', ['component' => 'progress-circle'])

<div
    {{
        $attributes
            ->merge([
                'id' => $getId(),
                'wire:key' => 'pc-' . $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fff-progress-circle',
                'fff-progress-circle--' . $size,
                'fff-progress-circle--' . $variant,
                'fff-progress-circle--layout-' . $contentLayout,
                'fi-color-' . $colorToken => filled($colorToken),
                'has-custom-accent' => $usesCustomAccentColor(),
                'is-fill-static' => ! $shouldAnimateFill(),
                'has-gradient' => $hasGradientStroke(),
                'has-track-gradient' => $usesExplicitTrackGradient(),
                'has-gap-arc' => $hasGapArc(),
                'has-below-label' => $hasBelowLabel(),
                'is-paused' => $isPaused(),
                'has-shell' => $hasShell,
                'has-card-chrome' => $hasCardChrome,
            ])
    }}
    data-slot="progress-circle"
    role="progressbar"
    aria-valuemin="0"
    aria-valuemax="100"
    aria-valuenow="{{ $getPercentage() }}"
    @if ($inlineStyles !== [])
        style="{{ implode('; ', $inlineStyles) }}"
    @endif
>
  @if ($hasCardChrome)
    <div class="fff-progress-circle__card">
        @if (filled($getHeading()))
            <div class="fff-progress-circle__card-heading">{{ $getHeading() }}</div>
        @endif

        @if (filled($getDescription()))
            <div class="fff-progress-circle__card-description">{{ $getDescription() }}</div>
        @endif

        <div class="fff-progress-circle__body">
            @include('filament-flex-fields::schemas.components.partials.progress-circle-frame')
        </div>

        @if (filled($getFooter()))
            <div class="fff-progress-circle__card-footer">{{ $getFooter() }}</div>
        @endif
    </div>
  @else
    @include('filament-flex-fields::schemas.components.partials.progress-circle-frame')
  @endif
</div>

@php
    use Filament\Support\Enums\IconSize;

    $size = $getSize();
    $colorToken = $getColorToken();
    $percentage = $getPercentage();
    $segments = $getNormalizedSegments();
    $hasSegments = $hasSegments();
    $segmentCount = count($segments);
    $isIndeterminate = $isIndeterminate();
    $isPillsVariant = $isPillsVariant();
    $hasCardChrome = $hasCardChrome();
    $usesAutoPillCount = $isPillsVariant && $usesAutoPillCount();
    $pillCount = $isPillsVariant && ! $usesAutoPillCount ? $getPillCount() : 0;
    $activePillCount = $isPillsVariant && ! $usesAutoPillCount ? $getActivePillCount() : 0;
    $hasDashedRemainder = ! $hasSegments && ! $isPillsVariant && $getRemainingTrackStyle() === 'dashed';
    $markerIconSize = match ($size) {
        'sm' => IconSize::ExtraSmall,
        'lg' => IconSize::Medium,
        default => IconSize::Small,
    };
    $segmentIconSize = match ($size) {
        'lg' => IconSize::Small,
        default => IconSize::ExtraSmall,
    };
    $inlineStyles = [];

    if ($accentColor = $getAccentCssColor()) {
        $inlineStyles[] = '--fff-progress-bar-accent: ' . $accentColor;
    }

    if ($shouldAnimateFill()) {
        $inlineStyles[] = '--fff-progress-fill-duration: ' . $getAnimationDuration() . 'ms';
    }

    if ($hasSegments) {
        $inlineStyles[] = '--fff-progress-bar-segment-count: ' . $segmentCount;
    }

    if ($isPillsVariant && ! $usesAutoPillCount) {
        $inlineStyles[] = '--fff-progress-bar-pill-count: ' . $pillCount;
    }
@endphp

@include('filament-flex-fields::partials.load-stylesheet', ['component' => 'progress-bar'])

<div
    {{
        $attributes
            ->merge([
                'id' => $getId(),
                'wire:key' => 'pb-' . $getId(),
            ], escape: false)
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fff-progress-bar',
                'fff-progress-bar--' . $size,
                'fi-color-' . $colorToken => filled($colorToken),
                'has-custom-accent' => $usesCustomAccentColor(),
                'is-fill-static' => ! $shouldAnimateFill(),
                'is-indeterminate' => $isIndeterminate,
                'has-segments' => $hasSegments,
                'has-segment-icons' => $hasSegments && $hasSegmentIcons(),
                'has-markers' => $hasTrackMarkers(),
                'has-dashed-remainder' => $hasDashedRemainder,
                'is-route' => $hasTrackMarkers() && $hasDashedRemainder,
                'is-pills' => $isPillsVariant,
                'has-shell' => $hasCardChrome,
                'has-card-chrome' => $hasCardChrome,
                'has-value-badge' => $shouldShowValueBadge(),
            ])
    }}
    data-slot="progress-bar"
    @if ($inlineStyles !== [])
        style="{{ implode('; ', $inlineStyles) }}"
    @endif
>
    @if ($hasCardChrome)
        <div class="fff-progress-bar__card">
            @if ($hasCardHeader())
                <div class="fff-progress-bar__card-header">
                    @if (filled($getLabel()))
                        <span class="fff-progress-bar__card-heading">{{ $getLabel() }}</span>
                    @endif

                    @if ($shouldShowValue() && $shouldShowValueBadge() && ! $isIndeterminate)
                        <span class="fff-progress-bar__value-badge">{{ $getFormattedValue() }}</span>
                    @endif
                </div>
            @endif

            @if (filled($getDescription()))
                <p class="fff-progress-bar__card-description">{{ $getDescription() }}</p>
            @endif

            @if ($isPillsVariant)
                @include('filament-flex-fields::schemas.components.partials.progress-bar-pills')
            @elseif ($hasSegments)
                @include('filament-flex-fields::schemas.components.partials.progress-bar-segments', [
                    'segments' => $segments,
                    'segmentCount' => $segmentCount,
                    'segmentIconSize' => $segmentIconSize,
                ])
            @else
                @include('filament-flex-fields::schemas.components.partials.progress-bar-track', [
                    'percentage' => $percentage,
                    'isIndeterminate' => $isIndeterminate,
                    'hasDashedRemainder' => $hasDashedRemainder,
                    'markerIconSize' => $markerIconSize,
                ])
            @endif

            @if (filled($getFooter()))
                <button type="button" class="fff-progress-bar__card-action">
                    {{ $getFooter() }}
                </button>
            @endif
        </div>
    @else
        @if ($hasHeader())
            <div class="fff-progress-bar__header">
                @if (filled($getLabel()))
                    <span class="fff-progress-bar__label">{{ $getLabel() }}</span>
                @endif

                @if ($shouldShowValue() && ! $isIndeterminate)
                    <span class="fff-progress-bar__value">{{ $getFormattedValue() }}</span>
                @endif
            </div>
        @endif

        @if ($isPillsVariant)
            @include('filament-flex-fields::schemas.components.partials.progress-bar-pills')
        @elseif ($hasSegments)
            @include('filament-flex-fields::schemas.components.partials.progress-bar-segments', [
                'segments' => $segments,
                'segmentCount' => $segmentCount,
                'segmentIconSize' => $segmentIconSize,
            ])
        @else
            @include('filament-flex-fields::schemas.components.partials.progress-bar-track', [
                'percentage' => $percentage,
                'isIndeterminate' => $isIndeterminate,
                'hasDashedRemainder' => $hasDashedRemainder,
                'markerIconSize' => $markerIconSize,
            ])
        @endif
    @endif
</div>

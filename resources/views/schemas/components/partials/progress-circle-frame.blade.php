@php
    $metrics = $getSvgMetrics();
    $contentLayout = $getContentLayout();
    $variant = $getVariant();
    $hasBelowLabel = $hasBelowLabel();
    $gradientFrom = $getGradientFrom();
    $gradientTo = $getGradientTo();
    $trackGradientFrom = $getTrackGradientFrom();
    $trackGradientTo = $getTrackGradientTo();
    $pausedIconSize = match ($getSize()) {
        'sm' => \Filament\Support\Enums\IconSize::Medium,
        'lg' => \Filament\Support\Enums\IconSize::ExtraLarge,
        default => \Filament\Support\Enums\IconSize::Large,
    };
@endphp

<div class="fff-progress-circle__frame" wire:key="pcf-{{ $getId() }}">
    @if ($contentLayout === 'left' || $contentLayout === 'right')
        <div class="fff-progress-circle__side-content fff-progress-circle__side-content--{{ $contentLayout }}">
            @if (filled($getLabel()))
                <span class="fff-progress-circle__side-label">{{ $getLabel() }}</span>
            @endif

            @if (filled($getFraction()))
                <span class="fff-progress-circle__side-fraction">{{ $getFraction() }}</span>
            @endif

            @if (filled($getDisplayValue()))
                <span class="fff-progress-circle__side-value">{{ $getDisplayValue() }}</span>
            @endif
        </div>
    @endif

  @if ($contentLayout === 'above')
    <div class="fff-progress-circle__above-content">
        @if (filled($getLabel()))
            <span class="fff-progress-circle__above-label">{{ $getLabel() }}</span>
        @endif
    </div>
  @endif

    <div
        class="fff-progress-circle__ring"
        wire:key="pcr-{{ $getId() }}"
        @if ($variant === 'semicircle')
            style="--fff-progress-circle-view-height: {{ $metrics['viewBoxHeight'] }}; --fff-progress-circle-semicircle-floor-inset: {{ $metrics['semicircleFloorInsetPercent'] }}%;"
        @endif
    >
        <svg
            class="fff-progress-circle__svg"
            viewBox="{{ $metrics['viewBox'] }}"
            aria-hidden="true"
        >
            @if ($hasGradientStroke() || $hasTrackGradientStroke())
                <defs>
                    @if ($hasGradientStroke())
                        <linearGradient
                            id="{{ $metrics['gradientId'] }}"
                            gradientUnits="userSpaceOnUse"
                            x1="{{ $metrics['gradientX1'] }}"
                            y1="{{ $metrics['gradientY1'] }}"
                            x2="{{ $metrics['gradientX2'] }}"
                            y2="{{ $metrics['gradientY2'] }}"
                        >
                            @if (filled($gradientFrom) && filled($gradientTo))
                                <stop offset="0%" stop-color="{{ $gradientFrom }}" />
                                <stop offset="100%" stop-color="{{ $gradientTo }}" />
                            @endif
                        </linearGradient>
                    @endif

                    @if ($hasTrackGradientStroke())
                        <linearGradient
                            id="{{ $metrics['trackGradientId'] }}"
                            gradientUnits="userSpaceOnUse"
                            x1="{{ $metrics['gradientX1'] }}"
                            y1="{{ $metrics['gradientY1'] }}"
                            x2="{{ $metrics['gradientX2'] }}"
                            y2="{{ $metrics['gradientY2'] }}"
                        >
                            @if (filled($trackGradientFrom) && filled($trackGradientTo))
                                <stop offset="0%" stop-color="{{ $trackGradientFrom }}" />
                                <stop offset="100%" stop-color="{{ $trackGradientTo }}" />
                            @endif
                        </linearGradient>
                    @endif
                </defs>
            @endif

            <circle
                class="fff-progress-circle__track"
                cx="{{ $metrics['centerX'] }}"
                cy="{{ $metrics['centerY'] }}"
                r="{{ $metrics['radius'] }}"
                fill="none"
                stroke-width="{{ $metrics['strokeWidth'] }}"
                stroke-dasharray="{{ $metrics['arcLength'] }} {{ $metrics['gapLength'] }}"
                transform="rotate({{ $metrics['rotation'] }} {{ $metrics['centerX'] }} {{ $metrics['centerY'] }})"
                @if ($hasTrackGradientStroke())
                    style="stroke: url(#{{ $metrics['trackGradientId'] }})"
                @endif
            />

            <circle
                class="fff-progress-circle__fill"
                cx="{{ $metrics['centerX'] }}"
                cy="{{ $metrics['centerY'] }}"
                r="{{ $metrics['radius'] }}"
                fill="none"
                stroke-width="{{ $metrics['strokeWidth'] }}"
                transform="rotate({{ $metrics['rotation'] }} {{ $metrics['centerX'] }} {{ $metrics['centerY'] }})"
                style="--fff-progress-circle-arc-length: {{ $metrics['arcLength'] }}; --fff-progress-circle-circumference: {{ $metrics['circumference'] }}; --fff-progress-circle-fill-ratio: {{ $getProgressRatio() }}; @if ($hasGradientStroke()) stroke: url(#{{ $metrics['gradientId'] }}); @endif"
            />
        </svg>

        <div class="fff-progress-circle__center">
            @if ($isPaused())
                <div class="fff-progress-circle__paused-stack">
                    <div class="fff-progress-circle__paused-ghost" aria-hidden="true">
                        @if (filled($getDisplayValue()) || (! filled($getFraction()) && ! filled($getLabel())))
                            <span class="fff-progress-circle__display-value">{{ $getFormattedValue() }}</span>
                        @endif

                        @if (filled($getFraction()))
                            <span class="fff-progress-circle__fraction">{{ $getFraction() }}</span>
                        @endif
                    </div>

                    <span class="fff-progress-circle__paused-icon">
                        {{ \Filament\Support\generate_icon_html($getPausedIcon(), size: $pausedIconSize) }}
                    </span>
                </div>
            @elseif ($contentLayout === 'center')
                @if (filled($getDisplayValue()) || (! filled($getFraction()) && (! filled($getLabel()) || $hasBelowLabel)))
                    <span class="fff-progress-circle__display-value">{{ $getFormattedValue() }}</span>
                @endif

                @if (filled($getFraction()))
                    <span class="fff-progress-circle__fraction">{{ $getFraction() }}</span>
                @endif

                @if (filled($getLabel()) && ! $hasBelowLabel)
                    <span class="fff-progress-circle__label">{{ $getLabel() }}</span>
                @endif
            @endif
        </div>
    </div>

    @if ($hasBelowLabel)
        <div class="fff-progress-circle__below-content">
            <span class="fff-progress-circle__below-label">{{ $getLabel() }}</span>
        </div>
    @endif
</div>

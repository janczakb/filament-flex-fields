<div
    class="fff-progress-bar__track"
    wire:key="pbt-{{ $getId() }}"
    role="progressbar"
    @unless ($isIndeterminate)
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="{{ $percentage }}"
    @endunless
    @if ($isIndeterminate)
        aria-busy="true"
    @endif
>
    @if ($hasStartMarker())
        <span class="fff-progress-bar__marker fff-progress-bar__marker--start">
            {{ \Filament\Support\generate_icon_html($getStartMarker(), size: $markerIconSize) }}
        </span>
    @endif

    @if ($hasDashedRemainder)
        <span class="fff-progress-bar__rail fff-progress-bar__rail--route" aria-hidden="true">
            <span
                class="fff-progress-bar__rail-fill"
                style="--fff-progress-bar-fill: {{ $percentage }}%"
            ></span>
            <span
                class="fff-progress-bar__rail-remainder"
                style="--fff-progress-bar-fill: {{ $percentage }}%"
            ></span>
        </span>
    @else
        <span class="fff-progress-bar__rail" aria-hidden="true">
            <span
                class="fff-progress-bar__fill"
                @unless ($isIndeterminate)
                    style="--fff-progress-bar-fill: {{ $percentage }}%"
                @endunless
            ></span>
        </span>
    @endif

    @if ($hasCurrentMarker() && ! $isIndeterminate)
        <span
            class="fff-progress-bar__marker fff-progress-bar__marker--current"
            style="--fff-progress-bar-marker-position: {{ $percentage }}%"
        >
            {{ \Filament\Support\generate_icon_html($getCurrentMarker(), size: $markerIconSize) }}
        </span>
    @endif

    @if ($hasEndMarker())
        <span class="fff-progress-bar__marker fff-progress-bar__marker--end">
            {{ \Filament\Support\generate_icon_html($getEndMarker(), size: $markerIconSize) }}
        </span>
    @endif
</div>
